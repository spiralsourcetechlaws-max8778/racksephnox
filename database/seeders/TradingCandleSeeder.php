<?php

namespace Database\Seeders;

use App\Models\TradingPair;
use App\Services\Trading\ChartService;
use Illuminate\Database\Seeder;

class TradingCandleSeeder extends Seeder
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

        foreach (['1m' => 500, '5m' => 500, '15m' => 500, '1h' => 500, '4h' => 500, '1d' => 365] as $interval => $limit) {
            \DB::table('trading_candles')
                ->where('pair_id', $pair->id)
                ->where('interval', $interval)
                ->delete();

            $service->seedSyntheticCandles($pair->id, $interval, $limit);
            $this->command->info("   ✅ {$interval}: {$limit} candles");
        }

        $this->command->info('✅ Total candles: ' . \DB::table('trading_candles')->count());
    }
}
