<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    private function authUser(): array { return session('auth_user'); }

    public function home()
    {
        $orders       = Order::with(['company', 'transporter', 'items.product'])->orderByDesc('created_at')->get();
        $totalOrders  = $orders->count();
        $openOrders   = $orders->where('status', 'OPEN')->count();
        $pendingDisp  = $orders->where('dispatch_status', 'PENDING')->count();
        $totalValue   = $orders->sum('total');

        $pageData = [
            'orders'         => $orders->map(fn($o) => [
                'id'              => $o->id,
                'companyId'       => $o->company_id,
                'companyName'     => strtoupper($o->company?->name ?? ''),
                'transportId'     => $o->transporter_id,
                'transportName'   => strtoupper($o->transporter?->name ?? ''),
                'total'           => $o->total,
                'status'          => $o->status,
                'dispatchStatus'  => $o->dispatch_status,
                'notes'           => $o->notes,
                'date'            => $o->created_at->toISOString(),
                'products'        => $o->items->map(fn($i) => [
                    'id'          => $i->id,
                    'productId'   => $i->product_id,
                    'productName' => strtoupper($i->product ? $i->product->formatName($i->grade) : ''),
                    'grade'       => strtoupper($i->grade ?? ''),
                    'quantity'    => $i->quantity,
                    'price'       => $i->price,
                ]),
            ]),
            'companies'          => Company::orderBy('name')->get()->map(fn($c)=>[
                'id'=>$c->id,'name'=>strtoupper($c->name ?? ''),'gst'=>$c->gst,'address'=>$c->address,'contact'=>$c->contact,'date'=>$c->created_at->toISOString()
            ]),
            'transportCompanies' => Transporter::orderBy('name')->get()->map(fn($t)=>[
                'id'=>$t->id,'name'=>strtoupper($t->name ?? ''),'gst'=>$t->gst,'contact'=>$t->contact,'vehicles'=>$t->vehicles,'date'=>$t->created_at->toISOString()
            ]),
            'products'           => Product::target()->active()->visibleTo($this->authUser()['role'])->get(['id', 'name', 'unit', 'type'])->map(fn($p) => [
                'id' => $p->id, 'name' => strtoupper($p->name ?? ''), 'unit' => $p->unit, 'type' => $p->type
            ]),
            'stats'              => compact('totalOrders', 'openOrders', 'pendingDisp', 'totalValue'),
        ];
        return view('sales.home', compact('pageData'));
    }

    public function action(Request $request)
    {
        $companies = Company::orderBy('name')->get()->map(fn($c)=>[
            'id'=>$c->id,'name'=>strtoupper($c->name ?? ''),'gst'=>$c->gst,'address'=>$c->address,'contact'=>$c->contact,'date'=>$c->created_at->toISOString()
        ]);
        $transportCompanies = Transporter::orderBy('name')->get()->map(fn($t)=>[
            'id'=>$t->id,'name'=>strtoupper($t->name ?? ''),'gst'=>$t->gst,'contact'=>$t->contact,'vehicles'=>$t->vehicles,'date'=>$t->created_at->toISOString()
        ]);
        $products = Product::active()->with('grades')->get(['id', 'name', 'unit', 'type'])->map(fn($p) => [
            'id' => $p->id, 'name' => strtoupper($p->name ?? ''), 'unit' => $p->unit, 'type' => $p->type,
            'grades' => $p->grades->pluck('name')->map('strtoupper')->toArray()
        ]);
        $grades = \App\Models\Grade::where('is_active', true)->pluck('name')->map('strtoupper')->toArray();

        $editOrder = null;
        if ($request->edit) {
            $editOrder = Order::with('items.product')->findOrFail($request->edit);
        }

        $editCompany = null;
        if ($request->editCompany) {
            $editCompany = Company::findOrFail($request->editCompany);
        }

        $pageData = compact('companies', 'transportCompanies', 'products', 'grades', 'editOrder', 'editCompany');
        return view('sales.action', compact('pageData'));
    }

    public function storeOrder(Request $request)
    {
        // Convert grade to uppercase for consistency and case-insensitive validation/storage
        $items = $request->input('items', []);
        foreach ($items as &$item) {
            if (isset($item['grade'])) {
                $item['grade'] = strtoupper($item['grade']);
            }
        }
        $request->merge(['items' => $items]);

        // Prevent duplicate orders: route to the update logic if an order ID is provided
        $orderId = $request->input('order_id') ?? $request->input('id');
        if ($orderId) {
            return $this->updateOrder($request, $orderId);
        }

        $request->validate([
            'company_id'      => 'required|exists:companies,id',
            'transporter_id'  => 'required|exists:transporters,id',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.grade'      => 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        $user  = $this->authUser();

        // Security check: Ensure products are visible to this user role
        $visibleProductIds = Product::visibleTo($user['role'])->pluck('id')->toArray();
        foreach ($request->items as $item) {
            if (!in_array($item['product_id'], $visibleProductIds)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized product access.'], 403);
            }
        }
        $total = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);

        DB::transaction(function () use ($request, $user, $total) {
            $order = Order::create([
                'created_by'      => $user['id'],
                'company_id'      => $request->company_id,
                'transporter_id'  => $request->transporter_id,
                'total'           => $total,
                'status'          => 'OPEN',
                'dispatch_status' => 'PENDING',
                'notes'           => $request->notes,
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'grade'      => $item['grade'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Order generated successfully!']);
    }

    public function storeCompany(Request $request)
    {
        $request->merge(['name' => strtoupper($request->name)]);

        $request->validate([
            'name'    => 'required|string|max:255|unique:companies,name',
            'gst'     => ['nullable', 'string', 'regex:/^(N\/A|[A-Za-z0-9]{15})$/i', 'unique:companies,gst'],
            'contact' => ['required', 'string', 'regex:/^(\+91\s*)?[0-9]{10}$/'],
            'address' => 'required|string|max:500',
        ], [
            'gst.regex'     => 'GST number must be 15 alphanumeric characters or N/A',
            'contact.regex' => 'Mobile number must be 10 digits (with or without +91)',
            'name.unique'   => 'Company name already exists',
            'gst.unique'    => 'GST number already registered'
        ]);

        $contact = preg_replace('/^\+91\s*/', '', $request->contact);
        $request->merge(['contact' => '+91 ' . $contact]);

        Company::create($request->only('name', 'gst', 'address', 'contact'));
        return response()->json(['success' => true, 'message' => 'Company saved!']);
    }

    public function updateCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $request->merge(['name' => strtoupper($request->name)]);

        $request->validate([
            'name'    => 'required|string|max:255|unique:companies,name,' . $company->id,
            'gst'     => ['nullable', 'string', 'regex:/^(N\/A|[A-Za-z0-9]{15})$/i', 'unique:companies,gst,' . $company->id],
            'contact' => ['required', 'string', 'regex:/^(\+91\s*)?[0-9]{10}$/'],
            'address' => 'required|string|max:500',
        ], [
            'gst.regex'     => 'GST number must be 15 alphanumeric characters or N/A',
            'contact.regex' => 'Mobile number must be 10 digits (with or without +91)',
            'name.unique'   => 'Company name already exists',
            'gst.unique'    => 'GST number already registered'
        ]);

        $contact = preg_replace('/^\+91\s*/', '', $request->contact);
        $request->merge(['contact' => '+91 ' . $contact]);

        $company->update($request->only('name', 'gst', 'address', 'contact'));
        return response()->json(['success' => true, 'message' => 'Company updated!']);
    }

    public function storeTransporter(Request $request)
    {
        $request->merge(['name' => strtoupper($request->name)]);

        $request->validate([
            'name'    => 'required|string|max:255|unique:transporters,name',
            'gst'     => ['nullable', 'string', 'regex:/^(N\/A|[A-Za-z0-9]{15})$/i', 'unique:transporters,gst'],
            'contact' => ['required', 'string', 'regex:/^(\+91\s*)?[0-9]{10}$/'],
            'vehicles' => 'nullable|string',
        ], [
            'gst.regex'     => 'GST number must be 15 alphanumeric characters or N/A',
            'contact.regex' => 'Mobile number must be 10 digits (with or without +91)',
            'name.unique'   => 'Transporter already exists',
            'gst.unique'    => 'GST number already registered'
        ]);

        $contact = preg_replace('/^\+91\s*/', '', $request->contact);
        $request->merge(['contact' => '+91 ' . $contact]);

        Transporter::create($request->only('name', 'gst', 'contact', 'vehicles'));
        return response()->json(['success' => true, 'message' => 'Transporter saved!']);
    }

    public function history()
    {
        $orders = Order::with(['company', 'transporter', 'items.product'])->orderByDesc('created_at')->get();
        $companies = Company::orderBy('name')->get();
        $transporters = Transporter::orderBy('name')->get();

        $pageData = [
            'orders'             => $orders->map(fn($o)=>[
                'id'             => $o->id,
                'companyId'      => $o->company_id,
                'companyName'    => strtoupper($o->company?->name ?? ''),
                'transportId'    => $o->transporter_id,
                'total'          => $o->total,
                'status'         => $o->status,
                'dispatchStatus' => $o->dispatch_status,
                'date'           => $o->created_at->toISOString(),
                'notes'          => $o->notes,
                'items'          => $o->items->map(fn($i)=>[
                    'id'          => $i->id,
                    'productId'   => $i->product_id,
                    'productName' => strtoupper($i->product ? $i->product->formatName($i->grade) : ''),
                    'grade'       => strtoupper($i->grade ?? ''),
                    'quantity'    => $i->quantity,
                    'price'       => $i->price,
                ]),
            ]),
            'companies'          => $companies->map(fn($c)=>[
                'id'=>$c->id,'name'=>strtoupper($c->name ?? ''),'gst'=>$c->gst,'contact'=>$c->contact,'address'=>$c->address,'date'=>$c->created_at->toISOString()
            ]),
            'transportCompanies' => $transporters->map(fn($t)=>[
                'id'=>$t->id,'name'=>strtoupper($t->name ?? ''),'gst'=>$t->gst,'contact'=>$t->contact,'vehicles'=>$t->vehicles,'date'=>$t->created_at->toISOString()
            ]),
        ];
        return view('sales.history', compact('pageData'));
    }

    public function updateOrder(Request $request, $id)
    {
        // Convert grade to uppercase for consistency and case-insensitive validation/storage
        $items = $request->input('items', []);
        foreach ($items as &$item) {
            if (isset($item['grade'])) {
                $item['grade'] = strtoupper($item['grade']);
            }
        }
        $request->merge(['items' => $items]);

        $order = Order::findOrFail($id);

        // Allow editing unless the order is fully dispatched or closed
        if ($order->status === 'CLOSED' || $order->dispatch_status === 'DONE') {
            return response()->json(['success' => false, 'message' => 'Fully dispatched or closed orders cannot be edited.'], 422);
        }

        $request->validate([
            'company_id'      => 'required|exists:companies,id',
            'transporter_id'  => 'required|exists:transporters,id',
            'items'           => 'required|array|min:1',
            'items.*.id'      => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.grade'      => 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        $user = $this->authUser();
        $visibleProductIds = Product::visibleTo($user['role'])->pluck('id')->toArray();
        foreach ($request->items as $item) {
            if (!in_array($item['product_id'], $visibleProductIds)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized product access.'], 403);
            }
        }

        // Validate that quantities don't go below dispatched amounts
        foreach ($request->items as $item) {
            if (!empty($item['id'])) {
                $orderItem = OrderItem::find($item['id']);
                if ($orderItem && $item['quantity'] < $orderItem->dispatched_qty) {
                    $product = $orderItem->product;
                    $productName = $product ? $product->formatName($item['grade']) : 'Unknown';
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot reduce {$productName} quantity to {$item['quantity']} kg. Already dispatched: {$orderItem->dispatched_qty} kg. Minimum allowed: {$orderItem->dispatched_qty} kg."
                    ], 422);
                }
            }
        }

        $total = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);

        DB::transaction(function () use ($request, $order, $total) {
            $order->update([
                'company_id'     => $request->company_id,
                'transporter_id' => $request->transporter_id,
                'total'          => $total,
                'notes'          => $request->notes,
            ]);

            $existingItemIds = [];

            foreach ($request->items as $item) {
                // Update existing item if ID is provided, maintaining dispatch history
                if (!empty($item['id'])) {
                    $orderItem = OrderItem::where('id', $item['id'])->where('order_id', $order->id)->first();
                    if ($orderItem) {
                        // Ensure we don't reduce quantity below what's already dispatched
                        $newQty = max((float)$item['quantity'], (float)$orderItem->dispatched_qty);
                        $orderItem->update([
                            'product_id' => $item['product_id'],
                            'grade'      => $item['grade'],
                            'quantity'   => $newQty,
                            'price'      => $item['price'],
                        ]);
                        $existingItemIds[] = $orderItem->id;
                        continue;
                    }
                }

                // Otherwise, create a new item attached to the same order
                $newItem = OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'grade'      => $item['grade'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
                $existingItemIds[] = $newItem->id;
            }

            // Only delete removed items if they haven't been dispatched
            $order->items()->whereNotIn('id', $existingItemIds)->where('dispatched_qty', '<=', 0)->delete();
        });

        return response()->json(['success' => true, 'message' => 'Order updated successfully!']);
    }

    public function cancelOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        if ($order->dispatch_status === 'DONE' || $order->dispatch_status === 'PARTIAL' || $order->status === 'CLOSED') {
            return response()->json(['success' => false, 'message' => 'Cannot cancel an order that is closed or has dispatch progress.'], 422);
        }

        $order->update(['status' => 'CANCELLED']);
        return response()->json(['success' => true, 'message' => 'Order cancelled successfully!']);
    }

    public function profile()
    {
        return view('sales.profile');
    }
}
