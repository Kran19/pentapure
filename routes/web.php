<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\FinishedController;
use App\Http\Controllers\HistoryPdfController;
use App\Http\Controllers\RawController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SemiController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

// ── Root Redirect ──────────────────────────────────────────────────────────
Route::get('/', fn () => redirect('/login'));
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('global.login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('global.login.post');

try {
    $users = \App\Models\User::where('status', 'ACTIVE')->orderBy('role')->orderBy('id')->get();
    $roleCounts = [];
    foreach ($users as $u) {
        $r = strtolower($u->role);
        if (!isset($roleCounts[$r])) {
            $roleCounts[$r] = 1;
            $u->login_slug = $r;
        } else {
            $roleCounts[$r]++;
            $u->login_slug = $r . $roleCounts[$r];
        }
    }
} catch (\Exception $e) {
    $users = collect();
}

$roleSlugs = [];
foreach($users as $u) {
    $roleSlugs[$u->role][] = $u->login_slug;
}

// Global push notifications route (maps to all slugs for backward compatibility or just use a generic route)
Route::post('/notifications/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe']);
Route::post('/notifications/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe']);
Route::get('/notifications/test', function() {
    $user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();
    if (!$user) return 'Not logged in';
    \Log::info('Triggering Test Notification for: ' . $user->name);
    return 'Notification sent to ' . $user->name . ' (Role: ' . $user->role . ')';
});

// ── Shared Routes (Under {user_slug} prefix) ──────────────────────────────
Route::prefix('{user_slug}')->middleware('auth.role:ADMIN,RAW,SEMI,FINISHED,SALES,DISPATCH,CASHIER,ATTENDANCE')->group(function() {
    Route::get('/api/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/api/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    Route::get('/api/locations', [\App\Http\Controllers\AdminController::class, 'getLocationsApi']);
    Route::get('/api/stock/locations', [\App\Http\Controllers\AdminController::class, 'stockLocationsBreakdownApi']);
    Route::post('/api/stock/locations/transfer', [\App\Http\Controllers\AdminController::class, 'transferStockLocationsApi']);

    Route::get('/product/{productId}/{stage}/history', [\App\Http\Controllers\AdminController::class, 'productStockHistory'])
        ->name('product.stock.history');
        
    Route::get('/stock/live', [\App\Http\Controllers\AdminController::class, 'liveStockApi']);
    Route::post('/stock/adjust', [\App\Http\Controllers\AdminController::class, 'adjustStock']);
    Route::post('/stock/bulk-add', [\App\Http\Controllers\AdminController::class, 'bulkAddStock']);
    
    Route::get('/history/{panel}/pdf', [\App\Http\Controllers\HistoryPdfController::class, 'download'])
        ->name('history.pdf');
    Route::get('/dispatch/pdf/{id}', [\App\Http\Controllers\HistoryPdfController::class, 'dispatchNotePdf'])
        ->name('dispatch.note.pdf');

    Route::get('/debug-notifications', function() {
        return view('admin.debug_notifications');
    });
    Route::get('/debug-check-sub', function() {
        $user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();
        if (!$user) return response()->json(['exists' => false]);
        $subs = $user->pushSubscriptions;
        return response()->json([
            'exists' => $subs->count() > 0,
            'count' => $subs->count(),
            'endpoint' => $subs->first() ? $subs->first()->endpoint : null
        ]);
    });
    
    Route::post('/admin/notifications/send', [\App\Http\Controllers\AdminController::class, 'sendNotification'])->middleware('auth.role:ADMIN');
});

// === RAW ROUTES ===
foreach ($roleSlugs['RAW'] ?? [] as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:RAW')->controller(RawController::class)->group(function () use ($slug) {

    Route::get('/home',    'home')->name($slug.'.home');
    Route::get('/action',  'action')->name($slug.'.action');
    Route::post('/action', 'storeInward')->name($slug.'.action.store');
    Route::post('/transfer-to-semi', 'transferToSemi')->name($slug.'.transfer_to_semi');
    Route::get('/po',      'po')->name($slug.'.po');
    Route::post('/po',     'storePO');
    Route::get('/history', 'history')->name($slug.'.history');
    Route::get('/profile', 'profile')->name($slug.'.profile');
    });
}

// === SEMI ROUTES ===
foreach ($roleSlugs['SEMI'] ?? [] as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:SEMI')->controller(SemiController::class)->group(function () use ($slug) {

    Route::get('/home',    'home')->name($slug.'.home');
    Route::get('/action',  'action')->name($slug.'.action');
    Route::get('/po',      'po')->name($slug.'.po');
    Route::post('/po',     [RawController::class, 'storePO']); // Shared logic from RawController
    Route::post('/action', 'storeProduction');
    Route::post('/transfer-to-semi', [RawController::class, 'transferToSemi'])->name($slug.'.transfer_to_semi');
    Route::get('/history', 'history')->name($slug.'.history');
    Route::get('/profile', 'profile')->name($slug.'.profile');
    });
}

