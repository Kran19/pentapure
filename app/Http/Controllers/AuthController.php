<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (session('auth_user')) {
            return $this->authenticatedRedirect();
        }
        $users = User::where('status', 'ACTIVE')->orderBy('role')->orderBy('id')->get(['id', 'name', 'role']);
        $slug = $request->segment(1);

        // Calculate role-based slugs for each user (e.g., raw, raw2, raw3)
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

        $selectedUser = $users->firstWhere('login_slug', strtolower($slug)) ?? $users->firstWhere('login_slug', $slug);
        
        return view('auth.login', compact('users', 'slug', 'selectedUser'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'password' => 'required|string|min:4',
        ]);

        $user = User::find($request->user_id);

        if (!$user || !(Hash::check($request->password, $user->password) || Hash::check(strtolower($request->password), $user->password) || Hash::check(strtoupper($request->password), $user->password))) {
            return back()->with('error', 'Invalid password. Please try again.')->withInput();
        }

        if ($user->status === 'BLOCKED') {
            return back()->with('error', 'Your account has been blocked. Contact Admin.');
        }

        // Calculate login_slug to store in session
        $allUsers = User::where('status', 'ACTIVE')->orderBy('role')->orderBy('id')->get();
        $roleCounts = [];
        $login_slug = strtolower($user->role);
        foreach ($allUsers as $u) {
            $r = strtolower($u->role);
            if (!isset($roleCounts[$r])) {
                $roleCounts[$r] = 1;
                $u->login_slug = $r;
            } else {
                $roleCounts[$r]++;
                $u->login_slug = $r . $roleCounts[$r];
            }
            if ($u->id === $user->id) {
                $login_slug = $u->login_slug;
            }
        }

        session(['auth_user' => [
            'id'          => $user->id,
            'name'        => $user->name,
            'role'        => $user->role,
            'permissions' => $user->permissions,
            'login_slug'  => $login_slug,
        ]]);

        // Save Push Subscription if provided during login
        if ($request->push_subscription) {
            \Log::info('Login with Push Subscription detected for user: ' . $user->name);
            $sub = json_decode($request->push_subscription, true);
            if (isset($sub['endpoint'], $sub['keys']['p256dh'], $sub['keys']['auth'])) {
                $user->updatePushSubscription(
                    $sub['endpoint'],
                    $sub['keys']['p256dh'],
                    $sub['keys']['auth']
                );
                \Log::info('Push Subscription updated for user: ' . $user->name);
            } else {
                \Log::error('Invalid Push Subscription data format during login');
            }
        }

        return $this->authenticatedRedirect();
    }

    public function logout(\Illuminate\Http\Request $request = null)
    {
        session()->forget('auth_user');
        session()->flush();
        if ($request && $request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        if (\Illuminate\Support\Facades\Auth::check()) {
            \Illuminate\Support\Facades\Auth::logout();
        }
        return redirect()->route('global.login')->with('success', 'Logged out successfully.');
    }

    public function redirectToRole(?string $role = null)
    {
        $role = $role ?? (session('auth_user')['role'] ?? null);
        $slug = session('auth_user')['login_slug'] ?? strtolower($role ?? '');

        if (in_array($role, ['SUB_ADMIN', 'STOCK_MANAGER'])) {
            $perms = session('auth_user')['permissions'] ?? [];

            $hasPerm = function($modKey) use ($perms) {
                return in_array('view_' . $modKey, $perms) 
                    || in_array('edit_' . $modKey, $perms)
                    || in_array('module_' . $modKey, $perms)
                    || in_array($modKey, $perms)
                    || in_array('can_manage', $perms);
            };

            $routeMap = [
                'admin_dashboard' => $slug . '.home',
                'admin_users' => $slug . '.users',
                'admin_stock' => $slug . '.stock',
                'admin_products' => $slug . '.products',
                'admin_grades' => $slug . '.grades',
                'admin_locations' => $slug . '.locations',
                'admin_po' => $slug . '.po',
                'admin_dispatch_activity' => $slug . '.dispatch.activity',
                'admin_cashier_overview' => $slug . '.cashier_overview',
                'admin_categories' => $slug . '.categories',
                'admin_logs' => $slug . '.logs',
                'admin_notifications' => $slug . '.notifications',
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

            foreach ($routeMap as $key => $target) {
                if ($hasPerm($key)) {
                    if (str_starts_with($target, '/')) {
                        return redirect($target);
                    }
                    if (\Illuminate\Support\Facades\Route::has($target)) {
                        return redirect()->route($target);
                    }
                }
            }
        }

        return redirect()->route($slug . '.home');
    }

    protected function authenticatedRedirect()
    {
        return $this->redirectToRole();
    }
}
