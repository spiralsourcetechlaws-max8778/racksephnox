<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryUserAchievement extends Model
{
    use HasFactory;

    protected $table = 'lottery_user_achievements';

    protected $fillable = ['user_id', 'lottery_achievement_id', 'achieved_at'];

    protected $casts = ['achieved_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function achievement()
    {
        return $this->belongsTo(LotteryAchievement::class, 'lottery_achievement_id');
    }
}
