<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\BigWinEvent;
use App\Notifications\Lottery\BigWinNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBigWinNotification implements ShouldQueue
{
    public function handle(BigWinEvent $event): void
    {
        try {
            $event->user->notify(new BigWinNotification($event->spin, $event->amount));
        } catch (\Throwable $e) {
            Log::warning('BigWin notification failed', ['msg' => $e->getMessage()]);
        }
    }
}
