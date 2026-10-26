<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    public function form()
    {
        $accounts = Auth::user()->bankAccounts;
        return view('withdrawal.form', compact('accounts'));
    }

    public function submit(Request $request)
    {
        // Changed min:530 to min:100
        $request->validate([
            'amount' => 'required|numeric|min:100|max:1000000', 
            'bank_account_id' => 'required|exists:user_bank_accounts,id'
        ]);
        
        $user = Auth::user();
        
        if ($user->wallet->balance < $request->amount) {
            return back()->withErrors(['amount' => 'Insufficient wallet balance.']);
        }
        
        WithdrawalRequest::create([
            'user_id' => $user->id, 
            'amount' => $request->amount, 
            'bank_account_id' => $request->bank_account_id, 
            'status' => 'pending'
        ]);
        
        return back()->with('success', 'Withdrawal request submitted successfully. Pending admin approval.');
    }
}
