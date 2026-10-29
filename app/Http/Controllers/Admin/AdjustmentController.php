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
    public function wallet(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type'   => 'required|in:credit,debit',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $wallet = $user->wallet;
        if (!$wallet) return back()->withErrors(['error' => 'User has no wallet.']);

        $amount = (float) $validated['amount'];
        if ($validated['type'] === 'debit' && $amount > $wallet->balance) {
            return back()->withErrors(['error' => "Cannot debit KES {$amount} — user only has KES {$wallet->balance}."]);
        }

        try {
            DB::transaction(function () use ($wallet, $validated, $amount, $user) {
                $desc = 'Admin ' . $validated['type'] . ': ' . $validated['reason'];
                if ($validated['type'] === 'credit') {
                    $wallet->credit($amount, $desc);
                } else {
                    $wallet->debit($amount, $desc);
                }
                $this->audit('wallet_' . $validated['type'], $user, ['amount' => $amount, 'reason' => $validated['reason']]);
            });
            return back()->with('success', "Wallet adjusted.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed: ' . $e->getMessage()]);
        }
    }

    public function machine(Request $request, User $user)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'amount'     => 'required|numeric|min:1',
            'vip_level'  => 'nullable|integer|in:0,1,2,3',
            'fund_from'  => 'required|in:wallet,admin',
            'reason'     => 'required|string|min:10|max:255',
        ]);

        $machine = Machine::findOrFail($validated['machine_id']);
        $amount  = (float) $validated['amount'];

        $min = (float) ($machine->vip1_start_amount ?? $machine->min_amount ?? 0);
        $max = (float) ($machine->vip3_start_amount ?? $machine->max_amount ?? 0);
        
        if ($min > 0 && $amount < $min) return back()->withErrors(['error' => "Amount below minimum (KES " . number_format($min, 0) . ")."]);
        if ($max > 0 && $amount > $max * 10) return back()->withErrors(['error' => "Amount above maximum (KES " . number_format($max * 10, 0) . ")."]);

        if ($validated['fund_from'] === 'wallet' && (!$user->wallet || $user->wallet->balance < $amount)) {
            return back()->withErrors(['error' => "User wallet insufficient."]);
        }

        try {
            DB::transaction(function () use ($user, $machine, $amount, $validated) {
                if ($validated['fund_from'] === 'wallet') {
                    $user->wallet->debit($amount, 'Machine investment: ' . $machine->name . ' (admin grant)');
                }

                $cols = Schema::getColumnListing('machine_investments');
                $growthRate = (float) ($machine->growth_rate ?? 25);
                $days = (int) ($machine->duration_days ?? $machine->cycle_days ?? 14);
                $dailyProfit = round($amount * $growthRate / 100 / $days, 2);
                $totalProjected = $amount + ($dailyProfit * $days);

                $data = [
                    'user_id'                => $user->id,
                    'machine_id'             => $machine->id,
                    'amount'                 => $amount,
                    'status'                 => 'active',
                    'vip_level'              => (int) ($validated['vip_level'] ?? 1),
                    'daily_profit'           => $dailyProfit,
                    'total_projected_profit' => $totalProjected,
                    'profit_credited'        => 0,
                    'start_date'             => now(),
                    'end_date'               => now()->addDays($days),
                    'last_accrued_at'        => now(),
                    'withdrawn'              => 0,
                    'early_withdrawn'        => 0,
                    'penalty_applied'        => 0,
                    'started_at'             => now(),
                    'ends_at'                => now()->addDays($days),
                    'matures_at'             => now()->addDays($days),
                    
                    // THE EXACT REQUIRED COLUMNS
                    'total_return'           => 0,
                    'total_returns'          => 0, 
                    'returns_earned'         => 0,
                    'return_earned'          => 0,
                    'expected_return'        => $totalProjected,
                ];

                // Only send keys that exist in the DB table
                $data = array_intersect_key($data, array_flip($cols));

                $investment = MachineInvestment::create($data);

                $this->audit('machine_created', $user, [
                    'machine' => $machine->name, 'amount' => $amount, 'reason' => $validated['reason'],
                ]);
            });

            return back()->with('success', "Machine investment created: {$machine->name}");
        } catch (\Throwable $e) {
            \Log::error('Machine investment failed', ['error' => $e->getMessage(), 'data' => $data ?? []]);
            return back()->withErrors(['error' => 'Database Error: ' . $e->getMessage()]);
        }
    }

    public function cancelMachine(Request $request, User $user, $investmentId)
    {
        $validated = $request->validate([
            'refund_amount' => 'nullable|numeric|min:0',
            'reason'        => 'required|string|min:10|max:255',
        ]);

        $investment = MachineInvestment::where('user_id', $user->id)->findOrFail($investmentId);
        if ($investment->status !== 'active') return back()->withErrors(['error' => 'Only active investments can be cancelled.']);

        try {
            DB::transaction(function () use ($investment, $user, $validated) {
                $investment->update(['status' => 'cancelled']);
                $refund = (float) ($validated['refund_amount'] ?? 0);
                if ($refund > 0 && $user->wallet) {
                    $user->wallet->credit($refund, 'Machine cancellation refund: ' . $validated['reason']);
                }
                $this->audit('machine_cancelled', $user, ['investment_id' => $investment->id, 'refund' => $refund, 'reason' => $validated['reason']]);
            });
            return back()->with('success', "Investment cancelled.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed: ' . $e->getMessage()]);
        }
    }

    public function trading(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type'   => 'required|in:credit,debit',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $account = $user->tradingAccount;
        if (!$account) $account = $user->tradingAccount()->create(['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0, 'total_pnl' => 0]);

        $amount = (float) $validated['amount'];
        if ($validated['type'] === 'debit' && $amount > $account->balance) return back()->withErrors(['error' => "Insufficient trading balance."]);

        try {
            DB::transaction(function () use ($account, $validated, $amount, $user) {
                $newBalance = $validated['type'] === 'credit' ? $account->balance + $amount : $account->balance - $amount;
                $account->update(['balance' => $newBalance]);
                $this->audit('trading_' . $validated['type'], $user, ['amount' => $amount, 'reason' => $validated['reason']]);
            });
            return back()->with('success', "Trading account adjusted.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed: ' . $e->getMessage()]);
        }
    }

    public function loan(Request $request, User $user)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'action'  => 'required|in:forgive,mark_paid,mark_defaulted,adjust_balance',
            'amount'  => 'nullable|numeric|min:0',
            'reason'  => 'required|string|min:10|max:255',
        ]);

        $loan = Loan::where('user_id', $user->id)->findOrFail($validated['loan_id']);

        try {
            DB::transaction(function () use ($loan, $validated, $user) {
                switch ($validated['action']) {
                    case 'forgive': $loan->update(['balance' => 0, 'status' => 'completed']); break;
                    case 'mark_paid': $loan->update(['status' => 'completed', 'balance' => 0]); break;
                    case 'mark_defaulted': $loan->update(['status' => 'defaulted']); break;
                    case 'adjust_balance':
                        $amount = (float) ($validated['amount'] ?? 0);
                        if ($amount <= 0 || $amount > $loan->balance) throw new \RuntimeException('Invalid adjust amount.');
                        $loan->update(['balance' => $loan->balance - $amount]);
                        break;
                }
                $this->audit('loan_' . $validated['action'], $user, ['loan_id' => $loan->id, 'reason' => $validated['reason']]);
            });
            return back()->with('success', "Loan adjusted.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed: ' . $e->getMessage()]);
        }
    }

    public function lottery(Request $request, User $user)
    {
        $validated = $request->validate([
            'action'        => 'required|in:grant_spins,grant_win,reset_stats',
            'spins'         => 'nullable|integer|min:1|max:100',
            'win_amount'    => 'nullable|numeric|min:1',
            'reason'        => 'required|string|min:10|max:255',
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
                switch ($validated['action']) {
                    case 'grant_spins':
                        $user->increment('free_spins_available', (int) $validated['spins']);
                        break;
                    case 'grant_win':
                        $winAmount = (float) $validated['win_amount'];
                        LotterySpin::create(['user_id' => $user->id, 'game_id' => null, 'bet_amount' => 0, 'win_amount' => $winAmount, 'is_win' => true]);
                        if ($user->wallet) $user->wallet->credit($winAmount, 'Lottery win (admin grant): ' . $validated['reason']);
                        break;
                    case 'reset_stats':
                        LotterySpin::where('user_id', $user->id)->delete();
                        break;
                }
                $this->audit('lottery_' . $validated['action'], $user, ['reason' => $validated['reason']]);
            });
            return back()->with('success', 'Lottery adjusted.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed: ' . $e->getMessage()]);
        }
    }

    protected function audit(string $action, User $affected, array $payload): void
    {
        Log::info('[ADMIN ADJUSTMENT]', [
            'action' => $action, 'admin_id' => auth()->id(), 'admin_email' => auth()->user()?->email,
            'affected_user' => $affected->id, 'payload' => $payload, 'ip' => request()->ip(),
        ]);

        try {
            if (Schema::hasTable('audit_logs')) {
                $cols = Schema::getColumnListing('audit_logs');
                $row = ['action' => $action];
                if (in_array('user_id', $cols)) $row['user_id'] = auth()->id();
                if (in_array('ip_address', $cols)) $row['ip_address'] = request()->ip();
                if (in_array('new_values', $cols)) $row['new_values'] = json_encode($payload);
                if (in_array('affected_user_id', $cols)) $row['affected_user_id'] = $affected->id;
                DB::table('audit_logs')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Audit log failed: ' . $e->getMessage());
        }
    }
}
