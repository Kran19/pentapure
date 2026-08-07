<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('auth_user')) {
            return $this->authenticatedRedirect();
        }
        $users = User::where('status', 'ACTIVE')->orderBy('role')->get(['id', 'name', 'role']);
        return view('auth.login', compact('users'));
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

        session(['auth_user' => [
            'id'          => $user->id,
            'name'        => $user->name,
            'role'        => $user->role,
            'permissions' => $user->permissions,
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
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }

    protected function authenticatedRedirect()
    {
        $role = session('auth_user')['role'];
        return match($role) {
            'ADMIN'      => redirect()->route('admin.home'),
            'SUB_ADMIN'  => redirect()->route('admin.home'),
            'RAW'        => redirect()->route('raw.home'),
            'SEMI'       => redirect()->route('semi.home'),
            'FINISHED'   => redirect()->route('finished.home'),
            'SALES'      => redirect()->route('sales.home'),
            'DISPATCH'   => redirect()->route('dispatch.home'),
            'CASHIER'    => redirect()->route('cashier.home'),
            'ATTENDANCE' => redirect()->route('attendance.home'),
            default      => redirect()->route('login'),
        };
    }
}
