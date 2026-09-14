<?php

namespace App\Jobs\Lottery;

use App\Models\LotteryDailyStreak;
use App\Services\Lottery\StreakService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AwardStreakBonuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(StreakService $streaks): void
    {
        // Reward users whose streak broke (reset their counter)
        LotteryDailyStreak::where('last_spin_date', '<', now()->subDay()->toDateString())
            ->update(['current_streak' => 0]);
    }
}
