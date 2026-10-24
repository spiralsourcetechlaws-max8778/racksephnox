<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineVip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Investment Machine Series — parallel to RX, code prefix IM0–IM6.
 * Same VIP tier structure (200/500/800 base, doubling per machine).
 *
 * If "Investment Machines" in your product are actually a different
 * entity (e.g. investment_plans table), replace the Machine and
 * MachineVip models here with the correct ones and adjust the
 * fillable columns. The structure (loop + try/catch + updateOrCreate)
 * stays identical.
 */
class InvestmentMachineSeriesSeeder extends Seeder
{
    protected array $machines = [
        ['IM0', 'Seed Vault',     200,   500,   800,   22.00, 30, 'Low'],
        ['IM1', 'Growth Engine',  400,   1000,  1600,  26.00, 30, 'Low'],
        ['IM2', 'Compound Flow',  800,   2000,  3200,  30.00, 30, 'Medium'],
        ['IM3', 'Yield Nexus',    1600,  4000,  6400,  34.00, 30, 'Medium'],
        ['IM4', 'Capital Orbits', 3200,  8000,  12800, 38.00, 30, 'Medium-High'],
        ['IM5', 'Prime Reserves', 6400,  16000, 25600, 42.00, 30, 'High'],
        ['IM6', 'Sovereign Fund', 12800, 32000, 51200, 48.00, 30, 'High'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Investment Machine Series starting...');

        $ok = [];
        $fail = [];

        foreach ($this->machines as [$code, $name, $vip1, $vip2, $vip3, $growth, $duration, $risk]) {
            $start = microtime(true);

            try {
                DB::transaction(function () use ($code, $name, $vip1, $vip2, $vip3, $growth, $duration, $risk) {
                    $machine = Machine::updateOrCreate(
                        ['code' => $code],
                        [
                            'name'                 => $name,
                            'vip1_start_amount'    => $vip1,
                            'vip2_start_amount'    => $vip2,
                            'vip3_start_amount'    => $vip3,
                            'growth_rate'          => $growth,
                            'duration_days'        => $duration,
                            'risk_profile'         => $risk,
                            'is_active'            => true,
                            'icon'                 => 'fa-vault',
                            'color'                => 'from-blue-400 to-indigo-500',
                            'referral_bonus_rate'  => 5,
                            'early_withdrawal_penalty' => 20,
                        ]
                    );

                    foreach ([1 => $vip1, 2 => $vip2, 3 => $vip3] as $level => $amount) {
                        MachineVip::updateOrCreate(
                            ['machine_id' => $machine->id, 'level' => $level],
                            [
                                'vip_level'           => $level,
                                'name'                => 'Tier ' . $level,
                                'start_amount'        => $amount,
                                'max_amount'          => $amount * 10,
                                'growth_rate'         => $growth,
                                'duration_days'       => $duration,
                                'daily_profit_min'    => round($amount * 0.008, 2),
                                'daily_profit_max'    => round($amount * 0.04, 2),
                                'bonus_multiplier'    => 1.00 + ($level * 0.08),
                                'referral_bonus_rate' => 5,
                                'is_active'           => true,
                            ]
                        );
                    }
                });

                $ms = round((microtime(true) - $start) * 1000);
                $this->command->info("SEED_OK|InvestmentMachineSeriesSeeder|{$code}|{$vip1}/{$vip2}/{$vip3}|{$ms}ms");
                $ok[] = $code;

            } catch (\Throwable $e) {
                $ms = round((microtime(true) - $start) * 1000);
                $msg = substr($e->getMessage(), 0, 400);
                $this->command->error("SEED_FAIL|InvestmentMachineSeriesSeeder|{$code}|{$msg}");
                \Log::error("SEED_FAIL|InvestmentMachineSeriesSeeder|{$code}", [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]);
                $fail[] = ['code' => $code, 'error' => $msg, 'ms' => $ms];
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
