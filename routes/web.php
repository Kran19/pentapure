<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\FinishedController;
use App\Http\Controllers\RawController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SemiController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

// ── Root Redirect ──────────────────────────────────────────────────────────
Route::get('/', fn () => redirect('/login'));

// Push Subscription Routes
Route::post('/notifications/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe']);
Route::post('/notifications/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe']);
Route::get('/notifications/test', function() {
    $user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();
    if (!$user) return "Not logged in";
    \Log::info('Triggering Test Notification for: ' . $user->name);
    try {
        $user->notify(new \App\Notifications\UserActivityNotification('Test Notification', 'If you see this, notifications are working!'));
        \Log::info('Test Notification handoff successful');
        return "Notification sent to " . $user->name;
    } catch (\Exception $e) {
        \Log::error('Test Notification failed: ' . $e->getMessage());
        return "Error: " . $e->getMessage();
    }
});

Route::get('/admin/debug-notifications', function() {
    return view('admin.debug_notifications');
});

Route::get('/admin/debug-check-sub', function() {
    $user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();
    if (!$user) return response()->json(['exists' => false]);
    $subs = $user->pushSubscriptions;
    return response()->json([
        'exists' => $subs->count() > 0,
        'count' => $subs->count(),
        'endpoint' => $subs->first() ? $subs->first()->endpoint : null
    ]);
});

// ── Auth Routes (No Auth Required) ────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ── RAW MATERIAL ROUTES ────────────────────────────────────────────────────
Route::prefix('raw')->middleware('auth.role:RAW')->controller(RawController::class)->group(function () {
    Route::get('/home',    'home')->name('raw.home');
    Route::get('/action',  'action')->name('raw.action');
    Route::post('/action', 'storeInward')->name('raw.action.store');
    Route::get('/po',      'home')->name('raw.po');
    Route::post('/po',     'storePO');
    Route::get('/history', 'history')->name('raw.history');
    Route::get('/profile', 'profile')->name('raw.profile');
});

// ── SEMI PRODUCTION ROUTES ─────────────────────────────────────────────────
Route::prefix('semi')->middleware('auth.role:SEMI')->controller(SemiController::class)->group(function () {
    Route::get('/home',    'home')->name('semi.home');
    Route::get('/action',  'action')->name('semi.action');
    Route::get('/po',      'home')->name('semi.po');
    Route::post('/po',     [RawController::class, 'storePO']); // Shared logic from RawController
    Route::post('/action', 'storeProduction');
    Route::get('/history', 'history')->name('semi.history');
    Route::get('/profile', 'profile')->name('semi.profile');
});

// ── FINISHED PRODUCTION ROUTES ─────────────────────────────────────────────
Route::prefix('finished')->middleware('auth.role:FINISHED')->controller(FinishedController::class)->group(function () {
    Route::get('/home',    'home')->name('finished.home');
    Route::get('/action',  'action')->name('finished.action');
    Route::get('/po',      'home')->name('finished.po');
    Route::post('/po',     [RawController::class, 'storePO']); // Shared logic from RawController
    Route::post('/action', 'storeProduction');
    Route::get('/history', 'history')->name('finished.history');
    Route::get('/profile', 'profile')->name('finished.profile');
});

// ── SALES ROUTES ───────────────────────────────────────────────────────────
Route::prefix('sales')->middleware('auth.role:SALES')->controller(SalesController::class)->group(function () {
    Route::get('/home',          'home')->name('sales.home');
    Route::get('/action',        'action')->name('sales.action');
    Route::post('/order',        'storeOrder');
    Route::post('/company',      'storeCompany');
    Route::post('/transport',    'storeTransporter');
    Route::get('/history',       'history')->name('sales.history');
    Route::get('/profile',       'profile')->name('sales.profile');
});

// ── DISPATCH ROUTES ────────────────────────────────────────────────────────
Route::prefix('dispatch')->middleware('auth.role:DISPATCH')->controller(DispatchController::class)->group(function () {
    Route::get('/home',     'home')->name('dispatch.home');
    Route::get('/action',   'action')->name('dispatch.action');
    Route::post('/action',  'storeDispatch');
    Route::post('/update-lr', 'updateLR');
    Route::get('/history',  'history')->name('dispatch.history');
    Route::get('/profile',  'profile')->name('dispatch.profile');
});

