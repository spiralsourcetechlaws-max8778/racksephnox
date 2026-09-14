<?php

namespace App\Services\Investment;

use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InvestmentService
{
    protected InterestCalculator $calculator;

    public function __construct(InterestCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Create a new investment for a user in a given plan.
     */
    public function create(User $user, InvestmentPlan $plan, float $amount): Investment
    {
        if (!$plan->is_active) {
            throw new RuntimeException('This plan is not currently active.');
        }
        if ($amount < $plan->min_amount) {
            throw new RuntimeException('Minimum for this plan is KES ' . number_format($plan->min_amount, 2));
        }
        if ($amount > $plan->max_amount) {
            throw new RuntimeException('Maximum for this plan is KES ' . number_format($plan->max_amount, 2));
        }

        $wallet = $user->wallet ?? Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        if ($wallet->balance < $amount) {
            throw new RuntimeException('Insufficient wallet balance.');
        }

        return DB::transaction(function () use ($user, $plan, $amount, $wallet) {

            // Debit wallet
            $wallet->balance -= $amount;
            $wallet->save();

            $projection = $this->calculator->project($amount, $plan);
            $startDate  = now();
            $endDate    = $startDate->copy()->addDays($plan->duration_days);

            $investment = Investment::create([
                'user_id'                => $user->id,
                'plan_id'                => $plan->id,
                'amount'                 => $amount,
                'daily_profit'           => $projection['daily_profit'],
                'total_projected_profit' => $projection['total_profit'],
                'remaining_days'         => $plan->duration_days,
                'status'                 => 'active',
                'start_date'             => $startDate,
                'end_date'               => $endDate,
                'last_accrued_at'        => $startDate,
            ]);

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $wallet->id,
                'type'          => 'investment',
                'amount'        => -$amount,
                'balance_after' => $wallet->balance,
                'description'   => "Investment in {$plan->name}",
                'reference'     => 'INV-' . $investment->id,
                'status'        => 'completed',
            ]);

            Log::info('Investment created', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount'  => $amount,
            ]);

            return $investment->fresh(['plan', 'user']);
        });
    }

    /**
     * Credit daily interest for a single investment.
     */
    public function accrue(Investment $investment): bool
    {
        if ($investment->status !== 'active') return false;
        if ($investment->last_accrued_at && $investment->last_accrued_at->isToday()) return false;

        return DB::transaction(function () use ($investment) {
            $wallet = $investment->user->wallet;
            if (!$wallet) return false;

            $wallet->balance += $investment->daily_profit;
            $wallet->save();

            $investment->remaining_days = max(0, $investment->remaining_days - 1);
            $investment->last_accrued_at = now();
            if ($investment->remaining_days === 0) {
                $investment->status = 'completed';
            }
            $investment->save();

            Transaction::create([
                'user_id'       => $investment->user_id,
                'wallet_id'     => $wallet->id,
                'type'          => 'interest',
                'amount'        => $investment->daily_profit,
                'balance_after' => $wallet->balance,
                'description'   => "Daily interest · {$investment->plan_name}",
                'reference'     => 'INT-' . $investment->id . '-' . now()->format('Ymd'),
                'status'        => 'completed',
            ]);

            return true;
        });
    }

    /**
     * Accrue interest for all active investments (used by scheduler).
     */
    public function accrueAll(): int
    {
        $count = 0;
        Investment::active()
            ->where(function ($q) {
                $q->whereNull('last_accrued_at')
                  ->orWhere('last_accrued_at', '<', now()->startOfDay());
            })
            ->chunkById(100, function ($list) use (&$count) {
                foreach ($list as $inv) {
                    if ($this->accrue($inv)) $count++;
                }
            });
        return $count;
    }
}
