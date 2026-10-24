<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference seeders, FK-safe order. Each is expected to be idempotent
     * (updateOrCreate / firstOrCreate) so repeated runs are no-ops.
     *
     * IMPORTANT: this list runs WITHOUT an outer DB::transaction. If seeder
     * N fails, seeders 1..N-1 have already committed. That's intentional —
     * partial reference data is better than a total rollback because one
     * seeder hit a schema gap. The failure is logged, remaining seeders
     * continue, and the whole run exits nonzero so entrypoint.sh reports
     * "seeding failed" while the successfully-seeded data stays in place.
     *
     * If a specific seeder is broken in production, set SKIP_SEED=1 in the
     * Render environment to skip the whole seeding step on next deploy,
     * then fix the seeder and remove the env var.
     */
    public function run(): void
    {
        $seeders = [
            LotterySymbolSeeder::class,
            LotteryGameSeeder::class,
            LotteryAchievementSeeder::class,
            LotteryMissionSeeder::class,
            LotteryBonusWheelSeeder::class,
            LoanProductSeeder::class,
            InvestmentPlanSeeder::class,
            RXMachineSeeder::class,
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
            $this->command->warn('Successful seeders already committed. Fix the');
            $this->command->warn('failures above and redeploy — succeeded seeders');
            $this->command->warn('will no-op, failed ones will retry.');
            $this->command->warn('');

            // Exit nonzero so entrypoint.sh reports "Seeding failed".
            // Container still boots — entrypoint handles the exception.
            throw new \RuntimeException(
                count($failed) . ' seeder(s) failed — see warnings above'
            );
        }

        $this->command->info('✅ All seeders completed.');
    }
}
