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

        // Live sync permissions and status from database on every request
        if (isset($user['id'])) {
            $dbUser = \App\Models\User::find($user['id']);
            if ($dbUser) {
                if ($dbUser->status === 'BLOCKED') {
                    session()->forget('auth_user');
                    return redirect('/login')->with('error', 'Your account has been blocked.');
                }
                $user['permissions'] = $dbUser->permissions ?? [];
                $user['role'] = $dbUser->role;
                session(['auth_user' => $user]);
            }
        }

        if (!empty($roles) && !in_array($user['role'], $roles)) {
            if (in_array($user['role'], ['SUB_ADMIN', 'STOCK_MANAGER'])) {
                // Allowed into middleware check for granular module evaluation below
            } else {
                abort(403, 'Unauthorized. You do not have access to this section.');
            }
        }

        // Granular permission check for SUB_ADMIN and STOCK_MANAGER
        if (in_array($user['role'], ['SUB_ADMIN', 'STOCK_MANAGER'])) {
            $path = trim($request->path(), '/');
            $segments = explode('/', $path);
            $seg1 = strtolower($segments[0] ?? '');
            $seg2 = strtolower($segments[1] ?? 'home');
            $seg3 = strtolower($segments[2] ?? '');

            // Skip strict checks for profile, logout, notifications API
            if (in_array($seg2, ['profile', 'logout']) || $seg1 === 'notifications' || $seg1 === 'logout' || str_starts_with($path, 'api/')) {
                view()->share('authUser', $user);
                return $next($request);
            }

            // Determine panel type
            $panel = 'admin';
            if (in_array($seg1, ['admin', 'sub_admin', 'stock_manager']) && $seg2 === 'order' && $seg3 === 'pdf') {
                $panel = 'sales';
                $seg2 = 'history';
            } elseif (in_array($seg1, ['admin', 'sub_admin', 'stock_manager']) && $seg2 === 'dispatch' && $seg3 === 'pdf') {
                $panel = 'dispatch';
                $seg2 = 'history';
            } elseif ($seg1 === 'order' && $seg2 === 'pdf') {
                $panel = 'sales';
                $seg2 = 'history';
            } elseif (in_array($seg1, ['admin', 'sub_admin', 'stock_manager']) && in_array($seg2, ['cashier', 'sales', 'dispatch', 'stock-manager', 'stock_manager', 'attendance']) && !empty($seg3)) {
                $panel = str_replace('-', '_', $seg2);
                $seg2 = $seg3;
            } elseif (str_contains($seg1, 'cashier')) $panel = 'cashier';
            elseif (str_contains($seg1, 'sales')) $panel = 'sales';
            elseif (str_contains($seg1, 'dispatch')) $panel = 'dispatch';
            elseif (str_contains($seg1, 'raw')) $panel = 'raw';
            elseif (str_contains($seg1, 'semi')) $panel = 'semi';
            elseif (str_contains($seg1, 'finished')) $panel = 'finished';
            elseif (str_contains($seg1, 'stock_manager')) $panel = 'stock_manager';
            elseif (str_contains($seg1, 'attendance')) $panel = 'attendance';
            elseif ($seg1 === 'admin' || $seg1 === 'sub_admin') $panel = 'admin';
            elseif (str_contains($seg1, 'sales')) $panel = 'sales';
            elseif (str_contains($seg1, 'dispatch')) $panel = 'dispatch';
            elseif (str_contains($seg1, 'raw')) $panel = 'raw';
            elseif (str_contains($seg1, 'semi')) $panel = 'semi';
            elseif (str_contains($seg1, 'finished')) $panel = 'finished';
            elseif (str_contains($seg1, 'stock_manager')) $panel = 'stock_manager';
            elseif (str_contains($seg1, 'attendance')) $panel = 'attendance';
            elseif ($seg1 === 'admin' || $seg1 === 'sub_admin') $panel = 'admin';

            if ($panel === 'admin' && $seg2 === 'attendance') {
                $panel = 'attendance';
                $seg2 = strtolower($segments[2] ?? 'dashboard');
            }

            // Map path to canonical module key
            $moduleKey = $panel . '_' . $seg2;
            
            if ($panel === 'cashier') {
                if (in_array($seg2, ['home', 'bill', 'categories', 'action'])) $moduleKey = 'cashier_action';
                elseif ($seg2 === 'ledger') $moduleKey = 'cashier_ledger';
                elseif ($seg2 === 'history') $moduleKey = 'cashier_history';
            } elseif ($panel === 'sales') {
                if (in_array($seg2, ['order', 'company', 'transport', 'action'])) $moduleKey = 'sales_action';
                elseif ($seg2 === 'history') $moduleKey = 'sales_history';
                elseif ($seg2 === 'home') $moduleKey = 'sales_home';
            } elseif ($panel === 'dispatch') {
                if (in_array($seg2, ['update-lr', 'revert', 'pdf', 'action'])) $moduleKey = 'dispatch_action';
                elseif ($seg2 === 'history') $moduleKey = 'dispatch_history';
                elseif ($seg2 === 'home') $moduleKey = 'dispatch_home';
            } elseif ($panel === 'raw') {
                if (in_array($seg2, ['transfer-to-semi', 'action'])) $moduleKey = 'raw_action';
                elseif ($seg2 === 'po') $moduleKey = 'raw_po';
                elseif ($seg2 === 'history') $moduleKey = 'raw_history';
                elseif ($seg2 === 'home') $moduleKey = 'raw_home';
            } elseif ($panel === 'semi') {
                if (in_array($seg2, ['transfer-to-semi', 'action'])) $moduleKey = 'semi_action';
                elseif ($seg2 === 'po') $moduleKey = 'semi_po';
                elseif ($seg2 === 'history') $moduleKey = 'semi_history';
                elseif ($seg2 === 'home') $moduleKey = 'semi_home';
            } elseif ($panel === 'finished') {
                if (in_array($seg2, ['quick-product', 'action'])) $moduleKey = 'finished_action';
                elseif ($seg2 === 'po') $moduleKey = 'finished_po';
                elseif ($seg2 === 'history') $moduleKey = 'finished_history';
                elseif ($seg2 === 'home') $moduleKey = 'finished_home';
            } elseif ($panel === 'stock_manager') {
                if ($seg2 === 'admin' && $seg3 === 'stock') $moduleKey = 'admin_stock';
                elseif (in_array($seg2, ['outward', 'action'])) $moduleKey = 'stock_manager_action';
                elseif (in_array($seg2, ['stock', 'note'])) $moduleKey = 'stock_manager_stock';
                elseif ($seg2 === 'po') $moduleKey = 'stock_manager_po';
                elseif ($seg2 === 'history') $moduleKey = 'stock_manager_history';
                elseif ($seg2 === 'home') $moduleKey = 'stock_manager_home';
                elseif ($seg2 === 'users') $moduleKey = 'admin_users';
            } elseif ($panel === 'attendance') {
                if (in_array($seg2, ['home', 'dashboard'])) $moduleKey = 'attendance_dashboard';
                elseif ($seg2 === 'departments') $moduleKey = 'attendance_departments';
                elseif (in_array($seg2, ['workers', 'team'])) $moduleKey = 'attendance_workers';
                elseif (in_array($seg2, ['daily', 'action'])) $moduleKey = 'attendance_daily';
                elseif (in_array($seg2, ['reports', 'history'])) $moduleKey = 'attendance_reports';
            } elseif ($panel === 'admin') {
                if (in_array($seg2, ['home', 'dashboard'])) $moduleKey = 'admin_dashboard';
                elseif ($seg2 === 'users') $moduleKey = 'admin_users';
                elseif ($seg2 === 'products') $moduleKey = 'admin_products';
                elseif ($seg2 === 'stock') $moduleKey = 'admin_stock';
                elseif ($seg2 === 'po') $moduleKey = 'admin_po';
                elseif (in_array($seg2, ['logs', 'cashier-logs'])) $moduleKey = 'admin_logs';
                elseif ($seg2 === 'grades') $moduleKey = 'admin_grades';
                elseif ($seg2 === 'locations') $moduleKey = 'admin_locations';
                elseif ($seg2 === 'categories') $moduleKey = 'admin_categories';
                elseif ($seg2 === 'dispatch-activity') $moduleKey = 'admin_dispatch_activity';
                elseif ($seg2 === 'cashier-overview') $moduleKey = 'admin_cashier_overview';
                elseif ($seg2 === 'notifications') $moduleKey = 'admin_notifications';
            }

            $userPermissions = $user['permissions'] ?? [];
            $shortKey = $seg2;

            // Check View Access
            $hasView = in_array('view_' . $moduleKey, $userPermissions)
                || in_array('edit_' . $moduleKey, $userPermissions)
                || in_array('module_' . $moduleKey, $userPermissions)
                || in_array($moduleKey, $userPermissions)
                || in_array('can_manage', $userPermissions)
                || ($seg2 === 'users' && ($user['role'] === 'STOCK_MANAGER' || $user['role'] === 'SUB_ADMIN'));

            if (!$hasView) {
                if (in_array($seg2, ['home', 'dashboard'])) {
                    $routeMap = [
                        'admin_users' => '/' . $seg1 . '/users',
                        'admin_stock' => '/' . $seg1 . '/stock',
                        'admin_products' => '/' . $seg1 . '/products',
                        'admin_grades' => '/' . $seg1 . '/grades',
                        'admin_locations' => '/' . $seg1 . '/locations',
                        'admin_po' => '/' . $seg1 . '/po',
                        'admin_dispatch_activity' => '/' . $seg1 . '/dispatch-activity',
                        'admin_cashier_overview' => '/' . $seg1 . '/cashier-overview',
                        'admin_categories' => '/' . $seg1 . '/categories',
                        'admin_logs' => '/' . $seg1 . '/logs',
                        'admin_notifications' => '/' . $seg1 . '/notifications',
                        'cashier_action' => '/cashier2/action',
                        'cashier_history' => '/cashier2/history',
                        'cashier_ledger' => '/cashier2/ledger',
                        'sales_home' => '/sales/home',
                        'sales_action' => '/sales/action',
                        'sales_history' => '/sales/history',
                        'dispatch_home' => '/dispatch/home',
                        'dispatch_action' => '/dispatch/action',
                        'dispatch_history' => '/dispatch/history',
                        'stock_manager_home' => '/stock_manager/home',
                        'stock_manager_action' => '/stock_manager/action',
                        'stock_manager_stock' => '/stock_manager/stock',
                        'stock_manager_po' => '/stock_manager/po',
                        'stock_manager_history' => '/stock_manager/history',
                        'attendance_dashboard' => '/attendance/dashboard',
                        'attendance_departments' => '/attendance/departments',
                        'attendance_workers' => '/attendance/workers',
                        'attendance_daily' => '/attendance/daily',
                        'attendance_reports' => '/attendance/reports',
                    ];
                    foreach ($routeMap as $k => $targetUrl) {
                        if (in_array('view_' . $k, $userPermissions) || in_array('edit_' . $k, $userPermissions) || in_array('module_' . $k, $userPermissions) || in_array($k, $userPermissions)) {
                            return redirect($targetUrl);
                        }
                    }
                }

                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized. You do not have View access to this section.'], 403);
                }
                abort(403, 'Unauthorized. You do not have View access to this section.');
            }

            // Check Write/Edit Access
            $hasEdit = in_array('edit_' . $moduleKey, $userPermissions)
                || in_array('can_manage', $userPermissions)
                || in_array('edit_module_' . $moduleKey, $userPermissions)
                || in_array('edit_' . $shortKey, $userPermissions)
                || ($seg2 === 'users' && ($user['role'] === 'STOCK_MANAGER' || $user['role'] === 'SUB_ADMIN'));

            $isReadOnly = !$hasEdit;
            view()->share('isReadOnly', $isReadOnly);

            $isWrite = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            if ($isWrite) {
                if (!$hasEdit) {
                    if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized. You only have View-Only permissions for this section.'], 403);
                    }
                    return redirect()->back()->with('error', 'Unauthorized. You only have View-Only permissions for this section.');
                }
            }
        } else {
            view()->share('isReadOnly', false);
        }

        // Share user with all views
        view()->share('authUser', $user);

        return $next($request);
    }
}

