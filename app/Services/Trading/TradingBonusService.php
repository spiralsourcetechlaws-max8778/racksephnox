<?php

namespace App\Services\Trading;

use App\Models\TradingAccount;
use App\Models\TradingBonusTracker;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TradingBonusService
{
    public function creditSignupBonus(User $user, float $amount = 100.0): ?TradingBonusTracker
    {
        if (TradingBonusTracker::where('user_id', $user->id)->where('bonus_type', 'signup')->exists()) {
            return null;
        }

        return DB::transaction(function () use ($user, $amount) {
            $tracker = TradingBonusTracker::create([
                'user_id'         => $user->id,
                'bonus_type'      => 'signup',
                'bonus_amount'    => $amount,
                'required_volume' => $amount * 10,
                'achieved_volume' => 0,
                'is_claimed'      => false,
                'expires_at'      => now()->addDays(30),
            ]);

            $account = TradingAccount::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );
            $account->balance += $amount;
            $account->save();

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => optional($user->wallet)->id ?? 0,
                'type'          => 'trading_bonus',
                'amount'        => $amount,
                'balance_after' => $account->balance,
                'description'   => 'Welcome trading bonus',
                'reference'     => 'BONUS-SIGNUP-' . $user->id,
                'status'        => 'completed',
            ]);

            return $tracker;
        });
    }

    public function recordVolume(User $user, float $kesVolume): void
    {
        TradingBonusTracker::where('user_id', $user->id)
            ->where('is_claimed', false)
            ->get()
            ->each(function (TradingBonusTracker $t) use ($kesVolume) {
                $t->achieved_volume += $kesVolume;
                if ($t->achieved_volume >= $t->required_volume) {
                    $t->is_claimed = true;
                }
                $t->save();
            });
    }
}
