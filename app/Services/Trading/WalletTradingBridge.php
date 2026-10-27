<?php

namespace App\Services\Trading;

use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletTradingBridge
{
    /**
     * Move funds from KES wallet → trading account.
     */
    public function walletToTrading(User $user, float $amount): void
    {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }

        DB::transaction(function () use ($user, $amount) {
            $wallet = $user->wallet;
            if (!$wallet) {
                throw new RuntimeException('You have no wallet. Contact support.');
            }
            if ((float) $wallet->balance < $amount) {
                throw new RuntimeException(
                    'Insufficient wallet balance. Available: KES ' . number_format((float) $wallet->balance, 2)
                );
            }

            $account = TradingAccount::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
            );

            $wallet->debit($amount, 'Transfer to trading account', 'transfer_out');

            $account->balance = (float) $account->balance + $amount;
            $account->save();

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $wallet->id,
                'type'          => 'trading_deposit',
                'amount'        => $amount,
                'balance_after' => $account->balance,
                'description'   => 'Funds transferred to trading',
                'reference'     => 'TRD-IN-' . $user->id . '-' . now()->timestamp,
                'status'        => 'completed',
            ]);
        });
    }

    /**
     * Move funds from trading account → KES wallet.
     */
    public function tradingToWallet(User $user, float $amount): void
    {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }

        DB::transaction(function () use ($user, $amount) {
            $account = $user->tradingAccount;
            if (!$account) {
                throw new RuntimeException('You have no trading account.');
            }
            if ((float) $account->balance < $amount) {
                throw new RuntimeException(
                    'Insufficient trading balance. Available: KES ' . number_format((float) $account->balance, 2)
                );
            }

            $wallet = $user->wallet;
            if (!$wallet) {
                throw new RuntimeException('You have no wallet. Contact support.');
            }

            $account->balance = (float) $account->balance - $amount;
            $account->save();

            $wallet->credit($amount, 'Transfer from trading account', 'transfer_in');

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $wallet->id,
                'type'          => 'trading_withdrawal',
                'amount'        => -$amount,
                'balance_after' => $account->balance,
                'description'   => 'Funds withdrawn from trading',
                'reference'     => 'TRD-OUT-' . $user->id . '-' . now()->timestamp,
                'status'        => 'completed',
            ]);
        });
    }
}
