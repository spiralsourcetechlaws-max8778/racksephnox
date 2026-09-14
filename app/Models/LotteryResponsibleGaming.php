<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryResponsibleGaming extends Model
{
    use HasFactory;

    protected $table = 'lottery_responsible_gaming';

    protected $fillable = [
        'user_id', 'daily_loss_cap', 'weekly_loss_cap', 'monthly_loss_cap',
        'daily_loss_used', 'weekly_loss_used', 'monthly_loss_used',
        'session_timeout_minutes', 'reality_check_minutes',
        'cool_down_minutes', 'cool_down_until',
        'self_exclusion_until', 'self_exclusion_reason', 'last_session_start',
    ];

    protected $casts = [
        'daily_loss_cap'     => 'float',
        'weekly_loss_cap'    => 'float',
        'monthly_loss_cap'   => 'float',
        'daily_loss_used'    => 'float',
        'weekly_loss_used'   => 'float',
        'monthly_loss_used'  => 'float',
        'cool_down_until'    => 'datetime',
        'self_exclusion_until' => 'datetime',
        'last_session_start' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsSelfExcludedAttribute(): bool
    {
        return $this->self_exclusion_until && $this->self_exclusion_until->isFuture();
    }

    public function getIsInCoolDownAttribute(): bool
    {
        return $this->cool_down_until && $this->cool_down_until->isFuture();
    }

    public function getCanBetAttribute(): bool
    {
        if ($this->is_self_excluded) return false;
        if ($this->is_in_cool_down)   return false;
        if ($this->daily_loss_cap && $this->daily_loss_used >= $this->daily_loss_cap) return false;
        if ($this->weekly_loss_cap && $this->weekly_loss_used >= $this->weekly_loss_cap) return false;
        if ($this->monthly_loss_cap && $this->monthly_loss_used >= $this->monthly_loss_cap) return false;
        return true;
    }
}
