<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'plan_id', 'amount', 'daily_profit',
        'total_projected_profit', 'remaining_days', 'status',
        'start_date', 'end_date', 'last_accrued_at',
    ];

    protected $casts = [
        'amount'                 => 'float',
        'daily_profit'           => 'float',
        'total_projected_profit' => 'float',
        'remaining_days'         => 'integer',
        'start_date'             => 'datetime',
        'end_date'               => 'datetime',
        'last_accrued_at'        => 'datetime',
    ];

    /* ---------- Relationships ---------- */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(InvestmentPlan::class, 'plan_id');
    }

    /* ---------- Scopes ---------- */
    public function scopeActive($q)       { return $q->where('status', 'active'); }
    public function scopeCompleted($q)    { return $q->where('status', 'completed'); }
    public function scopeForUser($q, $id) { return $q->where('user_id', $id); }

    /* ---------- Null-safe helpers ---------- */
    public function getPlanNameAttribute(): string
    {
        return $this->plan->name ?? 'Archived Plan';
    }

    public function getDurationDaysAttribute(): int
    {
        return (int) ($this->plan->duration_days ?? 0);
    }

    public function getDaysElapsedAttribute(): int
    {
        if (!$this->start_date) return 0;
        return max(0, (int) $this->start_date->diffInDays(now()));
    }

    public function getDaysRemainingCalculatedAttribute(): int
    {
        if (!$this->end_date) return 0;
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public function getProgressPercentAttribute(): float
    {
        $total = $this->duration_days;
        if ($total <= 0) return 0.0;
        return min(100.0, round(($this->days_elapsed / $total) * 100, 2));
    }

    public function getProfitCreditedAttribute(): float
    {
        $elapsed = $this->days_elapsed;
        return round($this->daily_profit * $elapsed, 2);
    }

    public function getProjectedTotalAttribute(): float
    {
        return round(($this->amount ?? 0) + ($this->total_projected_profit ?? 0), 2);
    }

    public function getCurrentValueAttribute(): float
    {
        return round(($this->amount ?? 0) + $this->profit_credited, 2);
    }

    public function getIsMaturedAttribute(): bool
    {
        return $this->remaining_days <= 0 || ($this->end_date && $this->end_date->isPast());
    }
}
