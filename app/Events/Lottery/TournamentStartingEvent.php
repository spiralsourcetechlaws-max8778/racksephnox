<?php

namespace App\Events\Lottery;

use App\Models\LotteryTournament;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentStartingEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public LotteryTournament $tournament) {}
}
