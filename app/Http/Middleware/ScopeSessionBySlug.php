<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeSessionBySlug
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->segment(1);
        if ($slug && !in_array($slug, ['css', 'js', 'images', 'api', 'build', 'storage', 'order', 'dispatch', 'pdf'])) {
            config(['session.cookie' => 'pentapure_session_' . $slug]);
            \Illuminate\Support\Facades\URL::defaults(['user_slug' => $slug]);
        }

        return $next($request);
    }
}
