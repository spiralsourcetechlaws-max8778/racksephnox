<?php

namespace App\Jobs\Lottery;

use App\Models\User;
use App\Services\Lottery\MissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetDailyMissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MissionService $missions): void
    {
        User::chunkById(500, function ($users) use ($missions) {
            foreach ($users as $user) {
                $missions->resetDaily($user);
            }
        });
    }
}
