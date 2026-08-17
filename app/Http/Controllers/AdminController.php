<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Category;
use App\Models\DispatchLog;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\User;
use App\Models\Worker;
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
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->select('stocks.product_id', 'stocks.stage', 'stocks.grade')
            ->where('stocks.stage', 'RAW')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.threshold')
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) < products.threshold")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->havingRaw("products.threshold > 0")
            ->get()
            ->count();

        $lowSemiCount = 0; // Removed as per user request

        $lowFinishedCount = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->select('stocks.product_id', 'stocks.stage', 'stocks.grade')
            ->where('stocks.stage', 'FINISHED')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.threshold')
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) < products.threshold")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->havingRaw("products.threshold > 0")
            ->get()
            ->count();

        $totalOrders  = Order::count();
        $totalRevenue = Order::sum('total');
        $pendingPOs   = PurchaseOrder::where('status', 'PENDING')->count();
        
        $totalWorkers = Worker::count();
        $presentToday = Attendance::where('date', \Carbon\Carbon::today()->toDateString())->whereIn('status', ['PRESENT', 'HALF_DAY'])->count();

        // --- Chart Data ---
        $days = [];
        $salesTrend = [];
        $productionTrend = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $days[] = $date->format('D (d M)');
            
            $salesTrend[] = Order::whereDate('created_at', $date)->sum('total') ?: 0;
            $productionTrend[] = ProductionLog::whereDate('created_at', $date)->sum('output_qty') ?: 0;
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
        $allCashiers = User::where('role', 'CASHIER')->get(['id', 'name']);
        return view('admin.users', ['pageData' => ['users' => $users, 'cashiers' => $allCashiers]]);
    }

    public function storeUser(Request $request)
    {
        $rules = [
            'name'   => 'required|string|max:100',
            'role'   => 'required|in:ADMIN,SUB_ADMIN,STOCK_MANAGER,RAW,SEMI,FINISHED,SALES,DISPATCH,CASHIER,ATTENDANCE',
            'branch' => 'nullable|string|max:100',
            'permissions' => 'nullable',
            'visible_cashiers' => 'nullable|array',
            'visible_cashiers.*' => 'exists:users,id',
        ];

        if (!$request->user_id) {
            $rules['email']    = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:4';
        } else {
            $rules['email']    = 'required|email|unique:users,email,' . $request->user_id;
            $rules['password'] = 'nullable|string|min:4';
        }

        $request->validate($rules);
        
        $permissions = is_string($request->permissions) ? json_decode($request->permissions, true) : ($request->permissions ?? []);
        if (!in_array($request->role, ['SUB_ADMIN', 'STOCK_MANAGER'])) {
            $permissions = [];
        }
        
        $visibleCashiers = $request->role === 'CASHIER' ? ($request->visible_cashiers ?? []) : [];
        // Make sure values are integers or strings
        $visibleCashiers = array_map('intval', $visibleCashiers);

        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
            $user->name  = $request->name;
            $user->email = $request->email;
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
            $user->visible_cashiers = $visibleCashiers;
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
                'visible_cashiers' => $visibleCashiers,
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
        $rawProducts = Product::where('type', 'RAW')
            ->orderBy('sort_order')
            ->get();
            
        $semiProducts = Product::with('grades')
            ->where('type', 'SEMI')
            ->orderBy('sort_order')
            ->get();

        $semiProducts->transform(function($p) {
            $p->gradeIds = $p->grades->pluck('id')->toArray();
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });

        $finishedProducts = Product::with('grades')
            ->where('type', 'FINISHED')
            ->orderBy('sort_order')
            ->get();
            
        $finishedProducts->transform(function($p) {
            $p->gradeIds = $p->grades->pluck('id')->toArray();
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });
            
        $allActiveGrades = \App\Models\Grade::where('is_active', true)->orderByRaw("CASE WHEN UPPER(name) = 'NONE' THEN 0 ELSE 1 END")->orderBy('id')->get();
        
        $pageData = [
            'rawProducts' => $rawProducts,
            'semiProducts' => $semiProducts,
            'finishedProducts' => $finishedProducts,
            'allGrades' => $allActiveGrades,
        ];
        return view('admin.products', compact('pageData'));
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:RAW,SEMI,FINISHED',
            'rate' => 'nullable|numeric|min:0',
            'threshold' => 'nullable|numeric|min:0',
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
            'threshold' => $request->threshold ?? 0.00,
            'allowed_roles' => $allowedRoles
        ];
        
        if ($imageUrl !== null) {
            $data['image_url'] = $imageUrl;
        }

        if ($request->product_id) {
            $product = Product::findOrFail($request->product_id);
            $product->update($data);
            $product->grades()->sync($grades);
            
            // Sync to stock_limits table
            \Illuminate\Support\Facades\DB::table('stock_limits')
                ->where('product_id', $product->id)
                ->update(['alert_limit' => $data['threshold']]);
                
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
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.unit', 'products.threshold', 'products.rate', 'products.sort_order', 'stock_limits.alert_limit')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                products.threshold,
                products.rate,
                stocks.stage,
                stocks.grade,
                IFNULL(stock_limits.alert_limit, products.threshold) as alert_limit,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->orderBy('stocks.stage')
            ->orderBy('products.sort_order')
            ->get();

        $allProducts = Product::with('grades')->orderBy('type')
            ->orderBy('sort_order')
            ->get();

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

        // Fetch location mappings from DB
        $locationStock = DB::table('stocks')
            ->join('locations', 'stocks.location_id', '=', 'locations.id')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'locations.name')
            ->selectRaw("
                stocks.product_id,
                stocks.stage,
                stocks.grade,
                locations.name as location_name,
                SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) > 0")
            ->get();

        $locationMappings = [];
        foreach ($locationStock as $ls) {
            $key = "{$ls->product_id}_{$ls->grade}_{$ls->stage}";
            $locationMappings[$key][$ls->location_name] = (float) $ls->quantity;
        }

        $pageData = [
            'allStock' => $allStock,
            'allProducts' => $allProducts,
            'stockLogsByKey' => $stockLogsByKey,
            'locationMappings' => $locationMappings,
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
        
        $date = $request->input('date');

        $stockQuery = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereIn('stocks.stage', $stages);

        if ($date) {
            $stockQuery->where('stocks.created_at', '<=', $date . ' 23:59:59');
        }

        $stockData = $stockQuery->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.unit', 'products.rate', 'products.sort_order')
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
            ->orderBy('products.sort_order')
            ->get();

        // Bulk query for all location breakdowns in a single SQL call
        $locQuery = DB::table('stocks')
            ->leftJoin('locations', 'stocks.location_id', '=', 'locations.id')
            ->whereIn('stocks.stage', $stages);

        if ($date) {
            $locQuery->where('stocks.created_at', '<=', $date . ' 23:59:59');
        }

        $allLocations = $locQuery->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'stocks.location_id', 'locations.name')
            ->selectRaw("
                stocks.product_id,
                stocks.stage,
                stocks.grade,
                stocks.location_id,
                IFNULL(locations.name, 'Unspecified') as name,
                SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) > 0")
            ->get();

        $locsByKey = [];
        foreach ($allLocations as $l) {
            $key = "{$l->product_id}_{$l->stage}_{$l->grade}";
            $locsByKey[$key][] = $l;
        }

        // Preload all matching products in a single SQL call
        $productIds = $stockData->pluck('productId')->unique();
        $productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $totalValuation = 0.0;
        $items = [];
        foreach ($stockData as $s) {
            $key = "{$s->productId}_{$s->stage}_{$s->grade}";
            $itemLocs = $locsByKey[$key] ?? [];

            $locStrings = [];
            $assignedSum = 0;
            foreach ($itemLocs as $l) {
                if ($l->location_id) {
                    $locStrings[] = "{$l->name} ({$l->quantity} {$s->unit})";
                    $assignedSum += $l->quantity;
                }
            }
            
            $unassigned = $s->quantity - $assignedSum;
            if ($unassigned > 0.01) {
                $locStrings[] = "Unspecified ({$unassigned} {$s->unit})";
            }
            
            $locationText = !empty($locStrings) ? implode(', ', $locStrings) : 'Not Specified';

            $rate = (float) ($s->rate ?? 0.00);
            $amount = $s->quantity * $rate;
            $totalValuation += $amount;

            $product = $productsMap->get($s->productId);
            $items[] = [
                'name' => ($product instanceof Product) ? $product->formatName($s->grade) : $s->name,
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
            'date' => $date,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.live-stock', $pdfData)
            ->setPaper('A4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isFontSubsettingEnabled', true);
        
        if ($date) {
            $filename = 'PentaPure_Stock_Valuation_Report_Up_To_' . \Carbon\Carbon::parse($date)->format('Ymd') . '.pdf';
        } else {
            $filename = 'PentaPure_Live_Stock_Valuation_Report_' . now()->format('Ymd_His') . '.pdf';
        }

        return $pdf->download($filename);
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
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.type', 'products.unit', 'products.rate', 'stock_limits.alert_limit')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.type,
                products.unit,
                products.rate,
                stocks.stage,
                stocks.grade,
                IFNULL(stock_limits.alert_limit, products.threshold) as alert_limit,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->orderBy('stocks.stage')
            ->orderBy('products.sort_order')
            ->get();

        // Fetch location mappings from DB
        $locationStock = DB::table('stocks')
            ->join('locations', 'stocks.location_id', '=', 'locations.id')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'locations.name')
            ->selectRaw("
                stocks.product_id,
                stocks.stage,
                stocks.grade,
                locations.name as location_name,
                SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) > 0")
            ->get();

        $locationMappings = [];
        foreach ($locationStock as $ls) {
            $key = "{$ls->product_id}_{$ls->grade}_{$ls->stage}";
            $locationMappings[$key][$ls->location_name] = (float) $ls->quantity;
        }

        return response()->json([
            'success' => true,
            'data' => $allStock,
            'locationMappings' => $locationMappings
        ]);
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

    public function updateProductRate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rate' => 'required|numeric|min:0'
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);
        $product->rate = $request->rate;
        $product->save();

        return response()->json(['success' => true, 'message' => 'Product rate updated successfully!']);
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
        DB::transaction(function() use ($request) {
            $po = PurchaseOrder::findOrFail($request->po_id);
            $po->status = 'DONE';
            $po->save();
        });

        return response()->json(['success' => true, 'message' => 'PO marked as read!']);
    }

    public function receivePO(Request $request)
    {
        DB::transaction(function() use ($request) {
            $po = PurchaseOrder::findOrFail($request->po_id);
            $po->status = 'RECEIVED';
            $po->save();
        });

        return response()->json(['success' => true, 'message' => 'PO marked as received!']);
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
                'description' => "Produced {$l->output_qty}kg of " . ($l->outputProduct ? $l->outputProduct->formatName($l->output_grade) : 'Unknown'),
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
        $salesLogs = Order::with(['creator', 'company'])
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
        $grades = \App\Models\Grade::orderByRaw("CASE WHEN UPPER(name) = 'NONE' THEN 0 ELSE 1 END")->orderBy('id')->paginate(50);
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
            'min_qty'     => 'nullable|numeric|min:0',
        ]);

        if ($request->has('min_qty')) {
            \App\Models\StockLimit::updateOrCreate(
                ['product_id' => $request->product_id, 'stage' => $request->stage, 'grade' => $request->grade],
                ['alert_limit' => $request->min_qty]
            );
        }

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

        if ($txnType === 'OUT') {
            Stock::deductStock(
                $request->product_id,
                $request->stage,
                $request->grade,
                $txnQty,
                session('auth_user')['id'],
                "{$note} [{$summary}]"
            );
        } else {
            $defaultLocId = Location::firstOrCreate(['name' => 'Main Warehouse'])->id;
            Stock::create([
                'product_id'       => $request->product_id,
                'user_id'          => session('auth_user')['id'],
                'stage'            => $request->stage,
                'grade'            => $request->grade,
                'location_id'      => $defaultLocId,
                'quantity'         => $txnQty,
                'transaction_type' => 'IN',
                'notes'            => "{$note} [{$summary}]",
            ]);
        }

        return response()->json(['success' => true, 'message' => "Stock updated! {$summary}."]);
    }

    public function bulkAddStock(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.stage' => 'required|in:RAW,SEMI,FINISHED',
            'items.*.grade' => 'required',
            'items.*.alert_limit' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.locations' => 'required|array',
            'items.*.locations.*.name' => 'required|string',
            'items.*.locations.*.qty' => 'required|numeric|min:0.01',
            'items.*.note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                $productId = $item['product_id'];
                $stage = $item['stage'];
                $grade = $item['grade'];
                $noteText = 'Bulk stock entry' . (!empty($item['note']) ? " — {$item['note']}" : '');
                $userId = session('auth_user')['id'] ?? auth()->id();

                if (isset($item['alert_limit'])) {
                    \App\Models\StockLimit::updateOrCreate(
                        ['product_id' => $productId, 'stage' => $stage, 'grade' => $grade],
                        ['alert_limit' => $item['alert_limit']]
                    );
                }

                if (isset($item['rate']) && $item['rate'] > 0) {
                    $product = Product::find($productId);
                    if ($product) {
                        $product->update(['rate' => $item['rate']]);
                    }
                }

                foreach ($item['locations'] as $loc) {
                    $locationId = Location::firstOrCreate(['name' => $loc['name']])->id;
                    $qty = (float) $loc['qty'];

                    Stock::create([
                        'product_id'       => $productId,
                        'user_id'          => $userId,
                        'stage'            => $stage,
                        'grade'            => $grade,
                        'location_id'      => $locationId,
                        'quantity'         => $qty,
                        'transaction_type' => 'IN',
                        'notes'            => "{$noteText} [Added {$qty} kg]",
                    ]);
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Stock entries added successfully!']);
    }
    // ── DISPATCH ACTIVITY ───────────────────────────────────────────────────
    public function dispatchActivity(Request $request)
    {
        $query = Order::with(['company', 'items.product', 'dispatchLog.user', 'transporter'])
            ->select('orders.*')
            ->addSelect(['dispatch_logs_count' => DispatchLog::selectRaw('COUNT(*)')
                ->whereColumn('order_id', 'orders.id')
            ])
            ->orderByDesc('created_at');

        $status = $request->status;
        if ($status) {
            if ($status === 'PENDING') {
                $query->where('dispatch_status', 'PENDING');
            } elseif ($status === 'PARTIAL_PENDING') {
                $query->where('dispatch_status', 'PARTIAL');
            } elseif ($status === 'PARTIAL_DISPATCH') {
                $query->where('dispatch_status', 'DONE')
                      ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) > 1');
            } elseif ($status === 'FULLY_DISPATCH') {
                $query->where('dispatch_status', 'DONE')
                      ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) <= 1');
            }
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
        $query = Order::with(['company', 'items.product', 'dispatchLog.user', 'transporter'])
            ->select('orders.*')
            ->addSelect(['dispatch_logs_count' => DispatchLog::selectRaw('COUNT(*)')
                ->whereColumn('order_id', 'orders.id')
            ])
            ->orderByDesc('created_at');

        $status = $request->status;
        if ($status) {
            if ($status === 'PENDING') {
                $query->where('dispatch_status', 'PENDING');
            } elseif ($status === 'PARTIAL_PENDING') {
                $query->where('dispatch_status', 'PARTIAL');
            } elseif ($status === 'PARTIAL_DISPATCH') {
                $query->where('dispatch_status', 'DONE')
                      ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) > 1');
            } elseif ($status === 'FULLY_DISPATCH') {
                $query->where('dispatch_status', 'DONE')
                      ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) <= 1');
            }
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
        $txs = \App\Models\Transaction::with(['user', 'bills'])->orderByDesc('created_at')->get();
        
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
    public function downloadCashierPdf(Request $request, $userId)
    {
        $cashier = User::findOrFail($userId);
        $controller = new \App\Http\Controllers\CashierController();
        return $controller->generateCashierPdf($request, (int) $userId, $cashier->name);
    }

    // ── LOCATIONS / WAREHOUSE MASTER ──────────────────────────────────────────
    public function locations()
    {
        $locations = Location::orderBy('name')->paginate(20);
        return view('admin.locations', compact('locations'));
    }

    public function getLocationsApi()
    {
        $locations = Location::orderBy('name')->get(['id', 'name', 'description']);
        return response()->json(['success' => true, 'locations' => $locations]);
    }

    public function storeLocationApi(Request $request)
    {
        $locationId = $request->location_id;
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . ($locationId ?? 'NULL'),
            'description' => 'nullable|string|max:500',
        ]);

        if ($locationId) {
            $location = Location::findOrFail($locationId);
            $location->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
            return response()->json(['success' => true, 'message' => 'Location updated successfully!', 'location' => $location]);
        }

        $location = Location::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json(['success' => true, 'message' => 'Location added successfully!', 'location' => $location]);
    }

    public function destroyLocationApi($id)
    {
        Location::destroy($id);
        return response()->json(['success' => true, 'message' => 'Location deleted successfully!']);
    }

    public function stockLocationsBreakdownApi(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stage' => 'required|string',
            'grade' => 'required|string',
        ]);

        // Query net stock grouped by location
        $breakdown = DB::table('stocks')
            ->join('locations', 'stocks.location_id', '=', 'locations.id')
            ->where('stocks.product_id', $request->product_id)
            ->where('stocks.stage', $request->stage)
            ->where('stocks.grade', $request->grade)
            ->groupBy('stocks.location_id', 'locations.name')
            ->selectRaw("
                stocks.location_id as location_id,
                locations.name as name,
                SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) > 0")
            ->get();

        return response()->json(['success' => true, 'breakdown' => $breakdown]);
    }

    public function transferStockLocationsApi(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stage' => 'required|string',
            'grade' => 'required|string',
            'from_location' => 'nullable|string', // If null/unspecified, deducts from null location_id stock
            'to_location' => 'required|string',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $qty = (float) $request->quantity;

        // Resolve locations
        $fromLocationName = $request->from_location ?: 'Main Warehouse';
        $fromLocationId = Location::firstOrCreate(['name' => $fromLocationName])->id;

        $toLocationId = Location::firstOrCreate(['name' => $request->to_location])->id;

        if ($fromLocationId === $toLocationId) {
            return response()->json(['success' => false, 'message' => 'Source and destination locations must be different.'], 422);
        }

        // Validate stock availability in the source location
        $available = DB::table('stocks')
            ->where('product_id', $request->product_id)
            ->where('stage', $request->stage)
            ->where('grade', $request->grade)
            ->where('location_id', $fromLocationId)
            ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net")
            ->value('net') ?? 0;

        if ($qty > $available) {
            return response()->json(['success' => false, 'message' => "Insufficient stock at source location. Available: {$available} kg"], 422);
        }

        DB::transaction(function () use ($request, $fromLocationId, $toLocationId, $qty) {
            $userId = session('auth_user')['id'] ?? null;

            // 1. Create OUT transaction for source location
            Stock::create([
                'product_id' => $request->product_id,
                'user_id' => $userId,
                'stage' => $request->stage,
                'grade' => $request->grade,
                'location_id' => $fromLocationId,
                'quantity' => $qty,
                'transaction_type' => 'OUT',
                'notes' => 'Transfer: Moved to location "' . $request->to_location . '"',
            ]);

            // 2. Create IN transaction for destination location
            Stock::create([
                'product_id' => $request->product_id,
                'user_id' => $userId,
                'stage' => $request->stage,
                'grade' => $request->grade,
                'location_id' => $toLocationId,
                'quantity' => $qty,
                'transaction_type' => 'IN',
                'notes' => 'Transfer: Received from location "' . ($request->from_location ?: 'Unspecified') . '"',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Stock transferred successfully!']);
    }

    public function productStockHistory(Request $request, $productId, $stage)
    {
        $grade = $request->query('grade', 'NONE');
        $product = Product::findOrFail($productId);
        
        $allLogs = Stock::with(['user:id,name', 'location:id,name'])
            ->where('product_id', $productId)
            ->where('stage', strtoupper($stage))
            ->where('grade', $grade)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        $balance = 0;
        foreach($allLogs as $log) {
            $balance += ($log->transaction_type === 'IN') ? $log->quantity : -$log->quantity;
            $log->running_balance = $balance;
        }
        
        $perPage = 25;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        
        $allLogs = $allLogs->reverse()->values();
        $items = $allLogs->slice(($page - 1) * $perPage, $perPage);
        $stockLogs = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, 
            $allLogs->count(), 
            $perPage, 
            $page, 
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
        $stockLogs->appends($request->all());

        $currentTotal = $balance;

        return view('shared.product-history', compact('product', 'stage', 'grade', 'stockLogs', 'currentTotal'));
    }
}
