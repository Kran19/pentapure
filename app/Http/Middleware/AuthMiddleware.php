<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = session('auth_user');

        if (!$user) {
            return redirect('/login')->with('error', 'Please login to continue.');
        }

        if (!empty($roles) && !in_array($user['role'], $roles)) {
            if ($user['role'] === 'SUB_ADMIN' && in_array('ADMIN', $roles)) {
                $path = $request->path();
                $method = $request->method();
                $isWrite = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
                
                $module = null;
                if (str_contains($path, 'admin/dashboard') || str_contains($path, 'admin/home')) $module = 'dashboard';
                elseif (str_contains($path, 'admin/users')) $module = 'users';
                elseif (str_contains($path, 'admin/products')) $module = 'products';
                elseif (str_contains($path, 'admin/stock')) $module = 'stock';
                elseif (str_contains($path, 'admin/po')) $module = 'po';
                elseif (str_contains($path, 'admin/logs') || str_contains($path, 'admin/cashier-logs')) $module = 'logs';
                elseif (str_contains($path, 'admin/grades')) $module = 'grades';
                elseif (str_contains($path, 'admin/categories')) $module = 'categories';
                elseif (str_contains($path, 'admin/dispatch-activity')) $module = 'dispatch';
                elseif (str_contains($path, 'admin/cashier-overview') || str_contains($path, 'admin/cashier')) $module = 'cashier';
                elseif (str_contains($path, 'admin/notifications')) $module = 'notifications';
                elseif (str_contains($path, 'admin/attendance')) $module = 'attendance';

                $userPermissions = $user['permissions'] ?? [];
                
                if ($module && !in_array('module_' . $module, $userPermissions)) {
                    abort(403, 'Unauthorized. You do not have access to the ' . ucfirst($module) . ' module.');
                }
                
                if ($isWrite && !in_array('can_manage', $userPermissions)) {
                    abort(403, 'Unauthorized. You only have View-Only permissions.');
                }
            } else {
                abort(403, 'Unauthorized. You do not have access to this section.');
            }
        }

        // Share user with all views
        view()->share('authUser', $user);

        return $next($request);
    }
}
