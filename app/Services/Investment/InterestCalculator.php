<?php

namespace App\Services\Investment;

use App\Models\InvestmentPlan;

class InterestCalculator
{
    /**
     * Daily profit for a given principal under a plan.
     */
    public function dailyProfit(float $principal, float $ratePercent): float
    {
        return round($principal * $ratePercent / 100, 2);
    }

    /**
     * Total projected profit over the plan's duration.
     */
    public function totalProfit(float $principal, InvestmentPlan $plan): float
    {
        return round($this->dailyProfit($principal, $plan->daily_interest_rate) * $plan->duration_days, 2);
    }

    /**
     * Full projection including totals and ROI.
     */
    public function project(float $principal, InvestmentPlan $plan): array
    {
        $daily = $this->dailyProfit($principal, $plan->daily_interest_rate);
        $total = round($daily * $plan->duration_days, 2);

        return [
            'principal'    => $principal,
            'daily_profit' => $daily,
            'total_profit' => $total,
            'total_return' => round($principal + $total, 2),
            'roi_percent'  => round(($total / max($principal, 1)) * 100, 2),
            'duration_days'=> $plan->duration_days,
            'rate_percent' => $plan->daily_interest_rate,
        ];
    }

    /**
     * Profit accrued so far for an active investment.
     */
    public function accrued(int $daysElapsed, float $dailyProfit): float
    {
        return round(max(0, $daysElapsed) * $dailyProfit, 2);
    }
}
