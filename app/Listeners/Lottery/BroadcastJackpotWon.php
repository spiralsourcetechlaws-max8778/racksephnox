<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\JackpotWonEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class BroadcastJackpotWon implements ShouldQueue
{
    public function handle(JackpotWonEvent $event): void
    {
        // Push into a recent-wins cache the frontend polls
        $recent = Cache::get('lottery_recent_wins', []);
        array_unshift($recent, [
            'user'   => $event->user->name,
            'tier'   => $event->tier,
            'amount' => $event->amount,
            'at'     => now()->toIso8601String(),
        ]);
        Cache::put('lottery_recent_wins', array_slice($recent, 0, 20), now()->addHours(24));
    }
}
