<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            // Reference data (FK-safe order)
            LotterySymbolSeeder::class,
            LotteryGameSeeder::class,
            LotteryAchievementSeeder::class,
            LotteryMissionSeeder::class,
            LotteryBonusWheelSeeder::class,
            InvestmentPlanSeeder::class,

            // Machine series — full detail (machines + machine_vips)
            RXMachineSeeder::class,             // legacy: existing RX set
            RXMachineSeriesSeeder::class,       // new: 7 RX, 200/500/800 ladder
            InvestmentMachineSeriesSeeder::class,

            // Loan products — full detail
            LoanProductSeeder::class,           // legacy: basic set
            LoanProductSeriesSeeder::class,     // new: 6 real products
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
            $this->command->warn('Successful seeders already committed.');
            $this->command->warn('Fix the failures above and redeploy.');
            $this->command->warn('');

            throw new \RuntimeException(
                count($failed) . ' seeder(s) failed — see warnings above'
            );
        }

        $this->command->info('✅ All seeders completed.');
    }
}
