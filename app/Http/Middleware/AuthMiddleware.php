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
            abort(403, 'Unauthorized. You do not have access to this section.');
        }

        // Share user with all views
        view()->share('authUser', $user);

        return $next($request);
    }
}
