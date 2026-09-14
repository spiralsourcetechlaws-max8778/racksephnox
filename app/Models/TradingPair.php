<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol', 'base_currency', 'quote_currency',
        'min_trade_amount', 'max_trade_amount', 'tick_size', 'is_active',
    ];

    protected $casts = [
        'min_trade_amount' => 'float',
        'max_trade_amount' => 'float',
        'tick_size'        => 'float',
        'is_active'        => 'boolean',
    ];

    public function candles() { return $this->hasMany(TradingCandle::class, 'pair_id'); }
    public function orders()  { return $this->hasMany(TradeOrder::class, 'pair_id'); }
}
