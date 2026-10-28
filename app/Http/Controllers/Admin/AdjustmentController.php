<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Machine;
use App\Models\MachineInvestment;
use App\Models\Loan;
use App\Models\LotterySpin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdjustmentController extends Controller
{
    // ══════════════════════════════════════════════════════════
    //  WALLET — credit or debit user's KES wallet
    // ══════════════════════════════════════════════════════════
    public function wallet(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type'   => 'required|in:credit,debit',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $wallet = $user->wallet;
        if (!$wallet) {
            return back()->withErrors(['error' => 'User has no wallet.']);
        }

        $amount = (float) $validated['amount'];
        $before = (float) $wallet->balance;

        if ($validated['type'] === 'debit' && $amount > $before) {
            return back()->withErrors(['error' => "Cannot debit KES {$amount} — user only has KES {$before}."]);
        }

        DB::transaction(function () use ($wallet, $validated, $amount, $user) {
            $desc = 'Admin ' . $validated['type'] . ': ' . $validated['reason'];
            if ($validated['type'] === 'credit') {
                $wallet->credit($amount, $desc);
            } else {
                $wallet->debit($amount, $desc);
            }
            $this->audit('wallet_' . $validated['type'], $user, [
                'before' => $wallet->balance + ($validated['type'] === 'credit' ? -$amount : $amount),
                'amount' => $amount,
                'reason' => $validated['reason'],
            ]);
        });

        $verb = $validated['type'] === 'credit' ? 'Credited' : 'Debited';
        return back()->with('success', "{$verb} KES " . number_format($amount, 2) . " — {$validated['reason']}");
    }

    // ══════════════════════════════════════════════════════════
    //  MACHINE — create a new machine investment for the user
    // ══════════════════════════════════════════════════════════
    public function machine(Request $request, User $user)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'amount'     => 'required|numeric|min:1',
            'vip_level'  => 'nullable|integer|in:0,1,2,3',
            'fund_from'  => 'required|in:wallet,admin', // wallet = debit user's wallet; admin = free grant
            'reason'     => 'required|string|min:10|max:255',
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);
        $amount  = (float) $validated['amount'];

        // Machine bounds — RX machines store vip1/vip3_start_amount, not min/max.
        // Fall back to min_amount/max_amount for non-RX machine types.
        $min = (float) ($machine->vip1_start_amount ?? $machine->min_amount ?? 0);
        $max = (float) ($machine->vip3_start_amount ?? 0);
        $max = $max > 0 ? $max * 10 : (float) ($machine->max_amount ?? 0);

        if ($min > 0 && $amount < $min) {
            return back()->withErrors(['error' => "Amount below machine minimum (KES " . number_format($min, 0) . ")."]);
        }
        if ($max > 0 && $amount > $max) {
            return back()->withErrors(['error' => "Amount above machine maximum (KES " . number_format($max, 0) . ")."]);
        }

        $wallet = $user->wallet;
        if ($validated['fund_from'] === 'wallet' && (!$wallet || $wallet->balance < $amount)) {
            return back()->withErrors(['error' => "User wallet insufficient. Balance: KES " . ($wallet->balance ?? 0)]);
        }

        DB::transaction(function () use ($user, $machine, $amount, $validated) {
            // Debit wallet if funding from wallet
            if ($validated['fund_from'] === 'wallet') {
                $user->wallet->debit($amount, 'Machine investment: ' . $machine->name . ' (admin grant)');
            }

            // Create investment with defensive column checks
            $cols = Schema::getColumnListing('machine_investments');
            $data = [
                'user_id'    => $user->id,
                'machine_id' => $machine->id,
                'amount'     => $amount,
                'status'     => 'active',
            ];

            if (in_array('vip_level', $cols) && $validated['vip_level']) {
                $data['vip_level'] = (int) $validated['vip_level'];
            }
            if (in_array('started_at', $cols))       $data['started_at']       = now();
            if (in_array('ends_at', $cols))          $data['ends_at']          = now()->addDays($machine->cycle_days ?? 14);
            if (in_array('matures_at', $cols))       $data['matures_at']       = now()->addDays($machine->cycle_days ?? 14);
            if (in_array('returns_earned', $cols))   $data['returns_earned']   = 0;
            if (in_array('total_returns', $cols))    $data['total_returns']    = 0;
            if (in_array('expected_return', $cols))  $data['expected_return']  = $amount * (1 + (($machine->growth_rate ?? 0) / 100));

            $investment = MachineInvestment::create($data);

            $this->audit('machine_created', $user, [
                'machine'      => $machine->name,
                'machine_code' => $machine->code,
                'amount'       => $amount,
                'vip_level'    => $validated['vip_level'] ?? 0,
                'fund_from'    => $validated['fund_from'],
                'reason'       => $validated['reason'],
                'investment_id' => $investment->id,
            ]);
        });

        return back()->with('success', "Machine investment created: {$machine->name} · KES " . number_format($amount, 2));
    }

    // ══════════════════════════════════════════════════════════
    //  MACHINE CANCEL — early withdrawal / termination
    // ══════════════════════════════════════════════════════════
    public function cancelMachine(Request $request, User $user, $investmentId)
    {
        $validated = $request->validate([
            'refund_amount' => 'nullable|numeric|min:0',
            'reason'        => 'required|string|min:10|max:255',
        ]);

        $investment = MachineInvestment::where('user_id', $user->id)->findOrFail($investmentId);

        if ($investment->status !== 'active') {
            return back()->withErrors(['error' => 'Only active investments can be cancelled.']);
        }

        DB::transaction(function () use ($investment, $user, $validated) {
            $investment->update(['status' => 'cancelled']);

            $refund = (float) ($validated['refund_amount'] ?? 0);
            if ($refund > 0 && $user->wallet) {
                $user->wallet->credit($refund, 'Machine cancellation refund: ' . $validated['reason']);
            }

            $this->audit('machine_cancelled', $user, [
                'investment_id' => $investment->id,
                'amount'        => $investment->amount,
                'refund'        => $refund,
                'reason'        => $validated['reason'],
            ]);
        });

        $refundMsg = ($validated['refund_amount'] ?? 0) > 0
            ? ' · refunded KES ' . number_format($validated['refund_amount'], 2)
            : '';
        return back()->with('success', "Investment cancelled{$refundMsg}");
    }

    // ══════════════════════════════════════════════════════════
    //  TRADING — credit or debit trading account
    // ══════════════════════════════════════════════════════════
    public function trading(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type'   => 'required|in:credit,debit',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $account = $user->tradingAccount;
        if (!$account) {
            // Auto-create if missing
            $account = $user->tradingAccount()->create([
                'balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0, 'total_pnl' => 0,
            ]);
        }

        $amount = (float) $validated['amount'];
        $before = (float) $account->balance;

        if ($validated['type'] === 'debit' && $amount > $before) {
            return back()->withErrors(['error' => "Cannot debit $amount — account has $before."]);
        }

        DB::transaction(function () use ($account, $validated, $amount, $user) {
            $newBalance = $validated['type'] === 'credit' ? $account->balance + $amount : $account->balance - $amount;
            $account->update(['balance' => $newBalance]);

            $this->audit('trading_' . $validated['type'], $user, [
                'before' => $account->balance + ($validated['type'] === 'credit' ? -$amount : $amount),
                'after'  => $newBalance,
                'amount' => $amount,
                'reason' => $validated['reason'],
            ]);
        });

        return back()->with('success', "Trading account " . $validated['type'] . ": $" . number_format($amount, 2));
    }

    // ══════════════════════════════════════════════════════════
    //  LOAN — adjust balance, forgive, or mark as paid
    // ══════════════════════════════════════════════════════════
    public function loan(Request $request, User $user)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'action'  => 'required|in:forgive,mark_paid,mark_defaulted,adjust_balance',
            'amount'  => 'nullable|numeric|min:0',
            'reason'  => 'required|string|min:10|max:255',
        ]);

        $loan = Loan::where('user_id', $user->id)->findOrFail($validated['loan_id']);
        $before = [
            'balance' => (float) ($loan->balance ?? 0),
            'status'  => $loan->status,
        ];

        DB::transaction(function () use ($loan, $validated, $user) {
            switch ($validated['action']) {
                case 'forgive':
                    $loan->update(['balance' => 0, 'status' => 'completed']);
                    break;

                case 'mark_paid':
                    $loan->update(['status' => 'completed']);
                    if (isset($loan->balance)) {
                        $loan->update(['balance' => 0]);
                    }
                    break;

                case 'mark_defaulted':
                    $loan->update(['status' => 'defaulted']);
                    break;

                case 'adjust_balance':
                    $amount = (float) ($validated['amount'] ?? 0);
                    if ($amount <= 0) {
                        throw new \RuntimeException('Amount required for adjust_balance.');
                    }
                    if ($amount > $loan->balance) {
                        throw new \RuntimeException('Cannot reduce balance below 0.');
                    }
                    $loan->update(['balance' => $loan->balance - $amount]);
                    break;
            }

            $this->audit('loan_' . $validated['action'], $user, [
                'loan_id'        => $loan->id,
                'action'         => $validated['action'],
                'amount'         => $validated['amount'] ?? null,
                'balance_before' => $before['balance'],
                'balance_after'  => $loan->fresh()->balance,
                'status_before'  => $before['status'],
                'status_after'   => $loan->fresh()->status,
                'reason'         => $validated['reason'],
            ]);
        });

        return back()->with('success', "Loan #{$loan->id} adjusted: {$validated['action']}");
    }

    // ══════════════════════════════════════════════════════════
    //  LOTTERY — grant free spins or manual wins
    // ══════════════════════════════════════════════════════════
    public function lottery(Request $request, User $user)
    {
        $validated = $request->validate([
            'action'        => 'required|in:grant_spins,grant_win,reset_stats',
            'spins'         => 'nullable|integer|min:1|max:100',
            'win_amount'    => 'nullable|numeric|min:1',
            'reason'        => 'required|string|min:10|max:255',
        ]);

        DB::transaction(function () use ($user, $validated) {
            switch ($validated['action']) {
                case 'grant_spins':
                    $spins = (int) $validated['spins'];
                    $user->increment('free_spins_available', $spins);
                    $this->audit('lottery_grant_spins', $user, [
                        'spins' => $spins,
                        'total_after' => $user->fresh()->free_spins_available,
                        'reason' => $validated['reason'],
                    ]);
                    break;

                case 'grant_win':
                    $winAmount = (float) $validated['win_amount'];
                    // Create a spin record marked as win
                    LotterySpin::create([
                        'user_id'    => $user->id,
                        'game_id'    => null,
                        'bet_amount' => 0,
                        'win_amount' => $winAmount,
                        'is_win'     => true,
                    ]);
                    // Credit wallet
                    if ($user->wallet) {
                        $user->wallet->credit($winAmount, 'Lottery win (admin grant): ' . $validated['reason']);
                    }
                    $this->audit('lottery_grant_win', $user, [
                        'win_amount' => $winAmount,
                        'reason'     => $validated['reason'],
                    ]);
                    break;

                case 'reset_stats':
                    $count = LotterySpin::where('user_id', $user->id)->count();
                    LotterySpin::where('user_id', $user->id)->delete();
                    $this->audit('lottery_reset_stats', $user, [
                        'deleted_spins' => $count,
                        'reason'        => $validated['reason'],
                    ]);
                    break;
            }
        });

        return back()->with('success', 'Lottery updated: ' . $validated['action']);
    }

    // ══════════════════════════════════════════════════════════
    //  AUDIT LOGGER
    // ══════════════════════════════════════════════════════════
    protected function audit(string $action, User $affected, array $payload): void
    {
        $adminId = auth()->id();
        $adminEmail = auth()->user()?->email ?? 'system';

        // Always log to file (works even if audit_logs schema differs)
        Log::info('[ADMIN ADJUSTMENT]', [
            'action'         => $action,
            'admin_id'       => $adminId,
            'admin_email'    => $adminEmail,
            'affected_user'  => $affected->id,
            'affected_email' => $affected->email,
            'payload'        => $payload,
            'ip'             => request()->ip(),
        ]);

        // Try DB audit_logs with defensive column detection
        try {
            if (!Schema::hasTable('audit_logs')) return;

            $cols = Schema::getColumnListing('audit_logs');
            $row = ['action' => $action];
            if (in_array('user_id', $cols))     $row['user_id']     = $adminId;
            if (in_array('ip_address', $cols))  $row['ip_address']  = request()->ip();
            if (in_array('user_agent', $cols))  $row['user_agent']  = request()->userAgent();
            if (in_array('new_values', $cols))  $row['new_values']  = json_encode($payload);
            if (in_array('url', $cols))         $row['url']         = request()->fullUrl();
            if (in_array('affected_user_id', $cols)) $row['affected_user_id'] = $affected->id;

            \DB::table('audit_logs')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('[ADMIN ADJUSTMENT] audit_logs write failed: ' . $e->getMessage());
        }
    }
}
