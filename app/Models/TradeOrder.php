<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeOrder extends Model
{
    use HasFactory;

    protected $table = 'trade_orders';

    protected $fillable = [
        'user_id', 'pair_id', 'side', 'order_type',
        'amount_btc', 'filled_amount', 'limit_price', 'stop_price',
        'price_per_btc', 'filled_kes', 'status',
        'take_profit', 'stop_loss', 'time_in_force',
    ];

    protected $casts = [
        'amount_btc'    => 'float',
        'filled_amount' => 'float',
        'limit_price'   => 'float',
        'stop_price'    => 'float',
        'price_per_btc' => 'float',
        'filled_kes'    => 'float',
        'take_profit'   => 'float',
        'stop_loss'     => 'float',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function pair() { return $this->belongsTo(TradingPair::class, 'pair_id'); }

    public function scopeOpen($q)         { return $q->whereIn('status', ['pending', 'partial']); }
    public function scopeCompleted($q)    { return $q->where('status', 'completed'); }
    public function scopeForUser($q, $id) { return $q->where('user_id', $id); }
}
