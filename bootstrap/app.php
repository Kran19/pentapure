<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'logout',
            '*/logout',
            'global/logout',
        ]);
        $middleware->web(prepend: [
            \App\Http\Middleware\ScopeSessionBySlug::class,
            \App\Http\Middleware\NoCache::class,
        ]);
        $middleware->alias([
            'auth.role' => \App\Http\Middleware\AuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, $request) {
            return redirect('/login')->with('error', 'Session expired. Please login again.');
        });
    })->create();

