<?php

namespace App\Events\Lottery;

use App\Models\LotteryTournament;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentEndedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public LotteryTournament $tournament, public array $winners = []) {}
}
