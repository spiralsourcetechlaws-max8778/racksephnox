<?php

namespace Database\Seeders;

use App\Models\LoanProduct;
use Illuminate\Database\Seeder;

class LoanProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Divine Micro Loan',
                'slug' => 'divine-micro',
                'description' => 'Instant micro-loans for daily needs. Approved within minutes.',
                'icon' => 'fa-bolt', 'color' => 'from-emerald-400 to-teal-500',
                'min_amount' => 500, 'max_amount' => 20_000,
                'interest_rate' => 8.00, 'interest_method' => 'flat',
                'min_duration_days' => 7, 'max_duration_days' => 30,
                'grace_period_days' => 1, 'late_fee_rate' => 5.00,
                'requires_guarantor' => false, 'requires_collateral' => false,
                'min_credit_score' => 0, 'frequency_hz' => 528,
            ],
            [
                'name' => 'Golden Personal Loan',
                'slug' => 'golden-personal',
                'description' => 'Flexible personal loans with reducing-balance interest.',
                'icon' => 'fa-user', 'color' => 'from-gold-400 to-amber-500',
                'min_amount' => 5_000, 'max_amount' => 200_000,
                'interest_rate' => 15.00, 'interest_method' => 'reducing',
                'min_duration_days' => 30, 'max_duration_days' => 180,
                'grace_period_days' => 3, 'late_fee_rate' => 5.00,
                'requires_guarantor' => false, 'requires_collateral' => false,
                'min_credit_score' => 400, 'frequency_hz' => 639,
            ],
            [
                'name' => 'Phi Business Loan',
                'slug' => 'phi-business',
                'description' => 'Scale your enterprise with structured business capital.',
                'icon' => 'fa-briefcase', 'color' => 'from-indigo-400 to-purple-500',
                'min_amount' => 50_000, 'max_amount' => 2_000_000,
                'interest_rate' => 12.00, 'interest_method' => 'reducing',
                'min_duration_days' => 90, 'max_duration_days' => 730,
                'grace_period_days' => 7, 'late_fee_rate' => 4.00,
                'requires_guarantor' => true, 'requires_collateral' => true,
                'min_credit_score' => 550, 'frequency_hz' => 741,
            ],
            [
                'name' => 'Infinity Mortgage',
                'slug' => 'infinity-mortgage',
                'description' => 'Long-term secured loans for land, housing, and major assets.',
                'icon' => 'fa-home', 'color' => 'from-rose-500 to-red-600',
                'min_amount' => 500_000, 'max_amount' => 20_000_000,
                'interest_rate' => 9.50, 'interest_method' => 'reducing',
                'min_duration_days' => 365, 'max_duration_days' => 3650,
                'grace_period_days' => 14, 'late_fee_rate' => 3.00,
                'requires_guarantor' => true, 'requires_collateral' => true,
                'min_credit_score' => 650, 'frequency_hz' => 963,
            ],
        ];

        foreach ($products as $p) {
            LoanProduct::updateOrCreate(['slug' => $p['slug']], $p);
        }

        $this->command->info('✅ Loan products: ' . LoanProduct::count());
    }
}
