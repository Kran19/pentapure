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
                'companyName'     => $o->company?->name,
                'transportId'     => $o->transporter_id,
                'transportName'   => $o->transporter?->name,
                'total'           => $o->total,
                'status'          => $o->status,
                'dispatchStatus'  => $o->dispatch_status,
                'notes'           => $o->notes,
                'date'            => $o->created_at->toISOString(),
                'products'        => $o->items->map(fn($i) => [
                    'productId'   => $i->product_id,
                    'productName' => $i->product?->name,
                    'grade'       => $i->grade,
                    'quantity'    => $i->quantity,
                    'price'       => $i->price,
                ]),
            ]),
            'companies'          => Company::orderBy('name')->get()->map(fn($c)=>[
                'id'=>$c->id,'name'=>$c->name,'gst'=>$c->gst,'address'=>$c->address,'contact'=>$c->contact,'date'=>$c->created_at->toISOString()
            ]),
            'transportCompanies' => Transporter::orderBy('name')->get()->map(fn($t)=>[
                'id'=>$t->id,'name'=>$t->name,'gst'=>$t->gst,'contact'=>$t->contact,'vehicles'=>$t->vehicles,'date'=>$t->created_at->toISOString()
            ]),
            'products'           => Product::target()->active()->get(['id', 'name', 'unit']),
            'stats'              => compact('totalOrders', 'openOrders', 'pendingDisp', 'totalValue'),
        ];
        return view('sales.home', compact('pageData'));
    }

    public function action()
    {
        $companies = Company::orderBy('name')->get()->map(fn($c)=>[
            'id'=>$c->id,'name'=>$c->name,'gst'=>$c->gst,'address'=>$c->address,'contact'=>$c->contact,'date'=>$c->created_at->toISOString()
        ]);
        $transportCompanies = Transporter::orderBy('name')->get()->map(fn($t)=>[
            'id'=>$t->id,'name'=>$t->name,'gst'=>$t->gst,'contact'=>$t->contact,'vehicles'=>$t->vehicles,'date'=>$t->created_at->toISOString()
        ]);
        $products = Product::target()->active()->get(['id', 'name', 'unit']);
        $grades = ['PPF', 'TPR', 'TPS', 'GOLD', 'PREMIUM', 'RICH', 'RICH+', 'EXTRA STRONG', 'REGULAR', 'DELUXE', 'PURE'];

        $pageData = compact('companies', 'transportCompanies', 'products', 'grades');
        return view('sales.action', compact('pageData'));
    }

    public function storeOrder(Request $request)
    {
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
        $request->validate([
            'name'    => 'required|string|max:255|unique:companies,name',
            'gst'     => 'required|string|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/|unique:companies,gst',
            'contact' => 'required|string|regex:/^[0-9]{10}$/',
            'address' => 'required|string|max:500',
        ], [
            'gst.regex'     => 'Invalid GST format (e.g. 22AAAAA0000A1Z5)',
            'contact.regex' => 'Mobile number must be exactly 10 digits',
            'name.unique'    => 'Company name already exists',
            'gst.unique'     => 'GST number already registered'
        ]);

        Company::create($request->only('name', 'gst',   'address', 'contact'));
        return response()->json(['success' => true, 'message' => 'Company saved!']);
    }

    public function storeTransporter(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255|unique:transporters,name',
            'gst'     => 'required|string|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/|unique:transporters,gst',
            'contact' => 'required|string|regex:/^[0-9]{10}$/',
            'vehicles' => 'nullable|string',
        ], [
            'gst.regex'     => 'Invalid GST format',
            'contact.regex' => 'Mobile number must be 10 digits',
            'name.unique'    => 'Transporter already exists'
        ]);

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
                'transportId'    => $o->transporter_id,
                'total'          => $o->total,
                'status'         => $o->status,
                'dispatchStatus' => $o->dispatch_status,
                'date'           => $o->created_at->toISOString(),
                'notes'          => $o->notes,
                'items'          => $o->items->map(fn($i)=>[
                    'productName' => $i->product?->name,
                    'grade'       => $i->grade,
                    'quantity'    => $i->quantity,
                    'price'       => $i->price,
                ]),
            ]),
            'companies'          => $companies->map(fn($c)=>[
                'id'=>$c->id,'name'=>$c->name,'gst'=>$c->gst,'contact'=>$c->contact,'address'=>$c->address,'date'=>$c->created_at->toISOString()
            ]),
            'transportCompanies' => $transporters->map(fn($t)=>[
                'id'=>$t->id,'name'=>$t->name,'gst'=>$t->gst,'contact'=>$t->contact,'vehicles'=>$t->vehicles,'date'=>$t->created_at->toISOString()
            ]),
        ];
        return view('sales.history', compact('pageData'));
    }

    public function profile()
    {
        return view('sales.profile');
    }
}
