<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description',
        'vip1_start_amount', 'vip2_start_amount', 'vip3_start_amount',
        'duration_days', 'growth_rate', 'is_active',
        'risk_profile', 'icon', 'color',
        'min_daily_profit', 'max_daily_profit',
        'referral_bonus_rate', 'early_withdrawal_penalty',
        'features', 'total_invested_limit',
        'compound_frequency', 'min_withdrawal', 'max_withdrawal',
        'bonus_multiplier', 'staking_reward', 'tier_multiplier',
    ];

    protected $casts = [
        'features'           => 'array',
        'vip1_start_amount'  => 'float',
        'vip2_start_amount'  => 'float',
        'vip3_start_amount'  => 'float',
        'growth_rate'        => 'float',
        'min_daily_profit'   => 'float',
        'max_daily_profit'   => 'float',
        'referral_bonus_rate'=> 'float',
        'early_withdrawal_penalty' => 'float',
        'total_invested_limit' => 'float',
        'min_withdrawal'     => 'float',
        'max_withdrawal'     => 'float',
        'bonus_multiplier'   => 'float',
        'staking_reward'     => 'float',
        'tier_multiplier'    => 'float',
        'is_active'          => 'boolean',
        'duration_days'      => 'integer',
    ];

    public function vips()
    {
        return $this->hasMany(MachineVip::class);
    }

    public function investments()
    {
        return $this->hasMany(MachineInvestment::class);
    }

    public function activeInvestments()
    {
        return $this->hasMany(MachineInvestment::class)->where('status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getVipStartAmount(int $level): float
    {
        return (float) ($this->{"vip{$level}_start_amount"} ?? 0);
    }

    public function getVipTier(int $level): ?MachineVip
    {
        return $this->vips()->where('level', $level)->first();
    }

    /* ---------- API helper methods (missing before) ---------- */

    public function getVIPAmounts(): array
    {
        return [
            1 => (float) $this->vip1_start_amount,
            2 => (float) $this->vip2_start_amount,
            3 => (float) $this->vip3_start_amount,
        ];
    }

    public function getStartAmountForVip(int $level): float
    {
        return (float) $this->{"vip{$level}_start_amount"} ?? 0;
    }

    public function getDailyProfit(float $amount, int $vipLevel = 1): float
    {
        $days = (int) ($this->duration_days ?: 14);
        $totalProfit = $this->getTotalReturn($amount, $vipLevel) - $amount;
        return $days > 0 ? round($totalProfit / $days, 2) : 0;
    }

    public function getTotalReturn(float $amount, int $vipLevel = 1): float
    {
        $vip = $this->getVipTier($vipLevel);
        $rate = $vip && isset($vip->growth_rate)
            ? (float) $vip->growth_rate
            : (float) $this->growth_rate;
        return round($amount * (1 + $rate / 100), 2);
    }

    public function getVIPDetails(): array
    {
        $details = [];
        foreach ([1, 2, 3] as $level) {
            $vip = $this->getVipTier($level);
            $details[$level] = [
                'level'          => $level,
                'start_amount'   => $this->getStartAmountForVip($level),
                'growth_rate'    => $vip->growth_rate ?? $this->growth_rate,
                'duration_days'  => $vip->duration_days ?? $this->duration_days,
                'bonus_multiplier' => $vip->bonus_multiplier ?? 1,
            ];
        }
        return $details;
    }

    public function getStatistics(): array
    {
        return [
            'total_invested'   => $this->total_invested,
            'active_invested'  => $this->active_invested,
            'investor_count'   => $this->investor_count,
            'duration_days'    => $this->duration_days ?? 14,
        ];
    }

    /* ---------- Accessors ---------- */

    public function getTotalInvestedAttribute(): float
    {
        return (float) $this->investments()->sum('amount');
    }

    public function getActiveInvestedAttribute(): float
    {
        return (float) $this->activeInvestments()->sum('amount');
    }

    public function getInvestorCountAttribute(): int
    {
        return (int) $this->investments()->distinct('user_id')->count('user_id');
    }
}
