<?php

namespace App\Http\Controllers;

use App\Services\Mpesa\StkPush;
use App\Services\Mpesa\CallbackHandler;
use App\Services\Mpesa\B2C;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    protected $stkPush;
    protected $callbackHandler;
    protected $b2c;

    public function __construct(StkPush $stkPush, CallbackHandler $callbackHandler, B2C $b2c)
    {
        $this->stkPush = $stkPush;
        $this->callbackHandler = $callbackHandler;
        $this->b2c = $b2c;
    }

    public function showDepositForm()
    {
        return view('mpesa.deposit');
    }

    public function initiateDeposit(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:10|max:150000',
        ]);

        $user = Auth::user();
        $reference = 'DEP' . $user->id . time();

        try {
            $response = $this->stkPush->initiate($request->phone, $request->amount, $reference);
            return response()->json(['message' => 'STK Push sent. Check your phone.']);
        } catch (\Exception $e) {
            Log::error('Deposit initiation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Could not initiate payment. Please try again.'], 500);
        }
    }

    public function callback(Request $request)
    {
        Log::info('M-Pesa callback received', $request->all());
        $this->callbackHandler->handleStkPush($request->all());
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    public function showWithdrawalForm()
    {
        $user = Auth::user();
        return view('mpesa.withdraw', compact('user'));
    }

    public function initiateWithdrawal(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:100',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        if ($user->wallet->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance.'], 422);
        }

        try {
            $reference = 'WDR' . $user->id . time();
            $response = $this->b2c->send($request->phone, $amount, $reference);
            $user->wallet->debit($amount, 'Withdrawal request: ' . $reference);
            return response()->json(['message' => 'Withdrawal request submitted.']);
        } catch (\Exception $e) {
            Log::error('Withdrawal initiation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Could not process withdrawal.'], 500);
        }
    }
}