// === FINISHED ROUTES ===
foreach ($roleSlugs['FINISHED'] ?? [] as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:FINISHED')->controller(FinishedController::class)->group(function () use ($slug) {

    Route::get('/home',    'home')->name($slug.'.home');
    Route::get('/action',  'action')->name($slug.'.action');
    Route::get('/po',      'po')->name($slug.'.po');
    Route::post('/po',     [RawController::class, 'storePO']); // Shared logic from RawController
    Route::post('/action', 'storeProduction');
    Route::post('/quick-product', [AdminController::class, 'storeProduct']);
    Route::post('/transfer-to-semi', [RawController::class, 'transferToSemi'])->name($slug.'.transfer_to_semi');
    Route::get('/history', 'history')->name($slug.'.history');
    Route::get('/profile', 'profile')->name($slug.'.profile');
    });
}

// === SALES ROUTES ===
foreach ($roleSlugs['SALES'] ?? [] as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:SALES')->controller(SalesController::class)->group(function () use ($slug) {

    Route::get('/home',          'home')->name($slug.'.home');
    Route::get('/action',        'action')->name($slug.'.action');
    Route::post('/order',        'storeOrder');
    Route::post('/order/{id}',   'updateOrder');
    Route::post('/order/{id}/cancel', 'cancelOrder');
    Route::post('/company',      'storeCompany');
    Route::post('/company/{id}', 'updateCompany');
    Route::post('/transport',    'storeTransporter');
    Route::get('/history',       'history')->name($slug.'.history');
    Route::get('/profile',       'profile')->name($slug.'.profile');
    Route::get('/order/pdf/{id}', [HistoryPdfController::class, 'salesOrderPdf'])->name($slug.'.order.pdf');
    });
}

// === DISPATCH ROUTES ===
foreach ($roleSlugs['DISPATCH'] ?? [] as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:DISPATCH')->controller(DispatchController::class)->group(function () use ($slug) {

    Route::get('/home',     'home')->name($slug.'.home');
    Route::get('/action',   'action')->name($slug.'.action');
    Route::post('/action',  'storeDispatch');
    Route::post('/update-lr', 'updateLR');
    Route::post('/revert/{id}', 'revertDispatch');
    Route::get('/history',  'history')->name($slug.'.history');
    Route::get('/profile',  'profile')->name($slug.'.profile');
    });
}

// === CASHIER ROUTES ===
foreach ($roleSlugs['CASHIER'] ?? [] as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:CASHIER')->controller(CashierController::class)->group(function () use ($slug) {

    Route::get('/home',                'home')->name($slug.'.home');
    Route::get('/action',              'action')->name($slug.'.action');
    Route::post('/action',             'storeTransaction');
    Route::get('/history',             'history')->name($slug.'.history');
    Route::get('/history/pdf',         'downloadPdf')->name($slug.'.pdf');
    Route::get('/ledger',              'ledger')->name($slug.'.ledger');
    Route::get('/profile',             'profile')->name($slug.'.profile');
    // Bill management
    Route::post('/bill/upload',        'uploadBill')->name($slug.'.bill.upload');
    Route::post('/categories',         [\App\Http\Controllers\AdminController::class, 'storeCategory']);
    Route::delete('/bill/{id}',        'destroyBill')->name($slug.'.bill.destroy');
    // Transaction management
    Route::put('/action/{id}',         'updateTransaction')->name($slug.'.action.update');
    Route::delete('/action/{id}',      'destroyTransaction')->name($slug.'.action.destroy');
    });
}

