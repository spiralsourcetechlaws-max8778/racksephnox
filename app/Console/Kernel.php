<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ============================================================
        // LOTTERY SCHEDULED JOBS
        // ============================================================
        $schedule->job(new \App\Jobs\Lottery\UpdateTournamentRankings)->everyFiveMinutes();
        $schedule->job(new \App\Jobs\Lottery\DistributeTournamentPrizes)->everyTenMinutes();
        $schedule->job(new \App\Jobs\Lottery\CleanupLotterySpins)->hourly();
        $schedule->job(new \App\Jobs\Lottery\UpdateLeaderboardCache)->everyMinute();
        $schedule->job(new \App\Jobs\Lottery\WarmupLotteryCache)->everyTenMinutes();
        $schedule->job(new \App\Jobs\Lottery\ProcessJackpotContributions)->everyMinute();
        $schedule->job(new \App\Jobs\Lottery\CheckJackpotMustDrop)->everyFiveMinutes();
        $schedule->job(new \App\Jobs\Lottery\ResetDailyMissions)->dailyAt('00:00');
        $schedule->job(new \App\Jobs\Lottery\AwardStreakBonuses)->dailyAt('00:05');

        // ============================================================
        // OTHER JOBS (existing)
        // ============================================================
        $schedule->command('cache:prune-stale-tags')->hourly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
