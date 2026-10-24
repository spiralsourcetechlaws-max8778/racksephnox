<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            LotterySymbolSeeder::class,
            LotteryGameSeeder::class,
            LotteryAchievementSeeder::class,
            LotteryMissionSeeder::class,
            LotteryBonusWheelSeeder::class,
            InvestmentPlanSeeder::class,

            RXMachineSeriesSeeder::class,
            InvestmentMachineSeriesSeeder::class,

            LoanProductSeeder::class,
            LoanProductSeriesSeeder::class,
        ];

        $failed = [];

        foreach ($seeders as $seeder) {
            try {
                $this->call($seeder);
                $this->command->info("✅ {$seeder}");
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $seeder;
                $this->command->warn("⚠️  {$seeder} — " . substr($e->getMessage(), 0, 240));
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

            throw new \RuntimeException(
                count($failed) . ' seeder(s) failed — see warnings above'
            );
        }

        $this->command->info('✅ All seeders completed.');
    }
}
