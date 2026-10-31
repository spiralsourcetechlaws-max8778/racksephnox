<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'machine_id', 'amount', 'profit_credited', 'status',
        'vip_level', 'daily_profit', 'total_projected_profit', 'start_date',
        'end_date', 'last_accrued_at', 'withdrawn', 'early_withdrawn',
        'penalty_applied', 'started_at', 'ends_at', 'matures_at',
        'returns_earned', 'total_returns', 'expected_return',
        'total_return', 'return_earned',
    ];

    protected $casts = [
        'amount'                 => 'float',
        'profit_credited'        => 'float',
        'daily_profit'           => 'float',
        'total_projected_profit' => 'float',
        'start_date'             => 'datetime',
        'end_date'               => 'datetime',
        'last_accrued_at'        => 'datetime',
        'started_at'             => 'datetime',
        'ends_at'                => 'datetime',
        'matures_at'             => 'datetime',
        'expected_return'        => 'float',
        'total_return'           => 'float',
        'withdrawn'              => 'boolean',
        'early_withdrawn'        => 'boolean',
        'penalty_applied'        => 'float',
    ];

    /* ─────────── Relationships ─────────── */

    public function user()    { return $this->belongsTo(User::class); }
    public function machine() { return $this->belongsTo(Machine::class); }

    /* ─────────── Scopes (required by MachineService + controllers) ─────────── */

    /**
     * Filter investments belonging to one user.
     * Used by MachineController@myInvestments, @show, and MachineService@getDashboardStats.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', (int) $userId);
    }

    /**
     * Filter active investments.
     * Used by MachineService@accrueDailyProfits and @getDashboardStats.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter completed investments.
     * Used by MachineService@getDashboardStats.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /* ─────────── Accessors (required by machines/show.blade.php + @status) ─────────── */

    /**
     * Days elapsed from start_date to min(now, end_date).
     */
    public function getDaysElapsedAttribute(): int
    {
        if (!$this->start_date) {
            return 0;
        }

        $now = now();
        $end = ($this->end_date && $now->greaterThan($this->end_date))
            ? $this->end_date
            : $now;

        return (int) $this->start_date->diffInDays($end);
    }

    /**
     * Days remaining from now to end_date. 0 if past or no end_date.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        $now = now();
        if ($now->greaterThan($this->end_date)) {
            return 0;
        }

        return (int) $now->diffInDays($this->end_date);
    }

    /**
     * Progress 0–100 across the cycle.
     */
    public function getProgressPercentAttribute(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0.0;
        }

        $total   = max(1, (int) $this->start_date->diffInDays($this->end_date));
        $elapsed = min($total, $this->days_elapsed);

        return round(($elapsed / $total) * 100, 2);
    }

    /**
     * Current value = principal + profit credited so far.
     */
    public function getCurrentValueAttribute(): float
    {
        return (float) ($this->amount ?? 0) + (float) ($this->profit_credited ?? 0);
    }

    /**
     * Projected final value = principal + total_projected_profit.
     * Falls back to daily_profit × duration when projected profit column is null.
     */
    public function getProjectedTotalAttribute(): float
    {
        $amount   = (float) ($this->amount ?? 0);
        $projected = (float) ($this->total_projected_profit ?? 0);

        if ($projected <= 0 && (float) $this->daily_profit > 0) {
            $days = ($this->start_date && $this->end_date)
                ? max(1, (int) $this->start_date->diffInDays($this->end_date))
                : 14;
            $projected = (float) $this->daily_profit * $days;
        }

        return round($amount + $projected, 2);
    }

    /**
     * Overdue = active + end_date passed + not yet withdrawn.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active'
            && $this->end_date
            && $this->end_date->isPast()
            && !$this->withdrawn;
    }
}
