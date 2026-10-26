<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function adjust(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'type' => 'required|in:credit,debit',
            'reason' => 'required|string|max:255',
        ]);

        $wallet = $user->wallet;
        
        if (!$wallet) {
            return back()->withErrors(['error' => 'User does not have a wallet.']);
        }

        $amount = abs($validated['amount']);
        $description = 'Admin Adjustment (' . ucfirst($validated['type']) . '): ' . $validated['reason'];

        // Perform the transaction
        if ($validated['type'] === 'credit') {
            $wallet->credit($amount, $description);
        } else {
            if ($wallet->balance < $amount) {
                return back()->withErrors(['error' => 'Insufficient user balance for this debit.']);
            }
            $wallet->debit($amount, $description);
        }

        // Commercial Standard: Audit Logging
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'action' => 'wallet_adjustment',
                'details' => $description . ' | Amount: KES ' . number_format($amount, 2),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // Fallback to standard log if AuditLog table has schema issues
            Log::warning('AuditLog failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Wallet adjusted successfully by KES ' . number_format($amount, 2));
    }
}
