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
        'total_return', 'return_earned' // Add all singular/plural variants
    ];

    protected $casts = [
        'amount' => 'float',
        'profit_credited' => 'float',
        'daily_profit' => 'float',
        'total_projected_profit' => 'float',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_accrued_at' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'matures_at' => 'datetime',
        'expected_return' => 'float',
        'total_return' => 'float',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function machine() { return $this->belongsTo(Machine::class); }
}
