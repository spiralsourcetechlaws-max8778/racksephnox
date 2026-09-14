<?php

namespace App\Jobs\Lottery;

use App\Services\Lottery\JackpotPoolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckJackpotMustDrop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(JackpotPoolService $pools): void
    {
        $pools->enforceMustDrop();
    }
}
