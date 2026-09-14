<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryBonusWheelSpin extends Model
{
    use HasFactory;

    protected $table = 'lottery_bonus_wheel_spins';

    protected $fillable = [
        'user_id', 'lottery_bonus_wheel_id', 'reward_amount', 'reward_type',
    ];

    protected $casts = ['reward_amount' => 'float'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wheel()
    {
        return $this->belongsTo(LotteryBonusWheel::class, 'lottery_bonus_wheel_id');
    }
}
