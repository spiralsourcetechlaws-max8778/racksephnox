<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'locked_balance',
        'btc_balance',
        'total_pnl',
        'currency',
    ];

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

    /**
     * Available KES for placing buy orders (balance minus locked funds).
     */
    public function getAvailableBalanceAttribute(): float
    {
        return max(0.0, (float) $this->balance - (float) $this->locked_balance);
    }

    /**
     * Total value of the account expressed in KES using live BTC price.
     */
    public function getTotalValueKesAttribute(): float
    {
        $btcPrice = (float) \Cache::get('btc_market_price', 0);
        return (float) $this->balance + ((float) $this->btc_balance * $btcPrice);
    }

    /**
     * Total BTC value expressed in KES.
     */
    public function getBtcValueKesAttribute(): float
    {
        $btcPrice = (float) \Cache::get('btc_market_price', 0);
        return (float) $this->btc_balance * $btcPrice;
    }
}
