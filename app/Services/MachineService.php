<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineInvestment;
use App\Models\MachineVip;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MachineService
{
    public const CYCLE_DAYS     = 14;
    public const EARLY_PENALTY  = 20;
    public const REFERRAL_RATE  = 5;

    public function getActiveMachines()
    {
        return Machine::active()->orderBy('code')->with('vips')->get();
    }

    public function getMachineByCode(string $code): Machine
    {
        return Machine::where('code', $code)->with('vips')->firstOrFail();
    }

    public function getVipTiers(Machine $machine)
    {
        return $machine->vips()->orderBy('level')->get();
    }

    public function getVipTier(Machine $machine, int $level): MachineVip
    {
        $vip = $machine->vips()->where('level', $level)->first();
        if (!$vip) {
            throw new RuntimeException("VIP level {$level} is not available for {$machine->code}.");
        }
        return $vip;
    }

    /**
     * Execute a machine investment atomically.
     */
    public function invest(User $user, Machine $machine, int $vipLevel, float $amount): MachineInvestment
    {
        $vip = $this->getVipTier($machine, $vipLevel);

        if (!$machine->is_active) {
            throw new RuntimeException('This machine is not currently accepting investments.');
        }

        $min = (float) ($vip->start_amount ?? $machine->getStartAmountForVip($vipLevel));
        if ($amount < $min) {
            throw new RuntimeException('Minimum for VIP ' . $vipLevel . ' is KES ' . number_format($min, 2));
        }

        $max = (float) ($vip->max_amount ?? 0);
        if ($max > 0 && $amount > $max) {
            throw new RuntimeException('Maximum for VIP ' . $vipLevel . ' is KES ' . number_format($max, 2));
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            throw new RuntimeException('Wallet not found. Contact support.');
        }
        if ((float) $wallet->balance < $amount) {
            throw new RuntimeException(
                'Insufficient balance. Your wallet: KES ' . number_format((float) $wallet->balance, 2)
                . ', required: KES ' . number_format($amount, 2)
            );
        }

        return DB::transaction(function () use ($user, $machine, $vip, $amount, $wallet) {
            $wallet->debit($amount, "Machine investment: {$machine->name} · VIP {$vip->level}", 'machine_investment');

            $days        = (int) ($machine->duration_days ?: self::CYCLE_DAYS);
            $growthRate  = (float) ($vip->growth_rate ?? $machine->growth_rate);
            $totalProfit = round($amount * $growthRate / 100, 2);
            $dailyProfit = $days > 0 ? round($totalProfit / $days, 2) : 0;
            $startDate   = now();
            $endDate     = $startDate->copy()->addDays($days);

            $cols = Schema::getColumnListing('machine_investments');
            $payload = [
                'user_id'         => $user->id,
                'machine_id'      => $machine->id,
                'vip_level'       => $vip->level,
                'amount'          => $amount,
                'daily_profit'    => $dailyProfit,
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'status'          => 'active',
                'profit_credited' => 0,
            ];

            if (in_array('total_projected_profit', $cols)) $payload['total_projected_profit'] = $totalProfit;
            if (in_array('total_return', $cols))           $payload['total_return'] = $amount + $totalProfit;
            if (in_array('last_accrued_at', $cols))        $payload['last_accrued_at'] = $startDate;
            if (in_array('last_profit_date', $cols))       $payload['last_profit_date'] = $startDate;
            if (in_array('withdrawn', $cols))              $payload['withdrawn'] = false;
            if (in_array('early_withdrawn', $cols))        $payload['early_withdrawn'] = false;
            if (in_array('penalty_applied', $cols))        $payload['penalty_applied'] = 0;

            $investment = MachineInvestment::create($payload);

            Transaction::where('user_id', $user->id)
                ->where('type', 'machine_investment')
                ->where('reference', null)
                ->latest('id')
                ->limit(1)
                ->update(['reference' => 'INV-' . $investment->id]);

            $this->payReferralBonus($user, $amount);

            Log::info('Machine investment created', [
                'investment_id' => $investment->id,
                'user_id'       => $user->id,
                'machine'       => $machine->code,
                'vip'           => $vip->level,
                'amount'        => $amount,
                'daily_profit'  => $dailyProfit,
                'total_profit'  => $totalProfit,
            ]);

            return $investment->fresh(['machine', 'user']);
        });
    }

    public function accrueDailyProfits(): int
    {
        $credited = 0;
        $cols = Schema::getColumnListing('machine_investments');
        $accrueCol = in_array('last_accrued_at', $cols) ? 'last_accrued_at'
                   : (in_array('last_profit_date', $cols) ? 'last_profit_date' : null);

        if (!$accrueCol) {
            Log::warning('accrueDailyProfits: no accrual timestamp column found');
            return 0;
        }

        MachineInvestment::active()
            ->where(function ($q) use ($accrueCol) {
                $q->whereNull($accrueCol)->orWhere($accrueCol, '<', now()->startOfDay());
            })
            ->chunkById(100, function ($investments) use (&$credited, $accrueCol) {
                foreach ($investments as $inv) {
                    try {
                        if ($this->accrueOne($inv, $accrueCol)) $credited++;
                    } catch (\Throwable $e) {
                        Log::error('accrueOne failed', ['investment_id' => $inv->id, 'error' => $e->getMessage()]);
                    }
                }
            });

        return $credited;
    }

    protected function accrueOne(MachineInvestment $inv, string $accrueCol): bool
    {
        if ($inv->status !== 'active') return false;
        if ($inv->end_date && $inv->end_date->isPast()) {
            $inv->update(['status' => 'completed']);
            return false;
        }

        $daily = (float) ($inv->daily_profit ?? 0);
        if ($daily <= 0) {
            $inv->update([$accrueCol => now()]);
            return false;
        }

        return DB::transaction(function () use ($inv, $daily, $accrueCol) {
            $wallet = $inv->user->wallet;
            if (!$wallet) {
                Log::warning('Investment user has no wallet', ['investment_id' => $inv->id]);
                return false;
            }

            $wallet->credit($daily, "Daily profit: {$inv->machine->name} VIP {$inv->vip_level}", 'machine_profit');

            $inv->profit_credited = (float) $inv->profit_credited + $daily;
            $inv->{$accrueCol} = now();
            $inv->save();

            return true;
        });
    }

    public function withdrawMatured(MachineInvestment $inv): float
    {
        if ($inv->withdrawn || $inv->status === 'completed') {
            throw new RuntimeException('Already withdrawn.');
        }
        if ($inv->end_date && $inv->end_date->isFuture()) {
            throw new RuntimeException('Investment cycle not yet complete.');
        }

        return DB::transaction(function () use ($inv) {
            $wallet = $inv->user->wallet;
            $payout = (float) $inv->amount + (float) $inv->profit_credited;

            $wallet->credit($payout, "Matured payout: {$inv->machine->name}", 'machine_payout');

            $inv->withdrawn = true;
            $inv->status    = 'completed';
            $inv->save();

            return $payout;
        });
    }

    public function earlyWithdraw(MachineInvestment $inv): float
    {
        if ($inv->withdrawn || $inv->status !== 'active') {
            throw new RuntimeException('Only active, unwithdrawn investments can be early withdrawn.');
        }

        return DB::transaction(function () use ($inv) {
            $wallet    = $inv->user->wallet;
            $principal = (float) $inv->amount;
            $penalty   = round($principal * self::EARLY_PENALTY / 100, 2);
            $payout    = $principal - $penalty;

            $wallet->credit($payout, "Early withdrawal (20% penalty): {$inv->machine->name}", 'machine_early_withdrawal');

            $inv->early_withdrawn = true;
            $inv->withdrawn       = true;
            $inv->penalty_applied = $penalty;
            $inv->status          = 'cancelled';
            $inv->save();

            return $payout;
        });
    }

    protected function payReferralBonus(User $user, float $amount): void
    {
        if (!$user->referred_by) return;

        $referrer = User::find($user->referred_by);
        if (!$referrer || !$referrer->wallet) return;

        $bonus = round($amount * self::REFERRAL_RATE / 100, 2);
        if ($bonus <= 0) return;

        $referrer->wallet->credit($bonus, "Referral bonus from {$user->name}", 'referral_bonus');
    }

    /**
     * Aggregated user stats for the Machines index page.
     *
     * Rewritten to avoid `->clone()` (Eloquent Builder does not expose a
     * named `clone()` method in vanilla Laravel — only the `__clone` magic
     * method triggered by `clone $builder`). Also guards the
     * `total_projected_profit` column so a missing column never 500s.
     */
    public function getDashboardStats(User $user): array
    {
        $base = MachineInvestment::where('user_id', $user->id);
        $hasProjected = Schema::hasColumn('machine_investments', 'total_projected_profit');

        return [
            'total_invested'   => (float) (clone $base)->sum('amount'),
            'total_profit'     => (float) (clone $base)->sum('profit_credited'),
            'active_count'     => (int)   (clone $base)->where('status', 'active')->count(),
            'completed_count'  => (int)   (clone $base)->where('status', 'completed')->count(),
            'projected_profit' => $hasProjected
                ? (float) (clone $base)->where('status', 'active')->sum('total_projected_profit')
                : 0.0,
        ];
    }
}
