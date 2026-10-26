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
        'bank_account_id' // <-- ADDED: This was missing and would break bank withdrawals
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Added relationship so the admin panel can see the bank details
    public function bankAccount()
    {
        return $this->belongsTo(UserBankAccount::class, 'bank_account_id');
    }
}
