<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('wallet');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        if ($request->filled('role')) {
            match ($request->role) {
                'admin' => $query->where('is_admin', true),
                'user'  => $query->where('is_admin', false),
                default => $query->where('admin_role', $request->role),
            };
        }
        $users = $query->latest()->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'admin_role' => 'nullable|in:super_admin,finance,support',
            'is_verified' => 'nullable|boolean',
            'kyc_status' => 'nullable|in:pending,verified,rejected',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_verified'] = $request->boolean('is_verified');
        $validated['kyc_status'] = $validated['kyc_status'] ?? 'pending';
        $validated['is_admin'] = !empty($validated['admin_role']);
        $validated['email_verified_at'] = $validated['is_verified'] ? now() : null;

        $user = User::create($validated);

        $initialBalance = (float) $request->input('initial_balance', 0);
        if ($initialBalance > 0 && $user->wallet) {
            $user->wallet->credit($initialBalance, 'Initial balance set by ' . auth()->user()->email);
        }

        return redirect()->route('admin.users.show', $user)->with('success', "User created: {$user->email}");
    }

    public function show(User $user)
    {
        $user->load([
            'wallet', 'machineInvestments.machine', 'tradingAccount', 'transactions' => fn($q) => $q->latest()->limit(10),
            'depositRequests' => fn($q) => $q->latest()->limit(5), 'withdrawalRequests' => fn($q) => $q->latest()->limit(5),
            'loans', 'lotterySpins', 'referrals', 'loanCreditScore',
        ]);

        $stats = [
            'wallet_balance' => (float) ($user->wallet?->balance ?? 0),
            'total_deposited' => (float) $user->depositRequests->whereIn('status', ['approved', 'completed'])->sum('amount'),
            'total_withdrawn' => (float) $user->withdrawalRequests->whereIn('status', ['completed', 'approved'])->sum('amount'),
            'total_invested' => (float) $user->machineInvestments->sum('amount'),
            'active_machines' => $user->machineInvestments->where('status', 'active')->count(),
            'transaction_count' => $user->transactions->count(),
            'trading_balance' => (float) ($user->tradingAccount?->balance ?? 0),
            'trading_pnl' => (float) ($user->tradingAccount?->total_pnl ?? 0),
            'active_loans' => $user->loans->where('status', 'active')->count(),
            'total_loans_principal' => (float) $user->loans->sum('principal'),
            'total_lottery_bets' => (float) $user->lotterySpins->sum('bet_amount'),
            'total_lottery_wins' => (float) $user->lotterySpins->sum('win_amount'),
            'credit_score' => $user->loanCreditScore?->score ?? null,
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $isSelf = $user->id === auth()->id();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'kyc_status' => 'nullable|in:pending,verified,rejected',
            'kyc_level' => 'nullable|in:basic,tier1,tier2',
            'admin_role' => 'nullable|in:user,super_admin,finance,support',
        ]);

        $newIsAdmin = $request->has('is_admin') ? $request->boolean('is_admin') : (bool) $user->is_admin;
        $newAdminRole = $request->has('admin_role') ? $request->input('admin_role') : $user->admin_role;

        if ($isSelf) {
            if (!$newIsAdmin) return back()->withErrors(['error' => 'Cannot remove your own admin access.']);
            if ($newAdminRole !== 'super_admin' && $user->admin_role === 'super_admin') return back()->withErrors(['error' => 'Cannot demote yourself.']);
        }

        if ($request->has('is_admin') && !$request->boolean('is_admin')) {
            $newIsAdmin = false; $newAdminRole = null;
        } else {
            if ($newAdminRole && $newAdminRole !== 'user') $newIsAdmin = true;
            if ($newIsAdmin && (!$newAdminRole || $newAdminRole === 'user')) $newAdminRole = $user->admin_role ?? 'super_admin';
            if (!$newIsAdmin) $newAdminRole = null;
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'kyc_status' => $validated['kyc_status'] ?? $user->kyc_status,
            'kyc_level' => $validated['kyc_level'] ?? $user->kyc_level,
            'is_admin' => $newIsAdmin,
            'admin_role' => $newAdminRole,
            'is_verified' => $request->has('is_verified') ? $request->boolean('is_verified') : $user->is_verified,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $user->is_active,
            'is_vip' => $request->has('is_vip') ? $request->boolean('is_vip') : $user->is_vip,
            'has_promo' => $request->has('has_promo') ? $request->boolean('has_promo') : $user->has_promo,
        ];
        if (!empty($validated['password'])) $data['password'] = Hash::make($validated['password']);
        if ($data['is_verified'] && !$user->email_verified_at) $data['email_verified_at'] = now();
        if (!$data['is_verified']) $data['email_verified_at'] = null;

        $user->update($data);
        return redirect()->route('admin.users.show', $user)->with('success', 'Changes saved.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) return back()->withErrors(['error' => 'Cannot delete yourself.']);
        $email = $user->email;
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', "User {$email} deleted.");
    }

    public function toggleAdmin(User $user)
    {
        if ($user->id === auth()->id() && $user->is_admin) return back()->withErrors(['error' => 'Cannot demote yourself.']);
        $user->is_admin = !$user->is_admin;
        $user->admin_role = $user->is_admin ? ($user->admin_role ?? 'super_admin') : null;
        $user->save();
        return back()->with('success', "Role updated.");
    }

    public function changeRole(Request $request, User $user)
    {
        if ($user->id === auth()->id()) return back()->withErrors(['error' => 'Cannot change own role.']);
        $validated = $request->validate(['role' => 'required|in:user,super_admin,finance,support']);
        $user->is_admin = $validated['role'] !== 'user';
        $user->admin_role = $user->is_admin ? $validated['role'] : null;
        $user->save();
        return back()->with('success', "Role updated.");
    }

    public function toggleVip(User $user) { $user->update(['is_vip' => !$user->is_vip]); return back()->with('success', 'VIP toggled.'); }
    public function togglePromo(User $user) { $user->update(['has_promo' => !$user->has_promo, 'promo_expires_at' => !$user->has_promo ? now()->addDays(7) : null]); return back()->with('success', 'Promo toggled.'); }
    public function export() { return back(); }
}
