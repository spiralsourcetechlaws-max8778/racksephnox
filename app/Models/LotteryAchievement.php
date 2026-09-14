<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryAchievement extends Model
{
    use HasFactory;

    protected $table = 'lottery_achievements';

    protected $fillable = [
        'name', 'description', 'requirement_type', 'requirement_value',
        'reward_amount', 'icon',
    ];

    protected $casts = [
        'requirement_value' => 'integer',
        'reward_amount'     => 'float',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'lottery_user_achievements')
                    ->withPivot('achieved_at')
                    ->withTimestamps();
    }

    public function userAchievements()
    {
        return $this->hasMany(LotteryUserAchievement::class);
    }
}
