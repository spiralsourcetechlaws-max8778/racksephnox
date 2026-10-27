<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'machine_id', 'vip_level',
        'amount', 'daily_profit', 'total_projected_profit', 'total_return',
        'profit_credited', 'status',
        'start_date', 'end_date', 'last_accrued_at', 'last_profit_date',
        'withdrawn', 'early_withdrawn', 'penalty_applied',
    ];

    protected $casts = [
        'amount'                 => 'float',
        'daily_profit'           => 'float',
        'total_projected_profit' => 'float',
        'total_return'           => 'float',
        'profit_credited'        => 'float',
        'penalty_applied'        => 'float',
        'vip_level'              => 'integer',
        'start_date'             => 'datetime',
        'end_date'               => 'datetime',
        'last_accrued_at'        => 'datetime',
        'last_profit_date'       => 'datetime',
        'withdrawn'              => 'boolean',
        'early_withdrawn'        => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function scopeActive($q)      { return $q->where('status', 'active'); }
    public function scopeCompleted($q)   { return $q->where('status', 'completed'); }
    public function scopeForUser($q, $id){ return $q->where('user_id', $id); }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) return 0;
        $seconds = now()->diffInSeconds($this->end_date, false);
        return $seconds > 0 ? (int) ceil($seconds / 86400) : 0;
    }

    public function getDaysElapsedAttribute(): int
    {
        if (!$this->start_date) return 0;
        $seconds = $this->start_date->diffInSeconds(now(), false);
        return $seconds > 0 ? (int) floor($seconds / 86400) : 0;
    }

    public function getProgressPercentAttribute(): float
    {
        if (!$this->machine || !$this->machine->duration_days) return 0;
        $elapsed = $this->days_elapsed;
        $total   = (int) $this->machine->duration_days;
        return $total > 0 ? min(100.0, round(($elapsed / $total) * 100, 2)) : 0;
    }

    public function getProjectedTotalAttribute(): float
    {
        return round((float) $this->amount + (float) $this->total_projected_profit, 2);
    }

    public function getCurrentValueAttribute(): float
    {
        return round((float) $this->amount + (float) $this->profit_credited, 2);
    }

    public function getCanWithdrawAttribute(): bool
    {
        return $this->status === 'active'
            && !$this->withdrawn
            && !$this->early_withdrawn
            && $this->days_remaining === 0;
    }
}
