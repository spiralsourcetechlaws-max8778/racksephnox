<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCreditScore;
use App\Models\User;

class LoanCreditService
{
    /**
     * Recompute credit score for a user based on repayment history.
     */
    public function recalculate(User $user): LoanCreditScore
    {
        // Keep the model in $record — never overwrite it with an int
        $record = LoanCreditScore::firstOrCreate(
            ['user_id' => $user->id],
            ['score' => 500, 'tier' => 'Silver']
        );

        $loans = Loan::forUser($user->id)->with('repayments')->get();

        $borrowed = (float) $loans->sum('principal');
        $repaid   = (float) $loans->sum('amount_paid');
        $defaults = (int) $loans->where('status', 'defaulted')->count();

        $onTime = 0;
        $late   = 0;
        foreach ($loans as $loan) {
            foreach ($loan->repayments as $r) {
                if ($r->status === 'paid' && $r->paid_at && $r->due_date) {
                    if ($r->paid_at <= $r->due_date) {
                        $onTime++;
                    } else {
                        $late++;
                    }
                }
            }
        }

        // Score algorithm — 300 to 850
        $repaymentRatio = $borrowed > 0 ? ($repaid / $borrowed) : 1;

        $newScore = 500
            + (int) round($repaymentRatio * 200)   // up to +200 for full repayment
            + ($onTime * 5)                         // +5 per on-time payment
            - ($late * 10)                          // -10 per late payment
            - ($defaults * 150);                    // -150 per default

        $newScore = max(300, min(850, $newScore));

        $record->update([
            'score'              => $newScore,
            'tier'               => $record->scoreTier($newScore),
            'total_borrowed'     => $borrowed,
            'total_repaid'       => $repaid,
            'on_time_payments'   => $onTime,
            'late_payments'      => $late,
            'defaults'           => $defaults,
            'last_calculated_at' => now(),
        ]);

        return $record->fresh();
    }

    /**
     * Eligibility check for a given amount.
     */
    public function isEligible(User $user, float $amount): bool
    {
        $credit = $this->recalculate($user);

        if ($credit->score < 400) return false;

        // Max exposure: 2× repaid amount, minimum 50,000
        $maxExposure = max(50_000, $credit->total_repaid * 2);
        return $amount <= $maxExposure;
    }

    /**
     * Get (or create) the credit score record for a user.
     */
    public function getScore(User $user): LoanCreditScore
    {
        return LoanCreditScore::firstOrCreate(
            ['user_id' => $user->id],
            ['score' => 500, 'tier' => 'Silver']
        );
    }
}
