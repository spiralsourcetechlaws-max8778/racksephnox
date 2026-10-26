<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositVerificationController extends Controller
{
    public function index()
    {
        $deposits = DepositRequest::with('user')->where('status', 'pending')->latest()->paginate(20);
        return view('admin.deposits.index', compact('deposits'));
    }

    public function verify(Request $request, DepositRequest $deposit)
    {
        if ($deposit->status !== 'pending') {
            return back()->withErrors(['error' => 'Deposit already processed.']);
        }

        DB::transaction(function () use ($deposit) {
            $deposit->update(['status' => 'approved']);
            // Credit the user's wallet
            $deposit->user->wallet->credit($deposit->amount, 'Deposit Approved #' . $deposit->id);
        });

        return back()->with('success', 'Deposit verified and user wallet credited.');
    }

    public function reject(Request $request, DepositRequest $deposit)
    {
        $deposit->update(['status' => 'rejected']);
        return back()->with('success', 'Deposit request rejected.');
    }
}
