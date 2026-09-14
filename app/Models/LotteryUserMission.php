<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryUserMission extends Model
{
    use HasFactory;

    protected $table = 'lottery_user_missions';

    protected $fillable = [
        'user_id', 'lottery_mission_id', 'progress', 'completed', 'claimed',
    ];

    protected $casts = [
        'progress'  => 'integer',
        'completed' => 'boolean',
        'claimed'   => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mission()
    {
        return $this->belongsTo(LotteryMission::class, 'lottery_mission_id');
    }

    public function getProgressPercentAttribute(): float
    {
        $target = $this->mission->requirement_value ?? 1;
        if ($target <= 0) return 0;
        return min(100, round(($this->progress / $target) * 100, 2));
    }
}
