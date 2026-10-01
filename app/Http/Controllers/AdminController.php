<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Category;
use App\Models\Company;
use App\Models\DispatchLog;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;


class AdminController extends Controller
{
    public function dashboard()
    {
        $getStockByUnits = function (string $stage) {
            return DB::table('stocks')
                ->leftJoin('products', 'stocks.product_id', '=', 'products.id')
                ->where('stocks.stage', $stage)
                ->selectRaw("
                    products.unit,
                    SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as net
                ")
                ->groupBy('products.unit')
                ->havingRaw('net > 0')
                ->orderByDesc('net')
                ->get()
                ->map(fn($r) => [
                    'unit'     => strtoupper(trim($r->unit ?: 'KG')),
                    'quantity' => (float) $r->net,
                ])
                ->groupBy('unit')
                ->map(fn($group, $unit) => [
                    'unit'     => $unit,
                    'quantity' => (float) $group->sum('quantity'),
                ])
                ->sortByDesc('quantity')
                ->values()
                ->toArray();
        };

        $rawStockUnits       = $getStockByUnits('RAW');
        $semiStockUnits      = $getStockByUnits('SEMI');
        $finishedStockUnits  = $getStockByUnits('FINISHED');
        $packagingStockUnits = $getStockByUnits('PACKAGING');

        $rawQty       = count($rawStockUnits) > 0 ? array_sum(array_column($rawStockUnits, 'quantity')) : 0;
        $semiQty      = count($semiStockUnits) > 0 ? array_sum(array_column($semiStockUnits, 'quantity')) : 0;
        $finishedQty  = count($finishedStockUnits) > 0 ? array_sum(array_column($finishedStockUnits, 'quantity')) : 0;
        $packagingQty = count($packagingStockUnits) > 0 ? array_sum(array_column($packagingStockUnits, 'quantity')) : 0;

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
            'rawQty', 'semiQty', 'finishedQty', 'packagingQty',
            'rawStockUnits', 'semiStockUnits', 'finishedStockUnits', 'packagingStockUnits',
            'lowRawCount', 'lowSemiCount', 'lowFinishedCount', 'lowPackagingCount',
            'totalOrders', 'totalRevenue', 'pendingPOs', 
            'totalWorkers', 'presentToday',
            'days', 'salesTrend', 'productionTrend'
        );
        return view('admin.dashboard', compact('pageData'));
    }

    public function users()
    {
        $users = User::with('parent')
            ->withCount([
                'stocks',
                'transactions',
                'transactionLogs',
                'dispatchLogs',
                'orders',
                'productionLogs',
                'purchaseOrders',
                'attendanceSubmissions',
                'subordinates'
            ])
            ->orderBy('role')
            ->paginate(15);
        $allCashiers = User::where('role', 'CASHIER')->get(['id', 'name']);
        $departments = \App\Models\Department::orderBy('name')->get();
        return view('admin.users', ['pageData' => ['users' => $users, 'cashiers' => $allCashiers, 'departments' => $departments]]);
    }

