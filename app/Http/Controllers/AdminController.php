<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

        $lowRawCount = DB::table('stocks')
            ->leftJoin('stock_limits', function($join) {
                $join->on('stocks.product_id', '=', 'stock_limits.product_id')
                     ->on('stocks.stage', '=', 'stock_limits.stage')
                     ->on('stocks.grade', '=', 'stock_limits.grade');
            })
            ->select('stocks.product_id', 'stocks.stage', 'stocks.grade')
            ->where('stocks.stage', 'RAW')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'stock_limits.alert_limit')
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) < IFNULL(stock_limits.alert_limit, 0)")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0") // Optional: exclude 0 stock? Actually if stock is 0, it should probably still alert if limit is > 0. Let's just use the limit comparison.
            ->get()
            ->count();

        $lowSemiCount = DB::table('stocks')
            ->leftJoin('stock_limits', function($join) {
                $join->on('stocks.product_id', '=', 'stock_limits.product_id')
                     ->on('stocks.stage', '=', 'stock_limits.stage')
                     ->on('stocks.grade', '=', 'stock_limits.grade');
            })
            ->select('stocks.product_id', 'stocks.stage', 'stocks.grade')
            ->where('stocks.stage', 'SEMI')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'stock_limits.alert_limit')
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) < IFNULL(stock_limits.alert_limit, 0)")
            ->get()
            ->count();

        $lowFinishedCount = DB::table('stocks')
            ->leftJoin('stock_limits', function($join) {
                $join->on('stocks.product_id', '=', 'stock_limits.product_id')
                     ->on('stocks.stage', '=', 'stock_limits.stage')
                     ->on('stocks.grade', '=', 'stock_limits.grade');
            })
            ->select('stocks.product_id', 'stocks.stage', 'stocks.grade')
            ->where('stocks.stage', 'FINISHED')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'stock_limits.alert_limit')
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) < IFNULL(stock_limits.alert_limit, 0)")
            ->get()
            ->count();

        $totalOrders  = \App\Models\Order::count();
        $totalRevenue = \App\Models\Order::sum('total');
        $pendingPOs   = \App\Models\PurchaseOrder::where('status', 'PENDING')->count();
        
        $totalWorkers = \App\Models\Worker::count();
        $presentToday = \App\Models\Attendance::where('date', \Carbon\Carbon::today()->toDateString())->whereIn('status', ['PRESENT', 'HALF_DAY'])->count();

        // --- Chart Data ---
        $days = [];
        $salesTrend = [];
        $productionTrend = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $days[] = $date->format('D (d M)');
            
            $salesTrend[] = \App\Models\Order::whereDate('created_at', $date)->sum('total') ?: 0;
            $productionTrend[] = \App\Models\ProductionLog::whereDate('created_at', $date)->sum('output_qty') ?: 0;
        }

        $pageData = compact(
            'rawQty', 'semiQty', 'finishedQty', 
            'lowRawCount', 'lowSemiCount', 'lowFinishedCount', 
            'totalOrders', 'totalRevenue', 'pendingPOs', 
            'totalWorkers', 'presentToday',
            'days', 'salesTrend', 'productionTrend'
        );
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
            'name'   => 'required|string|max:100',
            'role'   => 'required|in:ADMIN,SUB_ADMIN,RAW,SEMI,FINISHED,SALES,DISPATCH,CASHIER,ATTENDANCE',
            'branch' => 'nullable|string|max:100',
            'permissions' => 'nullable',
        ];

        if (!$request->user_id) {
            $rules['email']    = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:4';
        }

        $request->validate($rules);
        
        $permissions = is_string($request->permissions) ? json_decode($request->permissions, true) : ($request->permissions ?? []);
        if ($request->role !== 'SUB_ADMIN') {
            $permissions = [];
        }

        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
            $user->name  = $request->name;
            $user->role  = $request->role;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->parent_id = $request->parent_id ?: null;
            if ($request->role === 'CASHIER') {
                $user->branch = $request->branch;
            } else {
                $user->branch = null;
            }
            $user->permissions = $permissions;
            $user->save();
            $msg = 'User updated!';
        } else {
            User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => $request->role,
                'parent_id' => $request->parent_id ?: null,
                'branch'    => $request->role === 'CASHIER' ? $request->branch : null,
                'status'    => 'ACTIVE',
                'permissions' => $permissions,
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

    public function sendNotification(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'title'   => 'required|string|max:100',
                'message' => 'required|string|max:500',
                'type'    => 'required|in:info,warning,success,danger'
            ]);

            $user = User::findOrFail($request->user_id);
            \Log::info("Admin sending notification to User ID: {$user->id}, Title: {$request->title}");
            
            $user->notify(new \App\Notifications\GeneralNotification(
                $request->title,
                $request->message,
                $request->type
            ));

            return response()->json(['success' => true, 'message' => 'Notification sent successfully!']);
        } catch (\Exception $e) {
            \Log::error("Failed to send notification: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }

    // ── PRODUCTS ───────────────────────────────────────────────────────────
    public function products()
    {
        $products = Product::with('grades')
            ->orderBy('type')->orderBy('name')
            ->get();
            
        $allActiveGrades = \App\Models\Grade::where('is_active', true)->orderBy('name')->get();
        
        $pageData = [
            'products' => $products->map(function($p) {
                $p->gradeIds = $p->grades->pluck('id')->toArray();
                $p->gradeNames = $p->grades->pluck('name')->toArray();
                return $p;
            }),
            'allGrades' => $allActiveGrades,
            'paginator' => null
        ];
        return view('admin.products', compact('pageData'));
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:RAW,SEMI,FINISHED',
            'rate' => 'nullable|numeric|min:0',
            'grades' => 'nullable', // Could be stringified JSON or array
            'allowed_roles' => 'nullable', // Could be stringified JSON or array
            'image' => 'nullable|image|max:2048'
        ]);

        // Parse JSON arrays if sent via FormData
        $grades = is_string($request->grades) ? json_decode($request->grades, true) : ($request->grades ?? []);
        $allowedRoles = is_string($request->allowed_roles) ? json_decode($request->allowed_roles, true) : ($request->allowed_roles ?? []);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imageUrl = '/storage/' . $path;
        }

        $data = [
            'name' => $request->name,
            'type' => $request->type,
            'unit' => $request->unit ?? 'kg',
            'rate' => $request->rate ?? 0.00,
            'allowed_roles' => $allowedRoles
        ];
        
        if ($imageUrl !== null) {
            $data['image_url'] = $imageUrl;
        }

        if ($request->product_id) {
            $product = Product::findOrFail($request->product_id);
            $product->update($data);
            $product->grades()->sync($grades);
            $msg = 'Product updated!';
        } else {
            $product = Product::create($data);
            $product->grades()->sync($grades);
            $msg = 'Product created!';
        }

        return response()->json(['success' => true, 'message' => $msg, 'product' => $product]);
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
            ->leftJoin('stock_limits', function($join) {
                $join->on('stocks.product_id', '=', 'stock_limits.product_id')
                     ->on('stocks.stage', '=', 'stock_limits.stage')
                     ->on('stocks.grade', '=', 'stock_limits.grade');
            })
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.unit', 'stock_limits.alert_limit')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                stocks.stage,
                stocks.grade,
                IFNULL(stock_limits.alert_limit, 0) as alert_limit,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->orderBy('stocks.stage')
            ->orderBy('products.name')
            ->get();

        $allProducts = Product::orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'unit', 'is_active']);

        $stockLogsByKey = Stock::with(['user:id,name', 'product:id,name,unit'])
            ->latest()
            ->limit(500)
            ->get()
            ->groupBy(fn ($log) => "{$log->product_id}_{$log->grade}_{$log->stage}")
            ->map(fn ($logs) => $logs->map(fn ($log) => [
                'id' => $log->id,
                'product_id' => $log->product_id,
                'product_name' => $log->product?->name,
                'unit' => $log->product?->unit,
                'stage' => $log->stage,
                'grade' => $log->grade,
                'quantity' => (float) $log->quantity,
                'transaction_type' => $log->transaction_type,
                'notes' => $log->notes,
                'user_name' => $log->user?->name ?? 'Unknown',
                'created_at' => optional($log->created_at)->format('d M Y, h:i A'),
            ])->values());

        $pageData = [
            'allStock' => $allStock,
            'allProducts' => $allProducts,
            'stockLogsByKey' => $stockLogsByKey,
        ];
        return view('admin.stock', compact('pageData'));
    }

    public function downloadStockPdf(Request $request)
    {
        $stages = $request->input('stages', ['RAW', 'SEMI', 'FINISHED']);
        if (!is_array($stages)) {
            $stages = explode(',', $stages);
        }
        $stages = array_map('strtoupper', $stages);

        $locationsJson = $request->input('locations', '{}');
        $locations = json_decode($locationsJson, true) ?: [];

        $stockData = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereIn('stocks.stage', $stages)
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.unit', 'products.rate')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                products.rate,
                stocks.stage,
                stocks.grade,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->orderBy('stocks.stage')
            ->orderBy('products.name')
            ->get();

        $totalValuation = 0.0;
        $items = [];
        foreach ($stockData as $s) {
            $key = "{$s->productId}_{$s->grade}_{$s->stage}";
            $locMap = $locations[$key] ?? [];
            
            $locStrings = [];
            foreach ($locMap as $loc => $qty) {
                $locStrings[] = "{$loc} ({$qty} {$s->unit})";
            }
            $locationText = !empty($locStrings) ? implode(', ', $locStrings) : 'Not Specified';

            $rate = (float) ($s->rate ?? 0.00);
            $amount = $s->quantity * $rate;
            $totalValuation += $amount;

            $items[] = [
                'name' => $s->name,
                'stage' => $s->stage,
                'grade' => $s->grade,
                'quantity' => $s->quantity,
                'unit' => $s->unit,
                'location' => $locationText,
                'rate' => $rate,
                'amount' => $amount
            ];
        }

        $pdfData = [
            'items' => $items,
            'totalValuation' => $totalValuation,
            'generatedOn' => now()->format('d M Y, h:i A'),
            'stages' => $stages,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.live-stock', $pdfData)->setPaper('A4', 'portrait');
        return $pdf->download('PentaPure_Live_Stock_Valuation_Report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function liveStockApi()
    {
        $allStock = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->leftJoin('stock_limits', function($join) {
                $join->on('stocks.product_id', '=', 'stock_limits.product_id')
                     ->on('stocks.stage', '=', 'stock_limits.stage')
                     ->on('stocks.grade', '=', 'stock_limits.grade');
            })
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.unit', 'products.rate', 'stock_limits.alert_limit')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                products.rate,
                stocks.stage,
                stocks.grade,
                IFNULL(stock_limits.alert_limit, 0) as alert_limit,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->orderBy('stocks.stage')
            ->orderBy('products.name')
            ->get();

        return response()->json(['success' => true, 'data' => $allStock]);
    }

    public function setStockLimit(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stage' => 'required|string',
            'grade' => 'required|string',
            'alert_limit' => 'required|numeric|min:0'
        ]);

        \App\Models\StockLimit::updateOrCreate(
            [
                'product_id' => $request->product_id,
                'stage' => $request->stage,
                'grade' => $request->grade
            ],
            [
                'alert_limit' => $request->alert_limit
            ]
        );

        return response()->json(['success' => true, 'message' => 'Stock alert limit updated!']);
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
    public function cashierActivityLogs()
    {
        $logs = \App\Models\TransactionLog::with(['user', 'transaction'])
            ->orderByDesc('created_at')
            ->paginate(50);
            
        return view('admin.cashier_logs', ['pageData' => ['logs' => $logs]]);
    }

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

    public function categories()
    {
        $categories = Category::orderByDesc('is_active')->orderBy('name')->paginate(15);
        return view('admin.categories', ['pageData' => ['categories' => $categories]]);
    }

    public function storeCategory(Request $request)
    {

        if ($request->toggle) {
            $category = Category::findOrFail($request->category_id);
            $category->is_active = !$category->is_active;
            $category->save();
            return response()->json(['success' => true]);
        }

        if ($request->category_id) {
            $category = Category::findOrFail($request->category_id);
            $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            ]);
            $category->update(['name' => $request->name]);
            return response()->json(['success' => true, 'message' => 'Category updated!']);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);
        Category::create([
            'name' => $request->name,
        ]);
        return response()->json(['success' => true, 'message' => 'Category created!']);
    }

    public function toggleCategoryStatus(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:categories,id']);
        $category = Category::findOrFail($request->category_id);
        $category->is_active = !$category->is_active;
        $category->save();
        return response()->json(['success' => true, 'message' => 'Category status updated!']);
    }

    public function destroyCategory($id)
    {
        Category::destroy($id);
        return response()->json(['success' => true, 'message' => 'Category deleted!']);
    }

    public function adjustStock(Request $request)
    {

        $request->validate([

            'product_id'  => 'required|exists:products,id',
            'stage'       => 'required|in:RAW,SEMI,FINISHED',
            'grade'       => 'required',
            'quantity'    => 'required|numeric|min:0',
            'adjust_type' => 'nullable|in:set,add,subtract',
            'reason'      => 'nullable|string|max:255',
        ]);

        $type   = $request->input('adjust_type', 'set');
        $qty    = (float) $request->quantity;
        $reason = trim($request->input('reason', ''));
        $note   = 'Manual admin adjustment' . ($reason ? " — {$reason}" : '');

        // Current net qty for the product/stage/grade combination
        $current = (float) (DB::table('stocks')
            ->where('product_id', $request->product_id)
            ->where('stage', $request->stage)
            ->where('grade', $request->grade)
            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
            ->value('net') ?? 0);

        if ($type === 'set') {
            $diff = $qty - $current;
            if ($diff == 0) {
                return response()->json(['success' => true, 'message' => 'Stock is already at that value — no change made.']);
            }
            $txnQty  = abs($diff);
            $txnType = $diff > 0 ? 'IN' : 'OUT';
            $summary = "Set to {$qty} kg (was {$current} kg)";
        } elseif ($type === 'add') {
            if ($qty == 0) {
                return response()->json(['success' => true, 'message' => 'Nothing to add — quantity is 0.']);
            }
            $txnQty  = $qty;
            $txnType = 'IN';
            $summary = "Added {$qty} kg (was {$current} kg)";
        } else { // subtract
            if ($qty == 0) {
                return response()->json(['success' => true, 'message' => 'Nothing to subtract — quantity is 0.']);
            }
            if ($qty > $current) {
                return response()->json(['success' => false, 'message' => "Cannot subtract {$qty} kg — only {$current} kg in stock."]);
            }
            $txnQty  = $qty;
            $txnType = 'OUT';
            $summary = "Subtracted {$qty} kg (was {$current} kg)";
        }

        Stock::create([
            'product_id'       => $request->product_id,
            'user_id'          => session('auth_user')['id'],
            'stage'            => $request->stage,
            'grade'            => $request->grade,
            'quantity'         => $txnQty,
            'transaction_type' => $txnType,
            'notes'            => "{$note} [{$summary}]",
        ]);

        return response()->json(['success' => true, 'message' => "Stock updated! {$summary}."]);
    }
    // ── DISPATCH ACTIVITY ───────────────────────────────────────────────────
    public function dispatchActivity(Request $request)
    {
        $query = \App\Models\Order::with(['company', 'items.product', 'dispatchLog.user', 'transporter'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('dispatch_status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20)->withQueryString();

        $pageData = [
            'orders' => $orders,
            'filters' => [
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ]
        ];

        return view('admin.dispatch_activity', compact('pageData'));
    }

    public function dispatchActivityPdf(Request $request)
    {
        $query = \App\Models\Order::with(['company', 'items.product', 'dispatchLog.user', 'transporter'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('dispatch_status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.dispatch_activity_pdf', compact('orders'));
        return $pdf->download('dispatch-activity-' . now()->format('Y-m-d') . '.pdf');
    }

    public function cashierOverview()
    {
        $txs = \App\Models\Transaction::with('user')->orderByDesc('created_at')->get();
        
        $summary = [
            'totalIn'  => $txs->where('type', 'IN')->sum('amount'),
            'totalOut' => $txs->where('type', 'OUT')->sum('amount'),
            'balance'  => $txs->where('type', 'IN')->sum('amount') - $txs->where('type', 'OUT')->sum('amount'),
            'byCategory' => $txs->groupBy('category')->map(fn($group) => [
                'in' => $group->where('type', 'IN')->sum('amount'),
                'out' => $group->where('type', 'OUT')->sum('amount'),
            ]),
            'byCashier' => $txs->groupBy('user_id')->map(function($group) {
                $user = $group->first()->user;
                return [
                    'name' => $user ? $user->name : 'Unknown',
                    'in' => $group->where('type', 'IN')->sum('amount'),
                    'out' => $group->where('type', 'OUT')->sum('amount'),
                    'balance' => $group->where('type', 'IN')->sum('amount') - $group->where('type', 'OUT')->sum('amount'),
                ];
            })->values(),
        ];

        $pageData = [
            'transactions' => $txs,
            'summary' => $summary
        ];

        return view('admin.cashier_overview', compact('pageData'));
    }

    public function overviewPdf(Request $request)
    {
        $txs = \App\Models\Transaction::with('user')->orderByDesc('created_at')->get();
        
        $summary = [
            'totalIn'  => $txs->where('type', 'IN')->sum('amount'),
            'totalOut' => $txs->where('type', 'OUT')->sum('amount'),
            'balance'  => $txs->where('type', 'IN')->sum('amount') - $txs->where('type', 'OUT')->sum('amount'),
            'byCategory' => $txs->groupBy('category')->map(fn($group) => [
                'in' => $group->where('type', 'IN')->sum('amount'),
                'out' => $group->where('type', 'OUT')->sum('amount'),
            ]),
            'byCashier' => $txs->groupBy('user_id')->map(function($group) {
                $user = $group->first()->user;
                return [
                    'name' => $user ? $user->name : 'Unknown',
                    'in' => $group->where('type', 'IN')->sum('amount'),
                    'out' => $group->where('type', 'OUT')->sum('amount'),
                    'balance' => $group->where('type', 'IN')->sum('amount') - $group->where('type', 'OUT')->sum('amount'),
                ];
            })->values(),
        ];

        $pageData = [
            'transactions' => $txs,
            'summary' => $summary
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.cashier_overview_pdf', compact('pageData'));
        return $pdf->download('cashier-overview-' . now()->format('Y-m-d') . '.pdf');
    }

    // ── NOTIFICATION HISTORY ───────────────────────────────────────────────
    public function notificationHistory()
    {
        $sessionUser = session('auth_user');
        $user = $sessionUser ? User::find($sessionUser['id']) : null;

        $notifications = collect();
        if ($user) {
            $notifications = $user->notifications()
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($n) {
                    return (object)[
                        'id'         => $n->id,
                        'title'      => $n->data['title'] ?? 'Notification',
                        'message'    => $n->data['message'] ?? '',
                        'type'       => $n->data['type'] ?? 'info',
                        'url'        => $n->data['url'] ?? null,
                        'is_read'    => !is_null($n->read_at),
                        'read_at'    => $n->read_at,
                        'created_at' => $n->created_at,
                        'notif_class' => class_basename($n->type),
                    ];
                });
        }

        $pageData = [
            'notifications' => $notifications,
            'unreadCount'   => $notifications->where('is_read', false)->count(),
            'totalCount'    => $notifications->count(),
        ];

        return view('admin.notifications', compact('pageData'));
    }

    // ── ADMIN: DOWNLOAD ANY CASHIER'S PDF ──────────────────────────────────
    public function downloadCashierPdf(\Illuminate\Http\Request $request, $userId)
    {
        $cashier = User::findOrFail($userId);
        $controller = new \App\Http\Controllers\CashierController();
        return $controller->generateCashierPdf($request, (int) $userId, $cashier->name);
    }
}
