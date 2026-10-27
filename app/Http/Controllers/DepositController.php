<?php

namespace App\Http\Controllers;

use App\Models\DepositRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function form()
    {
        return view('deposit.form');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'amount'           => 'required|numeric|min:10|max:500000',
            'transaction_code' => 'required|string|max:50|unique:deposit_requests,transaction_reference',
        ], [
            'transaction_code.unique' => 'This M-Pesa transaction code has already been submitted.',
        ]);

        try {
            DepositRequest::create([
                'user_id'               => Auth::id(),
                'amount'                => (float) $request->amount,
                'transaction_reference' => strtoupper($request->transaction_code),
                'phone_number'          => Auth::user()->phone,
                'status'                => 'pending',
            ]);

            return back()->with('success', 'Deposit request submitted. An admin will verify it shortly.');
        } catch (\Throwable $e) {
            \Log::error('Deposit submit failed', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return back()->withErrors(['error' => 'Could not submit deposit request. Please try again.']);
        }
    }
}
