<?php

$content = file_get_contents('d:\pentapure\routes\web_backup.php');

$header = <<<EOT
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
    \$users = \App\Models\User::where('status', 'ACTIVE')->orderBy('role')->orderBy('id')->get();
    \$roleCounts = [];
    foreach (\$users as \$u) {
        \$r = strtolower(\$u->role);
        if (!isset(\$roleCounts[\$r])) {
            \$roleCounts[\$r] = 1;
            \$u->login_slug = \$r;
        } else {
            \$roleCounts[\$r]++;
            \$u->login_slug = \$r . \$roleCounts[\$r];
        }
    }
} catch (\Exception \$e) {
    \$users = collect();
}

\$roleSlugs = [];
foreach(\$users as \$u) {
    \$roleSlugs[\$u->role][] = \$u->login_slug;
}

// Global push notifications route (maps to all slugs for backward compatibility or just use a generic route)
Route::post('/notifications/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe']);
Route::post('/notifications/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe']);
Route::get('/notifications/test', function() {
    \$user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();
    if (!\$user) return 'Not logged in';
    \Log::info('Triggering Test Notification for: ' . \$user->name);
    return 'Notification sent to ' . \$user->name . ' (Role: ' . \$user->role . ')';
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
        \$user = session('auth_user') ? \App\Models\User::find(session('auth_user')['id']) : auth()->user();
        if (!\$user) return response()->json(['exists' => false]);
        \$subs = \$user->pushSubscriptions;
        return response()->json([
            'exists' => \$subs->count() > 0,
            'count' => \$subs->count(),
            'endpoint' => \$subs->first() ? \$subs->first()->endpoint : null
        ]);
    });
    
    Route::post('/admin/notifications/send', [\App\Http\Controllers\AdminController::class, 'sendNotification'])->middleware('auth.role:ADMIN');
});

EOT;

preg_match('/Route::prefix\(\'raw\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $rawMatch);
preg_match('/Route::prefix\(\'semi\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $semiMatch);
preg_match('/Route::prefix\(\'finished\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $finishedMatch);
preg_match('/Route::prefix\(\'sales\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $salesMatch);
preg_match('/Route::prefix\(\'dispatch\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $dispatchMatch);
preg_match('/Route::prefix\(\'cashier\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $cashierMatch);
preg_match('/Route::prefix\(\'admin\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $adminMatch);
preg_match('/Route::prefix\(\'attendance\'\)->.*?group\(function \(\) \{(.*?)\n\s*\}\);/s', $content, $attendanceMatch);

preg_match('/(\/\/ Admin can also generate any cashier.*?name\(\'cashier\.bill\.view\'\);)/s', $content, $specialMatch);

$output = $header . PHP_EOL;

function writeRoleBlock($role, $controller, $match, $isSpecial = false, $specialStr = '') {
    global $output;
    if (!isset($match[1])) return;

    $output .= "// === $role ROUTES ===\n";
    $output .= "foreach (\$roleSlugs['$role'] ?? [] as \$slug) {\n";
    
    $output .= "    Route::prefix(\$slug)->group(function () use (\$slug) {\n";
    $output .= "        Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name(\$slug.'.login.show');\n";
    $output .= "        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name(\$slug.'.login.post');\n";
    $output .= "        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name(\$slug.'.logout');\n";
    $output .= "    });\n\n";

    $output .= "    Route::prefix(\$slug)->middleware('auth.role:$role')->controller($controller)->group(function () use (\$slug) {\n";
    
    $inner = $match[1];
    $roleLower = strtolower($role);
    $inner = preg_replace("/->name\(\'({$roleLower})\.([^\']+)\'\)/", "->name(\$slug.'.\$2')", $inner);

    $output .= $inner . "\n";
    $output .= "    });\n";
    
    if ($isSpecial && $specialStr) {
        $specialReplaced = preg_replace("/->name\(\'admin\.([^\']+)\'\)/", "->name(\$slug.'.\$1')", $specialStr);
        $specialReplaced = str_replace("Route::get('/admin/", "Route::get('/", $specialReplaced);
        $specialReplaced = preg_replace("/Route::(get|post|put|delete)\(\'(.*?)\'/", "Route::\$1('/\$slug\$2'", $specialReplaced);
        $output .= "\n    " . trim($specialReplaced) . "\n";
    }
    
    $output .= "}\n\n";
}

writeRoleBlock('RAW', 'RawController::class', $rawMatch);
writeRoleBlock('SEMI', 'SemiController::class', $semiMatch);
writeRoleBlock('FINISHED', 'FinishedController::class', $finishedMatch);
writeRoleBlock('SALES', 'SalesController::class', $salesMatch);
writeRoleBlock('DISPATCH', 'DispatchController::class', $dispatchMatch);
writeRoleBlock('CASHIER', 'CashierController::class', $cashierMatch);
writeRoleBlock('ADMIN', 'AdminController::class', $adminMatch, true, $specialMatch[1] ?? '');
writeRoleBlock('ATTENDANCE', 'AttendanceController::class', $attendanceMatch);

file_put_contents('d:\pentapure\routes\web.php', $output);
echo "web.php regenerated successfully.\n";
