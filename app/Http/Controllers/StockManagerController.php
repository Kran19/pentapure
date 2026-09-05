<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
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

        $pageData = compact('totalItems', 'totalNetQty', 'todayInward', 'todayOutward', 'pendingPOs', 'liveStock');

        return view('stock_manager.home', compact('pageData'));
    }

    // ── ACTION: Stock Inward & Stock Outward Tabs ──────────────────────────
    public function action()
    {
        $user = $this->authUser();
        $products = Product::with('grades')->where('is_active', true)->orderBy('sort_order')->get();
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
            'stage'      => 'nullable|string|in:RAW,SEMI,FINISHED',
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
                    'grade'            => $request->grade ?? 'NONE',
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
            'stage'      => 'required|string|in:RAW,SEMI,FINISHED',
            'grade'      => 'nullable|string|max:50',
            'location'   => 'nullable|string',
            'notes'      => 'nullable|string',
            'location_splits' => 'nullable|array',
            'location_splits.*.location' => 'required_with:location_splits|string',
            'location_splits.*.quantity' => 'required_with:location_splits|numeric|min:0.001',
        ]);

        $user = $this->authUser();
        $grade = $request->grade ?? 'NONE';
        $stage = $request->stage;

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
        $netStock = DB::table('stocks')
            ->where('stage', $stage)
            ->where('product_id', $request->product_id)
            ->where('grade', $grade)
            ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as net")
            ->value('net') ?? 0;

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
                    'grade'            => $grade,
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

        // Location mappings
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

        return view('stock_manager.stock', compact('pageData'));
    }

    // ── PO: Purchase Orders Panel ──────────────────────────────────────────
    public function po()
    {
        $user = $this->authUser();
        $pos = PurchaseOrder::with(['user', 'product'])
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

    // ── PROFILE: User Profile ──────────────────────────────────────────────
    public function profile()
    {
        $authUser = $this->authUser();
        return view('stock_manager.profile', compact('authUser'));
    }

    // ── HELPER: Fetch net live stock ───────────────────────────────────────
    private function getAllLiveStock(): array
    {
        return DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.unit', 'products.type')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                products.type,
                stocks.stage,
                stocks.grade,
                SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->orderBy('stocks.stage')
            ->orderBy('products.name')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }
}
