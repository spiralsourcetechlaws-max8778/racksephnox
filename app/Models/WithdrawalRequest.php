<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'fee',
        'net_amount',
        'phone',
        'status',
        'admin_notes',
        'bank_account_id',
    ];

    protected $casts = [
        'amount'     => 'float',
        'fee'        => 'float',
        'net_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(UserBankAccount::class, 'bank_account_id');
    }

    public function scopePending($q)    { return $q->where('status', 'pending'); }
    public function scopeProcessing($q) { return $q->where('status', 'processing'); }
    public function scopeCompleted($q)  { return $q->where('status', 'completed'); }
    public function scopeRejected($q)   { return $q->where('status', 'rejected'); }
}
