<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'machine_id', 'vip_level',
        'amount', 'daily_profit', 'total_projected_profit',
        'profit_credited', 'status',
        'start_date', 'end_date', 'last_accrued_at',
        'withdrawn', 'early_withdrawn', 'penalty_applied',
    ];

    protected $casts = [
        'amount'                 => 'float',
        'daily_profit'           => 'float',
        'total_projected_profit' => 'float',
        'profit_credited'        => 'float',
        'vip_level'              => 'integer',
        'start_date'             => 'datetime',
        'end_date'               => 'datetime',
        'last_accrued_at'        => 'datetime',
        'withdrawn'              => 'boolean',
        'early_withdrawn'        => 'boolean',
        'penalty_applied'        => 'float',
    ];

    /* ---------- Relationships ---------- */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function vip()
    {
        return $this->hasOne(MachineVip::class, 'machine_id', 'machine_id')
                    ->where('level', $this->vip_level);
    }

    /* ---------- Scopes ---------- */
    public function scopeActive($q)      { return $q->where('status', 'active'); }
    public function scopeCompleted($q)   { return $q->where('status', 'completed'); }
    public function scopeForUser($q,$id) { return $q->where('user_id', $id); }

    /* ---------- Helpers ---------- */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) return 0;
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getDaysElapsedAttribute(): int
    {
        if (!$this->start_date) return 0;
        return max(0, $this->start_date->diffInDays(now()));
    }

    public function getProgressPercentAttribute(): float
    {
        if (!$this->machine || !$this->machine->duration_days) return 0;
        $elapsed = $this->days_elapsed;
        return min(100, round(($elapsed / $this->machine->duration_days) * 100, 2));
    }

    public function getProjectedTotalAttribute(): float
    {
        return round($this->amount + $this->total_projected_profit, 2);
    }

    public function getCurrentValueAttribute(): float
    {
        return round($this->amount + $this->profit_credited, 2);
    }

    public function getCanWithdrawAttribute(): bool
    {
        return $this->status === 'active' && !$this->withdrawn;
    }
}
