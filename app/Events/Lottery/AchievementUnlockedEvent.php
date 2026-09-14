<?php

namespace App\Events\Lottery;

use App\Models\LotteryAchievement;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementUnlockedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user, public LotteryAchievement $achievement) {}
}
