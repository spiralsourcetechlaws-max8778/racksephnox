<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_order_id', 'follower_id', 'trader_id',
        'original_amount', 'copied_amount', 'original_price',
        'copied_kes', 'side', 'status',
    ];

    protected $casts = [
        'original_amount' => 'float',
        'copied_amount'   => 'float',
        'original_price'  => 'float',
        'copied_kes'      => 'float',
    ];

    public function originalOrder() { return $this->belongsTo(TradeOrder::class, 'original_order_id'); }
    public function follower()      { return $this->belongsTo(User::class, 'follower_id'); }
    public function trader()        { return $this->belongsTo(User::class, 'trader_id'); }
}
