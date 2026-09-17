<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ─────────────────────────────────────────────────────
        // Safely seed a default trading pair ONLY if the table
        // exists and is empty. This prevents crashes during:
        //   · fresh installs before migrations have run
        //   · CI test runs where the test DB is empty
        //   · artisan commands that boot the app before DB setup
        // ─────────────────────────────────────────────────────
        $this->ensureDefaultTradingPair();

        // Share common data with all views (guarded the same way)
        View::composer('*', function ($view) {
            // Add any view-shared variables here, guarded by Schema::hasTable
            // Example:
            // $view->with('siteName', config('app.name'));
        });
    }

    /**
     * Ensure a default trading pair exists, without crashing the app
     * if the database or table isn't ready yet.
     */
    protected function ensureDefaultTradingPair(): void
    {
        try {
            // Skip if migrations haven't run yet
            if (!Schema::hasTable('trading_pairs')) {
                return;
            }

            // Only create the default pair if none exists
            if (\App\Models\TradingPair::query()->exists()) {
                return;
            }

            \App\Models\TradingPair::create([
                'symbol'           => 'BTCUSDT',
                'base_currency'    => 'BTC',
                'quote_currency'   => 'USDT',
                'min_trade_amount' => 0.0001,
                'max_trade_amount' => 100,
                'tick_size'        => 0.0001,
                'is_active'        => true,
            ]);
        } catch (\Throwable $e) {
            // Never let boot-time failures break the app
            logger()->warning('AppServiceProvider::ensureDefaultTradingPair failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