// === ADMIN ROUTES ===
$adminSlugs = array_merge(
    $roleSlugs['ADMIN'] ?? [],
    $roleSlugs['SUB_ADMIN'] ?? [],
    $roleSlugs['STOCK_MANAGER'] ?? []
);
foreach ($adminSlugs as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:ADMIN')->controller(AdminController::class)->group(function () use ($slug) {

    Route::get('/dashboard',          'dashboard')->name($slug.'.dashboard');
    Route::get('/home',               'dashboard')->name($slug.'.home');
    Route::get('/users',              'users')->name($slug.'.users');
    Route::post('/users',             'storeUser');
    Route::post('/users/toggle',      'toggleUserStatus');

    // Locations admin management
    Route::get('/locations',          'locations')->name($slug.'.locations');
    Route::post('/locations',         'storeLocationApi');
    Route::delete('/locations/{id}',  'destroyLocationApi');
    Route::delete('/users/{id}',      'destroyUser');
    Route::get('/products',           'products')->name($slug.'.products');
    Route::get('/products/pdf',       'productsPdf')->name($slug.'.products.pdf');
    Route::post('/products',          'storeProduct');
    Route::post('/products/toggle/{id}', 'toggleProductStatus');
    Route::delete('/products/{id}',   'destroyProduct');
    Route::get('/stock',              'stock')->name($slug.'.stock');
    Route::post('/stock/adjust',      'adjustStock');
    Route::post('/stock/limit',       'setStockLimit');
    Route::post('/stock/rate',        'updateProductRate');
    Route::post('/stock/pdf',         'downloadStockPdf')->name($slug.'.stock.pdf');
    Route::get('/po',                 'po')->name($slug.'.po');
    Route::post('/po/approve',        'approvePO');
    Route::post('/po/receive',        'receivePO');
    Route::delete('/po/{id}',         'destroyPO');
    Route::get('/logs',               'logs')->name($slug.'.logs');
    Route::get('/cashier-logs',       'cashierActivityLogs')->name($slug.'.cashier.logs');
    Route::get('/grades',             'grades')->name($slug.'.grades');
    Route::post('/grades',            'storeGrade');
    Route::delete('/grades/{id}',     'destroyGrade');
    Route::get('/notifications',      'notificationHistory')->name($slug.'.notifications');

    // Categories (Cashier expense categories)
    Route::get('/categories',        'categories')->name($slug.'.categories');
    Route::post('/categories',       'storeCategory');
    Route::post('/categories/toggle','toggleCategoryStatus');
    Route::delete('/categories/{id}','destroyCategory');

    // Dispatch Activity
    Route::get('/dispatch-activity', 'dispatchActivity')->name($slug.'.dispatch.activity');
    Route::get('/dispatch-activity/pdf', 'dispatchActivityPdf')->name($slug.'.dispatch.pdf');

    // ── CASHIER OVERVIEW ───────────────────────────────────────────────────
    Route::get('/cashier-overview',   'cashierOverview')->name($slug.'.cashier_overview');
    Route::get('/cashier-overview/pdf','overviewPdf')->name($slug.'.cashier_overview.pdf');
    Route::get('/cashier-logs',       'cashierActivityLogs')->name($slug.'.cashier.logs');

    // Admin Attendance sub-pages (read + full access)

    Route::get('/attendance/dashboard',   [AttendanceController::class, 'home'])->name($slug.'.attendance.dashboard');
    Route::get('/attendance/departments', [AttendanceController::class, 'departments'])->name($slug.'.attendance.departments');
    Route::post('/attendance/departments',[AttendanceController::class, 'storeDepartment']);
    Route::delete('/attendance/departments/{id}', [AttendanceController::class, 'destroyDepartment']);
    Route::get('/attendance/workers',     [AttendanceController::class, 'workers'])->name($slug.'.attendance.workers');
    Route::post('/attendance/workers',    [AttendanceController::class, 'storeWorker']);
    Route::delete('/attendance/workers/{id}', [AttendanceController::class, 'destroyWorker']);
    Route::get('/attendance/daily',       [AttendanceController::class, 'daily'])->name($slug.'.attendance.daily');
    Route::post('/attendance/daily',      [AttendanceController::class, 'storeDailyAttendance']);
    Route::get('/attendance/reports',     [AttendanceController::class, 'reports'])->name($slug.'.attendance.reports');
    Route::get('/attendance/reports/worker/{id}', [AttendanceController::class, 'workerReport']);
    });

    // Admin can also generate any cashier's PDF
    Route::get("/{$slug}/cashier/{userId}/pdf", [AdminController::class, 'downloadCashierPdf'])
        ->middleware('auth.role:ADMIN')
        ->name($slug.'.cashier.pdf');

    // Shared Bill View (Admin & Cashier)
    Route::get("/{$slug}/cashier/bill/{id}/view", [CashierController::class, 'viewBill'])
        ->middleware('auth.role:ADMIN,CASHIER')
        ->name($slug.'.cashier.bill.view');
}

// === ATTENDANCE ROUTES ===
foreach ($roleSlugs['ATTENDANCE'] ?? [] as $slug) {
    Route::prefix($slug)->group(function () use ($slug) {
        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name($slug.'.login.show');
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name($slug.'.login.post');
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name($slug.'.logout');
    });

    Route::prefix($slug)->middleware('auth.role:ATTENDANCE')->controller(AttendanceController::class)->group(function () use ($slug) {

    Route::get('/home',               'home')->name($slug.'.home');
    Route::get('/departments',        'departments')->name($slug.'.departments');
    Route::post('/departments',       'storeDepartment');
    Route::delete('/departments/{id}','destroyDepartment');

    Route::get('/workers',            'workers')->name($slug.'.workers');
    Route::post('/workers',           'storeWorker');
    Route::delete('/workers/{id}',    'destroyWorker');

    Route::get('/daily',              'daily')->name($slug.'.daily');
    Route::post('/daily',             'storeDailyAttendance');
    Route::get('/team',               'team')->name($slug.'.team');

    // JSON APIs for SPA views
    Route::get('/api/workers',        'workersJson');
    Route::get('/api/departments',    'departmentsJson');
    Route::get('/api/daily',          'dailyJson');

    Route::get('/history',            'reports')->name($slug.'.history');
    Route::get('/history/worker/{id}','workerReport');

    // Standard mobile nav aliases
    Route::get('/action',             'daily')->name($slug.'.action');
    Route::get('/history',            'reports')->name($slug.'.history');
    Route::get('/profile',            'home')->name($slug.'.profile');
    });
}

