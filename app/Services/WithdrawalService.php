<?php

namespace App\Services;

class WithdrawalService
{
    /**
     * Fee = percentage of amount, clamped between min and max.
     * Defaults: 1% of amount, min 10 KES, max 100 KES.
     */
    public static function calculateFee(float $amount): float
    {
        $config = config('mpesa.fees', []);
        $percent = (float) ($config['withdrawal_percent'] ?? 1.0);
        $min     = (float) ($config['withdrawal_min'] ?? 10);
        $max     = (float) ($config['withdrawal_max'] ?? 100);

        if ($amount < 1) return 0.0;

        $fee = $amount * ($percent / 100);
        return round(max($min, min($max, $fee)), 2);
    }

    public static function validateWithdrawal(float $amount): array
    {
        $limits = config('mpesa.limits', []);
        $min = (float) ($limits['min_withdrawal'] ?? 100);
        $max = (float) ($limits['max_withdrawal'] ?? 150000);

        if ($amount < $min) {
            return ['valid' => false, 'message' => 'Minimum withdrawal is KES ' . number_format($min)];
        }
        if ($amount > $max) {
            return ['valid' => false, 'message' => 'Maximum withdrawal is KES ' . number_format($max)];
        }
        return ['valid' => true, 'message' => ''];
    }

    public static function getNetAmount(float $amount): float
    {
        return round($amount - self::calculateFee($amount), 2);
    }
}
