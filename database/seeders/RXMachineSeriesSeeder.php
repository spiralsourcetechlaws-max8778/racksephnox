<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineVip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RX Machine Series — Spiral Origin root + 6 descendant machines.
 *
 * Each machine has 3 VIP tiers (VIP1/VIP2/VIP3) with escalating
 * start_amounts. Base pattern from Spiral Origin: 200/500/800. Each
 * subsequent machine doubles: 400/1000/1600, 800/2000/3200, etc.
 *
 * Idempotent: uses updateOrCreate keyed on machine.code and
 * (machine_id, level) for VIPs. Re-running on every deploy is a no-op
 * after the first.
 *
 * Per-machine try/catch: if one machine fails (missing column, FK
 * violation, etc.), the others still seed. Failure list is thrown at
 * the end so entrypoint.sh reports "seeding failed".
 *
 * Log format (greppable in Render logs):
 *   SEED_OK|RXMachineSeriesSeeder|RX0|200/500/800|12ms
 *   SEED_FAIL|RXMachineSeriesSeeder|RX3|SQLSTATE[42703]...
 */
class RXMachineSeriesSeeder extends Seeder
{
    /**
     * code, name, VIP1, VIP2, VIP3, growth_rate, duration_days, risk_profile
     */
    protected array $machines = [
        ['RX0', 'Spiral Origin', 200,   500,   800,   25.00, 14, 'Low'],
        ['RX1', 'Aurora Portal', 400,   1000,  1600,  28.00, 14, 'Low'],
        ['RX2', 'Nova Spiral',   800,   2000,  3200,  32.00, 14, 'Medium'],
        ['RX3', 'Zenith Gate',   1600,  4000,  6400,  36.00, 14, 'Medium'],
        ['RX4', 'Infinity Ring', 3200,  8000,  12800, 40.00, 14, 'Medium-High'],
        ['RX5', 'Quantum Loop',  6400,  16000, 25600, 45.00, 14, 'High'],
        ['RX6', 'Transcend Ark', 12800, 32000, 51200, 50.00, 14, 'High'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 RX Machine Series starting...');

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
                            'icon'                 => 'fa-microchip',
                            'color'                => 'from-gold-400 to-amber-400',
                            'referral_bonus_rate'  => 5,
                            'early_withdrawal_penalty' => 20,
                        ]
                    );

                    $tiers = [
                        1 => ['amount' => $vip1, 'min' => round($vip1 * 0.01, 2), 'max' => round($vip1 * 0.05, 2)],
                        2 => ['amount' => $vip2, 'min' => round($vip2 * 0.01, 2), 'max' => round($vip2 * 0.05, 2)],
                        3 => ['amount' => $vip3, 'min' => round($vip3 * 0.01, 2), 'max' => round($vip3 * 0.05, 2)],
                    ];

                    foreach ($tiers as $level => $cfg) {
                        MachineVip::updateOrCreate(
                            ['machine_id' => $machine->id, 'level' => $level],
                            [
                                'vip_level'           => $level,
                                'name'                => 'VIP ' . $level,
                                'start_amount'        => $cfg['amount'],
                                'max_amount'          => $cfg['amount'] * 10,
                                'growth_rate'         => $growth,
                                'duration_days'       => $duration,
                                'daily_profit_min'    => $cfg['min'],
                                'daily_profit_max'    => $cfg['max'],
                                'bonus_multiplier'    => 1.00 + ($level * 0.10),
                                'referral_bonus_rate' => 5,
                                'is_active'           => true,
                            ]
                        );
                    }
                });

                $ms = round((microtime(true) - $start) * 1000);
                $this->command->info("SEED_OK|RXMachineSeriesSeeder|{$code}|{$vip1}/{$vip2}/{$vip3}|{$ms}ms");
                $ok[] = $code;

            } catch (\Throwable $e) {
                $ms = round((microtime(true) - $start) * 1000);
                $msg = substr($e->getMessage(), 0, 400);
                $this->command->error("SEED_FAIL|RXMachineSeriesSeeder|{$code}|{$msg}");
                \Log::error("SEED_FAIL|RXMachineSeriesSeeder|{$code}", [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]);
                $fail[] = ['code' => $code, 'error' => $msg, 'ms' => $ms];
            }
        }

        $this->command->info('');
        $this->command->info('✅ ' . count($ok) . ' machines seeded: ' . implode(', ', $ok));

        if (!empty($fail)) {
            $this->command->warn('⚠️  ' . count($fail) . ' machines FAILED:');
            foreach ($fail as $f) {
                $this->command->warn("   {$f['code']} — {$f['error']}");
            }
            throw new \RuntimeException(
                count($fail) . ' RX machines failed: ' . implode(', ', array_column($fail, 'code'))
            );
        }
    }
}
