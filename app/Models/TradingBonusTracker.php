<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingBonusTracker extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bonus_type', 'bonus_amount',
        'required_volume', 'achieved_volume', 'is_claimed', 'expires_at',
    ];

    protected $casts = [
        'bonus_amount'    => 'float',
        'required_volume' => 'float',
        'achieved_volume' => 'float',
        'is_claimed'      => 'boolean',
        'expires_at'      => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function getProgressAttribute(): float
    {
        if ($this->required_volume === 0.0) return 100.0;
        return min(100, round(($this->achieved_volume / $this->required_volume) * 100, 2));
    }
}
