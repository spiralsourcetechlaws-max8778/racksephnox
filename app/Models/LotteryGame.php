<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LotteryGame extends Model
{
    use HasFactory;

    protected $table = 'lottery_games';

    protected $fillable = [
        'name', 'description', 'min_bet', 'max_bet', 'ticket_price', 'is_active',
        'settings', 'progressive_jackpot', 'jackpot_contribution_rate',
        'base_rtp', 'vip_rtp', 'promo_rtp',
        'volatility', 'reel_config', 'paylines', 'bonus_symbol_id',
        'free_spins_award', 'enable_free_spins', 'enable_bonus_buy', 'bonus_buy_price',
        'max_daily_loss', 'max_weekly_loss', 'max_monthly_loss', 'max_win_cap',
        'cool_down_minutes', 'session_timeout_minutes',
    ];

    protected $casts = [
        'settings'                  => 'array',
        'reel_config'               => 'array',
        'paylines'                  => 'array',
        'min_bet'                   => 'float',
        'max_bet'                   => 'float',
        'ticket_price'              => 'float',
        'progressive_jackpot'       => 'float',
        'jackpot_contribution_rate' => 'float',
        'base_rtp'                  => 'float',
        'vip_rtp'                   => 'float',
        'promo_rtp'                 => 'float',
        'bonus_buy_price'           => 'float',
        'enable_free_spins'         => 'boolean',
        'enable_bonus_buy'          => 'boolean',
        'is_active'                 => 'boolean',
    ];

    public function symbols()
    {
        return $this->belongsToMany(LotterySymbol::class, 'lottery_payouts')
                    ->withPivot('count', 'payout_multiplier')
                    ->withTimestamps();
    }

    public function payouts()
    {
        return $this->hasMany(LotteryPayout::class);
    }

    public function spins()
    {
        return $this->hasMany(LotterySpin::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }

    public function getReelCountAttribute(): int
    {
        return count($this->reel_config['reels'] ?? []);
    }

    public function getVolatilityTierAttribute(): string
    {
        return match ($this->volatility) {
            'low'     => 'Tier 1',
            'medium'  => 'Tier 2',
            'high'    => 'Tier 3',
            'extreme' => 'Tier 4',
            default   => 'Tier 2',
        };
    }
}
