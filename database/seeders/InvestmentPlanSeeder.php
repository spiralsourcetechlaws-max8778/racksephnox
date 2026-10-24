<?php

namespace Database\Seeders;

use App\Models\InvestmentPlan;
use Database\Seeders\Concerns\RecordsSeedRuns;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Investment plans — 4 tiers with every column from the post-ALTER
 * investment_plans schema populated (auto-reinvest, early withdrawal,
 * penalty, reinvestment cycles, infinite flag).
 */
class InvestmentPlanSeeder extends Seeder
{
    use RecordsSeedRuns;

    protected array $plans = [
        ['name' => 'Starter',  'min' => 500,    'max' => 10000,
         'rate' => 1.50, 'days' => 30,  'auto' => false, 'early' => true,
         'penalty' => 20, 'cycles' => 1, 'infinite' => false,
         'desc' => 'Entry-level plan. Daily accrual, monthly exit.'],

        ['name' => 'Growth',   'min' => 5000,   'max' => 50000,
         'rate' => 2.00, 'days' => 60,  'auto' => true,  'early' => true,
         'penalty' => 15, 'cycles' => 3, 'infinite' => false,
         'desc' => 'Compounding plan. Auto-reinvest on maturity.'],

        ['name' => 'Premium',  'min' => 25000,  'max' => 250000,
         'rate' => 2.75, 'days' => 90,  'auto' => true,  'early' => true,
         'penalty' => 10, 'cycles' => 6, 'infinite' => false,
         'desc' => 'High-yield. Extended horizon, lower exit penalty.'],

        ['name' => 'Sovereign','min' => 100000, 'max' => 2000000,
         'rate' => 3.50, 'days' => 180, 'auto' => true,  'early' => false,
         'penalty' => 25, 'cycles' => 12,'infinite' => true,
         'desc' => 'Infinite-cycle plan. No early exit. Highest yield.'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Investment Plans starting...');

        foreach ($this->plans as $p) {
            $start = microtime(true);
            try {
                DB::transaction(function () use ($p) {
                    InvestmentPlan::updateOrCreate(
                        ['name' => $p['name']],
                        [
                            'description'              => $p['desc'],
                            'min_amount'               => $p['min'],
                            'max_amount'               => $p['max'],
                            'daily_interest_rate'      => $p['rate'],
                            'duration_days'            => $p['days'],
                            'is_active'                => true,
                            'allow_auto_reinvest'      => $p['auto'],
                            'allow_early_withdrawal'   => $p['early'],
                            'early_withdrawal_penalty' => $p['penalty'],
                            'max_reinvestment_cycles'  => $p['cycles'],
                            'is_infinite'              => $p['infinite'],
                        ]
                    );
                });
                $ms = round((microtime(true) - $start) * 1000);
                $this->command->info("SEED_OK|InvestmentPlanSeeder|{$p['name']}|{$ms}ms");
                $this->recordSeedRun('InvestmentPlanSeeder', 'ok', $p['name'], $ms);
            } catch (\Throwable $e) {
                $ms = round((microtime(true) - $start) * 1000);
                $this->command->error("SEED_FAIL|InvestmentPlanSeeder|{$p['name']}|" . substr($e->getMessage(), 0, 300));
                $this->recordSeedRun('InvestmentPlanSeeder', 'failed', $p['name'], $ms, $e->getMessage());
            }
        }

        $this->command->info('✅ Investment plans seeded.');
    }
}
