<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CandleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure trading pair #1 exists
        DB::table('trading_pairs')->updateOrInsert(
            ['id' => 1],
            [
                'symbol' => 'BTCUSDT',
                'base_currency' => 'BTC',
                'quote_currency' => 'USDT',
                'min_trade_amount' => 0.0001,
                'max_trade_amount' => 100,
                'tick_size' => 0.0001,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Wipe existing candles for this pair
        DB::table('trading_candles')->where('pair_id', 1)->delete();

        // 3. Build 720 hourly candles
        $rows = [];
        $now = now();
        $base = 5_000_000.0;

        for ($n = 0; $n < 720; $n++) {
            $openTime  = $now->copy()->subHours(719 - $n);
            $closeTime = $openTime->copy()->addHour();

            $wave  = 50_000 * (($n % 48) / 48.0);
            $open  = round($base + $wave + mt_rand(-3000, 3000), 2);
            $close = round($open + mt_rand(-15000, 15000), 2);
            $high  = round(max($open, $close) + mt_rand(0, 20000), 2);
            $low   = round(min($open, $close) - mt_rand(0, 20000), 2);
            $vol   = round(0.5 + mt_rand(0, 500) / 100.0, 4);

            $rows[] = [
                'pair_id'    => 1,
                'interval'   => '1h',
                'open_time'  => $openTime->toDateTimeString(),
                'close_time' => $closeTime->toDateTimeString(),
                'open'       => $open,
                'high'       => $high,
                'low'        => $low,
                'close'      => $close,
                'volume'     => $vol,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('trading_candles')->insert($chunk);
        }

        $this->command->info('✅ Inserted ' . count($rows) . ' candles.');
    }
}
