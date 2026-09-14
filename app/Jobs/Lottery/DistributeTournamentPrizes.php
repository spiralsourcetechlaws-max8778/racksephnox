<?php

namespace App\Jobs\Lottery;

use App\Models\LotteryTournament;
use App\Services\Lottery\TournamentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DistributeTournamentPrizes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TournamentService $service): void
    {
        LotteryTournament::where('is_active', true)
            ->where('prize_distributed', false)
            ->where('end_date', '<', now())
            ->get()
            ->each(fn ($t) => $service->end($t));
    }
}
