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
    ];

    /* ---------- Relationships ---------- */
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

    /* ---------- Scopes ---------- */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* ---------- Helpers ---------- */
    public function getVipStartAmount(int $level): float
    {
        return (float) ($this->{"vip{$level}_start_amount"} ?? 0);
    }

    public function getVipTier(int $level): ?MachineVip
    {
        return $this->vips()->where('level', $level)->first();
    }

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

    public function getRoiBadgeAttribute(): string
    {
        return '88% in 14 days';
    }

    public function getFrequencyAttribute(): int
    {
        return 888;
    }

    public function getPhiAttribute(): float
    {
        return 1.61803398875;
    }

    public function getLambdaAttribute(): float
    {
        return 1.27201964951;
    }

    public function getVipLevelsAttribute(): int
    {
        return 3;
    }

    /* ---------- Currency helper ---------- */
    public function getFormattedVipAmountsAttribute(): array
    {
        return [
            1 => number_format($this->vip1_start_amount, 0),
            2 => number_format($this->vip2_start_amount, 0),
            3 => number_format($this->vip3_start_amount, 0),
        ];
    }
}
