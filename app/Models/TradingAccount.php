<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance', 'locked_balance', 'btc_balance', 'total_pnl', 'currency'];

    protected $casts = [
        'balance'        => 'float',
        'locked_balance' => 'float',
        'btc_balance'    => 'float',
        'total_pnl'      => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableBalanceAttribute(): float
    {
        return max(0, (float) $this->balance - (float) $this->locked_balance);
    }

    public function getTotalValueAttribute(): float
    {
        // balance is KES; btc_balance is in BTC (converted using market price at runtime)
        return (float) $this->balance;
    }

    public function getTotalBtcValueKesAttribute(): float
    {
        $btcPrice = \Cache::get('btc_market_price', 0);
        return (float) $this->btc_balance * $btcPrice;
    }
}
