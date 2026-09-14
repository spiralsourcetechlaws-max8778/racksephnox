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
use RuntimeException;

class MachineService
{
    public const CYCLE_DAYS = 14;
    public const EARLY_PENALTY = 20;   // %
    public const REFERRAL_RATE = 5;    // %

    /* ============================================================
     |  READ
     ============================================================ */

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
            throw new RuntimeException("VIP level {$level} not found for {$machine->code}.");
        }
        return $vip;
    }

    /* ============================================================
     |  INVEST
     ============================================================ */

    public function invest(User $user, Machine $machine, int $vipLevel, float $amount): MachineInvestment
    {
        $vip = $this->getVipTier($machine, $vipLevel);

        if (!$machine->is_active) {
            throw new RuntimeException('This machine is not currently active.');
        }
        if ($amount < $vip->start_amount) {
            throw new RuntimeException(
                'Minimum investment for VIP ' . $vipLevel . ' is KES ' . number_format($vip->start_amount, 2)
            );
        }
        if ($vip->max_amount && $amount > $vip->max_amount) {
            throw new RuntimeException(
                'Maximum investment for VIP ' . $vipLevel . ' is KES ' . number_format($vip->max_amount, 2)
            );
        }

        $wallet = $user->wallet ?? Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        if ($wallet->balance < $amount) {
            throw new RuntimeException('Insufficient wallet balance.');
        }

        return DB::transaction(function () use ($user, $machine, $vip, $amount, $wallet) {

            // 1. Debit wallet
            $wallet->balance -= $amount;
            $wallet->save();

            // 2. Calculate projections
            $days          = $machine->duration_days ?? self::CYCLE_DAYS;
            $growthRate    = (float) $vip->growth_rate;
            $totalProfit   = round($amount * $growthRate / 100, 2);
            $dailyProfit   = round($totalProfit / $days, 2);
            $startDate     = now();
            $endDate       = $startDate->copy()->addDays($days);

            // 3. Create the investment record
            $investment = MachineInvestment::create([
                'user_id'                => $user->id,
                'machine_id'             => $machine->id,
                'vip_level'              => $vip->level,
                'amount'                 => $amount,
                'daily_profit'           => $dailyProfit,
                'total_projected_profit' => $totalProfit,
                'profit_credited'        => 0,
                'status'                 => 'active',
                'start_date'             => $startDate,
                'end_date'               => $endDate,
                'last_accrued_at'        => $startDate,
                'withdrawn'              => false,
                'early_withdrawn'        => false,
                'penalty_applied'        => 0,
            ]);

            // 4. Record the transaction
            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $wallet->id,
                'type'          => 'machine_investment',
                'amount'        => -$amount,
                'balance_after' => $wallet->balance,
                'description'   => "Invested in {$machine->name} · VIP {$vip->level}",
                'reference'     => 'INV-' . $investment->id,
                'status'        => 'completed',
            ]);

            // 5. Referral bonus (if user was referred)
            $this->payReferralBonus($user, $amount);

            Log::info('Machine investment created', [
                'user_id' => $user->id,
                'machine' => $machine->code,
                'vip'     => $vip->level,
                'amount'  => $amount,
            ]);

            return $investment->fresh(['machine', 'user']);
        });
    }

    /* ============================================================
     |  ACCRUE DAILY PROFIT
     ============================================================ */

    /**
     * Credit daily profit to every active investment whose cycle has started.
     * Idempotent — runs only once per day per investment.
     */
    public function accrueDailyProfits(): int
    {
        $credited = 0;

        MachineInvestment::active()
            ->where(function ($q) {
                $q->whereNull('last_accrued_at')
                  ->orWhere('last_accrued_at', '<', now()->startOfDay());
            })
            ->chunkById(100, function ($investments) use (&$credited) {
                foreach ($investments as $inv) {
                    if ($this->accrueOne($inv)) $credited++;
                }
            });

        return $credited;
    }

    protected function accrueOne(MachineInvestment $inv): bool
    {
        if ($inv->status !== 'active') return false;
        if ($inv->days_remaining === 0) {
            $inv->update(['status' => 'completed']);
            return false;
        }

        return DB::transaction(function () use ($inv) {
            $wallet = $inv->user->wallet;

            $wallet->balance += $inv->daily_profit;
            $wallet->save();

            $inv->profit_credited += $inv->daily_profit;
            $inv->last_accrued_at  = now();
            $inv->save();

            Transaction::create([
                'user_id'       => $inv->user_id,
                'wallet_id'     => $wallet->id,
                'type'          => 'machine_profit',
                'amount'        => $inv->daily_profit,
                'balance_after' => $wallet->balance,
                'description'   => "Daily profit · {$inv->machine->name} VIP {$inv->vip_level}",
                'reference'     => 'PROFIT-' . $inv->id . '-' . now()->format('Ymd'),
                'status'        => 'completed',
            ]);

            return true;
        });
    }

    /* ============================================================
     |  WITHDRAWAL (matured cycle)
     ============================================================ */

    public function withdrawMatured(MachineInvestment $inv): float
    {
        if ($inv->withdrawn) {
            throw new RuntimeException('Already withdrawn.');
        }
        if ($inv->days_remaining > 0) {
            throw new RuntimeException('Investment cycle not yet complete.');
        }

        return DB::transaction(function () use ($inv) {
            $wallet = $inv->user->wallet;
            $payout = $inv->amount + $inv->profit_credited;

            $wallet->balance += $payout;
            $wallet->save();

            $inv->withdrawn = true;
            $inv->status    = 'completed';
            $inv->save();

            Transaction::create([
                'user_id'       => $inv->user_id,
                'wallet_id'     => $wallet->id,
                'type'          => 'machine_payout',
                'amount'        => $payout,
                'balance_after' => $wallet->balance,
                'description'   => "Matured payout · {$inv->machine->name}",
                'reference'     => 'PAYOUT-' . $inv->id,
                'status'        => 'completed',
            ]);

            return $payout;
        });
    }

    /* ============================================================
     |  EARLY WITHDRAWAL (20% penalty)
     ============================================================ */

    public function earlyWithdraw(MachineInvestment $inv): float
    {
        if ($inv->early_withdrawn || $inv->withdrawn) {
            throw new RuntimeException('Already withdrawn.');
        }
        if ($inv->status !== 'active') {
            throw new RuntimeException('Only active investments can be early withdrawn.');
        }

        return DB::transaction(function () use ($inv) {
            $wallet   = $inv->user->wallet;
            $principal = $inv->amount;
            $penalty   = round($principal * self::EARLY_PENALTY / 100, 2);
            $payout    = $principal - $penalty;

            $wallet->balance += $payout;
            $wallet->save();

            $inv->early_withdrawn = true;
            $inv->withdrawn       = true;
            $inv->penalty_applied = $penalty;
            $inv->status          = 'cancelled';
            $inv->save();

            Transaction::create([
                'user_id'       => $inv->user_id,
                'wallet_id'     => $wallet->id,
                'type'          => 'machine_early_withdrawal',
                'amount'        => $payout,
                'balance_after' => $wallet->balance,
                'description'   => "Early withdrawal (20% penalty) · {$inv->machine->name}",
                'reference'     => 'EARLY-' . $inv->id,
                'status'        => 'completed',
            ]);

            return $payout;
        });
    }

    /* ============================================================
     |  REFERRAL BONUS
     ============================================================ */

    protected function payReferralBonus(User $user, float $amount): void
    {
        if (!$user->referred_by) return;

        $referrer = User::find($user->referred_by);
        if (!$referrer || !$referrer->wallet) return;

        $bonus = round($amount * self::REFERRAL_RATE / 100, 2);
        if ($bonus <= 0) return;

        $referrer->wallet->balance += $bonus;
        $referrer->wallet->save();

        Transaction::create([
            'user_id'       => $referrer->id,
            'wallet_id'     => $referrer->wallet->id,
            'type'          => 'referral_bonus',
            'amount'        => $bonus,
            'balance_after' => $referrer->wallet->balance,
            'description'   => "Referral bonus from {$user->name}",
            'reference'     => 'REF-' . $user->id . '-' . now()->timestamp,
            'status'        => 'completed',
        ]);
    }

    /* ============================================================
     |  STATS
     ============================================================ */

    public function getDashboardStats(User $user): array
    {
        $investments = MachineInvestment::forUser($user->id);

        return [
            'total_invested'   => (float) $investments->sum('amount'),
            'total_profit'     => (float) $investments->sum('profit_credited'),
            'active_count'     => (int) $investments->clone()->active()->count(),
            'completed_count'  => (int) $investments->clone()->completed()->count(),
            'projected_profit' => (float) $investments->clone()->active()->sum('total_projected_profit'),
        ];
    }
}
