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

        DB::transaction(function () use ($wallet, $validated, $amount, $user) {
            $desc = 'Admin ' . $validated['type'] . ': ' . $validated['reason'];
            if ($validated['type'] === 'credit') {
                $wallet->credit($amount, $desc);
            } else {
                $wallet->debit($amount, $desc);
            }
            $this->audit('wallet_' . $validated['type'], $user, [
                'amount' => $amount,
                'reason' => $validated['reason'],
            ]);
        });

        $verb = $validated['type'] === 'credit' ? 'Credited' : 'Debited';
        return back()->with('success', "{$verb} KES " . number_format($amount, 2));
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
        $max = (float) ($machine->vip3_start_amount ?? 0);
        $max = $max > 0 ? $max * 10 : (float) ($machine->max_amount ?? 0);

        if ($min > 0 && $amount < $min) return back()->withErrors(['error' => "Amount below machine minimum (KES " . number_format($min, 0) . ")."]);
        if ($max > 0 && $amount > $max) return back()->withErrors(['error' => "Amount above machine maximum (KES " . number_format($max, 0) . ")."]);

        if ($validated['fund_from'] === 'wallet' && (!$user->wallet || $user->wallet->balance < $amount)) {
            return back()->withErrors(['error' => "User wallet insufficient."]);
        }

        DB::transaction(function () use ($user, $machine, $amount, $validated) {
            if ($validated['fund_from'] === 'wallet') {
                $user->wallet->debit($amount, 'Machine investment: ' . $machine->name . ' (admin grant)');
            }

            $cols = Schema::getColumnListing('machine_investments');
            $data = [
                'user_id'    => $user->id,
                'machine_id' => $machine->id,
                'amount'     => $amount,
                'status'     => 'active',
            ];

            if (in_array('vip_level', $cols) && $validated['vip_level']) $data['vip_level'] = (int) $validated['vip_level'];
            if (in_array('started_at', $cols))       $data['started_at']       = now();
            if (in_array('ends_at', $cols))          $data['ends_at']          = now()->addDays($machine->cycle_days ?? 14);
            if (in_array('matures_at', $cols))       $data['matures_at']       = now()->addDays($machine->cycle_days ?? 14);
            if (in_array('returns_earned', $cols))   $data['returns_earned']   = 0;
            if (in_array('total_returns', $cols))    $data['total_returns']    = 0;
            if (in_array('expected_return', $cols))  $data['expected_return']  = $amount * (1 + (($machine->growth_rate ?? 0) / 100));

            $investment = MachineInvestment::create($data);

            $this->audit('machine_created', $user, [
                'machine' => $machine->name, 'amount' => $amount, 'reason' => $validated['reason'],
            ]);
        });

        return back()->with('success', "Machine investment created: {$machine->name}");
    }

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
            $this->audit('machine_cancelled', $user, ['investment_id' => $investment->id, 'refund' => $refund, 'reason' => $validated['reason']]);
        });

        return back()->with('success', "Investment cancelled.");
    }

    public function trading(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type'   => 'required|in:credit,debit',
            'reason' => 'required|string|min:10|max:255',
        ]);

        $account = $user->tradingAccount;
        if (!$account) {
            $account = $user->tradingAccount()->create(['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0, 'total_pnl' => 0]);
        }

        $amount = (float) $validated['amount'];
        if ($validated['type'] === 'debit' && $amount > $account->balance) {
            return back()->withErrors(['error' => "Cannot debit — insufficient trading balance."]);
        }

        DB::transaction(function () use ($account, $validated, $amount, $user) {
            $newBalance = $validated['type'] === 'credit' ? $account->balance + $amount : $account->balance - $amount;
            $account->update(['balance' => $newBalance]);
            $this->audit('trading_' . $validated['type'], $user, ['amount' => $amount, 'reason' => $validated['reason']]);
        });

        return back()->with('success', "Trading account adjusted.");
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
    }

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
