<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Reference data — idempotent, safe on every run ──────
        DB::transaction(function () {
            $this->call([
                LotterySymbolSeeder::class,
                LotteryGameSeeder::class,
                LotteryAchievementSeeder::class,
                LotteryMissionSeeder::class,
                LotteryBonusWheelSeeder::class,

                LoanProductSeeder::class,
                InvestmentPlanSeeder::class,
                RXMachineSeeder::class,
            ]);
        });

        // ─── Trading pairs + candles — overwrite-safe ────────────
        // NOTE: TradingSeeder is DELIBERATELY NOT called — it wipes
        // trading_accounts, trade_orders, copy_trades, etc. Run it
        // manually ONCE via shell on a fresh database if you need the
        // price history it generates.
        DB::transaction(function () {
            $this->call([
                TradingCandleSeeder::class,
                AllIntervalsCandleSeeder::class,
            ]);
        });

        // ─── Admin user — env-guarded ────────────────────────────
        $this->call(AdminUserSeeder::class);

        // ─── Demo data — local only ──────────────────────────────
        if (app()->environment('local', 'development')) {
            $this->call(TestDataSeeder::class);
        }
    }
}
