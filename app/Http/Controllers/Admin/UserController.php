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
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            match ($request->role) {
                'admin'   => $query->where('is_admin', true),
                'user'    => $query->where('is_admin', false),
                default   => $query->where('admin_role', $request->role),
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'admin_role' => 'nullable|in:super_admin,finance,support',
            'is_verified' => 'nullable|boolean',
            'kyc_status'  => 'nullable|in:pending,verified,rejected',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_verified'] = $request->boolean('is_verified');
        $validated['kyc_status'] = $validated['kyc_status'] ?? 'pending';

        if (!empty($validated['admin_role'])) {
            $validated['is_admin'] = true;
        } else {
            $validated['is_admin'] = false;
            $validated['admin_role'] = null;
        }

        if ($validated['is_verified']) {
            $validated['email_verified_at'] = now();
        }

        $user = User::create($validated);

        $initialBalance = (float) $request->input('initial_balance', 0);
        if ($initialBalance > 0 && $user->wallet) {
            $user->wallet->credit($initialBalance, 'Initial balance set by ' . auth()->user()->email);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "{$user->role_label} created: {$user->email}");
    }

    public function show(User $user)
    {
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

        $stats = [
            'wallet_balance'        => (float) ($user->wallet?->balance ?? 0),
            'wallet_locked'         => (float) ($user->wallet?->locked_balance ?? 0),
            'total_deposited'       => (float) $user->depositRequests()->whereIn('status', ['approved', 'completed'])->sum('amount'),
            'total_withdrawn'       => (float) $user->withdrawalRequests()->whereIn('status', ['completed', 'approved'])->sum('amount'),
            'pending_deposits'      => $user->depositRequests()->where('status', 'pending')->count(),
            'pending_withdrawals'   => $user->withdrawalRequests()->where('status', 'pending')->count(),
            'total_invested'        => (float) $user->machineInvestments->sum('amount'),
            'active_machines'       => $user->machineInvestments->where('status', 'active')->count(),
            'total_lottery_bets'    => (float) $user->lotterySpins->sum('bet_amount'),
            'total_lottery_wins'    => (float) $user->lotterySpins->sum('win_amount'),
            'total_referrals'       => $user->referrals->count(),
            'total_loans_principal' => (float) $user->loans->sum('principal'),
            'active_loans'          => $user->loans->where('status', 'active')->count(),
            'trading_balance'       => (float) ($user->tradingAccount?->balance ?? 0),
            'trading_pnl'           => (float) ($user->tradingAccount?->total_pnl ?? 0),
            'open_orders'           => $user->tradeOrders->whereIn('status', ['open', 'pending'])->count(),
            'transaction_count'     => $user->transactions->count(),
            'credit_score'          => $user->loanCreditScore?->score ?? null,
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Unified update — handles EVERY field on the edit form in one PATCH.
     *
     * Fields handled:
     *   Identity : name, email, phone
     *   Password : password + password_confirmation (both optional)
     *   Status   : kyc_status, kyc_level, is_verified, is_active
     *   Admin    : is_admin, admin_role (super_admin only)
     *   User     : is_vip, has_promo (super_admin only)
     *
     * Safety guards (self-edit only):
     *   - Cannot remove own admin privileges
     *   - Cannot demote self from super_admin
     *   - Cannot unverify self
     *   - Cannot deactivate self
     *
     * All fields use `$request->has(...)` checks so disabled inputs
     * (like self-edit-locked toggles) safely fall back to current values.
     */
    public function update(Request $request, User $user)
    {
        $isSelf = $user->id === auth()->id();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'phone'       => 'nullable|string|max:20',
            'password'    => 'nullable|string|min:8|confirmed',
            'kyc_status'  => 'nullable|in:pending,verified,rejected',
            'kyc_level'   => 'nullable|in:basic,tier1,tier2',
            'admin_role'  => 'nullable|in:user,super_admin,finance,support',
        ]);

        // ─── Compute privilege values defensively ────────────────────
        // Missing fields (e.g. from disabled self-edit inputs) keep
        // their current value instead of being wiped.
        $newIsAdmin    = $request->has('is_admin')    ? $request->boolean('is_admin')    : (bool) $user->is_admin;
        $newIsVerified = $request->has('is_verified') ? $request->boolean('is_verified') : (bool) $user->is_verified;
        $newIsActive   = $request->has('is_active')   ? $request->boolean('is_active')   : ($user->is_active ?? true);
        $newIsVip      = $request->has('is_vip')      ? $request->boolean('is_vip')      : (bool) $user->is_vip;
        $newHasPromo   = $request->has('has_promo')   ? $request->boolean('has_promo')   : (bool) $user->has_promo;
        $newAdminRole  = $request->has('admin_role')  ? $request->input('admin_role')    : $user->admin_role;

        // ─── Safety guards for self-editing ─────────────────────────
        if ($isSelf) {
            if (!$newIsAdmin) {
                return back()->withInput()->withErrors(['error' => 'You cannot remove your own admin privileges.']);
            }
            if ($newAdminRole !== 'super_admin' && $user->admin_role === 'super_admin') {
                return back()->withInput()->withErrors(['error' => 'You cannot demote yourself from Super Admin.']);
            }
            if (!$newIsVerified) {
                return back()->withInput()->withErrors(['error' => 'You cannot unverify your own account.']);
            }
            if (!$newIsActive) {
                return back()->withInput()->withErrors(['error' => 'You cannot deactivate your own account.']);
            }
        }

        // ─── Reconcile is_admin <-> admin_role ───────────────────────
        if ($newAdminRole && $newAdminRole !== 'user') {
            $newIsAdmin = true;   // setting a role implies admin
        }
        if ($newIsAdmin && (!$newAdminRole || $newAdminRole === 'user')) {
            $newAdminRole = $user->admin_role ?? 'super_admin';   // admin with no role = super
        }
        if (!$newIsAdmin) {
            $newAdminRole = null;   // not admin = no role
        }

        // ─── Build the update payload ────────────────────────────────
        $data = [
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'phone'       => $validated['phone'] ?? null,
            'kyc_status'  => $validated['kyc_status'] ?? $user->kyc_status ?? 'pending',
            'kyc_level'   => $validated['kyc_level'] ?? $user->kyc_level ?? 'basic',
            'is_admin'    => $newIsAdmin,
            'admin_role'  => $newAdminRole,
            'is_verified' => $newIsVerified,
            'is_vip'      => $newIsVip,
            'has_promo'   => $newHasPromo,
            'is_active'   => $newIsActive,
        ];

        // Password (only if provided)
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        // Email verification timestamp
        if ($newIsVerified && !$user->email_verified_at) {
            $data['email_verified_at'] = now();
        } elseif (!$newIsVerified) {
            $data['email_verified_at'] = null;
        }

        // Promo expiry: set 7 days on first activation, clear when removed
        if ($newHasPromo && !$user->has_promo) {
            $data['promo_expires_at'] = now()->addDays(7);
        } elseif (!$newHasPromo) {
            $data['promo_expires_at'] = null;
        }
        // If has_promo was already true and stays true, keep existing expiry

        $user->update($data);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'All changes saved successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }
        $email = $user->email;
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', "User {$email} deleted.");
    }

    public function toggleAdmin(User $user)
    {
        if ($user->id === auth()->id() && $user->is_admin) {
            return back()->withErrors(['error' => 'You cannot demote yourself.']);
        }
        $user->is_admin = !$user->is_admin;
        $user->admin_role = $user->is_admin ? ($user->admin_role ?? 'super_admin') : null;
        $user->save();
        return back()->with('success', "{$user->name} is now: {$user->role_label}");
    }

    public function changeRole(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot change your own role.']);
        }
        $validated = $request->validate(['role' => 'required|in:user,super_admin,finance,support']);
        if ($validated['role'] === 'user') {
            $user->is_admin = false;
            $user->admin_role = null;
        } else {
            $user->is_admin = true;
            $user->admin_role = $validated['role'];
        }
        $user->save();
        return back()->with('success', "Role updated to: {$user->role_label}");
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
        return response()->stream(function() use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Referral', 'KYC', 'Balance', 'Joined']);
            foreach ($users as $u) {
                fputcsv($file, [
                    $u->id, $u->name, $u->email, $u->phone,
                    $u->role_label, $u->referral_code,
                    $u->kyc_status ?? 'pending',
                    $u->wallet?->balance ?? 0,
                    $u->created_at?->format('Y-m-d'),
                ]);
            }
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
