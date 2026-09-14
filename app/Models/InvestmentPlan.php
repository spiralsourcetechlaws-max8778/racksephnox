<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentPlan extends Model
{
    use HasFactory;

    protected $table = 'investment_plans';

    protected $fillable = [
        'name', 'description', 'min_amount', 'max_amount',
        'daily_interest_rate', 'duration_days', 'is_active',
    ];

    protected $casts = [
        'min_amount'          => 'float',
        'max_amount'          => 'float',
        'daily_interest_rate' => 'float',
        'duration_days'       => 'integer',
        'is_active'           => 'boolean',
    ];

    /* ---------- Relationships ---------- */
    public function investments()
    {
        return $this->hasMany(Investment::class, 'plan_id');
    }

    public function activeInvestments()
    {
        return $this->hasMany(Investment::class, 'plan_id')->where('status', 'active');
    }

    /* ---------- Scopes ---------- */
    public function scopeActive($q) { return $q->where('is_active', true); }

    /* ---------- Helpers ---------- */
    public function getRoiPercentAttribute(): float
    {
        return round($this->daily_interest_rate * $this->duration_days, 2);
    }

    public function getTotalReturnAttribute(float $amount = 0): float
    {
        return round($amount * (1 + $this->roi_percent / 100), 2);
    }

    public function getTotalInvestedAttribute(): float
    {
        return (float) $this->investments()->sum('amount');
    }

    public function getInvestorCountAttribute(): int
    {
        return (int) $this->investments()->distinct('user_id')->count('user_id');
    }

    public function getRangeLabelAttribute(): string
    {
        return 'KES ' . number_format($this->min_amount, 0)
             . ' – KES ' . number_format($this->max_amount, 0);
    }

    /**
     * Projected profit and total for a given principal.
     */
    public function project(float $amount): array
    {
        $dailyProfit = round($amount * $this->daily_interest_rate / 100, 2);
        $totalProfit = round($dailyProfit * $this->duration_days, 2);

        return [
            'daily_profit' => $dailyProfit,
            'total_profit' => $totalProfit,
            'total_return' => round($amount + $totalProfit, 2),
            'roi_percent'  => $this->roi_percent,
        ];
    }
}
