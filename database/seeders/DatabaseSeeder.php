<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference seeders, FK-safe order. Every seeder must be idempotent
     * (updateOrCreate / firstOrCreate). Re-running produces identical state.
     *
     * Per-seeder try/catch: one bad seeder does NOT roll back the others.
     * Deploy reports "Seeding failed" but does not crash.
     *
     * Destructive seeders (TradingSeeder, MachinesSeeder, BtcPriceSeeder,
     * CandleSeeder, LotteryPayoutSeeder) are INTENTIONALLY EXCLUDED. They
     * wipe or duplicate user data. Run once manually on a fresh DB only.
     */
    public function run(): void
    {
        $seeders = [
            // Lottery reference data
            LotterySymbolSeeder::class,
            LotteryGameSeeder::class,
            LotteryAchievementSeeder::class,
            LotteryMissionSeeder::class,
            LotteryBonusWheelSeeder::class,

            // Investment products
            InvestmentPlanSeeder::class,
            RXMachineSeriesSeeder::class,
            InvestmentMachineSeriesSeeder::class,

            // Loans
            LoanProductSeeder::class,
            LoanProductSeriesSeeder::class,

            // Trading candles — non-destructive, bootstraps only if empty
            AllIntervalsCandleSeeder::class,

            // Admin user — env-guarded, skips in production if no password
            AdminUserSeeder::class,
        ];

        $failed = [];

        foreach ($seeders as $seeder) {
            try {
                $this->call($seeder);
                $this->command->info("✅ {$seeder}");
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $seeder;
                $this->command->warn(
                    "⚠️  {$seeder} — " . substr($e->getMessage(), 0, 240)
                );
            }
        }

        if (!empty($failed)) {
            $this->command->warn('');
            $this->command->warn('─────────────────────────────────────────────────');
            $this->command->warn(count($failed) . ' seeder(s) failed:');
            foreach ($failed as $s) {
                $this->command->warn('  • ' . $s);
            }
            $this->command->warn('─────────────────────────────────────────────────');
            $this->command->warn('Successful seeders already committed. Fix the failures');
            $this->command->warn('above and redeploy — succeeded seeders will no-op.');
            $this->command->warn('');

            throw new \RuntimeException(count($failed) . ' seeder(s) failed — see warnings above');
        }

        $this->command->info('✅ All seeders completed.');
    }
}
