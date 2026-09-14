<?php

namespace App\Events\Lottery;

use App\Models\LotterySpin;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BigWinEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user, public LotterySpin $spin, public float $amount) {}
}
