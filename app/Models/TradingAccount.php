<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance', 'locked_balance', 'btc_balance'];

    protected $casts = [
        'balance'        => 'float',
        'locked_balance' => 'float',
        'btc_balance'    => 'float',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function getAvailableBalanceAttribute(): float
    {
        return max(0, $this->balance);
    }

    public function getTotalValueAttribute(): float
    {
        return $this->balance + $this->locked_balance;
    }
}
