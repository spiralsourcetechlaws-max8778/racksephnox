<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mpesa\B2C;
use App\Services\Mpesa\CallbackHandler;
use App\Services\Mpesa\StkPush;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(
        protected StkPush $stkPush,
        protected B2C $b2c,
        protected CallbackHandler $callbackHandler
    ) {}

    public function stkPush(Request $request)
    {
        $limits = config('mpesa.limits');

        $request->validate([
            'phone'  => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:' . $limits['min_deposit'] . '|max:' . $limits['max_deposit'],
        ]);

        $user = $request->user();

        try {
            $this->stkPush->initiate($user, $request->phone, (float) $request->amount);
            return response()->json([
                'success' => true,
                'message' => 'STK Push sent. Enter your M-Pesa PIN on your phone to complete.',
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('API STK push failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('API STK push exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Payment service unavailable'], 500);
        }
    }

    public function withdraw(Request $request)
    {
        $limits = config('mpesa.limits');

        $request->validate([
            'phone'  => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:' . $limits['min_withdrawal'] . '|max:' . $limits['max_withdrawal'],
        ]);

        $user = $request->user();
        $amount = (float) $request->amount;
        $fee    = WithdrawalService::calculateFee($amount);

        if (!$user->wallet || (float) $user->wallet->balance < ($amount + $fee)) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance (including fee)'], 422);
        }

        // CRITICAL: debit wallet BEFORE calling B2C so failure can't create money
        try {
            DB::transaction(function () use ($user, $request, $amount, $fee) {
                $user->wallet->debit($amount + $fee, 'M-Pesa withdrawal request');

                $reference = 'WDR' . $user->id . now()->format('His');

                \App\Models\WithdrawalRequest::create([
                    'user_id'    => $user->id,
                    'amount'     => $amount,
                    'fee'        => $fee,
                    'net_amount' => $amount,
                    'phone'      => $request->phone,
                    'status'     => 'processing',
                    'admin_notes' => 'Auto B2C via API',
                ]);

                $this->b2c->send($user, $request->phone, $amount, $reference, 'Withdrawal');
            });

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal submitted. You will receive funds shortly.',
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('API B2C withdrawal failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('API B2C withdrawal exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Withdrawal failed'], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $this->callbackHandler->handleStkPush($request->all());
        } catch (\Throwable $e) {
            Log::error('STK callback exception', ['error' => $e->getMessage()]);
        }
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function b2cResult(Request $request)
    {
        try {
            $this->callbackHandler->handleB2CResult($request->all());
        } catch (\Throwable $e) {
            Log::error('B2C result exception', ['error' => $e->getMessage()]);
        }
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function b2cTimeout(Request $request)
    {
        try {
            $this->callbackHandler->handleB2CTimeout($request->all());
        } catch (\Throwable $e) {
            Log::error('B2C timeout exception', ['error' => $e->getMessage()]);
        }
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
