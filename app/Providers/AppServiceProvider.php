<?php

namespace App\Providers;

use App\Models\MachineInvestment;
use App\Observers\MachineInvestmentObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        // Model observers
        // ─────────────────────────────────────────────────────
        MachineInvestment::observe(MachineInvestmentObserver::class);

        // ─────────────────────────────────────────────────────
        // Safely seed a default trading pair ONLY if the table
        // exists and is empty. This prevents crashes during:
        //   · fresh installs before migrations have run
        //   · cold boots where the DB isn't reachable yet
        //   · misconfigured env during deploy
        // ─────────────────────────────────────────────────────
        $this->ensureDefaultTradingPair();
    }

    /**
     * Seed BTC/USDT pair if it doesn't exist. Silent no-op when the
     * table is missing or the DB is unreachable — never blocks boot.
     */
    protected function ensureDefaultTradingPair(): void
    {
        try {
            if (!Schema::hasTable('trading_pairs')) {
                return;
            }

            if (DB::table('trading_pairs')->count() > 0) {
                return;
            }

            DB::table('trading_pairs')->insert([
                'symbol'           => 'BTCUSDT',
                'base_currency'    => 'BTC',
                'quote_currency'   => 'USDT',
                'min_trade_amount' => 0.0001,
                'max_trade_amount' => 100,
                'tick_size'        => 0.0001,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let this block boot — log and move on
            Log::warning('AppServiceProvider::ensureDefaultTradingPair failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);
        }
    }
}
