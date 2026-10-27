<?php

namespace Database\Seeders;

use App\Models\TradingPair;
use App\Models\TradingCandle;
use App\Services\Trading\ChartService;
use Illuminate\Database\Seeder;

/**
 * Bootstrap candle data for fresh installs ONLY.
 *
 * Design rules:
 *   - NEVER wipes existing candles. If the table has data, skip entirely.
 *     The trading:sync-candles scheduler fills real Binance data over time;
 *     this seeder is only here so the chart isn't empty on a fresh DB
 *     before the scheduler's first run.
 *   - Fires at most once per environment. Re-running on deploy is a no-op
 *     because candles already exist by then.
 */
class AllIntervalsCandleSeeder extends Seeder
{
    protected array $intervals = [
        '1m'  => 500,
        '5m'  => 500,
        '15m' => 500,
        '1h'  => 500,
        '4h'  => 500,
        '1d'  => 500,
    ];

    public function run(): void
    {
        $pair = TradingPair::firstOrCreate(
            ['symbol' => 'BTCUSDT'],
            [
                'base_currency'    => 'BTC',
                'quote_currency'   => 'USDT',
                'min_trade_amount' => 0.0001,
                'max_trade_amount' => 100,
                'tick_size'        => 0.0001,
                'is_active'        => 1,
            ]
        );

        $existingCount = TradingCandle::where('pair_id', $pair->id)->count();

        if ($existingCount > 0) {
            $this->command->info("⏭  Candles already present ({$existingCount}) — skipping seed. Scheduler will keep them fresh.");
            return;
        }

        $this->command->info('🌱 Fresh candle table — seeding synthetic baseline...');

        $service = new ChartService();

        foreach ($this->intervals as $interval => $limit) {
            $count = TradingCandle::where('pair_id', $pair->id)
                ->where('interval', $interval)
                ->count();

            if ($count > 0) {
                $this->command->info("   ⏭  {$interval}: {$count} candles exist — skipping");
                continue;
            }

            try {
                $service->seedSyntheticCandles($pair->id, $interval, $limit);
                $this->command->info("   ✅ {$interval}: seeded {$limit} candles");
            } catch (\Throwable $e) {
                $this->command->warn("   ⚠️  {$interval}: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ Total candles: ' . TradingCandle::count());
    }
}
