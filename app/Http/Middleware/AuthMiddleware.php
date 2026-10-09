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
                if ($dbUser->permissions !== null) {
                    $user['permissions'] = $dbUser->permissions;
                }
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
            $isWrite = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            $moduleKey = $this->resolveModuleKey($request);

            // Skip strict checks for routes that are not module-specific (profile, logout, notifications, etc.)
            if ($moduleKey === null) {
                view()->share('isReadOnly', false);
                view()->share('authUser', $user);
                return $next($request);
            }

            $userPermissions = $user['permissions'] ?? [];
            if (is_string($userPermissions)) {
                $userPermissions = json_decode($userPermissions, true) ?: [];
            }
            if (!is_array($userPermissions)) {
                $userPermissions = [];
            }

            // Normalise permissions for case-insensitive and string matching
            $normalizedPerms = [];
            foreach ($userPermissions as $p) {
                if (is_string($p)) {
                    $normalizedPerms[] = strtolower(trim($p));
                } elseif (is_numeric($p)) {
                    $normalizedPerms[] = (int)$p;
                }
            }

            // Check module equivalents so admin and stock_manager permissions align
            $moduleEquivalents = [
                'sales_order_pdf' => [
                    'sales_order_pdf',
                    'sales_history',
                    'sales_action',
                    'sales_home',
                    'dispatch_report',
                    'dispatch_history',
                    'dispatch_home',
                    'dispatch_action',
                    'admin_dispatch_activity',
                    'admin_dashboard',
                    'stock_manager_home',
                    'stock_manager_stock',
                ],
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
                'sales_history' => ['sales_history', 'dispatch_history', 'dispatch_report'],
                'dispatch_history' => ['dispatch_history', 'sales_history', 'dispatch_report'],
                'dispatch_report' => ['dispatch_report', 'dispatch_history', 'sales_history'],
            ];
            $keysToCheck = $moduleEquivalents[$moduleKey] ?? [$moduleKey];

            // 1. Check Edit (Write) Access
            $hasEdit = false;
            if ($user['role'] === 'STOCK_MANAGER' || in_array('can_manage', $normalizedPerms, true)) {
                $hasEdit = true;
            } else {
                foreach ($keysToCheck as $k) {
                    if (
                        in_array('edit_' . $k, $normalizedPerms, true) ||
                        in_array('edit_module_' . $k, $normalizedPerms, true)
                    ) {
                        $hasEdit = true;
                        break;
                    }
                }
            }

            // 2. Check View (Read) Access: Edit automatically grants View
            $hasView = false;
            if ($hasEdit || in_array('can_manage', $normalizedPerms, true)) {
                $hasView = true;
            } else {
                foreach ($keysToCheck as $k) {
                    if (
                        in_array('view_' . $k, $normalizedPerms, true) ||
                        in_array('module_' . $k, $normalizedPerms, true) ||
                        in_array($k, $normalizedPerms, true)
                    ) {
                        $hasView = true;
                        break;
                    }
                }
            }

            // Fallback for role defaults only if no granular module permissions configured at all
            $hasAnyModulePerm = false;
            foreach ($normalizedPerms as $np) {
                if (is_string($np) && (str_starts_with($np, 'view_') || str_starts_with($np, 'edit_'))) {
                    $hasAnyModulePerm = true;
                    break;
                }
            }

            if (!$hasAnyModulePerm && empty($normalizedPerms)) {
                if ($user['role'] === 'STOCK_MANAGER' && (str_starts_with($moduleKey, 'stock_manager_') || in_array($moduleKey, ['admin_categories', 'cashier_categories']))) {
                    $hasView = true;
                    $hasEdit = true;
                } elseif ($user['role'] === 'SUB_ADMIN' && in_array($moduleKey, ['admin_dashboard', 'stock_manager_home'])) {
                    $hasView = true;
                    $hasEdit = false;
                }
            }

            // Deny if no View access
            if (!$hasView) {
                // If hitting the home/dashboard without view permission, redirect to their first permitted page
                if ($request->isMethod('GET') && in_array($moduleKey, ['admin_dashboard', 'stock_manager_home'])) {
                    $firstUrl = $this->getFirstPermittedUrl($user, $normalizedPerms);
                    if ($firstUrl && $firstUrl !== $request->url()) {
                        return redirect($firstUrl);
                    }
                }

                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized. You do not have View access to this section.'], 403);
                }
                abort(403, 'Unauthorized. You do not have View access to this section.');
            }

            $isReadOnly = !$hasEdit;
            view()->share('isReadOnly', $isReadOnly);

            // Block write requests if user only has View-Only permission
            if ($isWrite && !$hasEdit) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized. You only have View-Only permissions for this section.'], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized. You only have View-Only permissions for this section.');
            }
        } else {
            view()->share('isReadOnly', false);
        }

        // Share user with all views
        view()->share('authUser', $user);

        return $next($request);
    }

    /**
     * Map request path to canonical permission module key
     */
    protected function resolveModuleKey(Request $request): ?string
    {
        $path = trim($request->path(), '/');
        $segments = explode('/', $path);
        if (strtolower($segments[0] ?? '') === 'public') {
            array_shift($segments);
        }

        $seg1 = strtolower($segments[0] ?? '');
        $seg2 = strtolower($segments[1] ?? 'home');
        $seg3 = strtolower($segments[2] ?? '');
        $seg4 = strtolower($segments[3] ?? '');

        // Skip auth/session helpers
        if (in_array($seg1, ['logout', 'notifications']) || in_array($seg2, ['profile', 'logout'])) {
            return null;
        }
        if ($seg1 === 'api' && in_array($seg2, ['notifications', 'locations'])) {
            return null;
        }

        // Check if $seg1 is a role or user slug prefix (e.g. admin, sub_admin, sub_admin2, cashier, etc.)
        $isRoleSlug = preg_match('/^(admin|sub_admin|stock_manager|cashier|sales|dispatch|attendance|raw|semi|finished)\d*$/i', $seg1);

        if ($isRoleSlug) {
            $prefix = preg_replace('/\d+$/', '', $seg1);
            $section = $seg2;
            $action = $seg3;
            $subaction = $seg4;
        } else {
            $prefix = '';
            $section = $seg1;
            $action = $seg2;
            $subaction = $seg3;
        }

        // 1. Attendance module
        if ($section === 'attendance' || $prefix === 'attendance') {
            $attSub = ($section === 'attendance') ? $action : $section;
            if (in_array($attSub, ['dashboard', 'home', ''])) return 'attendance_dashboard';
            if (in_array($attSub, ['departments', 'department'])) return 'attendance_departments';
            if (in_array($attSub, ['workers', 'worker', 'team'])) return 'attendance_workers';
            if (in_array($attSub, ['daily', 'clear'])) return 'attendance_daily';
            if (in_array($attSub, ['reports', 'history', 'report'])) return 'attendance_reports';
            return 'attendance_dashboard';
        }

        // 2. Cashier module
        if ($section === 'cashier' || $prefix === 'cashier') {
            $cashierSub = ($section === 'cashier') ? $action : $section;
            if (in_array($cashierSub, ['action', 'home', 'bill'])) return 'cashier_action';
            if (in_array($cashierSub, ['history'])) return 'cashier_history';
            if (in_array($cashierSub, ['ledger'])) return 'cashier_ledger';
            if (in_array($cashierSub, ['categories', 'category'])) return 'admin_categories';
            if (in_array($cashierSub, ['products'])) return 'admin_products';
            if (in_array($cashierSub, ['grades'])) return 'admin_grades';
            if (in_array($cashierSub, ['locations'])) return 'admin_locations';
            if (in_array($cashierSub, ['stock'])) return 'admin_stock';
            return 'cashier_action';
        }

        // 3. Sales module
        if ($section === 'sales' || $prefix === 'sales') {
            $salesSub = ($section === 'sales') ? $action : $section;
            if (in_array($salesSub, ['home', 'dashboard', ''])) return 'sales_home';
            if ($salesSub === 'order' && ($subaction === 'pdf' || in_array('pdf', $segments))) return 'sales_order_pdf';
            if (in_array($salesSub, ['action', 'order', 'company', 'transport'])) return 'sales_action';
            if (in_array($salesSub, ['history', 'download-lr', 'download-multiple-lr'])) return 'sales_history';
            return 'sales_action';
        }
        if (in_array($section, ['order', 'company', 'transport'])) {
            return ($action === 'pdf' || $subaction === 'pdf' || in_array('pdf', $segments)) ? 'sales_order_pdf' : 'sales_action';
        }

        // 4. Dispatch module
        if ($section === 'dispatch' || $prefix === 'dispatch') {
            $dispSub = ($section === 'dispatch') ? $action : $section;
            if ($dispSub === 'order' && in_array('pdf', $segments)) return 'sales_order_pdf';
            if (in_array($dispSub, ['home', 'dashboard', ''])) return 'dispatch_home';
            if (in_array($dispSub, ['action', 'update-lr', 'revert'])) return 'dispatch_action';
            if (in_array($dispSub, ['history', 'pdf', 'download-lr', 'download-multiple-lr'])) return 'dispatch_history';
            if (in_array($dispSub, ['report'])) return 'dispatch_report';
            return 'dispatch_action';
        }

        // 5. Stock Manager panel routes
        if (in_array($section, ['stock-manager', 'stock_manager']) || $prefix === 'stock_manager') {
            $smSub = in_array($section, ['stock-manager', 'stock_manager']) ? $action : $section;
            if (in_array($smSub, ['home', 'dashboard', ''])) return 'stock_manager_home';
            if (in_array($smSub, ['action', 'outward'])) return 'stock_manager_action';
            if (in_array($smSub, ['stock', 'live', 'adjust', 'limit', 'delete', 'bulk-add', 'note'])) return 'stock_manager_stock';
            if (in_array($smSub, ['po'])) return 'stock_manager_po';
            if (in_array($smSub, ['history'])) return 'stock_manager_history';
            if (in_array($smSub, ['products'])) return 'stock_manager_products';
            if (in_array($smSub, ['grades'])) return 'stock_manager_grades';
            if (in_array($smSub, ['locations'])) return 'stock_manager_locations';
            if (in_array($smSub, ['categories'])) return 'admin_categories';
            if (in_array($smSub, ['dispatch-activity'])) return 'admin_dispatch_activity';
            if (in_array($smSub, ['cashier-overview'])) return 'admin_cashier_overview';
            if (in_array($smSub, ['users'])) return 'admin_users';
            return 'stock_manager_home';
        }

        // 6. Admin Panel routes (or under admin / sub_admin prefix)
        if (in_array($section, ['home', 'dashboard', ''])) {
            if ($prefix === 'sub_admin' && in_array($section, ['home', ''])) {
                return 'stock_manager_home';
            }
            return 'admin_dashboard';
        }
        if (in_array($section, ['action', 'outward'])) return 'stock_manager_action';
        if (in_array($section, ['history'])) {
            if ($action === 'dispatch' || $subaction === 'dispatch') return 'dispatch_history';
            if ($action === 'sales' || $subaction === 'sales') return 'sales_history';
            return 'stock_manager_history';
        }
        if (in_array($section, ['users'])) return 'admin_users';
        if (in_array($section, ['products'])) return 'admin_products';
        if (in_array($section, ['stock'])) return 'admin_stock';
        if (in_array($section, ['po'])) return 'admin_po';
        if (in_array($section, ['logs', 'cashier-logs'])) return 'admin_logs';
        if (in_array($section, ['grades'])) return 'admin_grades';
        if (in_array($section, ['locations'])) return 'admin_locations';
        if (in_array($section, ['dispatch-activity'])) return 'admin_dispatch_activity';
        if (in_array($section, ['cashier-overview'])) return 'admin_cashier_overview';
        if (in_array($section, ['categories'])) return 'admin_categories';
        if (in_array($section, ['notifications'])) return 'admin_notifications';

        return null;
    }

    /**
     * Get the first permitted URL for a user to redirect to
     */
    protected function getFirstPermittedUrl(array $user, array $normalizedPerms): ?string
    {
        $slug = $user['login_slug'] ?? strtolower($user['role'] ?? 'sub_admin');
        $isSubAdmin = ($user['role'] === 'SUB_ADMIN');

        $routeMap = [
            'admin_dashboard'         => '/' . $slug . '/dashboard',
            'admin_users'             => '/' . $slug . '/users',
            'admin_stock'             => '/' . $slug . '/stock',
            'stock_manager_stock'     => $isSubAdmin ? '/' . $slug . '/stock-manager/stock' : '/stock_manager/stock',
            'admin_products'          => '/' . $slug . '/products',
            'stock_manager_products'  => $isSubAdmin ? '/' . $slug . '/stock-manager/products' : '/stock_manager/products',
            'admin_grades'            => '/' . $slug . '/grades',
            'stock_manager_grades'    => $isSubAdmin ? '/' . $slug . '/stock-manager/grades' : '/stock_manager/grades',
            'admin_locations'         => '/' . $slug . '/locations',
            'stock_manager_locations' => $isSubAdmin ? '/' . $slug . '/stock-manager/locations' : '/stock_manager/locations',
            'admin_po'                => '/' . $slug . '/po',
            'stock_manager_po'        => $isSubAdmin ? '/' . $slug . '/stock-manager/po' : '/stock_manager/po',
            'admin_dispatch_activity' => '/' . $slug . '/dispatch-activity',
            'admin_cashier_overview'  => '/' . $slug . '/cashier-overview',
            'admin_categories'        => '/' . $slug . '/categories',
            'admin_logs'              => '/' . $slug . '/logs',
            'admin_notifications'     => '/' . $slug . '/notifications',
            'cashier_action'          => $isSubAdmin ? '/' . $slug . '/cashier/action' : '/cashier2/action',
            'cashier_history'         => $isSubAdmin ? '/' . $slug . '/cashier/history' : '/cashier2/history',
            'cashier_ledger'          => $isSubAdmin ? '/' . $slug . '/cashier/ledger' : '/cashier2/ledger',
            'sales_home'              => $isSubAdmin ? '/' . $slug . '/sales/home' : '/sales/home',
            'sales_action'            => $isSubAdmin ? '/' . $slug . '/sales/action' : '/sales/action',
            'sales_history'           => $isSubAdmin ? '/' . $slug . '/sales/history' : '/sales/history',
            'dispatch_home'           => $isSubAdmin ? '/' . $slug . '/dispatch/home' : '/dispatch/home',
            'dispatch_action'         => $isSubAdmin ? '/' . $slug . '/dispatch/action' : '/dispatch/action',
            'dispatch_history'        => $isSubAdmin ? '/' . $slug . '/dispatch/history' : '/dispatch/history',
            'dispatch_report'         => $isSubAdmin ? '/' . $slug . '/dispatch/report' : '/dispatch/report',
            'stock_manager_home'      => $isSubAdmin ? '/' . $slug . '/home' : '/stock_manager/home',
            'stock_manager_action'    => $isSubAdmin ? '/' . $slug . '/stock-manager/action' : '/stock_manager/action',
            'stock_manager_history'   => $isSubAdmin ? '/' . $slug . '/stock-manager/history' : '/stock_manager/history',
            'attendance_dashboard'    => $isSubAdmin ? '/' . $slug . '/attendance/dashboard' : '/attendance/dashboard',
            'attendance_daily'        => $isSubAdmin ? '/' . $slug . '/attendance/daily' : '/attendance/daily',
            'attendance_departments'  => $isSubAdmin ? '/' . $slug . '/attendance/departments' : '/attendance/departments',
            'attendance_workers'      => $isSubAdmin ? '/' . $slug . '/attendance/workers' : '/attendance/workers',
            'attendance_reports'      => $isSubAdmin ? '/' . $slug . '/attendance/reports' : '/attendance/reports',
        ];

        foreach ($routeMap as $k => $targetUrl) {
            if (
                in_array('view_' . $k, $normalizedPerms, true) ||
                in_array('edit_' . $k, $normalizedPerms, true) ||
                in_array('module_' . $k, $normalizedPerms, true) ||
                in_array($k, $normalizedPerms, true)
            ) {
                return $targetUrl;
            }
        }

        return null;
    }
}

