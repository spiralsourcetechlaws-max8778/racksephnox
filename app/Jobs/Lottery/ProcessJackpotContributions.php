<?php

namespace App\Jobs\Lottery;

use App\Models\LotterySpin;
use App\Services\Lottery\JackpotPoolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessJackpotContributions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(JackpotPoolService $pools): void
    {
        // Safety net — any spins that slipped through the live feed
        LotterySpin::where('created_at', '>=', now()->subMinutes(2))
            ->whereNull('jackpot_tier')
            ->chunkById(200, function ($spins) use ($pools) {
                foreach ($spins as $spin) {
                    $pools->feedAll((float) $spin->bet_amount);
                }
            });
    }
}
