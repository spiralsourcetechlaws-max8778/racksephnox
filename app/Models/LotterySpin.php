<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotterySpin extends Model
{
    use HasFactory;

    protected $table = 'lottery_spins';

    protected $fillable = [
        'user_id', 'lottery_game_id', 'bet_amount', 'bet_in_kes', 'currency',
        'win_amount', 'symbols', 'is_free_spin', 'free_spin_used', 'last_free_spin_at',
        'jackpot_won', 'jackpot_tier', 'tax_paid',
        'provably_fair_seed', 'provably_fair_hash', 'client_seed', 'server_seed_hash', 'nonce',
    ];

    protected $casts = [
        'symbols'            => 'array',
        'bet_amount'         => 'float',
        'bet_in_kes'         => 'float',
        'win_amount'         => 'float',
        'jackpot_won'        => 'float',
        'tax_paid'           => 'float',
        'is_free_spin'       => 'boolean',
        'free_spin_used'     => 'boolean',
        'last_free_spin_at'  => 'datetime',
        'nonce'              => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(LotteryGame::class, 'lottery_game_id');
    }

    public function scopeForUser($q, $id)         { return $q->where('user_id', $id); }
    public function scopeWins($q)                { return $q->where('win_amount', '>', 0); }
    public function scopeBigWins($q, $min = 10000) { return $q->where('win_amount', '>=', $min); }
    public function scopeJackpotWins($q)         { return $q->where('jackpot_won', '>', 0); }

    public function getIsWinAttribute(): bool
    {
        return $this->win_amount > 0;
    }

    public function getNetResultAttribute(): float
    {
        return round($this->win_amount - $this->bet_amount, 2);
    }
}
