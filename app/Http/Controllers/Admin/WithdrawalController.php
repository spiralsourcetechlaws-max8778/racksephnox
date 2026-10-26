<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index()
    {
        $withdrawals = WithdrawalRequest::with(['user', 'bankAccount'])->where('status', 'pending')->latest()->paginate(20);
        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    public function complete(Request $request, WithdrawalRequest $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['error' => 'Withdrawal already processed.']);
        }

        // Funds were already deducted at submission. Just mark as completed.
        $withdrawal->update(['status' => 'completed']);

        return back()->with('success', 'Withdrawal marked as completed.');
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['error' => 'Withdrawal already processed.']);
        }

        DB::transaction(function () use ($withdrawal) {
            $withdrawal->update(['status' => 'rejected']);
            // REFUND the user's wallet since we held the funds earlier
            $withdrawal->user->wallet->credit($withdrawal->amount, 'Withdrawal Rejected #' . $withdrawal->id);
        });

        return back()->with('success', 'Withdrawal rejected and funds refunded to user.');
    }
}
