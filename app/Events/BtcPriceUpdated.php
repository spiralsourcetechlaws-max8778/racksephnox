<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BtcPriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public float $price_kes;
    public float $price_usd;
    public string $recorded_at;

    public function __construct(float $priceUsd, float $priceKes)
    {
        $this->price_usd  = $priceUsd;
        $this->price_kes  = $priceKes;
        $this->recorded_at = now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [new Channel('trading')];
    }

    public function broadcastAs(): string
    {
        return 'BtcPriceUpdated';
    }
}
