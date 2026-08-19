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
            $slug = $request->segment(1);
            if ($slug && \Illuminate\Support\Facades\Route::has($slug . '.login.show')) {
                return redirect()->route($slug . '.login.show')->with('error', 'Please login to continue.');
            }
            return redirect('/')->with('error', 'Please login to continue.');
        }

        if (!empty($roles) && !in_array($user['role'], $roles)) {
            if (in_array($user['role'], ['SUB_ADMIN', 'STOCK_MANAGER']) && in_array('ADMIN', $roles)) {
                $path = $request->path();
                $method = $request->method();
                $isWrite = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
                
                $segments = explode('/', $path);
                $endpoint = count($segments) > 1 ? $segments[1] : '';
                
                $module = null;
                if ($endpoint === 'dashboard' || $endpoint === 'home') $module = 'dashboard';
                elseif ($endpoint === 'users') $module = 'users';
                elseif ($endpoint === 'products') $module = 'products';
                elseif ($endpoint === 'stock') $module = 'stock';
                elseif ($endpoint === 'po') $module = 'po';
                elseif ($endpoint === 'logs' || $endpoint === 'cashier-logs') $module = 'logs';
                elseif ($endpoint === 'grades') $module = 'grades';
                elseif ($endpoint === 'locations') $module = 'locations';
                elseif ($endpoint === 'categories') $module = 'categories';
                elseif ($endpoint === 'dispatch-activity') $module = 'dispatch';
                elseif ($endpoint === 'cashier-overview' || $endpoint === 'cashier') $module = 'cashier';
                elseif ($endpoint === 'notifications') $module = 'notifications';
                elseif ($endpoint === 'attendance') $module = 'attendance';

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
