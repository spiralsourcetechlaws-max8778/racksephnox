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
    public function register(): void {}

    public function boot(): void
    {
        MachineInvestment::observe(MachineInvestmentObserver::class);
        $this->ensureWritableCacheDirs();
        $this->ensureDefaultTradingPair();
    }

    protected function ensureWritableCacheDirs(): void
    {
        $paths = [
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];
        foreach ($paths as $path) {
            try {
                if (!is_dir($path)) @mkdir($path, 0775, true);
                if (!is_writable($path)) @chmod($path, 0775);
                if (!is_writable($path)) {
                    Log::warning('AppServiceProvider: dir not writable', [
                        'path'  => $path,
                        'owner' => function_exists('posix_getpwuid') && fileowner($path) !== false
                                    ? (posix_getpwuid(fileowner($path))['name'] ?? 'unknown') : 'unknown',
                        'perms' => substr(sprintf('%o', @fileperms($path)), -4),
                        'php'   => function_exists('posix_getpwuid')
                                    ? (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown') : 'unknown',
                    ]);
                }
            } catch (\Throwable $e) {}
        }
    }

    protected function ensureDefaultTradingPair(): void
    {
        try {
            if (!Schema::hasTable('trading_pairs')) return;
            if (DB::table('trading_pairs')->count() > 0) return;
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
            Log::warning('AppServiceProvider::ensureDefaultTradingPair failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);
        }
    }
}
