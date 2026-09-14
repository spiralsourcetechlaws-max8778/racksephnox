<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\TournamentEndedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DistributeTournamentPrizesListener implements ShouldQueue
{
    public function handle(TournamentEndedEvent $event): void
    {
        Log::info('Tournament ended', [
            'tournament_id' => $event->tournament->id,
            'winners'       => count($event->winners),
        ]);
    }
}
