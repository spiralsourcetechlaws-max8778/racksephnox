<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BonusService
{
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
     * Core credit. Uses Wallet::credit() which atomically increments the
     * balance and creates the transaction (existing model helper). Adds
     * the bonus_credits idempotency guard on top.
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
                // Idempotency check (also enforced by unique index)
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

                // Use existing Wallet::credit() — handles increment + transaction log
                $wallet->credit($amount, $description, $bonusType . '_bonus');

                // Record the idempotency guard row
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
