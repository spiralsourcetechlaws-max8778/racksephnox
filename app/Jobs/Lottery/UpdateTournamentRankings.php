<?php

namespace App\Jobs\Lottery;

use App\Models\LotteryTournament;
use App\Services\Lottery\TournamentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTournamentRankings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TournamentService $service): void
    {
        LotteryTournament::active()->get()->each(function ($t) use ($service) {
            $service->updateRankings($t);
            $service->recomputeScores($t);
        });
    }
}
