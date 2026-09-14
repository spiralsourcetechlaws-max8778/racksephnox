<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryMission extends Model
{
    use HasFactory;

    protected $table = 'lottery_missions';

    protected $fillable = [
        'name', 'description', 'requirement_type', 'requirement_value',
        'reward_amount', 'is_active',
    ];

    protected $casts = [
        'requirement_value' => 'integer',
        'reward_amount'     => 'float',
        'is_active'         => 'boolean',
    ];

    public function userMissions()
    {
        return $this->hasMany(LotteryUserMission::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
