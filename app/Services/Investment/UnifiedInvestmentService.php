<?php

namespace App\Services\Investment;

use App\Models\Investment;
use App\Models\MachineInvestment;
use App\Models\User;
use Illuminate\Support\Collection;

class UnifiedInvestmentService
{
    /**
     * Combine legacy plan investments + machine investments
     * into a single normalized collection, skipping orphans.
     */
    public function getAllInvestments(?User $user = null): Collection
    {
        $user = $user ?? auth()->user();
        if (!$user) return collect();

        $legacy = Investment::with('plan')
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn ($i) => $i->plan !== null)
            ->map(fn ($i) => $this->normalizeLegacy($i));

        $machines = MachineInvestment::with('machine')
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn ($i) => $i->machine !== null)
            ->map(fn ($i) => $this->normalizeMachine($i));

        return $legacy->concat($machines)->sortByDesc('created_at')->values();
    }

    public function getStats(?User $user = null): array
    {
        $all = $this->getAllInvestments($user);
        $invested = (float) $all->sum('amount');
        $profit   = (float) $all->sum('profit_credited');

        return [
            'total_invested'   => $invested,
            'total_profit'     => $profit,
            'active_count'     => $all->where('status', 'active')->count(),
            'completed_count'  => $all->where('status', 'completed')->count(),
            'projected_profit' => (float) $all->where('status', 'active')->sum('projected_profit'),
            'roi'              => $invested > 0 ? round(($profit / $invested) * 100, 2) : 0,
        ];
    }

    /* ============================================================
     |  NORMALIZERS
     ============================================================ */

    protected function normalizeLegacy(Investment $inv): array
    {
        $profit = $inv->profit_credited;
        $target = (float) ($inv->total_projected_profit ?? 0);

        return [
            'id'               => $inv->id,
            'source'           => 'plan',
            'name'             => $inv->plan_name,
            'icon'             => 'fa-chart-line',
            'color'            => 'from-gold-400 to-amber-400',
            'amount'           => (float) $inv->amount,
            'daily_profit'     => (float) $inv->daily_profit,
            'profit_credited'  => $profit,
            'projected_profit' => $target,
            'status'           => $inv->status ?? 'active',
            'start_date'       => $inv->start_date,
            'end_date'         => $inv->end_date,
            'days_remaining'   => $inv->remaining_days,
            'progress_percent' => $inv->progress_percent,
            'created_at'       => $inv->created_at,
        ];
    }

    protected function normalizeMachine(MachineInvestment $inv): array
    {
        return [
            'id'               => $inv->id,
            'source'           => 'machine',
            'name'             => $inv->machine_name,
            'icon'             => $inv->machine_icon,
            'color'            => $inv->machine_color,
            'amount'           => (float) $inv->amount,
            'daily_profit'     => (float) ($inv->daily_profit ?? 0),
            'profit_credited'  => (float) ($inv->profit_credited ?? 0),
            'projected_profit' => (float) ($inv->total_projected_profit ?? 0),
            'status'           => $inv->status ?? 'active',
            'start_date'       => $inv->start_date,
            'end_date'         => $inv->end_date,
            'days_remaining'   => $inv->days_remaining ?? 0,
            'progress_percent' => method_exists($inv, 'progressPercentage')
                                    ? $inv->progressPercentage() : 0,
            'created_at'       => $inv->created_at,
        ];
    }
}
