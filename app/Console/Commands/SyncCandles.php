<?php

namespace App\Console\Commands;

use App\Models\TradingPair;
use App\Services\Trading\ChartService;
use Illuminate\Console\Command;

class SyncCandles extends Command
{
    protected $signature = 'trading:sync-candles {pair=BTCUSDT} {--interval=all} {--limit=500}';
    protected $description = 'Sync candle data for all intervals from Binance';

    protected array $intervals = ['1m', '5m', '15m', '1h', '4h', '1d'];

    public function handle(ChartService $service): int
    {
        $symbol = $this->argument('pair');
        $pair = TradingPair::where('symbol', $symbol)->first();

        if (!$pair) {
            $this->error("❌ Pair {$symbol} not found. Run: php artisan trading:seed-pairs");
            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $target = $this->option('interval');
        $toSync = $target === 'all' ? $this->intervals : [$target];

        $errors = 0;
        foreach ($toSync as $interval) {
            try {
                $service->syncHistoricalData($pair, $interval, $limit);
                $count = $pair->candles()->where('interval', $interval)->count();
                $this->line("  ✓ {$interval}: {$count} candles");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$interval}: " . $e->getMessage());
                $errors++;
            }
        }

        if ($errors === 0) {
            $this->info("✅ All intervals synced for {$symbol}");
            return self::SUCCESS;
        }

        $this->warn("⚠️  Completed with {$errors} error(s)");
        return self::FAILURE;
    }
}
