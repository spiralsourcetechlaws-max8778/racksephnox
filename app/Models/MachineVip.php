<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineVip extends Model
{
    use HasFactory;

    protected $table = 'machine_vips';

    protected $fillable = [
        'machine_id', 'level', 'vip_level', 'name',
        'start_amount', 'max_amount', 'growth_rate', 'duration_days',
        'daily_profit_min', 'daily_profit_max',
        'bonus_multiplier', 'referral_bonus_rate', 'is_active',
    ];

    protected $casts = [
        'level'              => 'integer',
        'vip_level'          => 'integer',
        'start_amount'       => 'float',
        'max_amount'         => 'float',
        'growth_rate'        => 'float',
        'duration_days'      => 'integer',
        'daily_profit_min'   => 'float',
        'daily_profit_max'   => 'float',
        'bonus_multiplier'   => 'float',
        'referral_bonus_rate'=> 'float',
        'is_active'          => 'boolean',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function investments()
    {
        return $this->hasMany(MachineInvestment::class, 'vip_level', 'level')
                    ->where('machine_id', $this->machine_id);
    }

    public function getRoiAttribute(): float
    {
        return round($this->growth_rate, 2);
    }

    public function getTotalReturnAttribute(): float
    {
        return round($this->start_amount * (1 + $this->growth_rate / 100), 2);
    }
}
