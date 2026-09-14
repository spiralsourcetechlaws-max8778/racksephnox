<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryJackpotPool extends Model
{
    use HasFactory;

    protected $table = 'lottery_jackpot_pools';

    protected $fillable = [
        'tier', 'frequency_hz', 'seed_amount', 'current_pool',
        'ceiling_amount', 'contribution_rate', 'wins_count',
        'last_won_at', 'last_winner_id', 'must_drop', 'is_active',
    ];

    protected $casts = [
        'seed_amount'       => 'float',
        'current_pool'      => 'float',
        'ceiling_amount'    => 'float',
        'contribution_rate' => 'float',
        'wins_count'        => 'integer',
        'last_won_at'       => 'datetime',
        'must_drop'         => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function wins()
    {
        return $this->hasMany(LotteryJackpotWin::class, 'tier', 'tier');
    }

    public function scopeActive($q)     { return $q->where('is_active', true); }
    public function scopeTier($q, $t)   { return $q->where('tier', $t); }

    public function getProgressToCeilingAttribute(): float
    {
        if (!$this->ceiling_amount || $this->ceiling_amount <= 0) return 0;
        return min(100, round(($this->current_pool / $this->ceiling_amount) * 100, 2));
    }

    public function getMustDropSoonAttribute(): bool
    {
        return $this->progress_to_ceiling >= 90;
    }

    public function getLabelAttribute(): string
    {
        return match ($this->tier) {
            'bronze' => '🥉 Bronze',
            'silver' => '🥈 Silver',
            'gold'   => '🥇 Gold',
            'cosmic' => '🌌 Cosmic',
            default  => ucfirst($this->tier),
        };
    }
}
