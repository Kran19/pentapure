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
            return redirect()->route('global.login')->with('error', 'Please login to continue.');
        }

        // Live sync permissions and status from database on every request
        if (isset($user['id'])) {
            $dbUser = \App\Models\User::find($user['id']);
            if ($dbUser) {
                if ($dbUser->status === 'BLOCKED') {
                    session()->forget('auth_user');
                    return redirect()->route('global.login')->with('error', 'Your account has been blocked.');
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
            if (strtolower($segments[0] ?? '') === 'public') {
                array_shift($segments);
            }
            $seg1 = strtolower($segments[0] ?? '');
            $seg2 = strtolower($segments[1] ?? 'home');
            $seg3 = strtolower($segments[2] ?? '');

            $isWrite = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);

            // Skip strict checks for profile, logout, notifications, or GET API helpers
            if (in_array($seg2, ['profile', 'logout']) || $seg1 === 'notifications' || $seg1 === 'logout' || (!$isWrite && str_contains($path, 'api/'))) {
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

            if ($panel === 'admin') {
                if ($seg2 === 'attendance') {
                    $panel = 'attendance';
                    $seg2 = strtolower($segments[2] ?? 'dashboard');
                } elseif ($seg2 === 'cashier') {
                    $panel = 'cashier';
                    $seg2 = strtolower($segments[2] ?? 'action');
                } elseif ($seg2 === 'sales') {
                    $panel = 'sales';
                    $seg2 = strtolower($segments[2] ?? 'home');
                } elseif ($seg2 === 'dispatch') {
                    $panel = 'dispatch';
                    $seg2 = strtolower($segments[2] ?? 'home');
                } elseif (in_array($seg2, ['stock-manager', 'stock_manager'])) {
                    $panel = 'stock_manager';
                    $seg2 = strtolower($segments[2] ?? 'home');
                }
            }

            // Map path to canonical module key
            $moduleKey = $panel . '_' . $seg2;
            
            if ($panel === 'cashier') {
                if ($seg2 === 'categories') $moduleKey = 'cashier_categories';
                elseif (in_array($seg2, ['home', 'bill', 'action'])) $moduleKey = 'cashier_action';
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
                elseif ($seg2 === 'products') $moduleKey = 'stock_manager_products';
                elseif ($seg2 === 'grades') $moduleKey = 'stock_manager_grades';
                elseif ($seg2 === 'locations') $moduleKey = 'stock_manager_locations';
                elseif ($seg2 === 'categories') $moduleKey = 'admin_categories';
                elseif ($seg2 === 'dispatch-activity') $moduleKey = 'admin_dispatch_activity';
                elseif ($seg2 === 'cashier-overview') $moduleKey = 'admin_cashier_overview';
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
            if (is_string($userPermissions)) {
                $userPermissions = json_decode($userPermissions, true) ?: [];
            }

            // Equivalent module keys across Admin and Stock Manager
            $moduleEquivalents = [
                'admin_stock' => ['admin_stock', 'stock_manager_stock'],
                'stock_manager_stock' => ['stock_manager_stock', 'admin_stock'],
                'admin_products' => ['admin_products', 'stock_manager_products'],
                'stock_manager_products' => ['stock_manager_products', 'admin_products'],
                'admin_grades' => ['admin_grades', 'stock_manager_grades'],
                'stock_manager_grades' => ['stock_manager_grades', 'admin_grades'],
                'admin_locations' => ['admin_locations', 'stock_manager_locations'],
                'stock_manager_locations' => ['stock_manager_locations', 'admin_locations'],
                'admin_po' => ['admin_po', 'stock_manager_po'],
                'stock_manager_po' => ['stock_manager_po', 'admin_po'],
                'admin_categories' => ['admin_categories', 'cashier_categories'],
                'cashier_categories' => ['cashier_categories', 'admin_categories'],
                'stock_manager_home' => ['stock_manager_home', 'admin_dashboard'],
                'admin_dashboard' => ['admin_dashboard', 'stock_manager_home'],
            ];

            $keysToCheck = $moduleEquivalents[$moduleKey] ?? [$moduleKey];
            if (!empty($seg2)) {
                $keysToCheck[] = $seg2;
            }
            $keysToCheck = array_values(array_unique(array_filter($keysToCheck)));

            // Check Edit (Write) Access
            $hasEdit = false;
            if (in_array('can_manage', $userPermissions)) {
                $hasEdit = true;
            } else {
                foreach ($keysToCheck as $k) {
                    if (in_array('edit_' . $k, $userPermissions) || in_array('edit_module_' . $k, $userPermissions)) {
                        $hasEdit = true;
                        break;
                    }
                }
            }

            // Check View (Read) Access
            $hasView = false;
            if ($hasEdit || in_array('can_manage', $userPermissions)) {
                $hasView = true;
            } else {
                foreach ($keysToCheck as $k) {
                    if (
                        in_array('view_' . $k, $userPermissions) ||
                        in_array('module_' . $k, $userPermissions) ||
                        in_array($k, $userPermissions)
                    ) {
                        $hasView = true;
                        break;
                    }
                }
            }

            // Fallback for users with no granular permissions configured
            if (empty($userPermissions)) {
                $hasView = (
                    ($user['role'] === 'STOCK_MANAGER' && (str_starts_with($moduleKey, 'stock_manager_') || str_starts_with($moduleKey, 'admin_'))) ||
                    ($user['role'] === 'SUB_ADMIN' && (str_starts_with($moduleKey, 'admin_') || str_starts_with($moduleKey, 'sub_admin_') || str_starts_with($moduleKey, 'stock_manager_'))) ||
                    ($user['role'] === 'CASHIER' && str_starts_with($moduleKey, 'cashier_')) ||
                    ($user['role'] === 'SALES' && str_starts_with($moduleKey, 'sales_')) ||
                    ($user['role'] === 'DISPATCH' && str_starts_with($moduleKey, 'dispatch_')) ||
                    ($user['role'] === 'ATTENDANCE' && str_starts_with($moduleKey, 'attendance_')) ||
                    ($user['role'] === 'RAW' && str_starts_with($moduleKey, 'raw_')) ||
                    ($user['role'] === 'SEMI' && str_starts_with($moduleKey, 'semi_')) ||
                    ($user['role'] === 'FINISHED' && str_starts_with($moduleKey, 'finished_'))
                );
                $hasEdit = $hasView;
            }

            if (!$hasView) {
                if (in_array($seg2, ['home', 'dashboard'])) {
                    $isSubAdmin = ($user['role'] === 'SUB_ADMIN');
                    $routeMap = [
                        'admin_users' => '/' . $seg1 . '/users',
                        'admin_stock' => '/' . $seg1 . '/stock',
                        'stock_manager_stock' => '/' . $seg1 . '/stock',
                        'admin_products' => '/' . $seg1 . '/products',
                        'stock_manager_products' => '/' . $seg1 . '/products',
                        'admin_grades' => '/' . $seg1 . '/grades',
                        'stock_manager_grades' => '/' . $seg1 . '/grades',
                        'admin_locations' => '/' . $seg1 . '/locations',
                        'stock_manager_locations' => '/' . $seg1 . '/locations',
                        'admin_po' => '/' . $seg1 . '/po',
                        'stock_manager_po' => '/' . $seg1 . '/po',
                        'admin_dispatch_activity' => '/' . $seg1 . '/dispatch-activity',
                        'admin_cashier_overview' => '/' . $seg1 . '/cashier-overview',
                        'admin_categories' => '/' . $seg1 . '/categories',
                        'cashier_categories' => '/' . $seg1 . '/categories',
                        'admin_logs' => '/' . $seg1 . '/logs',
                        'admin_notifications' => '/' . $seg1 . '/notifications',
                        'cashier_action' => $isSubAdmin ? '/' . $seg1 . '/cashier/action' : '/cashier2/action',
                        'cashier_history' => $isSubAdmin ? '/' . $seg1 . '/cashier/history' : '/cashier2/history',
                        'cashier_ledger' => $isSubAdmin ? '/' . $seg1 . '/cashier/ledger' : '/cashier2/ledger',
                        'sales_home' => $isSubAdmin ? '/' . $seg1 . '/sales/home' : '/sales/home',
                        'sales_action' => $isSubAdmin ? '/' . $seg1 . '/sales/action' : '/sales/action',
                        'sales_history' => $isSubAdmin ? '/' . $seg1 . '/sales/history' : '/sales/history',
                        'dispatch_home' => $isSubAdmin ? '/' . $seg1 . '/dispatch/home' : '/dispatch/home',
                        'dispatch_action' => $isSubAdmin ? '/' . $seg1 . '/dispatch/action' : '/dispatch/action',
                        'dispatch_history' => $isSubAdmin ? '/' . $seg1 . '/dispatch/history' : '/dispatch/history',
                        'dispatch_report' => $isSubAdmin ? '/' . $seg1 . '/dispatch/report' : '/dispatch/report',
                        'stock_manager_home' => $isSubAdmin ? '/' . $seg1 . '/home' : '/stock_manager/home',
                        'stock_manager_action' => $isSubAdmin ? '/' . $seg1 . '/stock-manager/action' : '/stock_manager/action',
                        'stock_manager_history' => $isSubAdmin ? '/' . $seg1 . '/stock-manager/history' : '/stock_manager/history',
                        'attendance_dashboard' => $isSubAdmin ? '/' . $seg1 . '/attendance/dashboard' : '/attendance/dashboard',
                        'attendance_departments' => $isSubAdmin ? '/' . $seg1 . '/attendance/departments' : '/attendance/departments',
                        'attendance_workers' => $isSubAdmin ? '/' . $seg1 . '/attendance/workers' : '/attendance/workers',
                        'attendance_daily' => $isSubAdmin ? '/' . $seg1 . '/attendance/daily' : '/attendance/daily',
                        'attendance_reports' => $isSubAdmin ? '/' . $seg1 . '/attendance/reports' : '/attendance/reports',
                    ];
                    foreach ($routeMap as $k => $targetUrl) {
                        $equivs = $moduleEquivalents[$k] ?? [$k];
                        foreach ($equivs as $eq) {
                            if (in_array('view_' . $eq, $userPermissions) || in_array('edit_' . $eq, $userPermissions) || in_array('module_' . $eq, $userPermissions) || in_array($eq, $userPermissions)) {
                                return redirect($targetUrl);
                            }
                        }
                    }
                }

                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized. You do not have View access to this section.'], 403);
                }
                abort(403, 'Unauthorized. You do not have View access to this section.');
            }

            $isReadOnly = !$hasEdit;
            view()->share('isReadOnly', $isReadOnly);

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

