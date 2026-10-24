<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineVip;
use Database\Seeders\Concerns\RecordsSeedRuns;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Investment Machine Series — code prefix IM0–IM6.
 * Same tier ladder as RX (200/500/800 doubling), longer durations
 * (30-day cycles) and slightly lower growth rates to reflect
 * income-oriented products.
 *
 * Every machines column populated. Idempotent. Per-machine try/catch.
 */
class InvestmentMachineSeriesSeeder extends Seeder
{
    use RecordsSeedRuns;

    /** code, name, vip1, vip2, vip3, growth_rate, duration, risk, icon, color, desc */
    protected array $machines = [
        ['IM0', 'Seed Vault',      200,   500,   800,   22.00, 30, 'Low',
         'fa-vault', 'from-blue-400 to-indigo-500',
         'Foundation vault. Steady 30-day compounding.'],

        ['IM1', 'Growth Engine',   400,   1000,  1600,  26.00, 30, 'Low',
         'fa-engine', 'from-cyan-400 to-blue-500',
         'Balanced growth. Reinvestment ready.'],

        ['IM2', 'Compound Flow',   800,   2000,  3200,  30.00, 30, 'Medium',
         'fa-water', 'from-teal-400 to-cyan-600',
         'Flowing compound yield. Monthly cycles.'],

        ['IM3', 'Yield Nexus',     1600,  4000,  6400,  34.00, 30, 'Medium',
         'fa-network-wired', 'from-indigo-400 to-purple-500',
         'Interconnected yields. High capital efficiency.'],

        ['IM4', 'Capital Orbits',  3200,  8000,  12800, 38.00, 30, 'Medium-High',
         'fa-satellite', 'from-purple-400 to-pink-500',
         'Orbital capital. Multi-cycle reinvestment.'],

        ['IM5', 'Prime Reserves',  6400,  16000, 25600, 42.00, 30, 'High',
         'fa-gem', 'from-amber-400 to-orange-500',
         'Prime asset reserve. High-yield focus.'],

        ['IM6', 'Sovereign Fund',  12800, 32000, 51200, 48.00, 30, 'High',
         'fa-crown', 'from-gold-400 to-amber-600',
         'Sovereign capital tier. Maximum compounding.'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Investment Machine Series starting...');

        $ok = [];
        $fail = [];

        foreach ($this->machines as [$code, $name, $vip1, $vip2, $vip3, $growth, $duration, $risk, $icon, $color, $desc]) {
            $start = microtime(true);

            try {
                DB::transaction(function () use ($code, $name, $vip1, $vip2, $vip3, $growth, $duration, $risk, $icon, $color, $desc) {
                    $dailyVip1 = round(($vip1 * $growth / 100) / $duration, 2);
                    $dailyVip3 = round(($vip3 * $growth / 100) / $duration, 2);

                    $machine = Machine::updateOrCreate(
                        ['code' => $code],
                        [
                            'name'                     => $name,
                            'description'              => $desc,
                            'vip1_start_amount'        => $vip1,
                            'vip2_start_amount'        => $vip2,
                            'vip3_start_amount'        => $vip3,
                            'duration_days'            => $duration,
                            'growth_rate'              => $growth,
                            'is_active'                => true,
                            'risk_profile'             => $risk,
                            'icon'                     => $icon,
                            'color'                    => $color,
                            'min_daily_profit'         => $dailyVip1,
                            'max_daily_profit'         => $dailyVip3,
                            'referral_bonus_rate'      => 5.00,
                            'early_withdrawal_penalty' => 20.00,
                            'features'                 => json_encode([
                                'Monthly cycles (30 days)',
                                'Reinvestment available',
                                '5% referral bonus',
                                'Early withdrawal allowed (20% penalty)',
                            ]),
                            'total_invested_limit'     => $vip3 * 100,
                            'compound_frequency'       => 1,
                            'min_withdrawal'           => 100.00,
                            'max_withdrawal'           => $vip3 * 10,
                            'bonus_multiplier'         => 1.00,
                            'staking_reward'           => 0.00,
                            'tier_multiplier'          => 1.00,
                        ]
                    );

                    foreach ([1 => $vip1, 2 => $vip2, 3 => $vip3] as $level => $amount) {
                        $daily = round(($amount * $growth / 100) / $duration, 2);

                        MachineVip::updateOrCreate(
                            ['machine_id' => $machine->id, 'level' => $level],
                            [
                                'vip_level'           => $level,
                                'name'                => 'Tier ' . $level,
                                'start_amount'        => $amount,
                                'max_amount'          => $amount * 10,
                                'growth_rate'         => $growth,
                                'duration_days'       => $duration,
                                'daily_profit_min'    => round($daily * 0.85, 2),
                                'daily_profit_max'    => round($daily * 1.15, 2),
                                'bonus_multiplier'    => 1.00 + ($level * 0.08),
                                'referral_bonus_rate' => 5.00,
                                'is_active'           => true,
                            ]
                        );
                    }
                });

                $ms = round((microtime(true) - $start) * 1000);
                $this->command->info("SEED_OK|InvestmentMachineSeriesSeeder|{$code}|{$vip1}/{$vip2}/{$vip3}|{$ms}ms");
                $this->recordSeedRun('InvestmentMachineSeriesSeeder', 'ok', $code, $ms);
                $ok[] = $code;

            } catch (\Throwable $e) {
                $ms = round((microtime(true) - $start) * 1000);
                $msg = substr($e->getMessage(), 0, 400);
                $this->command->error("SEED_FAIL|InvestmentMachineSeriesSeeder|{$code}|{$msg}");
                $this->recordSeedRun('InvestmentMachineSeriesSeeder', 'failed', $code, $ms, $e->getMessage());
                \Log::error("SEED_FAIL|InvestmentMachineSeriesSeeder|{$code}", [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]);
                $fail[] = ['code' => $code, 'error' => $msg];
            }
        }

        $this->command->info('');
        $this->command->info('✅ ' . count($ok) . ' investment machines seeded: ' . implode(', ', $ok));

        if (!empty($fail)) {
            $this->command->warn('⚠️  ' . count($fail) . ' investment machines FAILED:');
            foreach ($fail as $f) {
                $this->command->warn("   {$f['code']} — {$f['error']}");
            }
            throw new \RuntimeException(
                count($fail) . ' investment machines failed: ' . implode(', ', array_column($fail, 'code'))
            );
        }
    }
}