// ── CASHIER ROUTES ─────────────────────────────────────────────────────────
Route::prefix('cashier')->middleware('auth.role:CASHIER')->controller(CashierController::class)->group(function () {
    Route::get('/home',        'home')->name('cashier.home');
    Route::get('/action',      'action')->name('cashier.action');
    Route::post('/action',     'storeTransaction');
    Route::get('/history',     'history')->name('cashier.history');
    Route::get('/history/pdf', 'downloadPdf')->name('cashier.pdf');
    Route::get('/profile',     'profile')->name('cashier.profile');
});

// ── ADMIN ROUTES ───────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth.role:ADMIN')->controller(AdminController::class)->group(function () {
    Route::get('/dashboard',          'dashboard')->name('admin.dashboard');
    Route::get('/users',              'users')->name('admin.users');
    Route::post('/users',             'storeUser');
    Route::post('/users/toggle',      'toggleUserStatus');
    Route::delete('/users/{id}',      'destroyUser');
    Route::get('/products',           'products')->name('admin.products');
    Route::post('/products',          'storeProduct');
    Route::post('/products/toggle/{id}', 'toggleProductStatus');
    Route::delete('/products/{id}',   'destroyProduct');
    Route::get('/stock',              'stock')->name('admin.stock');
    Route::post('/stock/adjust',      'adjustStock');
    Route::get('/po',                 'po')->name('admin.po');
    Route::post('/po/approve',        'approvePO');
    Route::delete('/po/{id}',         'destroyPO');
    Route::get('/logs',               'logs')->name('admin.logs');
    Route::get('/grades',             'grades')->name('admin.grades');
    Route::post('/grades',            'storeGrade');
    Route::delete('/grades/{id}',     'destroyGrade');

    // Categories (Cashier expense categories)
    Route::get('/categories',        'categories')->name('admin.categories');
    Route::post('/categories',       'storeCategory');
    Route::post('/categories/toggle','toggleCategoryStatus');
    Route::delete('/categories/{id}','destroyCategory');

    // Admin Attendance sub-pages (read + full access)

    Route::get('/attendance/dashboard',   [AttendanceController::class, 'home'])->name('admin.attendance.dashboard');
    Route::get('/attendance/departments', [AttendanceController::class, 'departments'])->name('admin.attendance.departments');
    Route::post('/attendance/departments',[AttendanceController::class, 'storeDepartment']);
    Route::delete('/attendance/departments/{id}', [AttendanceController::class, 'destroyDepartment']);
    Route::get('/attendance/workers',     [AttendanceController::class, 'workers'])->name('admin.attendance.workers');
    Route::post('/attendance/workers',    [AttendanceController::class, 'storeWorker']);
    Route::delete('/attendance/workers/{id}', [AttendanceController::class, 'destroyWorker']);
    Route::get('/attendance/daily',       [AttendanceController::class, 'daily'])->name('admin.attendance.daily');
    Route::post('/attendance/daily',      [AttendanceController::class, 'storeDailyAttendance']);
    Route::get('/attendance/reports',     [AttendanceController::class, 'reports'])->name('admin.attendance.reports');
    Route::get('/attendance/reports/worker/{id}', [AttendanceController::class, 'workerReport']);
});

// ── ATTENDANCE ROUTES ──────────────────────────────────────────────────────

Route::prefix('attendance')->middleware('auth.role:ATTENDANCE')->controller(AttendanceController::class)->group(function () {
    Route::get('/home',               'home')->name('attendance.home');
    Route::get('/departments',        'departments')->name('attendance.departments');
    Route::post('/departments',       'storeDepartment');
    Route::delete('/departments/{id}','destroyDepartment');
    
    Route::get('/workers',            'workers')->name('attendance.workers');
    Route::post('/workers',           'storeWorker');
    Route::delete('/workers/{id}',    'destroyWorker');
    
    Route::get('/daily',              'daily')->name('attendance.daily');
    Route::post('/daily',             'storeDailyAttendance');
    Route::get('/team',               'team')->name('attendance.team');
    
    // JSON APIs for SPA views
    Route::get('/api/workers',        'workersJson');
    Route::get('/api/departments',    'departmentsJson');
    Route::get('/api/daily',          'dailyJson');
    
    Route::get('/reports',            'reports')->name('attendance.reports');
    Route::get('/reports/worker/{id}','workerReport');
    
    // Standard mobile nav aliases
    Route::get('/action',             'daily')->name('attendance.action');
    Route::get('/history',            'reports')->name('attendance.history');
    Route::get('/profile',            'home')->name('attendance.profile');
});
