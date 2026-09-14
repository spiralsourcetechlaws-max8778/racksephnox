<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryRevenueTarget extends Model
{
    use HasFactory;

    protected $table = 'lottery_revenue_targets';

    protected $fillable = [
        'target_amount', 'current_revenue', 'start_date', 'end_date', 'is_active',
    ];

    protected $casts = [
        'target_amount'   => 'float',
        'current_revenue' => 'float',
        'start_date'      => 'datetime',
        'end_date'        => 'datetime',
        'is_active'       => 'boolean',
    ];

    public function getProgressPercentAttribute(): float
    {
        if ($this->target_amount <= 0) return 0;
        return min(100, round(($this->current_revenue / $this->target_amount) * 100, 2));
    }

    public function getRemainingAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_revenue);
    }

    public function getDaysLeftAttribute(): int
    {
        if (!$this->end_date) return 0;
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
