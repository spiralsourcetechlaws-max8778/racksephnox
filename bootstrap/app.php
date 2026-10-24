<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Preserve existing custom middleware alias
        $middleware->alias([
            'antifraud' => \App\Http\Middleware\AntiFraud::class,
        ]);

        // Trust Render's load balancer so $request->isSecure() returns true
        // even though TLS terminates at Render and HTTP reaches the container.
        // This is required only if SESSION_SECURE_COOKIE=true (not set today),
        // but harmless and correct to have.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ─── 419 — Session/CSRF token expired ────────────────────────────
        // Replaces the raw "419 Page Expired" screen with a redirect to
        // /login and a user-friendly flash message. Fires when a form was
        // open longer than SESSION_LIFETIME (default 120 min).
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and log in again.',
                    'code'    => 'session_expired',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('status', 'Your session expired. Please log in again.');
        });
    })->create();
