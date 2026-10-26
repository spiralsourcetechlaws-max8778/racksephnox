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
        // Register ALL custom middleware aliases here.
        // Laravel 11+ treats this file as authoritative; if an alias
        // is missing here, it throws 'Target class [x] does not exist'.
        $middleware->alias([
            'admin'            => \App\Http\Middleware\Admin::class,
            'onboarding'       => \App\Http\Middleware\Onboarding::class,
            'two-factor'       => \App\Http\Middleware\TwoFactor::class,
            'api.throttle'     => \App\Http\Middleware\ApiThrottle::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'localization'     => \App\Http\Middleware\Localization::class,
            'antifraud'        => \App\Http\Middleware\AntiFraud::class,
            'mpesa.source'     => \App\Http\Middleware\VerifyMpesaSource::class,
        ]);

        // Trust Render's load balancer
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 419 Session Expired gracefully
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
