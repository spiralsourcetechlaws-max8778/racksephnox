<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use App\Services\Mpesa\B2C;
use App\Services\Mpesa\StkPush;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(
        protected StkPush $stkPush,
        protected B2C $b2c
    ) {}

    public function showDepositForm()
    {
        return view('mpesa.deposit');
    }

    public function initiateDeposit(Request $request)
    {
        $limits = config('mpesa.limits');

        $request->validate([
            'phone'  => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:' . $limits['min_deposit'] . '|max:' . $limits['max_deposit'],
        ]);

        $user = Auth::user();

        try {
            $this->stkPush->initiate($user, $request->phone, (float) $request->amount);
            return response()->json(['message' => 'STK Push sent. Check your phone and enter your M-Pesa PIN.']);
        } catch (\RuntimeException $e) {
            Log::warning('Web STK push failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Web STK push exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Payment service unavailable. Try again later.'], 500);
        }
    }

    public function showWithdrawalForm()
    {
        $user = Auth::user();
        $feePercent = config('mpesa.fees.withdrawal_percent', 1.0);
        $feeMin     = config('mpesa.fees.withdrawal_min', 10);
        $feeMax     = config('mpesa.fees.withdrawal_max', 100);

        return view('mpesa.withdraw', compact('user', 'feePercent', 'feeMin', 'feeMax'));
    }

    public function initiateWithdrawal(Request $request)
    {
        $limits = config('mpesa.limits');

        $request->validate([
            'phone'  => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:' . $limits['min_withdrawal'] . '|max:' . $limits['max_withdrawal'],
        ]);

        $user = Auth::user();
        $amount = (float) $request->amount;
        $fee    = WithdrawalService::calculateFee($amount);

        if (!$user->wallet || (float) $user->wallet->balance < ($amount + $fee)) {
            return response()->json(['error' => 'Insufficient balance (including fee of KES ' . number_format($fee, 2) . ')'], 422);
        }

        // CRITICAL: debit wallet and create withdrawal record BEFORE calling B2C.
        // If B2C fails, CallbackHandler refunds the wallet.
        try {
            DB::transaction(function () use ($user, $request, $amount, $fee) {
                $user->wallet->debit($amount + $fee, 'M-Pesa withdrawal request');

                WithdrawalRequest::create([
                    'user_id'    => $user->id,
                    'amount'     => $amount,
                    'fee'        => $fee,
                    'net_amount' => $amount,
                    'phone'      => $request->phone,
                    'status'     => 'processing',
                    'admin_notes' => 'Auto B2C',
                ]);

                $reference = 'WDR' . $user->id . now()->format('His');
                $this->b2c->send($user, $request->phone, $amount, $reference, 'Withdrawal');
            });

            return response()->json(['message' => 'Withdrawal submitted. Funds will reach your M-Pesa shortly.']);
        } catch (\RuntimeException $e) {
            Log::warning('Web B2C withdrawal failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Web B2C withdrawal exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Withdrawal failed. Please try again or contact support.'], 500);
        }
    }
}
