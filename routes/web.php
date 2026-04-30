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
    $user->notify(new \App\Notifications\UserActivityNotification('Test Notification', 'If you see this, notifications are working!'));
    return "Notification sent to " . $user->name;
});

// ── Auth Routes (No Auth Required) ────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ── RAW MATERIAL ROUTES ────────────────────────────────────────────────────
Route::prefix('raw')->middleware('auth.role:RAW')->controller(RawController::class)->group(function () {
    Route::get('/home',    'home');
    Route::get('/action',  'action');
    Route::post('/action', 'storeInward');
    Route::get('/po',      'home');
    Route::post('/po',     'storePO');
    Route::get('/history', 'history');
    Route::get('/profile', 'profile');
});

// ── SEMI PRODUCTION ROUTES ─────────────────────────────────────────────────
Route::prefix('semi')->middleware('auth.role:SEMI')->controller(SemiController::class)->group(function () {
    Route::get('/home',    'home');
    Route::get('/action',  'action');
    Route::get('/po',      'home');
    Route::post('/po',     [RawController::class, 'storePO']); // Shared logic from RawController
    Route::post('/action', 'storeProduction');
    Route::get('/history', 'history');
    Route::get('/profile', 'profile');
});

// ── FINISHED PRODUCTION ROUTES ─────────────────────────────────────────────
Route::prefix('finished')->middleware('auth.role:FINISHED')->controller(FinishedController::class)->group(function () {
    Route::get('/home',    'home');
    Route::get('/action',  'action');
    Route::get('/po',      'home');
    Route::post('/po',     [RawController::class, 'storePO']); // Shared logic from RawController
    Route::post('/action', 'storeProduction');
    Route::get('/history', 'history');
    Route::get('/profile', 'profile');
});

// ── SALES ROUTES ───────────────────────────────────────────────────────────
Route::prefix('sales')->middleware('auth.role:SALES')->controller(SalesController::class)->group(function () {
    Route::get('/home',          'home');
    Route::get('/action',        'action');
    Route::post('/order',        'storeOrder');
    Route::post('/company',      'storeCompany');
    Route::post('/transport',    'storeTransporter');
    Route::get('/history',       'history');
    Route::get('/profile',       'profile');
});

// ── DISPATCH ROUTES ────────────────────────────────────────────────────────
Route::prefix('dispatch')->middleware('auth.role:DISPATCH')->controller(DispatchController::class)->group(function () {
    Route::get('/home',     'home');
    Route::get('/action',   'action');
    Route::post('/action',  'storeDispatch');
    Route::get('/history',  'history');
    Route::get('/profile',  'profile');
});

// ── CASHIER ROUTES ─────────────────────────────────────────────────────────
Route::prefix('cashier')->middleware('auth.role:CASHIER')->controller(CashierController::class)->group(function () {
    Route::get('/home',        'home');
    Route::get('/action',      'action');
    Route::post('/action',     'storeTransaction');
    Route::get('/history',     'history');
    Route::get('/profile',     'profile');
});

// ── ADMIN ROUTES ───────────────────────────────────────────────────────────
Route::prefix('admin')->middleware('auth.role:ADMIN')->controller(AdminController::class)->group(function () {
    Route::get('/dashboard',          'dashboard');
    Route::get('/users',              'users');
    Route::post('/users',             'storeUser');
    Route::post('/users/toggle',      'toggleUserStatus');
    Route::delete('/users/{id}',      'destroyUser');
    Route::get('/products',           'products');
    Route::post('/products',          'storeProduct');
    Route::post('/products/toggle/{id}', 'toggleProductStatus');
    Route::delete('/products/{id}',   'destroyProduct');
    Route::get('/stock',              'stock');
    Route::post('/stock/adjust',      'adjustStock');
    Route::get('/po',                 'po');
    Route::post('/po/approve',        'approvePO');
    Route::delete('/po/{id}',         'destroyPO');
    Route::get('/logs',               'logs');
    Route::get('/grades',             'grades');
    Route::post('/grades',            'storeGrade');
    Route::delete('/grades/{id}',     'destroyGrade');
    // Admin Attendance sub-pages (read + full access)
    Route::get('/attendance/dashboard',   [AttendanceController::class, 'home']);
    Route::get('/attendance/departments', [AttendanceController::class, 'departments']);
    Route::post('/attendance/departments',[AttendanceController::class, 'storeDepartment']);
    Route::delete('/attendance/departments/{id}', [AttendanceController::class, 'destroyDepartment']);
    Route::get('/attendance/workers',     [AttendanceController::class, 'workers']);
    Route::post('/attendance/workers',    [AttendanceController::class, 'storeWorker']);
    Route::delete('/attendance/workers/{id}', [AttendanceController::class, 'destroyWorker']);
    Route::get('/attendance/daily',       [AttendanceController::class, 'daily']);
    Route::post('/attendance/daily',      [AttendanceController::class, 'storeDailyAttendance']);
    Route::get('/attendance/reports',     [AttendanceController::class, 'reports']);
    Route::get('/attendance/reports/worker/{id}', [AttendanceController::class, 'workerReport']);
});

// ── ATTENDANCE ROUTES ──────────────────────────────────────────────────────

Route::prefix('attendance')->middleware('auth.role:ATTENDANCE')->controller(AttendanceController::class)->group(function () {
    Route::get('/home',               'home');
    Route::get('/departments',        'departments');
    Route::post('/departments',       'storeDepartment');
    Route::delete('/departments/{id}','destroyDepartment');
    
    Route::get('/workers',            'workers');
    Route::post('/workers',           'storeWorker');
    Route::delete('/workers/{id}',    'destroyWorker');
    
    Route::get('/daily',              'daily');
    Route::post('/daily',             'storeDailyAttendance');
    Route::get('/team',               'team');
    
    // JSON APIs for SPA views
    Route::get('/api/workers',        'workersJson');
    Route::get('/api/departments',    'departmentsJson');
    Route::get('/api/daily',          'dailyJson');
    
    Route::get('/reports',            'reports');
    Route::get('/reports/worker/{id}','workerReport');
    
    // Standard mobile nav aliases
    Route::get('/action',             'daily');
    Route::get('/history',            'reports');
    Route::get('/profile',            'home'); 
});
