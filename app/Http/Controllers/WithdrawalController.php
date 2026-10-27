<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function form()
    {
        $user = Auth::user();
        $accounts = $user->bankAccounts;
        $feePercent = config('mpesa.fees.withdrawal_percent', 1.0);
        $feeMin     = config('mpesa.fees.withdrawal_min', 10);
        $feeMax     = config('mpesa.fees.withdrawal_max', 100);

        return view('withdrawal.form', compact('accounts', 'feePercent', 'feeMin', 'feeMax'));
    }

    public function submit(Request $request)
    {
        $limits = config('mpesa.limits');

        $request->validate([
            'amount'          => 'required|numeric|min:' . $limits['min_withdrawal'] . '|max:' . $limits['max_withdrawal'],
            'bank_account_id' => 'required|exists:user_bank_accounts,id',
        ]);

        $user = Auth::user();
        $amount = (float) $request->amount;
        $fee    = WithdrawalService::calculateFee($amount);
        $total  = $amount + $fee;

        if (!$user->wallet || (float) $user->wallet->balance < $total) {
            return back()->withErrors([
                'amount' => 'Insufficient wallet balance. Required: KES ' . number_format($total, 2)
                    . ' (includes KES ' . number_format($fee, 2) . ' fee).',
            ]);
        }

        try {
            // CRITICAL: debit wallet immediately so user cannot double-spend.
            // If admin rejects the withdrawal later, they refund via admin panel.
            DB::transaction(function () use ($user, $request, $amount, $fee) {
                $user->wallet->debit($amount + $fee, 'Bank withdrawal request');

                WithdrawalRequest::create([
                    'user_id'         => $user->id,
                    'amount'          => $amount,
                    'fee'             => $fee,
                    'net_amount'      => $amount,
                    'bank_account_id' => $request->bank_account_id,
                    'status'          => 'pending',
                ]);
            });

            return back()->with('success', 'Withdrawal request submitted. Funds are on hold pending admin approval.');
        } catch (\Throwable $e) {
            \Log::error('Withdrawal submit failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return back()->withErrors(['error' => 'Could not process withdrawal. Please try again.']);
        }
    }
}
