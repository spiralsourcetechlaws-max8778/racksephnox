<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('referral_code', 'like', '%' . $search . '%');
            });
        }
        
        $users = $query->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        // Eager-load all relationships to avoid N+1 queries
        $user->load([
            'wallet',
            'machineInvestments.machine',
            'tradingAccount',
            'tradeOrders' => fn($q) => $q->latest()->limit(10),
            'depositRequests' => fn($q) => $q->latest()->limit(10),
            'withdrawalRequests' => fn($q) => $q->latest()->limit(10),
            'transactions' => fn($q) => $q->latest()->limit(15),
            'loans' => fn($q) => $q->latest()->limit(10),
            'lotterySpins' => fn($q) => $q->latest()->limit(10),
            'referrals',
            'bankAccounts',
            'tradingProfile',
            'loanCreditScore',
        ]);

        // Compute summary stats in one pass (not in the blade)
        $stats = [
            'wallet_balance'          => (float) ($user->wallet?->balance ?? 0),
            'wallet_locked'           => (float) ($user->wallet?->locked_balance ?? 0),
            'total_deposited'         => (float) $user->depositRequests()->whereIn('status', ['approved', 'completed'])->sum('amount'),
            'total_withdrawn'         => (float) $user->withdrawalRequests()->whereIn('status', ['completed', 'approved'])->sum('amount'),
            'pending_deposits'        => $user->depositRequests()->where('status', 'pending')->count(),
            'pending_withdrawals'     => $user->withdrawalRequests()->where('status', 'pending')->count(),
            'total_invested'          => (float) $user->machineInvestments->sum('amount'),
            'active_machines'         => $user->machineInvestments->where('status', 'active')->count(),
            'total_lottery_bets'      => (float) $user->lotterySpins->sum('bet_amount'),
            'total_lottery_wins'      => (float) $user->lotterySpins->sum('win_amount'),
            'total_referrals'         => $user->referrals->count(),
            'total_loans_principal'   => (float) $user->loans->sum('principal'),
            'active_loans'            => $user->loans->where('status', 'active')->count(),
            'trading_balance'         => (float) ($user->tradingAccount?->balance ?? 0),
            'trading_pnl'             => (float) ($user->tradingAccount?->total_pnl ?? 0),
            'open_orders'             => $user->tradeOrders->whereIn('status', ['open', 'pending'])->count(),
            'transaction_count'       => $user->transactions->count(),
            'credit_score'            => $user->loanCreditScore?->score ?? null,
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'is_admin' => 'boolean',
            'is_verified' => 'boolean',
            'kyc_status' => 'string|in:pending,verified,rejected,approved',
        ]);

        $user->update($validated);
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function toggleAdmin(User $user)
    {
        $user->is_admin = !$user->is_admin;
        $user->save();
        return back()->with('success', 'Admin status toggled.');
    }

    public function toggleVip(User $user)
    {
        $user->is_vip = !$user->is_vip;
        $user->save();
        return back()->with('success', 'VIP status updated.');
    }

    public function togglePromo(User $user)
    {
        $user->has_promo = !$user->has_promo;
        $user->promo_expires_at = $user->has_promo ? now()->addDays(7) : null;
        $user->save();
        return back()->with('success', 'Promo status updated.');
    }

    public function export()
    {
        $users = User::all();
        $filename = 'users_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Referral Code', 'Admin', 'VIP', 'KYC Status', 'Balance', 'Joined']);
            foreach ($users as $u) {
                fputcsv($file, [
                    $u->id, $u->name, $u->email, $u->phone, $u->referral_code,
                    $u->is_admin ? 'Yes' : 'No',
                    $u->is_vip ? 'Yes' : 'No',
                    $u->kyc_status ?? 'pending',
                    $u->wallet?->balance ?? 0,
                    $u->created_at?->format('Y-m-d'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
