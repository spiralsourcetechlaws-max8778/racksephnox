<?php

namespace App\Services\Loans;

use App\Models\LoanProduct;

class LoanCalculator
{
    /**
     * Compute interest, totals, and installment schedule.
     */
    public function calculate(LoanProduct $product, float $principal, int $durationDays, string $frequency = 'monthly'): array
    {
        $rate      = (float) $product->interest_rate;      // % over the term
        $method    = $product->interest_method ?: 'reducing';
        $installments = $this->installmentCount($durationDays, $frequency);

        $totalInterest = match ($method) {
            'flat'      => $this->flatInterest($principal, $rate),
            'compound'  => $this->compoundInterest($principal, $rate, $installments),
            default     => $this->reducingInterest($principal, $rate, $installments),
        };

        $totalPayable = round($principal + $totalInterest, 2);
        $installment  = round($totalPayable / max(1, $installments), 2);
        $diff         = round($totalPayable - ($installment * $installments), 2);

        return [
            'principal'          => round($principal, 2),
            'interest_rate'      => $rate,
            'interest_method'    => $method,
            'total_interest'     => round($totalInterest, 2),
            'total_payable'      => $totalPayable,
            'installments'       => $installments,
            'installment_amount' => $installment,
            'rounding_adjust'    => $diff,
            'frequency'          => $frequency,
            'duration_days'      => $durationDays,
        ];
    }

    /**
     * Build a full amortization schedule (list of repayments).
     */
    public function schedule(array $calc, \DateTimeInterface $startDate): array
    {
        $rows = [];
        $balance = $calc['principal'];
        $principalPortion = round($calc['principal'] / $calc['installments'], 2);
        $interestPortion  = round($calc['total_interest'] / $calc['installments'], 2);

        $intervalDays = $this->intervalDays($calc['duration_days'], $calc['installments']);
        $due = \Carbon\Carbon::parse($startDate);

        for ($i = 0; $i < $calc['installments']; $i++) {
            $due = $due->copy()->addDays($intervalDays);
            $balance = max(0.0, round($balance - $principalPortion, 2));

            $amount = round($principalPortion + $interestPortion, 2);
            if ($i === $calc['installments'] - 1) {
                $amount = round($calc['total_payable'] - array_sum(array_column($rows, 'amount')), 2);
            }

            $rows[] = [
                'installment_no'    => $i + 1,
                'due_date'          => $due->toDateTimeString(),
                'amount'            => $amount,
                'principal_portion' => $principalPortion,
                'interest_portion'  => $interestPortion,
                'balance_after'     => $balance,
                'status'            => 'pending',
            ];
        }

        return $rows;
    }

    /* ---------- Methods ---------- */

    protected function flatInterest(float $principal, float $rate): float
    {
        return round($principal * $rate / 100, 2);
    }

    protected function reducingInterest(float $principal, float $rate, int $n): float
    {
        // Approximate reducing-balance interest using average balance
        $total = 0.0;
        $balance = $principal;
        $perPeriodRate = ($rate / 100) / max(1, $n);
        $principalPortion = $principal / max(1, $n);

        for ($i = 0; $i < $n; $i++) {
            $total += $balance * $perPeriodRate;
            $balance = max(0, $balance - $principalPortion);
        }
        return round($total, 2);
    }

    protected function compoundInterest(float $principal, float $rate, int $n): float
    {
        $periodsRate = ($rate / 100) / max(1, $n);
        $final = $principal * pow(1 + $periodsRate, $n);
        return round($final - $principal, 2);
    }

    protected function installmentCount(int $days, string $frequency): int
    {
        return match ($frequency) {
            'daily'  => max(1, $days),
            'weekly' => max(1, (int) ceil($days / 7)),
            default  => max(1, (int) ceil($days / 30)), // monthly
        };
    }

    protected function intervalDays(int $days, int $installments): int
    {
        return max(1, (int) floor($days / max(1, $installments)));
    }
}
