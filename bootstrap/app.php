<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
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
        $middleware->alias([
            'admin'            => \App\Http\Middleware\Admin::class,
            'role'             => \App\Http\Middleware\CheckRole::class,
            'onboarding'       => \App\Http\Middleware\Onboarding::class,
            'two-factor'       => \App\Http\Middleware\TwoFactor::class,
            'api.throttle'     => \App\Http\Middleware\ApiThrottle::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'localization'     => \App\Http\Middleware\Localization::class,
            'antifraud'        => \App\Http\Middleware\AntiFraud::class,
            'mpesa.source'     => \App\Http\Middleware\VerifyMpesaSource::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withSchedule(function (Schedule $schedule) {
        // ─── Machine investment returns ─────────────────────
        $schedule->command('machine:accrue')->dailyAt('00:10');
        $schedule->command('machine:accrue')->hourly(); // extra safety sweep

        // ─── Lottery jobs ───────────────────────────────────
        $schedule->job(new \App\Jobs\Lottery\UpdateTournamentRankings)->everyFiveMinutes();
        $schedule->job(new \App\Jobs\Lottery\DistributeTournamentPrizes)->everyTenMinutes();
        $schedule->job(new \App\Jobs\Lottery\CleanupLotterySpins)->hourly();
        $schedule->job(new \App\Jobs\Lottery\UpdateLeaderboardCache)->everyMinute();
        $schedule->job(new \App\Jobs\Lottery\WarmupLotteryCache)->everyTenMinutes();
        $schedule->job(new \App\Jobs\Lottery\ProcessJackpotContributions)->everyMinute();
        $schedule->job(new \App\Jobs\Lottery\CheckJackpotMustDrop)->everyFiveMinutes();
        $schedule->job(new \App\Jobs\Lottery\ResetDailyMissions)->dailyAt('00:00');
        $schedule->job(new \App\Jobs\Lottery\AwardStreakBonuses)->dailyAt('00:05');

        // ─── Housekeeping ───────────────────────────────────
        $schedule->command('cache:prune-stale-tags')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session expired. Please log in again.',
                    'code'    => 'session_expired',
                ], 419);
            }

            return redirect()->route('login')->with('status', 'Your session expired. Please log in again.');
        });
    })->create();
