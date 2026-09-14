<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'user_id', 'amount',
        'principal_portion', 'interest_portion', 'late_fee_portion',
        'balance_after', 'due_date', 'paid_at', 'status', 'reference', 'notes',
    ];

    protected $casts = [
        'amount'            => 'float',
        'principal_portion' => 'float',
        'interest_portion'  => 'float',
        'late_fee_portion'  => 'float',
        'balance_after'     => 'float',
        'due_date'          => 'datetime',
        'paid_at'           => 'datetime',
    ];

    public function loan() { return $this->belongsTo(Loan::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopePaid($q)    { return $q->where('status', 'paid'); }
    public function scopeOverdue($q) { return $q->where('status', 'pending')->where('due_date', '<', now()); }
}