    public function storeUser(Request $request)
    {
        if (empty($request->username)) {
            $baseUser = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $request->name ?: ($request->role ?: 'user')));
            if (empty($baseUser)) $baseUser = 'user';
            $candidateUser = $baseUser;
            $counter = 1;
            while (User::where('username', $candidateUser)->where('id', '!=', $request->user_id ?? 0)->exists()) {
                $counter++;
                $candidateUser = $baseUser . $counter;
            }
            $request->merge(['username' => $candidateUser]);
        }

        $rules = [
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . ($request->user_id ?? 'NULL') . ',id',
            'role'     => 'required|in:ADMIN,SUB_ADMIN,STOCK_MANAGER,CASHIER,SALES,DISPATCH,ATTENDANCE', // RAW, SEMI, FINISHED disabled
            'branch'   => 'required_if:role,CASHIER|nullable|string|max:100',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email',
            'permissions' => 'nullable',
            'visible_cashiers' => 'nullable|array',
            'visible_cashiers.*' => 'exists:users,id',
        ];

        if (!$request->user_id) {
            $rules['password'] = 'required|string|min:4';
        } else {
            $rules['password'] = 'nullable|string|min:4';
        }

        $request->validate($rules);
        
        $permissions = is_string($request->permissions) ? json_decode($request->permissions, true) : ($request->permissions ?? []);
        if (!in_array($request->role, ['SUB_ADMIN', 'STOCK_MANAGER', 'ATTENDANCE'])) {
            $permissions = [];
        }

        if ($request->role === 'STOCK_MANAGER') {
            $defaultStockPerms = [
                'view_stock_manager_home', 'edit_stock_manager_home', 'stock_manager_home',
                'view_stock_manager_action', 'edit_stock_manager_action', 'stock_manager_action',
                'view_stock_manager_stock', 'edit_stock_manager_stock', 'stock_manager_stock',
                'view_stock_manager_po', 'edit_stock_manager_po', 'stock_manager_po',
                'view_stock_manager_history', 'edit_stock_manager_history', 'stock_manager_history',
                'view_stock_manager_products', 'edit_stock_manager_products', 'stock_manager_products',
                'view_stock_manager_grades', 'edit_stock_manager_grades', 'stock_manager_grades',
                'view_stock_manager_locations', 'edit_stock_manager_locations', 'stock_manager_locations'
            ];
            $permissions = array_values(array_unique(array_merge($defaultStockPerms, $permissions)));
        }
        
        $visibleCashiers = $request->role === 'CASHIER' ? ($request->visible_cashiers ?? null) : null;
        if (is_array($visibleCashiers)) {
            $visibleCashiers = !empty($visibleCashiers) ? array_map('intval', $visibleCashiers) : null;
        }

        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
            $user->name     = $request->name;
            $user->username = strtolower(trim($request->username));
            $user->email    = $request->email;
            $user->phone    = $request->phone;
            $user->role     = $request->role;
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
                'username'  => strtolower(trim($request->username)),
                'email'     => $request->email,
                'phone'     => $request->phone,
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
        if ($id == session('auth_user')['id']) {
            return response()->json(['success' => false, 'message' => 'Cannot delete yourself!'], 422);
        }
        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['success' => false, 'message' => 'User not found!'], 404);
        }
        if ($targetUser->role === 'ADMIN' || strtoupper($targetUser->role) === 'SUPER_ADMIN' || strtolower($targetUser->name) === 'super admin') {
            return response()->json(['success' => false, 'message' => 'Super Admin cannot be deleted!'], 422);
        }

        $summary = $targetUser->getAssociatedDataSummary();
        if (!empty($summary)) {
            $details = collect($summary)->map(fn($cnt, $type) => "{$cnt} {$type}")->implode(', ');
            return response()->json([
                'success' => false,
                'message' => "Cannot delete user '{$targetUser->name}': this user has associated data ({$details}) in the system! To delete this user, their associated records must be cleared or reassigned first."
            ], 422);
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
        $withCounts = [
            'stocks',
            'grades as custom_grades_count' => function($q) {
                $q->whereNotIn(\Illuminate\Support\Facades\DB::raw('UPPER(name)'), ['NONE', 'N/A', 'NA', 'N / A']);
            }
        ];

        $rawProducts = Product::with('grades')
            ->withCount($withCounts)
            ->where('type', 'RAW')
            ->orderBy('sort_order')
            ->get();

        $rawProducts->transform(function($p) {
            $p->gradeIds = $p->grades->pluck('id')->toArray();
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });
            
        $semiProducts = Product::with('grades')
            ->withCount($withCounts)
            ->where('type', 'SEMI')
            ->orderBy('sort_order')
            ->get();

        $semiProducts->transform(function($p) {
            $p->gradeIds = $p->grades->pluck('id')->toArray();
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });

        $finishedProducts = Product::with('grades')
            ->withCount($withCounts)
            ->where('type', 'FINISHED')
            ->orderBy('sort_order')
            ->get();
            
        $finishedProducts->transform(function($p) {
            $p->gradeIds = $p->grades->pluck('id')->toArray();
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });

        $packagingProducts = Product::with('grades')
            ->withCount($withCounts)
            ->where('type', 'PACKAGING')
            ->orderBy('sort_order')
            ->get();

        $packagingProducts->transform(function($p) {
            $p->gradeIds = $p->grades->pluck('id')->toArray();
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });
            
        $allActiveGrades = \App\Models\Grade::where('is_active', true)->orderByRaw("CASE WHEN UPPER(name) IN ('NONE', 'N/A') THEN 0 ELSE 1 END")->orderBy('id')->get();
        
        $pageData = [
            'rawProducts' => $rawProducts,
            'semiProducts' => $semiProducts,
            'finishedProducts' => $finishedProducts,
            'packagingProducts' => $packagingProducts,
            'allGrades' => $allActiveGrades,
        ];
        return view('admin.products', compact('pageData'));
    }

    public function productsPdf()
    {
        $rawProducts = Product::where('type', 'RAW')
            ->orderBy('sort_order')
            ->get();
            
        $semiProducts = Product::with('grades')
            ->where('type', 'SEMI')
            ->orderBy('sort_order')
            ->get();

        $semiProducts->transform(function($p) {
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });

        $finishedProducts = Product::with('grades')
            ->where('type', 'FINISHED')
            ->orderBy('sort_order')
            ->get();
            
        $finishedProducts->transform(function($p) {
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });

        $packagingProducts = Product::with('grades')
            ->where('type', 'PACKAGING')
            ->orderBy('sort_order')
            ->get();
            
        $packagingProducts->transform(function($p) {
            $p->gradeNames = $p->grades->pluck('name')->toArray();
            return $p;
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.products_pdf', [
            'rawProducts' => $rawProducts,
            'semiProducts' => $semiProducts,
            'finishedProducts' => $finishedProducts,
            'packagingProducts' => $packagingProducts,
        ])->setPaper('A4', 'portrait');

        return $pdf->download('PentaPure_Products_List_' . now()->format('Ymd_His') . '.pdf');
    }

    public function storeProduct(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|in:RAW,SEMI,FINISHED,PACKAGING',
                'rate' => 'nullable|numeric|min:0',
                'threshold' => 'nullable|numeric|min:0',
                'grades' => 'nullable',
                'allowed_roles' => 'nullable',
                'image' => 'nullable|image|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Parse JSON arrays safely
            $grades = [];
            if ($request->has('grades') && $request->grades !== null && $request->grades !== '' && $request->grades !== 'null') {
                if (is_array($request->grades)) {
                    $grades = $request->grades;
                } else {
                    $decoded = json_decode($request->grades, true);
                    $grades = is_array($decoded) ? $decoded : [];
                }
                $grades = array_values(array_filter($grades, fn($g) => !empty($g)));
            }

            $allowedRoles = [];
            if ($request->has('allowed_roles') && $request->allowed_roles !== null && $request->allowed_roles !== '' && $request->allowed_roles !== 'null') {
                if (is_array($request->allowed_roles)) {
                    $allowedRoles = $request->allowed_roles;
                } else {
                    $decoded = json_decode($request->allowed_roles, true);
                    $allowedRoles = is_array($decoded) ? $decoded : [];
                }
                $allowedRoles = array_values(array_filter($allowedRoles, fn($r) => !empty($r)));
            }

            $imageUrl = $request->image_url;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $imageUrl = '/storage/' . $path;
            }

            $data = [
                'name' => trim((string)$request->name),
                'type' => strtoupper(trim((string)$request->type)),
                'unit' => !empty($request->unit) ? trim((string)$request->unit) : 'KG',
                'rate' => (float)($request->filled('rate') ? $request->rate : 0.00),
                'threshold' => (float)($request->filled('threshold') ? $request->threshold : 0.00),
                'allowed_roles' => $allowedRoles
            ];
            
            if ($imageUrl !== null) {
                $data['image_url'] = $imageUrl;
            }

            if ($request->filled('product_id')) {
                $product = Product::findOrFail($request->product_id);
                $product->update($data);
                if (is_array($grades)) {
                    $product->grades()->sync($grades);
                }
                
                // Sync to stock_limits table if present
                try {
                    \Illuminate\Support\Facades\DB::table('stock_limits')
                        ->where('product_id', $product->id)
                        ->update(['alert_limit' => $data['threshold']]);
                } catch (\Throwable $limitEx) {
                    \Illuminate\Support\Facades\Log::warning('Stock limits sync skipped: ' . $limitEx->getMessage());
                }
                    
                $msg = 'Product updated successfully!';
            } else {
                $product = Product::create($data);
                if (is_array($grades) && !empty($grades)) {
                    $product->grades()->sync($grades);
                }
                $msg = 'Product created successfully!';
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'product' => $product
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('storeProduct failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['image'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyProduct($id)
    {
        $product = Product::withCount([
            'stocks',
            'grades' => function($q) {
                $q->whereNotIn(\Illuminate\Support\Facades\DB::raw('UPPER(name)'), ['NONE', 'N/A', 'NA', 'N / A']);
            },
            'orderItems',
            'purchaseOrders',
            'productionLogs',
            'productionInputs'
        ])->findOrFail($id);

        $reasons = [];
        if ($product->stocks_count > 0) {
            $reasons[] = "it has {$product->stocks_count} record" . ($product->stocks_count > 1 ? 's' : '') . " in Stock";
        }
        if ($product->grades_count > 0) {
            $reasons[] = "it has {$product->grades_count} assigned grade" . ($product->grades_count > 1 ? 's' : '') . " in Grades Master";
        }
        if ($product->order_items_count > 0) {
            $reasons[] = "it is used in Orders ({$product->order_items_count} item" . ($product->order_items_count > 1 ? 's' : '') . ")";
        }
        if ($product->purchase_orders_count > 0) {
            $reasons[] = "it is used in Purchase Orders ({$product->purchase_orders_count} PO" . ($product->purchase_orders_count > 1 ? 's' : '') . ")";
        }
        if ($product->production_logs_count > 0 || $product->production_inputs_count > 0) {
            $prodCount = $product->production_logs_count + $product->production_inputs_count;
            $reasons[] = "it is linked to Production logs ({$prodCount} entry/entries)";
        }

        if (!empty($reasons)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product: ' . implode(' and ', $reasons) . '!'
            ], 422);
        }

        $product->grades()->detach();
        $product->delete();
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
                products.sort_order,
                COALESCE(NULLIF(stock_limits.alert_limit, 0), NULLIF(products.threshold, 0), stock_limits.alert_limit, products.threshold, 0) as alert_limit,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) >= 0")
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
                'date' => $log->date ? \Carbon\Carbon::parse($log->date)->format('d M Y, h:i A') : optional($log->created_at)->format('d M Y, h:i A'),
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
        $stages = $request->input('stages', ['RAW', 'SEMI', 'FINISHED', 'PACKAGING']);
        if (!is_array($stages)) {
            $stages = explode(',', $stages);
        }
        $stages = array_map('strtoupper', $stages);
        
        $date = $request->input('date');

        $stockQuery = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereIn('stocks.stage', $stages);

        if ($date) {
            $stockQuery->whereRaw('COALESCE(stocks.date, stocks.created_at) <= ?', [$date . ' 23:59:59']);
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
            $locQuery->whereRaw('COALESCE(stocks.date, stocks.created_at) <= ?', [$date . ' 23:59:59']);
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
            
            $locationText = !empty($locStrings) ? '&bull; ' . implode('<br>&bull; ', $locStrings) : 'Not Specified';

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

        $authUser = session('auth_user');
        $userRole = strtoupper($authUser['role'] ?? '');
        $isStockManager = ($userRole === 'STOCK_MANAGER') || str_contains($request->path(), 'stock-manager') || str_contains($request->path(), 'stock_manager');

        $pdfData = [
            'items' => $items,
            'totalValuation' => $totalValuation,
            'generatedOn' => now()->format('d M Y, h:i A'),
            'stages' => $stages,
            'date' => $date,
            'isStockManager' => $isStockManager,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.live-stock', $pdfData)
            ->setPaper('A4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isFontSubsettingEnabled', true);
        
        $asOnDate = $date ? \Carbon\Carbon::parse($date)->format('d-m-Y') : now()->format('d-m-Y');
        $filename = 'PentaPure_Live_Stock_As_On_Date_' . $asOnDate . '.pdf';

        return $pdf->download($filename);
    }

    public function downloadStockCsv(Request $request)
    {
        $stages = $request->input('stages', ['RAW', 'SEMI', 'FINISHED', 'PACKAGING']);
        if (!is_array($stages)) {
            $stages = explode(',', $stages);
        }
        $stages = array_filter(array_map('trim', array_map('strtoupper', $stages)));
        if (empty($stages)) {
            $stages = ['RAW', 'SEMI', 'FINISHED', 'PACKAGING'];
        }

        $date = $request->input('date');

        $stockQuery = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereIn('stocks.stage', $stages);

        if ($date) {
            $stockQuery->whereRaw('COALESCE(stocks.date, stocks.created_at) <= ?', [$date . ' 23:59:59']);
        }

        $stockData = $stockQuery->groupBy(
                'stocks.product_id',
                'stocks.stage',
                'stocks.grade',
                'products.name',
                'products.unit',
                'products.rate',
                'products.sort_order'
            )
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.unit,
                products.rate,
                stocks.stage,
                stocks.grade,
                products.sort_order,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) > 0")
            ->orderBy('stocks.stage')
            ->orderBy('products.sort_order')
            ->orderBy('products.name')
            ->get();

        // Bulk query for all location breakdowns in a single SQL call
        $locQuery = DB::table('stocks')
            ->leftJoin('locations', 'stocks.location_id', '=', 'locations.id')
            ->whereIn('stocks.stage', $stages);

        if ($date) {
            $locQuery->whereRaw('COALESCE(stocks.date, stocks.created_at) <= ?', [$date . ' 23:59:59']);
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

        $authUser = session('auth_user');
        $userRole = strtoupper($authUser['role'] ?? '');
        $isStockManager = ($userRole === 'STOCK_MANAGER') || str_contains($request->path(), 'stock-manager') || str_contains($request->path(), 'stock_manager');

        $asOnDate = $date ? \Carbon\Carbon::parse($date)->format('d-m-Y') : now()->format('d-m-Y');
        $filename = 'PentaPure_Live_Stock_As_On_Date_' . $asOnDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($stockData, $locsByKey, $stages, $date, $asOnDate, $isStockManager) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens it with proper UTF-8 encoding
            fputs($handle, "\xEF\xBB\xBF");

            // Header Section: Matches PDF Report Title and Metadata
            fputcsv($handle, ['PENTAPURE LIVE STOCK AS ON DATE ' . $asOnDate]);
            fputcsv($handle, [
                $date 
                    ? 'Historical Stock Valuation Report as on ' . \Carbon\Carbon::parse($date)->format('d M Y')
                    : 'Real-Time Live Stock Inventory Status'
            ]);
            fputcsv($handle, ['PentaPure FOOD & SPICES PVT. LTD.', '', '', 'Email: info@pentapure.com', 'Phone: +91 98765 43210', 'Web: www.pentapure.com']);
            
            $stageLabels = array_map(function($st) {
                $st = strtoupper(trim($st));
                return $st === 'FINISHED' ? 'FG' : ($st === 'PACKAGING' ? 'PKG' : $st);
            }, $stages);
            $stageLabelsStr = implode(', ', $stageLabels);
            $reportType = $isStockManager ? 'Live Stock Report' : ($date ? 'Stock Valuation (Historical)' : 'Stock Valuation (Live)');
            $valuationRef = $isStockManager ? 'N/A' : 'Internal Product Reference Rates';

            fputcsv($handle, ['Generated On:', now()->format('d M Y, h:i A'), '', 'Included Stages:', $stageLabelsStr]);
            fputcsv($handle, ['Report Type:', $reportType, '', 'Valuation Ref:', $valuationRef]);

            // Summary Stats (matching PDF summary cards)
            $totalItemsCount = count($stockData);
            $totalQtySum = 0.0;
            $totalValuationSum = 0.0;
            foreach ($stockData as $s) {
                $qty = (float) $s->quantity;
                $rate = (float) ($s->rate ?? 0.0);
                $totalQtySum += $qty;
                $totalValuationSum += round($qty * $rate, 2);
            }

            if (!$isStockManager) {
                fputcsv($handle, [
                    'Total Items:', $totalItemsCount,
                    'Total Stock Qty:', round($totalQtySum, 3),
                    'Total Valuation (Ref):', 'Rs. ' . number_format($totalValuationSum, 2),
                    'Report Mode:', empty($date) ? 'LIVE' : 'HISTORICAL'
                ]);
            } else {
                fputcsv($handle, [
                    'Total Items:', $totalItemsCount,
                    'Total Stock Qty:', round($totalQtySum, 3),
                    'Active Stages:', count($stages) . ' Stages',
                    'Report Mode:', empty($date) ? 'LIVE' : 'HISTORICAL'
                ]);
            }

            // Blank row before data table
            fputcsv($handle, []);

            // Data Table Headers
            if (!$isStockManager) {
                fputcsv($handle, [
                    '#',
                    'Product Name',
                    'Grade',
                    'Stage',
                    'Location Breakdown',
                    'Available Qty',
                    'Unit',
                    'Rate (Ref Rs.)',
                    'Valuation (Rs.)'
                ]);
            } else {
                fputcsv($handle, [
                    '#',
                    'Product Name',
                    'Grade',
                    'Stage',
                    'Location Breakdown',
                    'Available Qty',
                    'Unit'
                ]);
            }

            // Data Rows
            $srNo = 1;
            foreach ($stockData as $s) {
                $key = "{$s->productId}_{$s->stage}_{$s->grade}";
                $itemLocs = $locsByKey[$key] ?? [];

                $locStrings = [];
                $assignedSum = 0.0;
                foreach ($itemLocs as $l) {
                    if ($l->location_id) {
                        $locStrings[] = "{$l->name} (" . round((float)$l->quantity, 3) . " {$s->unit})";
                        $assignedSum += (float) $l->quantity;
                    }
                }
                $unassigned = (float)$s->quantity - $assignedSum;
                if ($unassigned > 0.01) {
                    $locStrings[] = "Unspecified (" . round($unassigned, 3) . " {$s->unit})";
                }
                $locText = !empty($locStrings) ? implode(' | ', $locStrings) : 'Not Specified';

                $qty = (float) $s->quantity;
                $rate = (float) ($s->rate ?? 0.00);
                $amount = round($qty * $rate, 2);

                $stageRaw = strtoupper($s->stage ?? '');
                $stageLabel = $stageRaw === 'FINISHED' ? 'FG' : ($stageRaw === 'PACKAGING' ? 'PKG' : $stageRaw);
                $hasGrade = !empty($s->grade) && !in_array(strtoupper($s->grade), ['NONE', 'N/A', 'DEFAULT', '-']);
                $gradeText = $hasGrade ? strtoupper($s->grade) : '-';

                if (!$isStockManager) {
                    fputcsv($handle, [
                        $srNo++,
                        $s->name,
                        $gradeText,
                        $stageLabel,
                        $locText,
                        round($qty, 3),
                        $s->unit,
                        round($rate, 2),
                        round($amount, 2)
                    ]);
                } else {
                    fputcsv($handle, [
                        $srNo++,
                        $s->name,
                        $gradeText,
                        $stageLabel,
                        $locText,
                        round($qty, 3),
                        $s->unit
                    ]);
                }
            }

            // Blank line followed by Total Row
            fputcsv($handle, []);
            if (!$isStockManager) {
                fputcsv($handle, [
                    'TOTAL',
                    'Total Items: ' . $totalItemsCount,
                    '',
                    '',
                    'TOTAL STOCK VALUATION (REF):',
                    round($totalQtySum, 3),
                    '',
                    '',
                    round($totalValuationSum, 2)
                ]);
            } else {
                fputcsv($handle, [
                    'TOTAL',
                    'Total Items: ' . $totalItemsCount,
                    '',
                    '',
                    'TOTAL QUANTITY:',
                    round($totalQtySum, 3),
                    ''
                ]);
            }

            // Blank line followed by Notes
            fputcsv($handle, []);
            if (!$isStockManager) {
                fputcsv($handle, ['* Note: Rates and valuation amounts listed above are based on internal stock reference costs and are not linked to sales panels.']);
            }
            if ($date) {
                fputcsv($handle, ['* Stock quantities and estimated valuations reflect recorded transactions up to ' . \Carbon\Carbon::parse($date)->format('d M Y') . '.']);
            } else {
                fputcsv($handle, ['* Stock quantities reflect real-time live inventory recorded in the system.']);
            }
            fputcsv($handle, ['* PentaPure Live Stock Inventory System - Automated Export']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
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
            ->groupBy('stocks.product_id', 'stocks.stage', 'stocks.grade', 'products.name', 'products.type', 'products.unit', 'products.rate', 'products.threshold', 'products.sort_order', 'stock_limits.alert_limit')
            ->selectRaw("
                stocks.product_id as productId,
                products.name,
                products.type,
                products.unit,
                products.rate,
                products.threshold,
                products.sort_order,
                stocks.stage,
                stocks.grade,
                COALESCE(NULLIF(stock_limits.alert_limit, 0), NULLIF(products.threshold, 0), stock_limits.alert_limit, products.threshold, 0) as alert_limit,
                SUM(CASE WHEN stocks.transaction_type='IN' THEN stocks.quantity ELSE -stocks.quantity END) as quantity
            ")
            ->havingRaw("SUM(CASE WHEN stocks.transaction_type = 'IN' THEN stocks.quantity ELSE -stocks.quantity END) >= 0")
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
        $statusPriority = "CASE 
            WHEN status = 'PENDING' THEN 1
            WHEN status = 'READ' THEN 2
            WHEN status = 'ORDERED' THEN 3
            WHEN status IN ('RECEIVED', 'DONE', 'COMPLETED') THEN 4
            WHEN status = 'REJECTED' THEN 5
            ELSE 6
        END";

        $pos = PurchaseOrder::with(['user', 'product'])
            ->orderByRaw($statusPriority)
            ->orderByDesc('created_at')
            ->paginate(100);
            
        return view('admin.po', ['pageData' => ['purchaseOrders' => $pos]]);
    }

    public function approvePO(Request $request)
    {
        DB::transaction(function() use ($request) {
            $po = PurchaseOrder::findOrFail($request->po_id);
            $po->status = 'READ';
            $po->save();
        });

        return response()->json(['success' => true, 'message' => 'PO marked as read!']);
    }

    public function orderPO(Request $request)
    {
        DB::transaction(function() use ($request) {
            $po = PurchaseOrder::findOrFail($request->po_id);
            $po->status = 'ORDERED';
            $po->save();
        });

        return response()->json(['success' => true, 'message' => 'PO marked as ordered!']);
    }

    public function rejectPO(Request $request)
    {
        DB::transaction(function() use ($request) {
            $po = PurchaseOrder::findOrFail($request->po_id);
            $po->status = 'REJECTED';
            $po->save();
        });

        return response()->json(['success' => true, 'message' => 'PO request rejected!']);
    }

    public function receivePO(Request $request)
    {
        DB::transaction(function() use ($request) {
            $po = PurchaseOrder::findOrFail($request->po_id);
            $po->status = 'RECEIVED';
            if ($request->filled('date')) {
                $po->date = Carbon::parse($request->date);
            } else {
                $po->date = now();
            }
            if ($request->has('note')) {
                $po->note = $request->note;
            }
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

    public static function getLogsClearedAt(): ?string
    {
        $file = storage_path('app/admin_logs_cleared_at.txt');
        if (file_exists($file)) {
            $val = trim((string)@file_get_contents($file));
            if (!empty($val)) {
                return $val;
            }
        }
        return \Illuminate\Support\Facades\Cache::get('admin_logs_cleared_at');
    }

    public static function setLogsClearedAt(?string $val = null): string
    {
        $ts = $val ?: now()->toDateTimeString();
        $file = storage_path('app/admin_logs_cleared_at.txt');
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0755, true);
        }
        @file_put_contents($file, $ts);
        \Illuminate\Support\Facades\Cache::forever('admin_logs_cleared_at', $ts);
        return $ts;
    }

    public function clearLogs(Request $request)
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $tables = [
            'production_log_inputs',
            'production_logs',
            'transaction_logs',
            'notifications',
            'dispatch_logs',
            'dispatch_log_items',
            'dispatch_item_locations',
        ];

        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                \Illuminate\Support\Facades\DB::table($table)->truncate();
            }
        }

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        self::setLogsClearedAt();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All system activity logs have been cleared successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'All system activity logs have been cleared successfully!');
    }

    public function logs()
    {
        $clearedAt = self::getLogsClearedAt();

        // 1. Production Logs (Raw/Semi/Finished)
        $prodQuery = ProductionLog::with(['user', 'outputProduct'])->orderByDesc('created_at');
        if ($clearedAt) $prodQuery->where('created_at', '>', $clearedAt);
        $prodLogs = $prodQuery->get()->map(fn($l) => [
            'category'    => 'Production',
            'date'        => $l->created_at->toISOString(),
            'description' => "Produced {$l->output_qty}kg of " . ($l->outputProduct ? $l->outputProduct->formatName($l->output_grade) : 'Unknown'),
            'by'          => $l->user?->name,
            'role'        => $l->user?->role,
        ]);

        // 2. Dispatch Logs
        $dispQuery = DispatchLog::with(['user', 'order.company'])->orderByDesc('created_at');
        if ($clearedAt) $dispQuery->where('created_at', '>', $clearedAt);
        $dispLogs = $dispQuery->get()->map(fn($d) => [
            'category'    => 'Dispatch',
            'date'        => $d->created_at->toISOString(),
            'description' => "Dispatched Order #{$d->order_id} to {$d->order?->company?->name}",
            'by'          => $d->user?->name,
            'role'        => 'DISPATCH',
        ]);

        // 3. Sales Orders
        $salesQuery = Order::with(['creator', 'company'])->orderByDesc('created_at');
        if ($clearedAt) $salesQuery->where('created_at', '>', $clearedAt);
        $salesLogs = $salesQuery->get()->map(fn($o) => [
            'category'    => 'Sales',
            'date'        => $o->created_at->toISOString(),
            'description' => "Created Order #{$o->id} for {$o->company?->name} (Total: ₹{$o->total})",
            'by'          => $o->creator?->name,
            'role'        => 'SALES',
        ]);

        // 4. Purchase Orders
        $poQuery = PurchaseOrder::with(['user', 'product'])->orderByDesc('created_at');
        if ($clearedAt) $poQuery->where('created_at', '>', $clearedAt);
        $poLogs = $poQuery->get()->map(fn($p) => [
            'category'    => 'Purchase',
            'date'        => $p->created_at->toISOString(),
            'description' => "Requested {$p->quantity}kg of {$p->product?->name} (Status: {$p->status})",
            'by'          => $p->user?->name,
            'role'        => $p->user?->role,
        ]);

        // 5. Stock Adjustments & Inwards
        $stockQuery = Stock::with(['user', 'product'])
            ->where(function($q) {
                $q->whereIn('notes', ['Manual admin adjustment', 'PO approved & auto-inwarded'])
                  ->orWhere('transaction_type', 'IN');
            })
            ->orderByDesc('created_at');
        if ($clearedAt) $stockQuery->where('created_at', '>', $clearedAt);
        $stockLogs = $stockQuery->get()->map(fn($s) => [
            'category'    => 'Inventory',
            'date'        => $s->created_at->toISOString(),
            'description' => "{$s->transaction_type}ward: {$s->quantity}kg of {$s->product?->name} ({$s->stage}). Notes: {$s->notes}",
            'by'          => $s->user?->name,
            'role'        => $s->user?->role,
        ]);

        // 6. Cashier Transactions (New)
        $cashQuery = \App\Models\Transaction::with('user')->orderByDesc('created_at');
        if ($clearedAt) $cashQuery->where('created_at', '>', $clearedAt);
        $cashLogs = $cashQuery->get()->map(fn($t) => [
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
        $grades = \App\Models\Grade::withCount('products')
            ->orderByRaw("CASE WHEN UPPER(name) IN ('NONE', 'N/A') THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->paginate(50);
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
            if (in_array(strtoupper(trim($grade->name)), ['NONE', 'N/A', 'NA', 'N / A'], true)) {
                return response()->json(['success' => false, 'message' => 'Fixed system grade (N/A) cannot be edited!'], 403);
            }
            $grade->update(['name' => $request->name]);
            return response()->json(['success' => true, 'message' => 'Grade updated!']);
        }

        $request->validate(['name' => 'required|string|unique:grades,name']);
        \App\Models\Grade::create(['name' => $request->name]);
        return response()->json(['success' => true, 'message' => 'Grade created!']);
    }

    public function destroyGrade($id)
    {
        $grade = \App\Models\Grade::withCount('products')->findOrFail($id);
        if (in_array(strtoupper(trim($grade->name)), ['NONE', 'N/A', 'NA', 'N / A'], true)) {
            return response()->json(['success' => false, 'message' => 'Fixed system grade (N/A) cannot be deleted!'], 403);
        }
        if ($grade->products_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete grade: it is assigned to {$grade->products_count} product" . ($grade->products_count > 1 ? 's' : '') . "!"
            ], 422);
        }
        $grade->delete();
        return response()->json(['success' => true, 'message' => 'Grade deleted!']);
    }

    public function categories()
    {
        $categories = Category::orderByDesc('is_active')->orderBy('name')->paginate(15);

        foreach ($categories as $cat) {
            $cat->transactions_count = $cat->getUsageCount();
        }

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
        $category = Category::findOrFail($id);

        $usageCount = $category->getUsageCount();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category '{$category->name}': it is currently in use across {$usageCount} " . (\Illuminate\Support\Str::plural('transaction record', $usageCount)) . "! To delete this category, associated records must be cleared or reassigned first."
            ], 422);
        }

        $category->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted!']);
    }

    public function adjustStock(Request $request)
    {
        try {
            if ($request->input('stage') === 'FG') {
                $request->merge(['stage' => 'FINISHED']);
            }
            if ($request->input('stage') === 'ALL' && $request->filled('product_id')) {
                $prod = Product::find($request->product_id);
                if ($prod) {
                    $request->merge(['stage' => $prod->type]);
                }
            }

            $request->validate([
                'product_id'      => 'required|exists:products,id',
                'stage'           => 'required|in:RAW,SEMI,FINISHED,FG,PACKAGING',
                'grade'           => 'required',
                'date'            => 'nullable|date',
                'quantity'        => 'nullable|numeric|min:0',
                'adjust_type'     => 'nullable|in:set,add,subtract',
                'reason'          => 'nullable|string|max:255',
                'location'        => 'nullable|string',
                'min_qty'         => 'nullable|numeric|min:0',
                'location_splits' => 'nullable|array',
                'location_splits.*.location' => 'required_with:location_splits|string',
                'location_splits.*.quantity' => 'required_with:location_splits|numeric|min:0',
            ]);

            if ($request->has('min_qty') && $request->min_qty !== null) {
                \App\Models\StockLimit::updateOrCreate(
                    ['product_id' => $request->product_id, 'stage' => $request->stage, 'grade' => $request->grade],
                    ['alert_limit' => $request->min_qty]
                );
            }

            $type      = $request->input('adjust_type', 'add');
            $reason    = trim($request->input('reason', ''));
            $userId    = session('auth_user')['id'] ?? auth()->id() ?? User::first()?->id ?? 1;
            $stockDate = $request->filled('date') ? Carbon::parse($request->date)->setTime(now()->hour, now()->minute, now()->second) : now();

            // Handle multiple location splits if provided
            $splits = $request->input('location_splits');
            if (!empty($splits) && is_array($splits)) {
                $summaries = [];
                DB::transaction(function() use ($request, $type, $reason, $userId, $splits, &$summaries, $stockDate) {
                    foreach ($splits as $split) {
                        $locName = trim($split['location']);
                        $splitQty = (float) $split['quantity'];
                        if ($splitQty <= 0 && $type !== 'set') continue;

                        $locId = Location::firstOrCreate(['name' => $locName])->id;
                        $note = "Manual adjustment at location '{$locName}'" . ($reason ? " — {$reason}" : '');

                        $locAvail = (float) (DB::table('stocks')
                            ->where('product_id', $request->product_id)
                            ->where('stage', $request->stage)
                            ->where('grade', $request->grade)
                            ->where('location_id', $locId)
                            ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                            ->value('net') ?? 0);

                        $createStockTxn = function($txnQty, $txnType, $summary) use ($request, $userId, $locId, $stockDate, $note) {
                            $stock = new Stock([
                                'product_id'       => $request->product_id,
                                'user_id'          => $userId,
                                'stage'            => $request->stage,
                                'grade'            => $request->grade,
                                'location_id'      => $locId,
                                'quantity'         => $txnQty,
                                'transaction_type' => $txnType,
                                'date'             => $stockDate,
                                'notes'            => "{$note} [{$summary}]",
                            ]);
                            $stock->created_at = $stockDate;
                            $stock->updated_at = $stockDate;
                            $stock->save();
                        };

                        if ($type === 'set') {
                            $diff = $splitQty - $locAvail;
                            if ($diff != 0) {
                                $txnQty  = abs($diff);
                                $txnType = $diff > 0 ? 'IN' : 'OUT';
                                $summary = "Set '{$locName}' to {$splitQty} kg (was {$locAvail} kg)";
                                $createStockTxn($txnQty, $txnType, $summary);
                                $summaries[] = $summary;
                            }
                        } elseif ($type === 'add') {
                            if ($splitQty > 0) {
                                $summary = "Added {$splitQty} kg to '{$locName}'";
                                $createStockTxn($splitQty, 'IN', $summary);
                                $summaries[] = $summary;
                            }
                        } else { // subtract
                            if ($splitQty > 0) {
                                if ($splitQty > $locAvail) {
                                    throw new \Exception("Cannot subtract {$splitQty} kg from location '{$locName}' — only {$locAvail} kg available.");
                                }
                                $summary = "Subtracted {$splitQty} kg from '{$locName}'";
                                $createStockTxn($splitQty, 'OUT', $summary);
                                $summaries[] = $summary;
                            }
                        }
                    }
                });

                if (empty($summaries)) {
                    return response()->json(['success' => true, 'message' => 'No stock changes were needed.']);
                }

                return response()->json(['success' => true, 'message' => 'Stock updated! ' . implode(' | ', $summaries)]);
            }

            // Single location fallback
            $qty          = (float) $request->quantity;
            $locationName = $request->input('location') ? trim($request->location) : 'Main Warehouse';
            $locationId   = Location::firstOrCreate(['name' => $locationName])->id;
            $note = "Manual adjustment at location '{$locationName}'" . ($reason ? " — {$reason}" : '');

            $locAvailable = (float) (DB::table('stocks')
                ->where('product_id', $request->product_id)
                ->where('stage', $request->stage)
                ->where('grade', $request->grade)
                ->where('location_id', $locationId)
                ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                ->value('net') ?? 0);

            if ($type === 'set') {
                $diff = $qty - $locAvailable;
                if ($diff == 0) {
                    return response()->json(['success' => true, 'message' => "Stock at location '{$locationName}' is already {$qty} kg — no change made."]);
                }
                $txnQty  = abs($diff);
                $txnType = $diff > 0 ? 'IN' : 'OUT';
                $summary = "Set location '{$locationName}' to {$qty} kg (was {$locAvailable} kg)";
            } elseif ($type === 'add') {
                if ($qty == 0) {
                    return response()->json(['success' => true, 'message' => 'Nothing to add — quantity is 0.']);
                }
                $txnQty  = $qty;
                $txnType = 'IN';
                $summary = "Added {$qty} kg to '{$locationName}' (location stock was {$locAvailable} kg)";
            } else { // subtract
                if ($qty == 0) {
                    return response()->json(['success' => true, 'message' => 'Nothing to subtract — quantity is 0.']);
                }
                if ($qty > $locAvailable) {
                    return response()->json(['success' => false, 'message' => "Cannot subtract {$qty} kg from location '{$locationName}' — only {$locAvailable} kg available in this location."]);
                }
                $txnQty  = $qty;
                $txnType = 'OUT';
                $summary = "Subtracted {$qty} kg from '{$locationName}' (location stock was {$locAvailable} kg)";
            }

            DB::transaction(function () use ($request, $locationId, $txnQty, $txnType, $note, $summary, $userId, $stockDate) {
                $stock = new Stock([
                    'product_id'       => $request->product_id,
                    'user_id'          => $userId,
                    'stage'            => $request->stage,
                    'grade'            => $request->grade,
                    'location_id'      => $locationId,
                    'quantity'         => $txnQty,
                    'transaction_type' => $txnType,
                    'date'             => $stockDate,
                    'notes'            => "{$note} [{$summary}]",
                ]);
                $stock->created_at = $stockDate;
                $stock->updated_at = $stockDate;
                $stock->save();
            });

            return response()->json(['success' => true, 'message' => "Stock updated! {$summary}."]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->validator->errors()->first()
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Stock adjust error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function deleteStock(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'stage'      => 'required|in:RAW,SEMI,FINISHED',
                'grade'      => 'required',
            ]);

            $product = Product::findOrFail($request->product_id);

            // Check current net quantity across all locations
            $currentQty = (float) (DB::table('stocks')
                ->where('product_id', $request->product_id)
                ->where('stage', $request->stage)
                ->where('grade', $request->grade)
                ->selectRaw("SUM(CASE WHEN transaction_type='IN' THEN quantity ELSE -quantity END) as net")
                ->value('net') ?? 0);

            if ($currentQty > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete stock entry: current quantity is " . number_format($currentQty, 2) . " {$product->unit}. Stock quantity must be 0 to delete."
                ], 422);
            }

            DB::transaction(function() use ($request) {
                // Delete all stock logs for this specific product, stage, and grade
                DB::table('stocks')
                    ->where('product_id', $request->product_id)
                    ->where('stage', $request->stage)
                    ->where('grade', $request->grade)
                    ->delete();

                // Also clean up any custom stock_limits entry for this product, stage, grade
                DB::table('stock_limits')
                    ->where('product_id', $request->product_id)
                    ->where('stage', $request->stage)
                    ->where('grade', $request->grade)
                    ->delete();
            });

            return response()->json(['success' => true, 'message' => 'Stock entry deleted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->validator->errors()->first()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function bulkAddStock(Request $request)
    {
        try {
            if ($request->has('items') && is_array($request->items)) {
                $items = $request->items;
                foreach ($items as &$item) {
                    if (isset($item['stage'])) {
                        if ($item['stage'] === 'FG') {
                            $item['stage'] = 'FINISHED';
                        } elseif ($item['stage'] === 'ALL' && !empty($item['product_id'])) {
                            $prod = Product::find($item['product_id']);
                            if ($prod) {
                                $item['stage'] = $prod->type;
                            }
                        }
                    }
                }
                unset($item);
                $request->merge(['items' => $items]);
            }

            $request->validate([
                'date'               => 'nullable|date',
                'items'              => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.stage'      => 'required|in:RAW,SEMI,FINISHED,FG,PACKAGING',
                'items.*.grade'      => 'required',
                'items.*.date'       => 'nullable|date',
                'items.*.alert_limit'=> 'nullable|numeric|min:0',
                'items.*.rate'       => 'nullable|numeric|min:0',
                'items.*.locations'  => 'required|array',
                'items.*.locations.*.name' => 'required|string',
                'items.*.locations.*.qty'  => 'required|numeric|min:0.01',
                'items.*.note'       => 'nullable|string|max:255',
            ]);

            DB::transaction(function () use ($request) {
                foreach ($request->items as $item) {
                    $productId = $item['product_id'];
                    $stage = $item['stage'];
                    $grade = $item['grade'];
                    $noteText = 'Bulk stock entry' . (!empty($item['note']) ? " — {$item['note']}" : '');
                    $userId = session('auth_user')['id'] ?? auth()->id() ?? User::first()?->id ?? 1;

                    $entryDate = !empty($item['date']) ? $item['date'] : ($request->input('date') ?: now()->toDateString());
                    $stockDate = Carbon::parse($entryDate)->setTime(now()->hour, now()->minute, now()->second);

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

                        $stock = new Stock([
                            'product_id'       => $productId,
                            'user_id'          => $userId,
                            'stage'            => $stage,
                            'grade'            => $grade,
                            'location_id'      => $locationId,
                            'quantity'         => $qty,
                            'transaction_type' => 'IN',
                            'date'             => $stockDate,
                            'notes'            => "{$noteText} [Added {$qty} kg]",
                        ]);
                        $stock->created_at = $stockDate;
                        $stock->updated_at = $stockDate;
                        $stock->save();
                    }
                }
            });

            return response()->json(['success' => true, 'message' => 'Stock entries added successfully!']);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->validator->errors()->first()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    // ── DISPATCH ACTIVITY ───────────────────────────────────────────────────
    public function dispatchActivity(Request $request)
    {
        $statusPriority = "CASE 
            WHEN TRIM(COALESCE(orders.dispatch_status, '')) IN ('', 'PENDING', 'OPEN', 'UNASSIGNED') THEN 1
            WHEN orders.dispatch_status IN ('PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED') THEN 2
            WHEN orders.dispatch_status IN ('DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED', 'CLOSED') THEN 3
            ELSE 4
        END";

        $dueCol = Schema::hasColumn('orders', 'due_date') ? 'orders.due_date' : 'orders.date';

        $pendingDueSort = "CASE 
            WHEN orders.dispatch_status IN ('DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED', 'CLOSED') THEN '9999-12-31'
            ELSE COALESCE({$dueCol}, orders.date, orders.created_at)
        END";

        $completedDateSort = "CASE 
            WHEN orders.dispatch_status IN ('DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED', 'CLOSED') THEN COALESCE(orders.date, orders.created_at)
            ELSE '1970-01-01'
        END";

        $query = Order::with(['company', 'items.product', 'dispatchLog.user', 'dispatchLogs.user', 'transporter', 'creator'])
            ->select('orders.*')
            ->addSelect(['dispatch_logs_count' => DispatchLog::selectRaw('COUNT(*)')
                ->whereColumn('order_id', 'orders.id')
            ])
            ->orderByRaw("{$statusPriority} ASC")
            ->orderByRaw("{$pendingDueSort} ASC")
            ->orderByRaw("{$completedDateSort} DESC")
            ->orderByDesc('orders.id');

        // Search Filter (Order ID, Company, Salesperson, Transporter, Product name)
        if ($request->filled('q')) {
            $searchTerm = trim($request->q);
            $query->where(function($q) use ($searchTerm) {
                $q->where('orders.id', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('company', function($compQ) use ($searchTerm) {
                      $compQ->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('creator', function($userQ) use ($searchTerm) {
                      $userQ->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('transporter', function($transQ) use ($searchTerm) {
                      $transQ->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('items.product', function($prodQ) use ($searchTerm) {
                      $prodQ->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        $companies = Company::orderBy('name')->get()->map(fn($c) => [
            'id'   => $c->id,
            'name' => strtoupper($c->name ?? '')
        ]);

        $dateRange = $request->range ?? ($request->filled('date_from') || $request->filled('date_to') || $request->filled('start') || $request->filled('end') ? 'custom' : 'all');
        $dateFrom = $request->start ?? $request->date_from;
        $dateTo = $request->end ?? $request->date_to;
        $companyId = $request->company_id;

        if ($companyId) {
            $query->where('orders.company_id', $companyId);
        }

        if ($dateRange && $dateRange !== 'all') {
            if ($dateRange === 'today') {
                $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), Carbon::today());
            } elseif ($dateRange === 'yesterday') {
                $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), Carbon::yesterday());
            } elseif ($dateRange === 'this_week') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($dateRange === 'last_week') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            } elseif ($dateRange === 'this_month') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            } elseif ($dateRange === 'last_month') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
            } elseif ($dateRange === 'custom') {
                if ($dateFrom) {
                    $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), '<=', $dateTo);
                }
            }
        }

        // Compute status counts before applying specific status filter
        $countQuery = clone $query;
        $statusCounts = [
            'ALL' => (clone $countQuery)->count(),
            'PENDING' => (clone $countQuery)->where(function($q) {
                $q->whereIn('dispatch_status', ['PENDING', 'OPEN', 'UNASSIGNED'])
                  ->orWhereNull('dispatch_status');
            })->count(),
            'PARTIAL' => (clone $countQuery)->where(function($q) {
                $q->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED'])
                  ->orWhere(function($sub) {
                      $sub->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED'])
                          ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) > 1');
                  });
            })->count(),
            'FULLY_DISPATCH' => (clone $countQuery)->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED'])->count(),
        ];

        $status = strtoupper(trim((string)$request->status));
        if ($status && $status !== 'ALL') {
            if ($status === 'PENDING') {
                $query->where(function($q) {
                    $q->whereIn('dispatch_status', ['PENDING', 'OPEN', 'UNASSIGNED'])
                      ->orWhereNull('dispatch_status');
                });
            } elseif ($status === 'PARTIAL') {
                $query->where(function($q) {
                    $q->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED'])
                      ->orWhere(function($sub) {
                          $sub->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED'])
                              ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) > 1');
                      });
                });
            } elseif ($status === 'PARTIAL_PENDING') {
                $query->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING']);
            } elseif ($status === 'PARTIAL_DISPATCH') {
                $query->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED'])
                      ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) > 1');
            } elseif ($status === 'FULLY_DISPATCH' || $status === 'FULLY_DISPATCHED') {
                $query->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED']);
            }
        }

        $orders = $query->paginate(20)->withQueryString();

        $pageData = [
            'orders' => $orders,
            'companies' => $companies,
            'statusCounts' => $statusCounts,
            'filters' => [
                'status' => $request->status ?: 'ALL',
                'range' => $dateRange,
                'start' => $dateFrom,
                'end' => $dateTo,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'company_id' => $companyId,
                'q' => $request->q,
            ]
        ];

        return view('admin.dispatch_activity', compact('pageData'));
    }

    public function dispatchActivityPdf(Request $request)
    {
        $statusPriority = "CASE 
            WHEN TRIM(COALESCE(orders.dispatch_status, '')) IN ('', 'PENDING', 'OPEN', 'UNASSIGNED') THEN 1
            WHEN orders.dispatch_status IN ('PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED') THEN 2
            WHEN orders.dispatch_status IN ('DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED', 'CLOSED') THEN 3
            ELSE 4
        END";

        $dueCol = Schema::hasColumn('orders', 'due_date') ? 'orders.due_date' : 'orders.date';

        $pendingDueSort = "CASE 
            WHEN orders.dispatch_status IN ('DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED', 'CLOSED') THEN '9999-12-31'
            ELSE COALESCE({$dueCol}, orders.date, orders.created_at)
        END";

        $completedDateSort = "CASE 
            WHEN orders.dispatch_status IN ('DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED', 'CLOSED') THEN COALESCE(orders.date, orders.created_at)
            ELSE '1970-01-01'
        END";

        $query = Order::with(['company', 'items.product', 'dispatchLog.user', 'dispatchLogs.user', 'transporter', 'creator'])
            ->select('orders.*')
            ->addSelect(['dispatch_logs_count' => DispatchLog::selectRaw('COUNT(*)')
                ->whereColumn('order_id', 'orders.id')
            ])
            ->orderByRaw("{$statusPriority} ASC")
            ->orderByRaw("{$pendingDueSort} ASC")
            ->orderByRaw("{$completedDateSort} DESC")
            ->orderByDesc('orders.id');

        if ($request->filled('q')) {
            $searchTerm = trim($request->q);
            $query->where(function($q) use ($searchTerm) {
                $q->where('orders.id', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('company', function($compQ) use ($searchTerm) {
                      $compQ->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('creator', function($userQ) use ($searchTerm) {
                      $userQ->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('transporter', function($transQ) use ($searchTerm) {
                      $transQ->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('items.product', function($prodQ) use ($searchTerm) {
                      $prodQ->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        $status = strtoupper(trim((string)$request->status));
        if ($status && $status !== 'ALL') {
            if ($status === 'PENDING') {
                $query->where(function($q) {
                    $q->whereIn('dispatch_status', ['PENDING', 'OPEN', 'UNASSIGNED'])
                      ->orWhereNull('dispatch_status');
                });
            } elseif ($status === 'PARTIAL') {
                $query->where(function($q) {
                    $q->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED'])
                      ->orWhere(function($sub) {
                          $sub->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED'])
                              ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) > 1');
                      });
                });
            } elseif ($status === 'PARTIAL_PENDING') {
                $query->whereIn('dispatch_status', ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING']);
            } elseif ($status === 'PARTIAL_DISPATCH') {
                $query->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED'])
                      ->whereRaw('(SELECT COUNT(*) FROM dispatch_logs WHERE dispatch_logs.order_id = orders.id) > 1');
            } elseif ($status === 'FULLY_DISPATCH' || $status === 'FULLY_DISPATCHED') {
                $query->whereIn('dispatch_status', ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED']);
            }
        }
        if ($request->filled('company_id')) {
            $query->where('orders.company_id', $request->company_id);
        }

        $dateRange = $request->range ?? ($request->filled('date_from') || $request->filled('date_to') || $request->filled('start') || $request->filled('end') ? 'custom' : 'all');
        $dateFrom = $request->start ?? $request->date_from;
        $dateTo = $request->end ?? $request->date_to;

        if ($dateRange && $dateRange !== 'all') {
            if ($dateRange === 'today') {
                $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), Carbon::today());
            } elseif ($dateRange === 'yesterday') {
                $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), Carbon::yesterday());
            } elseif ($dateRange === 'this_week') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($dateRange === 'last_week') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            } elseif ($dateRange === 'this_month') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            } elseif ($dateRange === 'last_month') {
                $query->whereBetween(DB::raw('COALESCE(orders.date, orders.created_at)'), [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
            } elseif ($dateRange === 'custom') {
                if ($dateFrom) {
                    $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate(DB::raw('COALESCE(orders.date, orders.created_at)'), '<=', $dateTo);
                }
            }
        }

        $orders = $query->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.dispatch_activity_pdf', compact('orders'))
            ->setPaper('A4', 'landscape');
        $fromDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') : now()->format('d-m-Y');
        $toDate   = $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d-m-Y') : now()->format('d-m-Y');
        $randomSerial = rand(1000, 9999);
        if ($dateFrom && $dateTo && $dateFrom !== $dateTo) {
            $filename = 'pentapure_dispatch_activity_' . $fromDate . 'to' . $toDate . '_' . $randomSerial . '.pdf';
        } else {
            $filename = 'pentapure_dispatch_activity_' . $fromDate . '_' . $randomSerial . '.pdf';
        }
        return $pdf->download($filename);
    }

    public function cashierOverview(Request $request)
    {
        $baseQuery = \App\Models\Transaction::with(['user', 'bills'])->orderByDesc('created_at');

        if ($request->filled('cashier_id')) {
            $baseQuery->where('user_id', $request->cashier_id);
        }
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $allTxs = $baseQuery->get();

        $cashiers = User::where('role', 'CASHIER')
            ->orWhereIn('id', \App\Models\Transaction::select('user_id')->distinct())
            ->orderBy('name')
            ->get();
        
        $summary = [
            'totalIn'  => $allTxs->where('type', 'IN')->sum('amount'),
            'totalOut' => $allTxs->where('type', 'OUT')->sum('amount'),
            'balance'  => $allTxs->where('type', 'IN')->sum('amount') - $allTxs->where('type', 'OUT')->sum('amount'),
            'byCategory' => $allTxs->groupBy('category')->map(fn($group) => [
                'in' => $group->where('type', 'IN')->sum('amount'),
                'out' => $group->where('type', 'OUT')->sum('amount'),
            ]),
            'byCashier' => $allTxs->groupBy('user_id')->map(function($group) {
                $user = $group->first()->user;
                return [
                    'name' => $user ? $user->name : 'Unknown',
                    'in' => $group->where('type', 'IN')->sum('amount'),
                    'out' => $group->where('type', 'OUT')->sum('amount'),
                    'balance' => $group->where('type', 'IN')->sum('amount') - $group->where('type', 'OUT')->sum('amount'),
                ];
            })->values(),
        ];

        // Running balance chronologically (oldest to newest)
        $chronoTxs = $allTxs->sort(function($a, $b) {
            $tA = strtotime($a->date ?: $a->created_at);
            $tB = strtotime($b->date ?: $b->created_at);
            if ($tA === $tB) {
                return $a->id <=> $b->id;
            }
            return $tA <=> $tB;
        });

        $runningBal = 0;
        $balMap = [];
        foreach ($chronoTxs as $t) {
            if ($t->type === 'IN') {
                $runningBal += (float)$t->amount;
            } else {
                $runningBal -= (float)$t->amount;
            }
            $balMap[$t->id] = $runningBal;
        }

        // Apply type/status filter if specified
        $statusFilter = strtoupper(trim((string)($request->type ?: $request->status)));
        if ($statusFilter && in_array($statusFilter, ['IN', 'OUT'])) {
            $txs = $allTxs->where('type', $statusFilter);
        } else {
            $txs = $allTxs;
        }

        // Sort descending (newest first) for ledger display
        $txs = $txs->sort(function($a, $b) {
            $tA = strtotime($a->date ?: $a->created_at);
            $tB = strtotime($b->date ?: $b->created_at);
            if ($tA === $tB) {
                return $b->id <=> $a->id;
            }
            return $tB <=> $tA;
        })->values();

        $pageData = [
            'transactions' => $txs,
            'summary' => $summary,
            'cashiers' => $cashiers,
            'selectedCashier' => $request->cashier_id,
            'balMap' => $balMap,
        ];

        return view('admin.cashier_overview', compact('pageData'));
    }

    public function overviewPdf(Request $request)
    {
        $baseQuery = \App\Models\Transaction::with('user')->orderByDesc('created_at');

        if ($request->filled('cashier_id')) {
            $baseQuery->where('user_id', $request->cashier_id);
        }
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $allTxs = $baseQuery->get();
        
        $summary = [
            'totalIn'  => $allTxs->where('type', 'IN')->sum('amount'),
            'totalOut' => $allTxs->where('type', 'OUT')->sum('amount'),
            'balance'  => $allTxs->where('type', 'IN')->sum('amount') - $allTxs->where('type', 'OUT')->sum('amount'),
            'byCategory' => $allTxs->groupBy('category')->map(fn($group) => [
                'in' => $group->where('type', 'IN')->sum('amount'),
                'out' => $group->where('type', 'OUT')->sum('amount'),
            ]),
            'byCashier' => $allTxs->groupBy('user_id')->map(function($group) {
                $user = $group->first()->user;
                return [
                    'name' => $user ? $user->name : 'Unknown',
                    'in' => $group->where('type', 'IN')->sum('amount'),
                    'out' => $group->where('type', 'OUT')->sum('amount'),
                    'balance' => $group->where('type', 'IN')->sum('amount') - $group->where('type', 'OUT')->sum('amount'),
                ];
            })->values(),
        ];

        $statusFilter = strtoupper(trim((string)($request->type ?: $request->status)));
        if ($statusFilter && in_array($statusFilter, ['IN', 'OUT'])) {
            $txs = $allTxs->where('type', $statusFilter)->values();
        } else {
            $txs = $allTxs;
        }

        $pageData = [
            'transactions' => $txs,
            'summary' => $summary
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.cashier_overview_pdf', compact('pageData'));
        $filename = 'pentapure_cashier_overview_' . now()->format('d-m-Y') . '_' . rand(1000, 9999) . '.pdf';
        return $pdf->download($filename);
    }

    // ── NOTIFICATION HISTORY ───────────────────────────────────────────────
    public function notificationHistory()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            $sessionUser = session('auth_user');
            if ($sessionUser) $user = User::find($sessionUser['id']);
        }

        $notifications = collect();
        if ($user) {
            if (in_array($user->role, ['ADMIN', 'SUB_ADMIN'])) {
                // Admin views all system notification records in DB
                $rawNotifs = \Illuminate\Support\Facades\DB::table('notifications')
                    ->orderByDesc('created_at')
                    ->get();

                $notifications = $rawNotifs->map(function ($n) {
                    $data = json_decode($n->data ?? '{}', true) ?: [];
                    $notifiableUser = User::find($n->notifiable_id);
                    $targetName = $notifiableUser ? $notifiableUser->name : 'User #' . $n->notifiable_id;
                    return (object)[
                        'id'          => $n->id,
                        'title'       => ($data['title'] ?? 'Notification') . ' → (' . $targetName . ')',
                        'message'     => $data['message'] ?? '',
                        'type'        => $data['type'] ?? 'info',
                        'url'         => $data['url'] ?? null,
                        'is_read'     => !is_null($n->read_at),
                        'read_at'     => $n->read_at ? Carbon::parse($n->read_at) : null,
                        'created_at'  => Carbon::parse($n->created_at),
                        'notif_class' => 'Sent to ' . $targetName,
                    ];
                });
            } else {
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
        $locations = Location::withCount(['stocks', 'dispatchLocations'])->orderBy('name')->paginate(20);
        return view('admin.locations', compact('locations'));
    }

    public function getLocationsApi()
    {
        if (Location::count() === 0) {
            $defaults = ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
            foreach ($defaults as $d) {
                Location::firstOrCreate(['name' => $d]);
            }
        }
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
        $loc = Location::withCount(['stocks', 'dispatchLocations'])->findOrFail($id);

        if (in_array(strtoupper(trim($loc->name)), ['MAIN WAREHOUSE', 'DEFAULT'], true)) {
            return response()->json(['success' => false, 'message' => 'Fixed system location (Main Warehouse) cannot be deleted!'], 403);
        }

        $usageCount = ($loc->stocks_count ?? 0) + ($loc->dispatch_locations_count ?? 0);
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete location: it is currently in use across {$usageCount} stock/dispatch " . (\Illuminate\Support\Str::plural('record', $usageCount)) . "!"
            ], 422);
        }

        $loc->delete();
        return response()->json(['success' => true, 'message' => 'Location deleted successfully!']);
    }

    public function stockLocationsBreakdownApi(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stage' => 'nullable|string',
            'grade' => 'nullable|string',
        ]);

        $locations = Location::orderBy('name')->get();
        $grade = (!empty($request->grade) && strtoupper($request->grade) !== 'ALL') ? $request->grade : null;
        $stage = (!empty($request->stage) && strtoupper($request->stage) !== 'ALL') ? $request->stage : null;

        $stockCountsQuery = DB::table('stocks')
            ->where('stocks.product_id', $request->product_id);
            
        if ($stage) {
            $stockCountsQuery->where('stocks.stage', $stage);
        }

        if ($grade) {
            $stockCountsQuery->where(function($q) use ($grade) {
                if (strtoupper($grade) === 'NONE') {
                    $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '');
                } else {
                    $q->where('grade', $grade);
                }
            });
        }

        $stockCounts = $stockCountsQuery
            ->whereNotNull('stocks.location_id')
            ->groupBy('stocks.location_id')
            ->selectRaw("stocks.location_id, SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity")
            ->pluck('quantity', 'location_id')
            ->toArray();

        $unassignedStockQuery = DB::table('stocks')
            ->where('stocks.product_id', $request->product_id);

        if ($stage) {
            $unassignedStockQuery->where('stocks.stage', $stage);
        }

        if ($grade) {
            $unassignedStockQuery->where(function($q) use ($grade) {
                if (strtoupper($grade) === 'NONE') {
                    $q->where('grade', 'NONE')->orWhereNull('grade')->orWhere('grade', '');
                } else {
                    $q->where('grade', $grade);
                }
            });
        }

        $unassignedStock = (float) $unassignedStockQuery
            ->whereNull('stocks.location_id')
            ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as quantity")
            ->value('quantity');

        $breakdown = $locations->map(function($loc) use ($stockCounts, $unassignedStock) {
            $qty = (float) ($stockCounts[$loc->id] ?? 0);
            if (strtoupper($loc->name) === 'MAIN WAREHOUSE' && $unassignedStock > 0) {
                $qty += $unassignedStock;
            }
            return [
                'location_id' => $loc->id,
                'name' => $loc->name,
                'quantity' => max(0, $qty),
            ];
        })->sortByDesc('quantity')->values();

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
            ->orderByRaw('COALESCE(date, created_at) asc')
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

    public function updateStockNote(Request $request, $id)
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
}
