<?php

namespace App\Jobs\Lottery;

use App\Models\LotterySpin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class UpdateLeaderboardCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        foreach (['day', 'week', 'month'] as $period) {
            $from = match ($period) {
                'day'   => now()->startOfDay(),
                'week'  => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
            };

            $rows = LotterySpin::where('created_at', '>=', $from)
                ->selectRaw('user_id, SUM(win_amount) AS total_win, COUNT(*) AS spins')
                ->groupBy('user_id')
                ->orderByDesc('total_win')
                ->with('user')
                ->limit(100)
                ->get();

            Cache::put("lottery_leaderboard_{$period}", $rows, now()->addMinutes(5));
        }
    }
}
