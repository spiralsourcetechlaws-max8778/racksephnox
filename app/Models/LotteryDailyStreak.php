<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryDailyStreak extends Model
{
    use HasFactory;

    protected $table = 'lottery_daily_streaks';

    protected $fillable = [
        'user_id', 'current_streak', 'longest_streak', 'last_spin_date',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_spin_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStreakMultiplierAttribute(): float
    {
        return match (true) {
            $this->current_streak >= 90 => 3.0,
            $this->current_streak >= 30 => 2.0,
            $this->current_streak >= 7  => 1.5,
            default                     => 1.0,
        };
    }
}
