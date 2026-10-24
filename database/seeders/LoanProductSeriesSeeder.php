<?php

namespace Database\Seeders;

use App\Models\LoanProduct;
use Database\Seeders\Concerns\RecordsSeedRuns;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loan product catalog — 6 real Kenyan-market loan types.
 * Every loan_products column populated (post-000006 schema):
 *   name, slug, description, icon, color, min_amount, max_amount,
 *   interest_rate, interest_method, min/max_duration_days,
 *   grace_period_days, late_fee_rate, requires_guarantor,
 *   requires_collateral, min_credit_score, is_active, frequency_hz.
 *
 * Idempotent: updateOrCreate on slug.
 * Per-product try/catch, failure tracking via seed_runs.
 */
class LoanProductSeriesSeeder extends Seeder
{
    use RecordsSeedRuns;

    /** slug, name, min, max, rate, method, minDur, maxDur, grace, lateRate, guarantor, collateral, minScore, icon, color, desc */
    protected array $products = [
        ['quick-cash', 'Quick Cash', 500, 10000, 12.00, 'flat', 7, 30, 2, 3.00,
         false, false, 300,
         'fa-bolt', 'from-amber-400 to-orange-500',
         'Instant small loan for emergencies. Approved within minutes.'],

        ['emergency-fund', 'Emergency Fund', 1000, 50000, 15.00, 'reducing', 14, 90, 3, 5.00,
         false, false, 400,
         'fa-first-aid', 'from-red-400 to-rose-500',
         'Fast access to emergency capital. Same-day disbursement.'],

        ['business-boost', 'Business Boost', 10000, 500000, 18.00, 'reducing', 30, 180, 5, 5.00,
         true, false, 500,
         'fa-briefcase', 'from-blue-400 to-indigo-500',
         'Working capital for SMEs. Flexible repayment schedules.'],

        ['agriculture-loan', 'Agriculture Loan', 20000, 1000000, 14.00, 'reducing', 90, 365, 14, 4.00,
         true, false, 450,
         'fa-seedling', 'from-green-400 to-emerald-600',
         'Seasonal financing for farmers. Grace period aligned to harvest.'],

        ['education-loan', 'Education Loan', 15000, 300000, 10.00, 'reducing', 90, 365, 30, 3.00,
         true, false, 550,
         'fa-graduation-cap', 'from-cyan-400 to-blue-500',
         'School and university fees. Repaid monthly over the academic year.'],

        ['asset-finance', 'Asset Finance', 100000, 5000000, 16.00, 'reducing', 180, 730, 15, 5.00,
         true, true, 600,
         'fa-home', 'from-purple-400 to-pink-500',
         'Large-ticket financing for property, vehicles, equipment.'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Loan Product Series starting...');

        $ok = [];
        $fail = [];

        foreach ($this->products as [$slug, $name, $min, $max, $rate, $method, $minDur, $maxDur, $grace, $lateRate, $guarantor, $collateral, $minScore, $icon, $color, $desc]) {
            $start = microtime(true);

            try {
                DB::transaction(function () use ($slug, $name, $min, $max, $rate, $method, $minDur, $maxDur, $grace, $lateRate, $guarantor, $collateral, $minScore, $icon, $color, $desc) {
                    LoanProduct::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name'                 => $name,
                            'description'          => $desc,
                            'icon'                 => $icon,
                            'color'                => $color,
                            'min_amount'           => $min,
                            'max_amount'           => $max,
                            'interest_rate'        => $rate,
                            'interest_method'      => $method,
                            'min_duration_days'    => $minDur,
                            'max_duration_days'    => $maxDur,
                            'grace_period_days'    => $grace,
                            'late_fee_rate'        => $lateRate,
                            'requires_guarantor'   => $guarantor,
                            'requires_collateral'  => $collateral,
                            'min_credit_score'     => $minScore,
                            'is_active'            => true,
                            'frequency_hz'         => 528,
                        ]
                    );
                });

                $ms = round((microtime(true) - $start) * 1000);
                $this->command->info("SEED_OK|LoanProductSeriesSeeder|{$slug}|{$min}-{$max}@{$rate}%|{$ms}ms");
                $this->recordSeedRun('LoanProductSeriesSeeder', 'ok', $slug, $ms);
                $ok[] = $slug;

            } catch (\Throwable $e) {
                $ms = round((microtime(true) - $start) * 1000);
                $msg = substr($e->getMessage(), 0, 400);
                $this->command->error("SEED_FAIL|LoanProductSeriesSeeder|{$slug}|{$msg}");
                $this->recordSeedRun('LoanProductSeriesSeeder', 'failed', $slug, $ms, $e->getMessage());
                \Log::error("SEED_FAIL|LoanProductSeriesSeeder|{$slug}", [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]);
                $fail[] = ['slug' => $slug, 'error' => $msg];
            }
        }

        $this->command->info('');
        $this->command->info('✅ ' . count($ok) . ' loan products seeded: ' . implode(', ', $ok));

        if (!empty($fail)) {
            $this->command->warn('⚠️  ' . count($fail) . ' loan products FAILED:');
            foreach ($fail as $f) {
                $this->command->warn("   {$f['slug']} — {$f['error']}");
            }
            throw new \RuntimeException(
                count($fail) . ' loan products failed: ' . implode(', ', array_column($fail, 'slug'))
            );
        }
    }
}
