<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MachinesSeeder extends Seeder
{
    /** Insert only columns that actually exist. */
    private function insertSafe(string $table, array $rows): void
    {
        if (!Schema::hasTable($table) || empty($rows)) return;

        $existing = Schema::getColumnListing($table);
        $filtered = array_map(
            fn($row) => array_intersect_key($row, array_flip($existing)),
            $rows
        );

        foreach (array_chunk($filtered, 200) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    public function run(): void
    {
        $this->command->info('🚀 Seeding RX Machine Series (7 portals × 3 VIP tiers)...');

        // Golden Ratio constants
        $phi    = 1.61803398875;
        $lambda = 1.27201964951;
        $pi     = 3.14159265359;
        $e      = 2.71828182846;

        // ---------------------------------------------------------
        // 1. SEVEN RX MACHINES
        // ---------------------------------------------------------
        $machines = [
            [
                'code' => 'RX0', 'name' => 'Origin Machine',
                'icon' => 'fa-seedling', 'color' => 'from-emerald-400 to-teal-500',
                'vip1' => 1_000,    'vip2' => 5_000,    'vip3' => 25_000,
                'growth' => 25.00, 'risk' => 'Low',
                'desc' => 'The genesis portal. Entry into the sacred spiral.',
            ],
            [
                'code' => 'RX1', 'name' => 'Aurora Machine',
                'icon' => 'fa-sun', 'color' => 'from-yellow-400 to-amber-500',
                'vip1' => 2_000,    'vip2' => 10_000,   'vip3' => 50_000,
                'growth' => 30.00, 'risk' => 'Low-Medium',
                'desc' => 'Dawn resonance. Amplifies golden ratio gains.',
            ],
            [
                'code' => 'RX2', 'name' => 'Nova Machine',
                'icon' => 'fa-star', 'color' => 'from-orange-400 to-red-500',
                'vip1' => 5_000,    'vip2' => 25_000,   'vip3' => 100_000,
                'growth' => 38.20, 'risk' => 'Medium',
                'desc' => 'Stellar ignition. Exponential spiral expansion.',
            ],
            [
                'code' => 'RX3', 'name' => 'Prism Machine',
                'icon' => 'fa-gem', 'color' => 'from-cyan-400 to-blue-500',
                'vip1' => 10_000,   'vip2' => 50_000,   'vip3' => 250_000,
                'growth' => 45.00, 'risk' => 'Medium',
                'desc' => 'Spectral division. Multi-channel compounding.',
            ],
            [
                'code' => 'RX4', 'name' => 'Eclipse Machine',
                'icon' => 'fa-moon', 'color' => 'from-indigo-400 to-purple-500',
                'vip1' => 25_000,   'vip2' => 100_000,  'vip3' => 500_000,
                'growth' => 55.00, 'risk' => 'Medium-High',
                'desc' => 'Hidden light. Depth resonance reveals hidden yields.',
            ],
            [
                'code' => 'RX5', 'name' => 'Quantum Machine',
                'icon' => 'fa-atom', 'color' => 'from-fuchsia-400 to-pink-500',
                'vip1' => 50_000,   'vip2' => 250_000,  'vip3' => 1_000_000,
                'growth' => 70.00, 'risk' => 'High',
                'desc' => 'Entangled yields. Observation collapses to profit.',
            ],
            [
                'code' => 'RX6', 'name' => 'Infinity Machine',
                'icon' => 'fa-infinity', 'color' => 'from-rose-500 to-red-600',
                'vip1' => 100_000,  'vip2' => 500_000,  'vip3' => 2_000_000,
                'growth' => 88.00, 'risk' => 'Very High',
                'desc' => 'The final portal. Infinite spiral of divine abundance.',
            ],
        ];

        DB::table('machines')->delete();

        $machineIds = [];

        foreach ($machines as $m) {
            DB::table('machines')->insert([
                'name'                    => $m['name'],
                'code'                    => $m['code'],
                'description'             => $m['desc'],
                'vip1_start_amount'       => $m['vip1'],
                'vip2_start_amount'       => $m['vip2'],
                'vip3_start_amount'       => $m['vip3'],
                'duration_days'           => 14,
                'growth_rate'             => $m['growth'],
                'is_active'               => 1,
                'risk_profile'            => $m['risk'],
                'icon'                    => $m['icon'],
                'color'                   => $m['color'],
                'min_daily_profit'        => round($m['vip1'] * $m['growth'] / 100 / 14, 2),
                'max_daily_profit'        => round($m['vip3'] * $m['growth'] / 100 / 14, 2),
                'referral_bonus_rate'     => 5.00,
                'early_withdrawal_penalty'=> 20.00,
                'features'                => json_encode([
                    'phi'          => $phi,
                    'lambda'       => $lambda,
                    'pi'           => $pi,
                    'e'            => $e,
                    'frequency_hz' => 888,
                    'roi_percent'  => 88,
                    'cycle_days'   => 14,
                    'portal_count' => 3,
                ]),
                'total_invested_limit'    => $m['vip3'] * 10,
                'compound_frequency'      => 1,
                'min_withdrawal'          => 100.00,
                'max_withdrawal'          => 10_000_000.00,
                'bonus_multiplier'        => 1.00,
                'staking_reward'          => 5.00,
                'tier_multiplier'         => 1.00,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            $machineIds[$m['code']] = DB::table('machines')->where('code', $m['code'])->value('id');
        }

        $this->command->info('✅ Machines: ' . DB::table('machines')->count());

        // ---------------------------------------------------------
        // 2. VIP TIERS — 3 per machine (21 total)
        // ---------------------------------------------------------
        DB::table('machine_vips')->delete();

        $vipRows = [];

        foreach ($machines as $m) {
            $id = $machineIds[$m['code']];

            // VIP 1 — baseline
            $vipRows[] = $this->buildVipRow($id, 1, $m['vip1'], $m['growth'] * 1.00, $m['code']);
            // VIP 2 — λ multiplier (1.272)
            $vipRows[] = $this->buildVipRow($id, 2, $m['vip2'], $m['growth'] * $lambda, $m['code']);
            // VIP 3 — φ multiplier (1.618)
            $vipRows[] = $this->buildVipRow($id, 3, $m['vip3'], $m['growth'] * $phi, $m['code']);
        }

        $this->insertSafe('machine_vips', $vipRows);
        $this->command->info('✅ VIP tiers: ' . DB::table('machine_vips')->count());

        // ---------------------------------------------------------
        // 3. SAMPLE INVESTMENTS
        // ---------------------------------------------------------
        $userIds = DB::table('users')->pluck('id');
        $hasInvestments = DB::table('machine_investments')->count();

        if ($hasInvestments === 0 && $userIds->isNotEmpty()) {
            $invRows = [];
            $statuses = ['active', 'active', 'active', 'completed'];

            foreach ($userIds as $uid) {
                $count = mt_rand(1, 3);
                $codes = array_rand($machineIds, min($count, count($machineIds)));
                if (!is_array($codes)) $codes = [$codes];

                foreach ($codes as $code) {
                    $machineId = $machineIds[$code];
                    $vipLevel  = mt_rand(1, 3);
                    $startAmt  = DB::table('machines')->where('id', $machineId)
                                    ->value("vip{$vipLevel}_start_amount") ?? 1000;
                    $amount    = round($startAmt * (1 + mt_rand(0, 100) / 100), 2);
                    $status    = $statuses[array_rand($statuses)];
                    $startedAt = now()->subDays(mt_rand(1, 30));
                    $growth    = DB::table('machines')->where('id', $machineId)->value('growth_rate') ?? 25;
                    $projected = round($amount * (1 + $growth / 100), 2);

                    $invRows[] = [
                        'user_id'                => $uid,
                        'machine_id'             => $machineId,
                        'amount'                 => $amount,
                        'profit_credited'        => $status === 'completed'
                                                        ? round($projected - $amount, 2)
                                                        : round(($projected - $amount) * mt_rand(10, 80) / 100, 2),
                        'status'                 => $status,
                        'vip_level'              => $vipLevel,
                        'daily_profit'           => round($amount * $growth / 100 / 14, 2),
                        'total_projected_profit' => $projected - $amount,
                        'start_date'             => $startedAt->toDateTimeString(),
                        'end_date'               => $startedAt->copy()->addDays(14)->toDateTimeString(),
                        'last_accrued_at'        => now()->subHours(mt_rand(1, 24)),
                        'withdrawn'              => 0,
                        'early_withdrawn'        => 0,
                        'penalty_applied'        => 0,
                        'created_at'             => $startedAt,
                        'updated_at'             => now(),
                    ];
                }
            }

            $this->insertSafe('machine_investments', $invRows);
        }

        $this->command->info('✅ Machine investments: ' . DB::table('machine_investments')->count());

        // ---------------------------------------------------------
        // Summary
        // ---------------------------------------------------------
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('✅ RX Machine Series fully seeded.');
        $this->command->info('   7 machines · 21 VIP tiers');
        $this->command->info('   φ = ' . $phi . '  λ = ' . $lambda);
        $this->command->info('   π = ' . $pi . '  e = ' . $e);
        $this->command->info('   Frequency: 888 Hz · ROI: 88% in 14 days');
        $this->command->info('═══════════════════════════════════════════');
    }

    private function buildVipRow(int $machineId, int $level, float $start, float $growth, string $code): array
    {
        $daily = round($start * $growth / 100 / 14, 2);

        return [
            'machine_id'         => $machineId,
            'level'              => $level,
            'vip_level'          => $level,
            'name'               => "{$code} VIP {$level}",
            'start_amount'       => $start,
            'max_amount'         => $start * 100,
            'growth_rate'        => round($growth, 4),
            'duration_days'      => 14,
            'daily_profit_min'   => $daily,
            'daily_profit_max'   => round($daily * 1.618, 2),
            'bonus_multiplier'   => 1 + ($level - 1) * 0.5,
            'referral_bonus_rate'=> 5.00 + ($level - 1) * 1.00,
            'is_active'          => 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }
}
