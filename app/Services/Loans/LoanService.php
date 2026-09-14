<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class LoanService
{
    public function __construct(
        protected LoanCalculator $calculator,
        protected LoanCreditService $creditService
    ) {}

    /* ============================================================
     |  APPLY FOR A LOAN
     ============================================================ */
    public function apply(User $user, LoanProduct $product, float $amount, int $durationDays, string $frequency = 'monthly', ?string $purpose = null): Loan
    {
        if (!$product->is_active) {
            throw new RuntimeException('This loan product is currently unavailable.');
        }
        if ($amount < $product->min_amount || $amount > $product->max_amount) {
            throw new RuntimeException("Amount must be between {$product->min_amount} and {$product->max_amount}.");
        }
        if ($durationDays < $product->min_duration_days || $durationDays > $product->max_duration_days) {
            throw new RuntimeException("Duration must be between {$product->min_duration_days} and {$product->max_duration_days} days.");
        }

        $credit = $this->creditService->recalculate($user);
        if ($credit->score < $product->min_credit_score) {
            throw new RuntimeException("Minimum credit score of {$product->min_credit_score} required. Your score: {$credit->score}.");
        }

        if (!$this->creditService->isEligible($user, $amount)) {
            throw new RuntimeException('Your current credit exposure does not allow this amount.');
        }

        $calc = $this->calculator->calculate($product, $amount, $durationDays, $frequency);

        return DB::transaction(function () use ($user, $product, $amount, $durationDays, $frequency, $purpose, $calc, $credit) {

            $loan = Loan::create([
                'reference'                   => $this->generateReference(),
                'user_id'                     => $user->id,
                'loan_product_id'             => $product->id,
                'principal'                   => $calc['principal'],
                'interest_rate'               => $calc['interest_rate'],
                'interest_method'             => $calc['interest_method'],
                'total_interest'              => $calc['total_interest'],
                'total_payable'               => $calc['total_payable'],
                'amount_paid'                 => 0,
                'balance'                     => $calc['total_payable'],
                'duration_days'               => $durationDays,
                'repayment_frequency'         => $frequency,
                'installments'                => $calc['installments'],
                'installment_amount'          => $calc['installment_amount'],
                'status'                      => 'pending',
                'purpose'                     => $purpose,
                'credit_score_at_application' => $credit->score,
            ]);

            Log::info('Loan application submitted', [
                'user_id' => $user->id,
                'ref'     => $loan->reference,
                'amount'  => $amount,
            ]);

            return $loan->fresh(['product', 'user']);
        });
    }

    /* ============================================================
     |  APPROVE
     ============================================================ */
    public function approve(Loan $loan, User $admin): Loan
    {
        if ($loan->status !== 'pending') {
            throw new RuntimeException('Only pending loans can be approved.');
        }

        return DB::transaction(function () use ($loan, $admin) {
            $loan->update([
                'status'       => 'approved',
                'approved_by'  => $admin->id,
                'approved_at'  => now(),
            ]);

            return $loan->fresh();
        });
    }

    /* ============================================================
     |  REJECT
     ============================================================ */
    public function reject(Loan $loan, User $admin, string $reason): Loan
    {
        if (!in_array($loan->status, ['pending', 'approved'])) {
            throw new RuntimeException('This loan cannot be rejected.');
        }

        $loan->update([
            'status'           => 'rejected',
            'approved_by'      => $admin->id,
            'rejected_reason'  => $reason,
        ]);

        return $loan->fresh();
    }

    /* ============================================================
     |  DISBURSE
     ============================================================ */
    public function disburse(Loan $loan): Loan
    {
        if ($loan->status !== 'approved') {
            throw new RuntimeException('Only approved loans can be disbursed.');
        }

        return DB::transaction(function () use ($loan) {
            $user   = $loan->user;
            $wallet = $user->wallet ?? Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            // Credit principal to wallet
            $wallet->balance += $loan->principal;
            $wallet->save();

            // Generate amortization schedule
            $calc     = [
                'principal'         => $loan->principal,
                'total_interest'    => $loan->total_interest,
                'total_payable'     => $loan->total_payable,
                'installments'      => $loan->installments,
                'duration_days'     => $loan->duration_days,
            ];
            $schedule = $this->calculator->schedule($calc, now());

            foreach ($schedule as $row) {
                LoanRepayment::create([
                    'loan_id'           => $loan->id,
                    'user_id'           => $loan->user_id,
                    'amount'            => $row['amount'],
                    'principal_portion' => $row['principal_portion'],
                    'interest_portion'  => $row['interest_portion'],
                    'late_fee_portion'  => 0,
                    'balance_after'     => $row['balance_after'],
                    'due_date'          => $row['due_date'],
                    'status'            => 'pending',
                    'reference'         => 'RP-' . $loan->id . '-' . $row['installment_no'],
                ]);
            }

            $firstDue = $schedule[0]['due_date'] ?? now()->addDays(30)->toDateTimeString();

            $loan->update([
                'status'         => 'active',
                'disbursed_at'   => now(),
                'first_due_date' => $firstDue,
                'next_due_date'  => $firstDue,
            ]);

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $wallet->id,
                'type'          => 'loan_disbursement',
                'amount'        => $loan->principal,
                'balance_after' => $wallet->balance,
                'description'   => "Loan disbursement · {$loan->reference}",
                'reference'     => $loan->reference,
                'status'        => 'completed',
            ]);

            Log::info('Loan disbursed', ['ref' => $loan->reference, 'amount' => $loan->principal]);

            return $loan->fresh(['repayments']);
        });
    }

    /* ============================================================
     |  REPAY
     ============================================================ */
    public function repay(Loan $loan, float $amount, ?string $reference = null): Loan
    {
        if (!$loan->can_be_repaid) {
            throw new RuntimeException('This loan cannot be repaid in its current state.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('Repayment amount must be positive.');
        }

        $user   = $loan->user;
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $amount) {
            throw new RuntimeException('Insufficient wallet balance for repayment.');
        }

        return DB::transaction(function () use ($loan, $amount, $wallet, $reference) {

            $wallet->balance -= $amount;
            $wallet->save();

            $remaining = $amount;

            // Apply late fees first
            if ($loan->late_fees > 0) {
                $apply = min($remaining, $loan->late_fees);
                $loan->late_fees -= $apply;
                $remaining -= $apply;
            }

            // Apply to next unpaid installments
            foreach ($loan->repayments()->pending()->orderBy('due_date')->get() as $inst) {
                if ($remaining <= 0) break;
                $apply = min($remaining, $inst->amount);

                $inst->status       = $apply >= $inst->amount ? 'paid' : 'pending';
                $inst->paid_at      = $apply >= $inst->amount ? now() : null;
                $inst->notes        = "Partial/Full payment applied: KES " . number_format($apply, 2);
                $inst->save();

                $remaining -= $apply;
            }

            $loan->amount_paid = min($loan->total_payable, $loan->amount_paid + $amount);
            $loan->balance     = max(0, round($loan->total_payable - $loan->amount_paid, 2));
            $loan->last_payment_at = now();

            $next = $loan->repayments()->pending()->orderBy('due_date')->first();
            $loan->next_due_date = $next->due_date ?? null;

            if ($loan->balance <= 0) {
                $loan->status    = 'completed';
                $loan->closed_at = now();
            }
            $loan->save();

            Transaction::create([
                'user_id'       => $loan->user_id,
                'wallet_id'     => $wallet->id,
                'type'          => 'loan_repayment',
                'amount'        => -$amount,
                'balance_after' => $wallet->balance,
                'description'   => "Loan repayment · {$loan->reference}",
                'reference'     => $reference ?? ('REPAY-' . $loan->id . '-' . now()->timestamp),
                'status'        => 'completed',
            ]);

            // Recalculate credit score after each repayment
            $this->creditService->recalculate($user);

            Log::info('Loan repayment', ['ref' => $loan->reference, 'amount' => $amount]);

            return $loan->fresh(['repayments']);
        });
    }

    /* ============================================================
     |  CANCEL
     ============================================================ */
    public function cancel(Loan $loan): Loan
    {
        if (!in_array($loan->status, ['pending', 'approved'])) {
            throw new RuntimeException('Only pending or approved loans can be cancelled.');
        }
        $loan->update(['status' => 'cancelled']);
        return $loan->fresh();
    }

    /* ============================================================
     |  DEFAULT HANDLING (scheduler)
     ============================================================ */
    public function flagDefaults(): int
    {
        $count = 0;
        Loan::active()
            ->where('next_due_date', '<', now()->subDays(30))
            ->chunkById(50, function ($loans) use (&$count) {
                foreach ($loans as $loan) {
                    $loan->update(['status' => 'defaulted']);
                    $this->creditService->recalculate($loan->user);
                    $count++;
                }
            });
        return $count;
    }

    /* ============================================================
     |  UTILS
     ============================================================ */
    protected function generateReference(): string
    {
        return 'LN-' . strtoupper(Str::random(8)) . '-' . now()->format('ymd');
    }
}
