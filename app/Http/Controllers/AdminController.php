<?php

namespace App\Http\Controllers;

use App\Models\DispatchLog;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $rawQty      = DB::table('stocks')->where('stage', 'RAW')
            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")->value('net') ?? 0;
        $semiQty     = DB::table('stocks')->where('stage', 'SEMI')
            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")->value('net') ?? 0;
        $finishedQty = DB::table('stocks')->where('stage', 'FINISHED')
            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")->value('net') ?? 0;

        $totalOrders  = \App\Models\Order::count();
        $totalRevenue = \App\Models\Order::sum('total');
        $pendingPOs   = \App\Models\PurchaseOrder::where('status', 'PENDING')->count();
        
        $totalWorkers = \App\Models\Worker::count();
        $presentToday = \App\Models\Attendance::where('date', \Carbon\Carbon::today()->toDateString())->whereIn('status', ['PRESENT', 'HALF_DAY'])->count();

        $pageData = compact('rawQty', 'semiQty', 'finishedQty', 'totalOrders', 'totalRevenue', 'pendingPOs', 'totalWorkers', 'presentToday');
        return view('admin.dashboard', compact('pageData'));
    }

    // ── USERS ──────────────────────────────────────────────────────────────
    public function users()
    {
        $users = User::with('parent')->orderBy('role')->paginate(15);
        return view('admin.users', ['pageData' => ['users' => $users]]);
    }

    public function storeUser(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'role' => 'required|in:ADMIN,RAW,SEMI,FINISHED,SALES,DISPATCH,CASHIER,ATTENDANCE',
        ];

        if (!$request->user_id) {
            $rules['email']    = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:4';
        }

        $request->validate($rules);

        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
            $user->name  = $request->name;
            $user->role  = $request->role;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->parent_id = $request->parent_id ?: null;
            $user->save();
            $msg = 'User updated!';
        } else {
            User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => $request->role,
                'parent_id' => $request->parent_id ?: null,
                'status'    => 'ACTIVE',
            ]);
            $msg = 'User created!';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function toggleUserStatus(Request $request)
    {
        if($request->user_id == session('auth_user')['id']) {
            return response()->json(['success' => false, 'message' => 'Cannot block yourself!']);
        }
        $user = User::findOrFail($request->user_id);
        $user->status = $user->status === 'BLOCKED' ? 'ACTIVE' : 'BLOCKED';
        $user->save();
        return response()->json(['success' => true, 'message' => "User {$user->status}!"]);
    }

    public function destroyUser($id)
    {
        if($id == session('auth_user')['id']) {
            return response()->json(['success' => false, 'message' => 'Cannot delete yourself!']);
        }
        User::destroy($id);
        return response()->json(['success' => true, 'message' => 'User deleted!']);
    }

    // ── PRODUCTS ───────────────────────────────────────────────────────────
    public function products()
    {
        $products = Product::with('grades')
            ->where('type', '!=', 'FINISHED')
            ->orderBy('type')->orderBy('name')
            ->paginate(15);
            
        $allActiveGrades = \App\Models\Grade::where('is_active', true)->orderBy('name')->get();
        
        $pageData = [
            'products' => $products,
            'allGrades' => $allActiveGrades
        ];
        return view('admin.products', compact('pageData'));
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:RAW,SEMI,FINISHED',
            'grades' => 'nullable|array'
        ]);

        if ($request->product_id) {
            $product = Product::findOrFail($request->product_id);
            $product->update($request->only('name', 'unit', 'image_url'));
            $product->grades()->sync($request->grades ?? []);
            $msg = 'Product updated!';
        } else {
            $product = Product::create([
                'name'      => $request->name,
                'type'      => $request->type,
                'unit'      => $request->unit ?? 'kg',
                'image_url' => $request->image_url,
            ]);
            $product->grades()->sync($request->grades ?? []);
            $msg = 'Product created!';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function destroyProduct($id)
    {
        Product::destroy($id);
        return response()->json(['success' => true, 'message' => 'Product deleted!']);
    }

    public function toggleProductStatus($id)
    {
        $p = Product::findOrFail($id);
        $p->is_active = !$p->is_active;
        $p->save();
        return response()->json(['success' => true]);
    }

    // ── LIVE STOCK ─────────────────────────────────────────────────────────
    public function stock()
    {
        $allStock = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.unit')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                stocks.stage,
                stocks.grade,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("quantity > 0")
            ->where('products.type', '!=', 'FINISHED')
            ->orderBy('stocks.stage')
            ->orderBy('products.name')
            ->get();

        $pageData = ['allStock' => $allStock];
        return view('admin.stock', compact('pageData'));
    }

    // ── PURCHASE ORDERS ────────────────────────────────────────────────────
    public function po()
    {
        $pos = PurchaseOrder::with(['user', 'product'])
            ->orderByDesc('created_at')
            ->paginate(15);
            
        return view('admin.po', ['pageData' => ['purchaseOrders' => $pos]]);
    }

    public function approvePO(Request $request)
    {
        $po = PurchaseOrder::findOrFail($request->po_id);
        $po->status = 'DONE';
        $po->save();

        // Auto-inward to RAW stock when admin marks as arrived
        Stock::create([
            'product_id'       => $po->product_id,
            'user_id'          => session('auth_user')['id'],
            'stage'            => 'RAW',
            'grade'            => 'NONE',
            'quantity'         => $po->quantity,
            'transaction_type' => 'IN',
            'notes'            => "PO #{$po->id} approved & auto-inwarded",
        ]);

        return response()->json(['success' => true, 'message' => 'PO marked as arrived and stock updated!']);
    }

    public function destroyPO($id)
    {
        PurchaseOrder::destroy($id);
        return response()->json(['success' => true, 'message' => 'Order deleted!']);
    }

    // ── ACTIVITY LOGS ──────────────────────────────────────────────────────
    public function logs()
    {
        // 1. Production Logs (Raw/Semi/Finished)
        $prodLogs = ProductionLog::with(['user', 'outputProduct'])
            ->orderByDesc('created_at')->get()->map(fn($l) => [
                'category'    => 'Production',
                'date'        => $l->created_at->toISOString(),
                'description' => "Produced {$l->output_qty}kg of {$l->outputProduct?->name} ({$l->output_grade})",
                'by'          => $l->user?->name,
                'role'        => $l->user?->role,
            ]);

        // 2. Dispatch Logs
        $dispLogs = DispatchLog::with(['user', 'order.company'])
            ->orderByDesc('created_at')->get()->map(fn($d) => [
                'category'    => 'Dispatch',
                'date'        => $d->created_at->toISOString(),
                'description' => "Dispatched Order #{$d->order_id} to {$d->order?->company?->name}",
                'by'          => $d->user?->name,
                'role'        => 'DISPATCH',
            ]);

        // 3. Sales Orders
        $salesLogs = \App\Models\Order::with(['creator', 'company'])
            ->orderByDesc('created_at')->get()->map(fn($o) => [
                'category'    => 'Sales',
                'date'        => $o->created_at->toISOString(),
                'description' => "Created Order #{$o->id} for {$o->company?->name} (Total: ₹{$o->total})",
                'by'          => $o->creator?->name,
                'role'        => 'SALES',
            ]);

        // 4. Purchase Orders
        $poLogs = PurchaseOrder::with(['user', 'product'])
            ->orderByDesc('created_at')->get()->map(fn($p) => [
                'category'    => 'Purchase',
                'date'        => $p->created_at->toISOString(),
                'description' => "Requested {$p->quantity}kg of {$p->product?->name} (Status: {$p->status})",
                'by'          => $p->user?->name,
                'role'        => $p->user?->role,
            ]);

        // 5. Stock Adjustments & Inwards
        $stockLogs = Stock::with(['user', 'product'])
            ->whereIn('notes', ['Manual admin adjustment', 'PO approved & auto-inwarded'])
            ->orWhere('transaction_type', 'IN')
            ->orderByDesc('created_at')->get()->map(fn($s) => [
                'category'    => 'Inventory',
                'date'        => $s->created_at->toISOString(),
                'description' => "{$s->transaction_type}ward: {$s->quantity}kg of {$s->product?->name} ({$s->stage}). Notes: {$s->notes}",
                'by'          => $s->user?->name,
                'role'        => $s->user?->role,
            ]);

        // 6. Cashier Transactions (New)
        $cashLogs = \App\Models\Transaction::with('user')
            ->orderByDesc('created_at')->get()->map(fn($t) => [
                'category'    => 'Cashier',
                'date'        => $t->created_at->toISOString(),
                'description' => "Cash {$t->type}: ₹{$t->amount} for {$t->category}. Note: {$t->note}",
                'by'          => $t->user?->name,
                'role'        => 'CASHIER',
            ]);

        $allLogs = $prodLogs->concat($dispLogs)->concat($salesLogs)->concat($poLogs)->concat($stockLogs)->concat($cashLogs)
            ->sortByDesc('date')->values();
        
        $pageData = [
            'logs'  => $allLogs,
            'users' => User::all(['id', 'name', 'role']),
        ];
        return view('admin.logs', compact('pageData'));
    }

    public function grades()
    {
        $grades = \App\Models\Grade::orderBy('name')->paginate(15);
        return view('admin.grades', ['pageData' => ['grades' => $grades]]);
    }

    public function storeGrade(Request $request)
    {
        if ($request->toggle) {
            $grade = \App\Models\Grade::findOrFail($request->grade_id);
            $grade->is_active = !$grade->is_active;
            $grade->save();
            return response()->json(['success' => true]);
        }

        if ($request->grade_id) {
            $grade = \App\Models\Grade::findOrFail($request->grade_id);
            $grade->update(['name' => $request->name]);
            return response()->json(['success' => true, 'message' => 'Grade updated!']);
        }

        $request->validate(['name' => 'required|string|unique:grades,name']);
        \App\Models\Grade::create(['name' => $request->name]);
        return response()->json(['success' => true, 'message' => 'Grade created!']);
    }

    public function destroyGrade($id)
    {
        \App\Models\Grade::destroy($id);
        return response()->json(['success' => true, 'message' => 'Grade deleted!']);
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stage'      => 'required',
            'grade'      => 'required',
            'quantity'   => 'required|numeric'
        ]);

        // Current net qty
        $current = DB::table('stocks')
            ->where('product_id', $request->product_id)
            ->where('stage', $request->stage)
            ->where('grade', $request->grade)
            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
            ->value('net') ?? 0;

        $diff = $request->quantity - $current;
        if ($diff == 0) return response()->json(['success' => true, 'message' => 'No change needed.']);

        Stock::create([
            'product_id'       => $request->product_id,
            'user_id'          => session('auth_user')['id'],
            'stage'            => $request->stage,
            'grade'            => $request->grade,
            'quantity'         => abs($diff),
            'transaction_type' => $diff > 0 ? 'IN' : 'OUT',
            'notes'            => 'Manual admin adjustment',
        ]);

        return response()->json(['success' => true, 'message' => 'Stock adjusted successfully!']);
    }
}
