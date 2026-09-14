<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'user_id', 'loan_product_id',
        'principal', 'interest_rate', 'interest_method',
        'total_interest', 'total_payable',
        'amount_paid', 'balance', 'late_fees',
        'duration_days', 'repayment_frequency', 'installments', 'installment_amount',
        'status', 'purpose',
        'approved_by', 'approved_at', 'rejected_reason',
        'disbursed_at', 'first_due_date', 'next_due_date', 'last_payment_at', 'closed_at',
        'credit_score_at_application',
    ];

    protected $casts = [
        'principal'       => 'float',
        'interest_rate'   => 'float',
        'total_interest'  => 'float',
        'total_payable'   => 'float',
        'amount_paid'     => 'float',
        'balance'         => 'float',
        'late_fees'       => 'float',
        'installment_amount' => 'float',
        'duration_days'   => 'integer',
        'installments'    => 'integer',
        'credit_score_at_application' => 'integer',
        'approved_at'     => 'datetime',
        'disbursed_at'    => 'datetime',
        'first_due_date'  => 'datetime',
        'next_due_date'   => 'datetime',
        'last_payment_at' => 'datetime',
        'closed_at'       => 'datetime',
    ];

    public function user()        { return $this->belongsTo(User::class); }
    public function product()     { return $this->belongsTo(LoanProduct::class, 'loan_product_id'); }
    public function repayments()  { return $this->hasMany(LoanRepayment::class); }
    public function guarantors()  { return $this->hasMany(LoanGuarantor::class); }
    public function collaterals() { return $this->hasMany(LoanCollateral::class); }
    public function approver()    { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeActive($q)       { return $q->where('status', 'active'); }
    public function scopePending($q)      { return $q->where('status', 'pending'); }
    public function scopeCompleted($q)    { return $q->where('status', 'completed'); }
    public function scopeForUser($q, $id) { return $q->where('user_id', $id); }
    public function scopeOverdue($q)      { return $q->where('status', 'active')->where('next_due_date', '<', now()); }

    public function getProductNameAttribute(): string
    {
        return $this->product->name ?? 'Archived Product';
    }

    public function getProgressPercentAttribute(): float
    {
        $total = (float) $this->total_payable;
        if ($total <= 0) return 0.0;
        return min(100.0, round(($this->amount_paid / $total) * 100, 2));
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0.0, round($this->total_payable - $this->amount_paid, 2));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active'
            && $this->next_due_date
            && $this->next_due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return (int) $this->next_due_date->diffInDays(now());
    }

    public function getCanBeRepaidAttribute(): bool
    {
        return in_array($this->status, ['active', 'approved']);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'text-green-400',
            'pending'   => 'text-yellow-400',
            'approved'  => 'text-blue-400',
            'completed' => 'text-gold',
            'rejected'  => 'text-red-400',
            'defaulted' => 'text-red-600',
            'cancelled' => 'text-gray-400',
            default     => 'text-ivory',
        };
    }
}
