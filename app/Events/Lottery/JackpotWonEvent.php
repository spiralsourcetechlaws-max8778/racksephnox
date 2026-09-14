<?php

namespace App\Events\Lottery;

use App\Models\LotteryJackpotWin;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JackpotWonEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user, public LotteryJackpotWin $win, public string $tier, public float $amount) {}
}
