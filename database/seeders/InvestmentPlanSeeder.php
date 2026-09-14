<?php

namespace Database\Seeders;

use App\Models\InvestmentPlan;
use Illuminate\Database\Seeder;

class InvestmentPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Plan',
                'description' => 'Entry-level plan with steady daily returns.',
                'min_amount' => 1_000,
                'max_amount' => 50_000,
                'daily_interest_rate' => 1.50,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Growth Plan',
                'description' => 'Balanced plan for accelerated compounding.',
                'min_amount' => 10_000,
                'max_amount' => 250_000,
                'daily_interest_rate' => 2.25,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Plan',
                'description' => 'High-yield plan for serious investors.',
                'min_amount' => 50_000,
                'max_amount' => 1_000_000,
                'daily_interest_rate' => 3.00,
                'duration_days' => 45,
                'is_active' => true,
            ],
            [
                'name' => 'Divine Plan',
                'description' => 'The sacred 88% ROI tier — 14-day cycle.',
                'min_amount' => 100_000,
                'max_amount' => 5_000_000,
                'daily_interest_rate' => 6.28, // 88% / 14 days
                'duration_days' => 14,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            InvestmentPlan::updateOrCreate(['name' => $plan['name']], $plan);
        }

        $this->command->info('✅ Investment plans: ' . InvestmentPlan::count());
    }
}
