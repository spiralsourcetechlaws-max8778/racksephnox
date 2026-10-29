<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WalletController extends Controller
{
    public function adjust(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'type'   => 'required|in:credit,debit',
            'reason' => 'required|string|max:255',
        ]);

        $wallet = $user->wallet;
        if (!$wallet) return back()->withErrors(['error' => 'User does not have a wallet.']);

        $amount = abs($validated['amount']);
        $description = 'Admin Adjustment (' . ucfirst($validated['type']) . '): ' . $validated['reason'];

        try {
            if ($validated['type'] === 'credit') {
                $wallet->credit($amount, $description);
            } else {
                $wallet->debit($amount, $description);
            }

            // Defensive Audit Log
            if (Schema::hasTable('audit_logs')) {
                $cols = Schema::getColumnListing('audit_logs');
                $row = [
                    'action' => 'wallet_adjustment',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                if (in_array('user_id', $cols)) $row['user_id'] = auth()->id();
                if (in_array('ip_address', $cols)) $row['ip_address'] = $request->ip();
                if (in_array('new_values', $cols)) $row['new_values'] = json_encode(['target_user' => $user->id, 'amount' => $amount, 'type' => $validated['type'], 'reason' => $validated['reason']]);
                DB::table('audit_logs')->insert($row);
            }
        } catch (\Throwable $e) {
            Log::error('Wallet adjustment failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Transaction failed: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Wallet adjusted successfully by KES ' . number_format($amount, 2));
    }
}
