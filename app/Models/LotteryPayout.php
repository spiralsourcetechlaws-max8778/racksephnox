<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryPayout extends Model
{
    use HasFactory;

    protected $table = 'lottery_payouts';

    protected $fillable = [
        'lottery_game_id', 'lottery_symbol_id', 'count', 'payout_multiplier',
    ];

    protected $casts = [
        'count'             => 'integer',
        'payout_multiplier' => 'float',
    ];

    public function game()
    {
        return $this->belongsTo(LotteryGame::class, 'lottery_game_id');
    }

    public function symbol()
    {
        return $this->belongsTo(LotterySymbol::class, 'lottery_symbol_id');
    }
}
