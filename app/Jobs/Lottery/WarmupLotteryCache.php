<?php

namespace App\Jobs\Lottery;

use App\Models\LotteryGame;
use App\Models\LotteryTournament;
use App\Services\Lottery\JackpotPoolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class WarmupLotteryCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(JackpotPoolService $jackpots): void
    {
        Cache::put('lottery_jackpots', $jackpots->allPools(), now()->addMinutes(10));
        Cache::put('lottery_active_tournaments', LotteryTournament::active()->get(), now()->addMinutes(10));
        Cache::put('lottery_active_games', LotteryGame::active()->get(), now()->addMinutes(30));
    }
}
