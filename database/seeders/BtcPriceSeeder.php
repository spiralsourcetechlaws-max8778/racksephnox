<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BtcPriceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('btc_price_history')->truncate();

        $rows = [];
        $now = now();

        for ($n = 0; $n < 168; $n++) { // 7 days hourly
            $ts = $now->copy()->subHours(167 - $n);
            $rows[] = [
                'price'              => round(38_000 + mt_rand(0, 2_000), 2),
                'price_kes'          => round(5_000_000 + mt_rand(0, 200_000), 2),
                'high'               => round(5_200_000 + mt_rand(0, 100_000), 2),
                'low'                => round(4_900_000 - mt_rand(0, 100_000), 2),
                'volume'             => round(1 + mt_rand(0, 500) / 100.0, 4),
                'percent_change_24h' => round((mt_rand(-400, 400)) / 100.0, 2),
                'recorded_at'        => $ts->toDateTimeString(),
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('btc_price_history')->insert($chunk);
        }

        $this->command->info('✅ Inserted ' . count($rows) . ' BTC price history rows.');
    }
}
