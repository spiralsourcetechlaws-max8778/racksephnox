<?php

namespace App\Jobs\Lottery;

use App\Models\LotterySpin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupLotterySpins implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Archive spins older than 90 days by soft-deleting if the column exists
        if (\Schema::hasColumn('lottery_spins', 'deleted_at')) {
            LotterySpin::where('created_at', '<', now()->subDays(90))->delete();
        }
    }
}
