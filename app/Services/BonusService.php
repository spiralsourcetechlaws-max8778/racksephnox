<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BonusService
{
    /**
     * Credit a signup bonus to a newly registered user.
     * Idempotent on (signup, user.id).
     */
    public function creditSignupBonus(User $user): ?float
    {
        return $this->credit(
            $user,
            (float) config('bonuses.signup'),
            'signup',
            $user->id,
            'Welcome bonus'
        );
    }

    /**
     * Credit a referral bonus to the REFERRER when a new user registers.
     * Idempotent on (referral, referred.id) — one bonus per referral relationship.
     */
    public function creditReferralBonus(User $referrer, User $referred): ?float
    {
        return $this->credit(
            $referrer,
            (float) config('bonuses.referral'),
            'referral',
            $referred->id,
            "Referral bonus — {$referred->name} ({$referred->email}) joined via your link"
        );
    }

    /**
     * Credit a machine activation bonus to the investor.
     * Idempotent on (machine_activation, machine_investment.id).
     */
    public function creditMachineActivationBonus(User $user, $machine, int $investmentId): ?float
    {
        return $this->credit(
            $user,
            (float) config('bonuses.rx_machine_activation'),
            'machine_activation',
            $investmentId,
            "Activation bonus — {$machine->code} ({$machine->name})"
        );
    }

    /**
     * Core credit method. Returns amount credited, or null if skipped
     * (already credited, or zero amount).
     */
    protected function credit(
        User $user,
        float $amount,
        string $bonusType,
        int $referenceId,
        string $description
    ): ?float {
        if ($amount <= 0) {
            return null;
        }

        try {
            return DB::transaction(function () use ($user, $amount, $bonusType, $referenceId, $description) {
                // Idempotency check via unique index
                $already = DB::table('bonus_credits')
                    ->where('bonus_type', $bonusType)
                    ->where('reference_id', $referenceId)
                    ->exists();

                if ($already) {
                    return null;
                }

                // Wallet: create if missing
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0, 'locked_balance' => 0]
                );

                // Credit
                $wallet->balance = (float) $wallet->balance + $amount;
                $wallet->save();

                // Log the transaction
                Transaction::forceCreate([
                    'user_id'       => $user->id,
                    'wallet_id'     => $wallet->id,
                    'type'          => $bonusType . '_bonus',
                    'amount'        => $amount,
                    'balance_after' => $wallet->balance,
                    'description'   => $description,
                    'reference'     => strtoupper($bonusType) . '-' . Str::ulid(),
                    'status'        => 'completed',
                ]);

                // Record the bonus credit (idempotency guard)
                DB::table('bonus_credits')->insert([
                    'user_id'      => $user->id,
                    'bonus_type'   => $bonusType,
                    'reference_id' => $referenceId,
                    'amount'       => $amount,
                    'description'  => $description,
                    'credited_at'  => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                return $amount;
            });
        } catch (\Throwable $e) {
            // A unique constraint hit here means a race — treat as already credited
            if (str_contains($e->getMessage(), 'bonus_credits')) {
                Log::info('Bonus already credited (race)', [
                    'user_id' => $user->id,
                    'bonus_type' => $bonusType,
                    'reference_id' => $referenceId,
                ]);
                return null;
            }

            report($e);
            return null;
        }
    }
}
