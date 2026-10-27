<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DEPRECATED — superseded by AllIntervalsCandleSeeder.
 *
 * This seeder previously wiped candles on every deploy, destroying real
 * Binance-synced data. It's now a no-op that delegates to the newer
 * non-destructive seeder. Kept as a class to avoid breaking any external
 * references (Console/Kernel.php, tests, etc.).
 */
class TradingCandleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('⏭  TradingCandleSeeder is deprecated — delegating to AllIntervalsCandleSeeder');
        $this->call(AllIntervalsCandleSeeder::class);
    }
}
