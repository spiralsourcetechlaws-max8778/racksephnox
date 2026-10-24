<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineVip;
use Database\Seeders\Concerns\RecordsSeedRuns;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RXMachineSeriesSeeder extends Seeder
{
    use RecordsSeedRuns;

    protected array $machines = [
        ['RX0', 'Spiral Origin', 200,   500,   800,   25.00, 14, 'Low',
         'fa-seedling', 'from-emerald-400 to-teal-500',
         'Genesis portal. Entry into the sacred spiral.'],
        ['RX1', 'Aurora Portal', 400,   1000,  1600,  28.00, 14, 'Low',
         'fa-sun', 'from-yellow-400 to-amber-500',
         'Dawn resonance. Amplifies golden ratio gains.'],
        ['RX2', 'Nova Spiral',   800,   2000,  3200,  32.00, 14, 'Medium',
         'fa-star', 'from-orange-400 to-red-500',
         'Stellar ignition. Exponential spiral expansion.'],
        ['RX3', 'Zenith Gate',   1600,  4000,  6400,  36.00, 14, 'Medium',
         'fa-mountain', 'from-red-400 to-rose-500',
         'Apex threshold. Compounding power unlocked.'],
        ['RX4', 'Infinity Ring', 3200,  8000,  12800, 40.00, 14, 'Medium-High',
         'fa-infinity', 'from-purple-400 to-indigo-500',
         'Endless loop. Continuous reinvestment wave.'],
        ['RX5', 'Quantum Loop',  6400,  16000, 25600, 45.00, 14, 'High',
         'fa-atom', 'from-cyan-400 to-blue-600',
         'Quantum resonance. High-yield spiral.'],
        ['RX6', 'Transcend Ark', 12800, 32000, 51200, 50.00, 14, 'High',
         'fa-rocket', 'from-gold-400 to-amber-500',
         'Ultimate vessel. Transcend capital limits.'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 RX Machine Series starting...');
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
                                'Daily profit payouts',
                                'Compound interest available',
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
                                'name'                => 'VIP ' . $level,
                                'start_amount'        => $amount,
                                'max_amount'          => $amount * 10,
                                'growth_rate'         => $growth,
                                'duration_days'       => $duration,
                                'daily_profit_min'    => round($daily * 0.80, 2),
                                'daily_profit_max'    => round($daily * 1.20, 2),
                                'bonus_multiplier'    => 1.00 + ($level * 0.10),
                                'referral_bonus_rate' => 5.00,
                                'is_active'           => true,
                            ]
                        );
                    }
                });

                $ms = round((microtime(true) - $start) * 1000);
                $this->command->info("SEED_OK|RXMachineSeriesSeeder|{$code}|{$vip1}/{$vip2}/{$vip3}|{$ms}ms");
                $this->recordSeedRun('RXMachineSeriesSeeder', 'ok', $code, $ms);
                $ok[] = $code;
            } catch (\Throwable $e) {
                $ms = round((microtime(true) - $start) * 1000);
                $msg = substr($e->getMessage(), 0, 400);
                $this->command->error("SEED_FAIL|RXMachineSeriesSeeder|{$code}|{$msg}");
                $this->recordSeedRun('RXMachineSeriesSeeder', 'failed', $code, $ms, $e->getMessage());
                $fail[] = ['code' => $code, 'error' => $msg];
            }
        }

        $this->command->info('');
        $this->command->info('✅ ' . count($ok) . ' RX machines seeded: ' . implode(', ', $ok));

        if (!empty($fail)) {
            throw new \RuntimeException(
                count($fail) . ' RX machines failed: ' . implode(', ', array_column($fail, 'code'))
            );
        }
    }
}
