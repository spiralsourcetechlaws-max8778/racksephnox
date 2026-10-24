<?php
namespace Database\Seeders;

use App\Models\TradingPair;
use App\Services\Trading\ChartService;
use Illuminate\Database\Seeder;

class AllIntervalsCandleSeeder extends Seeder
{
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

        $service = new ChartService();

        foreach (['1m', '5m', '15m', '1h', '4h', '1d'] as $interval) {
            // Wipe any existing candles for a clean seed
            \DB::table('trading_candles')
                ->where('pair_id', $pair->id)
                ->where('interval', $interval)
                ->delete();

            $service->seedSyntheticCandles($pair->id, $interval, 500);
            $this->command->info("✅ Seeded 500 candles for {$interval}");
        }
    }
}
