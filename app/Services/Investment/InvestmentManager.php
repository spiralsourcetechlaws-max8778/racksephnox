<?php

namespace App\Services\Investment;

use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use Illuminate\Support\Collection;

class InvestmentManager
{
    protected InvestmentService $service;
    protected InterestCalculator $calculator;

    public function __construct(InvestmentService $service, InterestCalculator $calculator)
    {
        $this->service    = $service;
        $this->calculator = $calculator;
    }

    public function getActivePlans(): Collection
    {
        return InvestmentPlan::active()->orderBy('min_amount')->get();
    }

    public function getPlanStats(InvestmentPlan $plan): array
    {
        return [
            'investors'      => $plan->investor_count,
            'total_invested' => $plan->total_invested,
            'roi_percent'    => $plan->roi_percent,
            'duration_days'  => $plan->duration_days,
            'rate_percent'   => $plan->daily_interest_rate,
        ];
    }

    public function getUserInvestments(User $user): Collection
    {
        return Investment::with('plan')
            ->forUser($user->id)
            ->latest()
            ->get()
            ->filter(fn ($i) => $i->plan !== null)
            ->values();
    }

    public function getUserStats(User $user): array
    {
        $investments = $this->getUserInvestments($user);

        $invested = $investments->sum('amount');
        $profit   = $investments->sum('profit_credited');

        return [
            'total_invested'    => (float) $invested,
            'total_profit'      => (float) $profit,
            'active_count'      => $investments->where('status', 'active')->count(),
            'completed_count'   => $investments->where('status', 'completed')->count(),
            'projected_profit'  => (float) $investments->where('status', 'active')->sum('total_projected_profit'),
            'roi'               => $invested > 0 ? round(($profit / $invested) * 100, 2) : 0,
        ];
    }

    public function invest(User $user, InvestmentPlan $plan, float $amount): Investment
    {
        return $this->service->create($user, $plan, $amount);
    }

    public function preview(float $amount, InvestmentPlan $plan): array
    {
        return $this->calculator->project($amount, $plan);
    }
}
