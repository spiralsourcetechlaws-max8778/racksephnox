<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotterySymbol extends Model
{
    use HasFactory;

    protected $table = 'lottery_symbols';

    protected $fillable = [
        'name', 'display_name', 'icon', 'multiplier', 'is_divine',
    ];

    protected $casts = [
        'multiplier' => 'float',
        'is_divine'  => 'boolean',
    ];

    public function games()
    {
        return $this->belongsToMany(LotteryGame::class, 'lottery_payouts')
                    ->withPivot('count', 'payout_multiplier')
                    ->withTimestamps();
    }

    public function payouts()
    {
        return $this->hasMany(LotteryPayout::class);
    }

    public function scopeDivine($q)
    {
        return $q->where('is_divine', true);
    }
}
