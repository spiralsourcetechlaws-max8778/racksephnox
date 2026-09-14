<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingCandle extends Model
{
    use HasFactory;

    protected $fillable = [
        'pair_id', 'interval', 'open_time', 'close_time',
        'open', 'high', 'low', 'close', 'volume',
    ];

    protected $casts = [
        'open_time'  => 'datetime',
        'close_time' => 'datetime',
        'open'       => 'float',
        'high'       => 'float',
        'low'        => 'float',
        'close'      => 'float',
        'volume'     => 'float',
    ];

    public function pair() { return $this->belongsTo(TradingPair::class, 'pair_id'); }
}
