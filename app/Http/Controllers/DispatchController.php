<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Transporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    private function authUser(): array { return session('auth_user'); }

    public function home()
    {
        $pending   = Order::with(['company', 'transporter'])->where('dispatch_status', 'PENDING')->orderByDesc('created_at')->get();
        $completed = Order::with(['company', 'transporter'])->where('dispatch_status', 'DONE')->orderByDesc('created_at')->get();

        $pageData = [
            'pendingOrders'   => $pending->map(fn($o) => [
                'id'           => $o->id,
                'companyId'    => $o->company_id,
                'transportId'  => $o->transporter_id,
                'total'        => $o->total,
                'date'         => $o->created_at->toISOString(),
            ]),
            'completedOrders' => $completed->map(fn($o) => [
                'id'           => $o->id,
                'companyId'    => $o->company_id,
                'transportId'  => $o->transporter_id,
                'total'        => $o->total,
                'date'         => $o->created_at->toISOString(),
            ]),
            'companies'           => Company::all(['id', 'name']),
            'transportCompanies'  => Transporter::all(['id', 'name']),
        ];
        return view('dispatch.home', compact('pageData'));
    }

    public function action()
    {
        $pendingOrders = Order::with(['company', 'transporter'])
            ->where('dispatch_status', 'PENDING')
            ->orderByDesc('created_at')
            ->get();

        $pageData = [
            'pendingOrders' => $pendingOrders->map(fn($o)=>[
                'id'          => $o->id,
                'company'     => [
                    'name'    => $o->company?->name,
                    'gst'     => $o->company?->gst,
                    'contact' => $o->company?->contact,
                    'address' => $o->company?->address,
                ],
                'transporter' => [
                    'name'     => $o->transporter?->name,
                    'gst'      => $o->transporter?->gst,
                    'contact'  => $o->transporter?->contact,
                    'vehicles' => $o->transporter?->vehicles,
                ],
                'items'       => $o->items->map(fn($i)=>[
                    'productName' => $i->product?->name,
                    'quantity'    => $i->quantity,
                    'grade'       => $i->grade,
                ])
            ])
        ];
        return view('dispatch.action', compact('pageData'));
    }

    public function storeDispatch(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'lr_image' => 'nullable|string', // base64 from JS or file path
        ]);

        $user  = $this->authUser();
        $order = Order::with('items.product')->find($request->order_id);

        if ($order->dispatch_status === 'DONE') {
            return response()->json(['success' => false, 'message' => 'Order already dispatched.'], 422);
        }

        // Check finished stock availability for all order items
        foreach ($order->items as $item) {
            $available = DB::table('stocks')
                ->where('product_id', $item->product_id)
                ->where('stage', 'FINISHED')
                ->where('grade', $item->grade)
                ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                ->value('net') ?? 0;

            if ($item->quantity > $available) {
                $pName = $item->product?->name;
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient Finished Stock for {$pName} ({$item->grade}). Need: {$item->quantity}, Have: {$available}"
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $user, $order) {
            // Deduct finished stock
            foreach ($order->items as $item) {
                Stock::create([
                    'product_id'       => $item->product_id,
                    'user_id'          => $user['id'],
                    'stage'            => 'FINISHED',
                    'grade'            => $item->grade,
                    'quantity'         => $item->quantity,
                    'transaction_type' => 'OUT',
                    'notes'            => "Dispatched: Order #{$order->id}",
                ]);
            }

            // Handle LR image - save base64 as file
            $lrPath = null;
            if ($request->lr_image) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->lr_image));
                $lrPath    = 'lr_images/' . uniqid('LR_') . '.jpg';
                file_put_contents(public_path($lrPath), $imageData);
            }

            // Dispatch log
            DispatchLog::create([
                'user_id'        => $user['id'],
                'order_id'       => $order->id,
                'transporter_id' => $order->transporter_id,
                'lr_image_path'  => $lrPath,
            ]);

            // Update order status
            $order->update(['status' => 'CLOSED', 'dispatch_status' => 'DONE']);
        });

        return response()->json(['success' => true, 'message' => 'Order dispatched successfully!']);
    }

    public function history()
    {
        $logs = DispatchLog::with(['order.company', 'order.transporter', 'user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($d) => [
                'id'            => $d->id,
                'orderId'       => $d->order_id,
                'companyName'   => $d->order?->company?->name,
                'transportName' => $d->transporter?->name ?? $d->order?->transporter?->name,
                'dispatchedBy'  => $d->user?->name,
                'lrImage'       => $d->lr_image_path ? asset($d->lr_image_path) : null,
                'orderTotal'    => $d->order?->total,
                'date'          => $d->created_at->toISOString(),
            ]);

        $pageData = ['dispatchLogs' => $logs];
        return view('dispatch.history', compact('pageData'));
    }

    public function profile()
    {
        return view('dispatch.profile');
    }
}
