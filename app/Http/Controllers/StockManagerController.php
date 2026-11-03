<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockManagerController extends Controller
{
    private function authUser(): array
    {
        return session('auth_user') ?? ['id' => null, 'name' => 'Stock Manager', 'role' => 'STOCK_MANAGER'];
    }

    // ── HOME: Dashboard summary ────────────────────────────────────────────
    public function home()
    {
        $user = $this->authUser();
        $liveStock = $this->getAllLiveStock();
        
        $totalItems = count($liveStock);
        $totalNetQty = array_sum(array_column($liveStock, 'quantity'));

        $todayInward = Stock::whereDate('created_at', today())
            ->where('transaction_type', 'IN')
            ->sum('quantity');

        $todayOutward = Stock::whereDate('created_at', today())
            ->where('transaction_type', 'OUT')
            ->sum('quantity');

        $pendingPOs = PurchaseOrder::where('status', 'PENDING')->count();

        $getLowCount = function (string $stage): int {
            return DB::table('stocks')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->leftJoin('stock_limits', function ($join) {
                    $join->on('stocks.product_id', '=', 'stock_limits.product_id')
                         ->on('stocks.stage', '=', 'stock_limits.stage')
                         ->on('stocks.grade', '=', 'stock_limits.grade');
                })
                ->select('stocks.product_id', 'stocks.stage', 'stocks.grade')
                ->where('stocks.stage', $stage)
                ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.threshold', 'stock_limits.alert_limit')
                ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) <= IFNULL(stock_limits.alert_limit, products.threshold)")
                ->havingRaw("SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
                ->havingRaw("IFNULL(stock_limits.alert_limit, products.threshold) > 0")
                ->get()
                ->count();
        };

        $lowRawCount       = $getLowCount('RAW');
        $lowSemiCount      = $getLowCount('SEMI');
        $lowFinishedCount  = $getLowCount('FINISHED');
        $lowPackagingCount = $getLowCount('PACKAGING');

        $pageData = compact('totalItems', 'totalNetQty', 'todayInward', 'todayOutward', 'pendingPOs', 'liveStock', 'lowRawCount', 'lowSemiCount', 'lowFinishedCount', 'lowPackagingCount');

        return view('stock_manager.home', compact('pageData'));
    }

    // ── ACTION: Stock Inward & Stock Outward Tabs ──────────────────────────
    public function action()
    {
        $user = $this->authUser();
        $products = Product::with('grades')->where('is_active', true)->orderBy('name', 'asc')->get();
        $liveStock = $this->getAllLiveStock();
        $locations = Location::orderBy('name')->pluck('name')->toArray();
        if (empty($locations)) {
            $locations = ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
        }

        $purchaseOrders = PurchaseOrder::with('product')
            ->where('status', 'DONE')
            ->orderByDesc('created_at')
            ->get();

        $pageData = [
            'products' => $products,
            'liveStock' => $liveStock,
            'locations' => $locations,
            'purchaseOrders' => $purchaseOrders,
        ];

        return view('stock_manager.action', compact('pageData'));
    }

    // ── POST: Store Stock Inward ───────────────────────────────────────────
    // ── POST: Store Stock Inward ───────────────────────────────────────────
    public function storeInward(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|numeric|min:0.001',
            'stage'      => 'nullable|string|in:RAW,SEMI,FINISHED,PACKAGING',
            'grade'      => 'nullable|string|max:50',
            'location'   => 'nullable|string',
            'notes'      => 'nullable|string',
            'location_splits' => 'nullable|array',
            'location_splits.*.location' => 'required_with:location_splits|string',
            'location_splits.*.quantity' => 'required_with:location_splits|numeric|min:0.001',
        ]);

        $user = $this->authUser();
        $product = Product::findOrFail($request->product_id);
        $stage = $request->stage ?: ($product->type ?: 'RAW');

        $locationSplits = $request->location_splits;
        if (empty($locationSplits) && $request->quantity) {
            $locationSplits = [
                ['location' => $request->location ?: 'Main Warehouse', 'quantity' => (float)$request->quantity]
            ];
        }

        if (empty($locationSplits)) {
            return response()->json(['success' => false, 'message' => 'Please select at least one location and enter a valid quantity.'], 422);
        }

        DB::transaction(function() use ($request, $user, $product, $stage, $locationSplits) {
            $notes = trim($request->notes ?? '');
            
            $refType = $request->reference_type;
            if ($refType === 'PO' && $request->po_id) {
                $po = PurchaseOrder::find($request->po_id);
                if ($po) $notes .= ($notes ? ' | ' : '') . "PO Ref: #" . $po->id;
            } elseif ($refType === 'Other' && !empty($request->other_note)) {
                $notes .= ($notes ? ' | ' : '') . trim($request->other_note);
            }

            if ($notes === '') {
                $notes = 'INWARD BY STOCK MANAGER';
            }

            foreach ($locationSplits as $split) {
                $locName = trim($split['location'] ?? 'Main Warehouse');
                $qty = (float) ($split['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $locationId = Location::firstOrCreate(['name' => $locName])->id;

                Stock::create([
                    'product_id'       => $product->id,
                    'user_id'          => $user['id'],
                    'stage'            => $stage,
                    'grade'            => (!empty($request->grade) && strtoupper($request->grade) !== 'ALL') ? $request->grade : 'NONE',
                    'location_id'      => $locationId,
                    'quantity'         => $qty,
                    'transaction_type' => 'IN',
                    'notes'            => $notes,
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Stock inward recorded successfully!']);
        }

        return redirect()->back()->with('success', 'Stock inward recorded successfully!');
    }

    // ── POST: Store Stock Outward ──────────────────────────────────────────
    public function storeOutward(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|numeric|min:0.001',
            'stage'      => 'nullable|string',
            'grade'      => 'nullable|string|max:50',
            'location'   => 'nullable|string',
            'notes'      => 'nullable|string',
            'location_splits' => 'nullable|array',
            'location_splits.*.location' => 'required_with:location_splits|string',
            'location_splits.*.quantity' => 'required_with:location_splits|numeric|min:0.001',
        ]);

        $user = $this->authUser();
        $product = Product::findOrFail($request->product_id);
        $stage = ($request->stage && in_array($request->stage, ['RAW', 'SEMI', 'FINISHED', 'PACKAGING'])) ? $request->stage : ($product->type ?: 'RAW');
        $grade = (!empty($request->grade) && strtoupper($request->grade) !== 'ALL') ? $request->grade : null;

        $locationSplits = $request->location_splits;
        if (empty($locationSplits) && $request->quantity) {
            $locationSplits = [
                ['location' => $request->location ?: 'Main Warehouse', 'quantity' => (float)$request->quantity]
            ];
        }

        if (empty($locationSplits)) {
            return response()->json(['success' => false, 'message' => 'Please select at least one location and enter a valid quantity.'], 422);
        }

        $totalQtyToOutward = array_sum(array_column($locationSplits, 'quantity'));

        // Check if enough stock exists overall
        $stockQuery = DB::table('stocks')
            ->where('stage', $stage)
            ->where('product_id', $request->product_id);

        if ($grade) {
            $stockQuery->where(function($q) use ($grade) {
                if (strtoupper($grade) === 'NONE') {
                    $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '');
                } else {
                    $q->where('grade', $grade);
                }
            });
        }

        $netStock = (float) ($stockQuery
            ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net")
            ->value('net') ?? 0);

        if ($netStock < $totalQtyToOutward) {
            return response()->json(['success' => false, 'message' => "Insufficient {$stage} stock. Total requested: {$totalQtyToOutward} kg, Available: {$netStock} kg"], 400);
        }

        DB::transaction(function() use ($request, $user, $stage, $grade, $locationSplits) {
            $notes = trim($request->notes ?? '');
            if ($notes === '') {
                $notes = 'OUTWARD BY STOCK MANAGER';
            }

            foreach ($locationSplits as $split) {
                $locName = trim($split['location'] ?? 'Main Warehouse');
                $qty = (float) ($split['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $locationId = Location::firstOrCreate(['name' => $locName])->id;

                Stock::create([
                    'product_id'       => $request->product_id,
                    'user_id'          => $user['id'],
                    'stage'            => $stage,
                    'grade'            => $grade ?: 'NONE',
                    'location_id'      => $locationId,
                    'quantity'         => $qty,
                    'transaction_type' => 'OUT',
                    'notes'            => $notes,
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Stock outward recorded successfully!']);
        }

        return redirect()->back()->with('success', 'Stock outward recorded successfully!');
    }

    // ── LIVE STOCK: Admin-like Live Stock view ────────────────────────────
    public function stock()
    {
        $hasLimitRate = \Illuminate\Support\Facades\Schema::hasColumn('stock_limits', 'rate');
        $rateSelect = $hasLimitRate 
            ? "COALESCE(stock_limits.rate, products.rate, 0) as rate" 
            : "products.rate";
        $rateGroupBy = $hasLimitRate ? ['stock_limits.rate'] : [];

        $stageNormExpr = "CASE 
            WHEN UPPER(TRIM(stocks.stage)) IN ('FG', 'FINISHED') THEN 'FINISHED'
            WHEN UPPER(TRIM(stocks.stage)) IN ('PKG', 'PM', 'PACKAGING') THEN 'PACKAGING'
            WHEN UPPER(TRIM(stocks.stage)) = 'SEMI' THEN 'SEMI'
            WHEN UPPER(TRIM(stocks.stage)) = 'RAW' THEN 'RAW'
            ELSE UPPER(TRIM(stocks.stage))
        END";

        $gradeNormExpr = "CASE 
            WHEN stocks.grade IS NULL OR UPPER(TRIM(stocks.grade)) IN ('', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-') THEN 'NONE' 
            ELSE UPPER(TRIM(stocks.grade)) 
        END";

        $stockAgg = DB::table('stocks')
            ->selectRaw("
                stocks.product_id,
                {$stageNormExpr} as norm_stage,
                {$gradeNormExpr} as norm_grade,
                SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->groupBy('stocks.product_id', DB::raw('norm_stage'), DB::raw('norm_grade'));

        $stageLimitNorm = "CASE 
            WHEN UPPER(TRIM(stage)) IN ('FG', 'FINISHED') THEN 'FINISHED' 
            WHEN UPPER(TRIM(stage)) IN ('PKG', 'PM', 'PACKAGING') THEN 'PACKAGING' 
            WHEN UPPER(TRIM(stage)) = 'SEMI' THEN 'SEMI' 
            WHEN UPPER(TRIM(stage)) = 'RAW' THEN 'RAW' 
            ELSE UPPER(TRIM(stage)) 
        END";
        $gradeLimitNorm = "CASE 
            WHEN grade IS NULL OR UPPER(TRIM(grade)) IN ('', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-') THEN 'NONE' 
            ELSE UPPER(TRIM(grade)) 
        END";

        $rateSubSelect = $hasLimitRate ? "MAX(COALESCE(rate, 0)) as rate," : "";
        $limitsAgg = DB::table('stock_limits')
            ->selectRaw("
                product_id,
                {$stageLimitNorm} as norm_stage,
                {$gradeLimitNorm} as norm_grade,
                {$rateSubSelect}
                MAX(alert_limit) as alert_limit
            ")
            ->groupBy('product_id', DB::raw("{$stageLimitNorm}"), DB::raw("{$gradeLimitNorm}"));

        $rateSelect = $hasLimitRate 
            ? "COALESCE(limits_agg.rate, products.rate, 0) as rate" 
            : "products.rate";

        $allStock = DB::table(DB::raw("({$stockAgg->toSql()}) as aggregated_stocks"))
            ->mergeBindings($stockAgg)
            ->join('products', 'aggregated_stocks.product_id', '=', 'products.id')
            ->leftJoinSub($limitsAgg, 'limits_agg', function($join) {
                $join->on('aggregated_stocks.product_id', '=', 'limits_agg.product_id')
                     ->on('aggregated_stocks.norm_stage', '=', 'limits_agg.norm_stage')
                     ->on('aggregated_stocks.norm_grade', '=', 'limits_agg.norm_grade');
            })
            ->selectRaw("
                aggregated_stocks.product_id as productId,
                products.name,
                products.unit,
                products.threshold,
                {$rateSelect},
                aggregated_stocks.norm_stage as stage,
                aggregated_stocks.norm_grade as grade,
                products.sort_order,
                COALESCE(NULLIF(limits_agg.alert_limit, 0), NULLIF(products.threshold, 0), limits_agg.alert_limit, products.threshold, 0) as alert_limit,
                aggregated_stocks.quantity
            ")
            ->where('aggregated_stocks.quantity', '>=', 0)
            ->orderBy('aggregated_stocks.norm_stage')
            ->orderBy('products.name', 'asc')
            ->orderBy('aggregated_stocks.norm_grade', 'asc')
            ->get();

        $allStock = $allStock->unique(fn($item) => "{$item->productId}_{$item->stage}_{$item->grade}")->values();

        $allProducts = Product::with('grades')
            ->orderBy('name', 'asc')
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
                'created_at' => optional($log->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ])->values());

        $allLocations = Location::orderBy('name')->get(['id', 'name']);
        $primaryLocName = $allLocations->first(fn($l) => in_array(strtoupper(trim($l->name)), ['MAIN WAREHOUSE', 'DEFAULT', 'GENERAL', 'WAREHOUSE', 'MAIN']))?->name ?: $allLocations->first()?->name;

        // Fetch location mappings from DB with normalized stage and grade
        $locationStock = DB::table('stocks')
            ->join('locations', 'stocks.location_id', '=', 'locations.id')
            ->selectRaw("
                stocks.product_id,
                {$stageNormExpr} as norm_stage,
                {$gradeNormExpr} as norm_grade,
                locations.name as location_name,
                SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity
            ")
            ->groupBy('stocks.product_id', DB::raw('norm_stage'), DB::raw('norm_grade'), 'locations.name')
            ->havingRaw("quantity > 0")
            ->get();

        $unassignedStock = DB::table('stocks')
            ->whereNull('stocks.location_id')
            ->selectRaw("
                stocks.product_id,
                {$stageNormExpr} as norm_stage,
                {$gradeNormExpr} as norm_grade,
                SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity
            ")
            ->groupBy('stocks.product_id', DB::raw('norm_stage'), DB::raw('norm_grade'))
            ->havingRaw("quantity > 0")
            ->get();

        $locationMappings = [];
        foreach ($locationStock as $ls) {
            $normG = $ls->norm_grade;
            $normStage = $ls->norm_stage;
            $qty = (float) $ls->quantity;

            foreach (["{$ls->product_id}_{$normG}_{$normStage}", "{$ls->product_id}__{$normStage}"] as $k) {
                $locationMappings[$k][$ls->location_name] = ($locationMappings[$k][$ls->location_name] ?? 0) + $qty;
            }
        }

        if ($primaryLocName) {
            foreach ($unassignedStock as $us) {
                $normG = $us->norm_grade;
                $normStage = $us->norm_stage;
                $qty = (float) $us->quantity;

                foreach (["{$us->product_id}_{$normG}_{$normStage}", "{$us->product_id}__{$normStage}"] as $k) {
                    $locationMappings[$k][$primaryLocName] = ($locationMappings[$k][$primaryLocName] ?? 0) + $qty;
                }
            }
        }

        $pageData = [
            'allStock' => $allStock,
            'allProducts' => $allProducts,
            'stockLogsByKey' => $stockLogsByKey,
            'locationMappings' => $locationMappings,
        ];

        $isReadOnly = view()->shared('isReadOnly') ?? false;

        return view('stock_manager.stock', compact('pageData', 'isReadOnly'));
    }

    // ── PO: Purchase Orders Panel ──────────────────────────────────────────
    public function po()
    {
        $user = $this->authUser();
        $pos = PurchaseOrder::with(['user', 'product'])
            ->where('user_id', $user['id'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $products = Product::where('is_active', true)->orderBy('sort_order')->get();

        $pageData = [
            'purchaseOrders' => $pos,
            'products' => $products,
        ];

        return view('stock_manager.po', compact('pageData'));
    }

    // ── POST: Create Purchase Order Request ────────────────────────────────
    public function storePO(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:0.001',
            'note'       => 'nullable|string',
        ]);

        $user = $this->authUser();

        PurchaseOrder::create([
            'user_id'    => $user['id'],
            'product_id' => $request->product_id,
            'quantity'   => $request->quantity,
            'note'       => $request->note,
            'status'     => 'PENDING',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Purchase order request created successfully!']);
        }

        return redirect()->back()->with('success', 'Purchase order request created successfully!');
    }

    // ── POST: Update Purchase Order Request ────────────────────────────────
    public function updatePO(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'note'     => 'nullable|string',
        ]);

        $user = $this->authUser();
        $po = PurchaseOrder::where('id', $id)
            ->where('user_id', $user['id'])
            ->firstOrFail();

        $po->update([
            'quantity' => $request->quantity,
            'note'     => $request->note,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Purchase order updated successfully!']);
        }

        return redirect()->back()->with('success', 'Purchase order updated successfully!');
    }

    // ── POST: Mark Purchase Order as Received ──────────────────────────────
    public function receivePO(Request $request)
    {
        $request->validate([
            'po_id' => 'required|exists:purchase_orders,id',
            'date'  => 'nullable|date',
            'note'  => 'nullable|string',
        ]);

        $user = $this->authUser();
        DB::transaction(function() use ($request, $user) {
            $query = PurchaseOrder::where('id', $request->po_id);
            if (!in_array($user['role'] ?? '', ['ADMIN', 'SUB_ADMIN'])) {
                $query->where('user_id', $user['id']);
            }
            $po = $query->firstOrFail();

            $po->status = 'RECEIVED';
            if ($request->filled('date')) {
                $po->date = Carbon::parse($request->date);
            } else {
                $po->date = now();
            }
            if ($request->filled('note')) {
                $po->note = $request->note;
            }
            $po->save();
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'PO marked as received!']);
        }

        return redirect()->back()->with('success', 'PO marked as received!');
    }

    // ── DELETE: Delete Purchase Order Request ──────────────────────────────
    public function destroyPO($id)
    {
        $user = $this->authUser();
        $po = PurchaseOrder::where('id', $id)
            ->where('user_id', $user['id'])
            ->firstOrFail();

        $po->delete();

        return response()->json(['success' => true, 'message' => 'Purchase order deleted successfully!']);
    }

    // ── HISTORY: Transaction History Logs ──────────────────────────────────
    public function history()
    {
        $stocks = Stock::with(['product', 'user', 'location'])
            ->orderByDesc('created_at')
            ->paginate(30);

        $pageData = [
            'history' => $stocks,
        ];

        return view('stock_manager.history', compact('pageData'));
    }

    // ── POST: Update Stock Log Note ───────────────────────────────────────
    public function updateNote(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $stock = Stock::findOrFail($id);
        $stock->notes = trim($request->notes ?? '');
        $stock->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Note updated successfully!',
                'notes'   => $stock->notes,
            ]);
        }

        return redirect()->back()->with('success', 'Note updated successfully!');
    }

    // ── DELETE: Delete Single Stock History Entry ─────────────────────────
    public function destroyHistory($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock history entry deleted successfully!',
            ]);
        }

        return redirect()->back()->with('success', 'Stock history entry deleted successfully!');
    }

    // ── POST: Clear All Stock History Entries ─────────────────────────────
    public function clearHistory(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Stock::truncate();
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Throwable $e) {
            Stock::query()->delete();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All stock history has been cleared successfully!',
            ]);
        }

        return redirect()->back()->with('success', 'All stock history has been cleared successfully!');
    }

    // ── PROFILE: User Profile ──────────────────────────────────────────────
    public function profile()
    {
        $authUser = $this->authUser();
        return view('stock_manager.profile', compact('authUser'));
    }

    // ── HELPER: Fetch net live stock ───────────────────────────────────────
    private function getAllLiveStock(): array
    {
        $stageNormExpr = "CASE 
            WHEN UPPER(TRIM(stocks.stage)) IN ('FG', 'FINISHED') THEN 'FINISHED'
            WHEN UPPER(TRIM(stocks.stage)) IN ('PKG', 'PM', 'PACKAGING') THEN 'PACKAGING'
            WHEN UPPER(TRIM(stocks.stage)) = 'SEMI' THEN 'SEMI'
            WHEN UPPER(TRIM(stocks.stage)) = 'RAW' THEN 'RAW'
            ELSE UPPER(TRIM(stocks.stage))
        END";

        $gradeNormExpr = "CASE 
            WHEN stocks.grade IS NULL OR UPPER(TRIM(stocks.grade)) IN ('', 'NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', 'NULL', '-') THEN 'NONE' 
            ELSE UPPER(TRIM(stocks.grade)) 
        END";

        $stockAgg = DB::table('stocks')
            ->selectRaw("
                stocks.product_id,
                {$stageNormExpr} as norm_stage,
                {$gradeNormExpr} as norm_grade,
                SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->groupBy('stocks.product_id', DB::raw('norm_stage'), DB::raw('norm_grade'));

        return DB::table(DB::raw("({$stockAgg->toSql()}) as aggregated_stocks"))
            ->mergeBindings($stockAgg)
            ->join('products', 'aggregated_stocks.product_id', '=', 'products.id')
            ->selectRaw("
                aggregated_stocks.product_id as productId,
                products.name,
                products.unit,
                products.type,
                aggregated_stocks.norm_stage as stage,
                aggregated_stocks.norm_grade as grade,
                aggregated_stocks.quantity
            ")
            ->where('aggregated_stocks.quantity', '>', 0)
            ->orderBy('aggregated_stocks.norm_stage')
            ->orderBy('products.name')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }
}
