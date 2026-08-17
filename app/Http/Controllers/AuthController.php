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

        if (!$user || !Hash::check($request->password, $user->password)) {
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

    public function logout()
    {
        session()->forget('auth_user');
        return redirect()->route('global.login')->with('success', 'Logged out successfully.');
    }

    public function redirectToRole(?string $role = null)
    {
        $role = $role ?? (session('auth_user')['role'] ?? null);
        $slug = session('auth_user')['login_slug'] ?? strtolower($role ?? '');

        if (in_array($role, ['SUB_ADMIN', 'STOCK_MANAGER'])) {
            $perms = session('auth_user')['permissions'] ?? [];
            if (in_array('module_dashboard', $perms)) return redirect()->route($slug . '.home');
            if (in_array('module_stock', $perms)) return redirect()->route($slug . '.stock');
            if (in_array('module_products', $perms)) return redirect()->route($slug . '.products');
            if (in_array('module_po', $perms)) return redirect()->route($slug . '.po');
            if (in_array('module_dispatch', $perms)) return redirect()->route($slug . '.dispatch');
            if (in_array('module_cashier', $perms)) return redirect()->route($slug . '.cashier_overview');
            if (in_array('module_attendance', $perms)) return redirect()->route($slug . '.attendance.dashboard');
            if (in_array('module_users', $perms)) return redirect()->route($slug . '.users');
            if (in_array('module_grades', $perms)) return redirect()->route($slug . '.grades');
            if (in_array('module_categories', $perms)) return redirect()->route($slug . '.categories');
            if (in_array('module_logs', $perms)) return redirect()->route($slug . '.logs');
            return redirect()->route($slug . '.home');
        }

        return redirect()->route($slug . '.home');
    }

    protected function authenticatedRedirect()
    {
        return $this->redirectToRole();
    }
}
