<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\RecordsSeedRuns;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use RecordsSeedRuns;

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
     *
     * Every run is logged to `seed_runs` (via RecordsSeedRuns) with
     * duration, status and error — so a production deploy has a
     * queryable audit trail independent of log scroll.
     */
    public function run(): void
    {
        $seeders = [
            // Lottery reference data — Family A symbols (matches frontend JS map)
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

            // Trading candles — non-destructive, bootstraps only if empty.
            // Self-creates the BTCUSDT trading pair via firstOrCreate().
            AllIntervalsCandleSeeder::class,

            // Admin user — env-guarded, skips in production if no password
            AdminUserSeeder::class,
        ];

        // Essie admin — only runs when ESSIE_ADMIN_PASSWORD is present.
        // Seeder is itself idempotent (existence check + env guard), so
        // including it unconditionally would also work — but we gate it
        // here so seed_runs reflects "actually attempted" vs "not set".
        if (env('ESSIE_ADMIN_PASSWORD')) {
            $seeders[] = EssieAdminSeeder::class;
        }

        $failed = [];

        foreach ($seeders as $seeder) {
            $start = microtime(true);
            try {
                $this->call($seeder);
                $ms = (int) round((microtime(true) - $start) * 1000);
                $this->recordSeedRun($seeder, 'ok', null, $ms);
                $this->command->info("✅ {$seeder} ({$ms}ms)");
            } catch (\Throwable $e) {
                $ms = (int) round((microtime(true) - $start) * 1000);
                $this->recordSeedRun($seeder, 'failed', null, $ms, $e->getMessage());
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
