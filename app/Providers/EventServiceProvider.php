<?php
namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\InvestmentCreated;
use App\Events\MpesaPaymentReceived;
use App\Listeners\SendInvestmentConfirmation;
use App\Listeners\AwardReferralBonus;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\Lottery\BigWinEvent::class => [
            \App\Listeners\Lottery\SendBigWinNotification::class,
        ],
        \App\Events\Lottery\JackpotWonEvent::class => [
            \App\Listeners\Lottery\BroadcastJackpotWon::class,
        ],
        \App\Events\Lottery\TournamentEndedEvent::class => [
            \App\Listeners\Lottery\DistributeTournamentPrizesListener::class,
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        InvestmentCreated::class => [
            SendInvestmentConfirmation::class,
        ],
        MpesaPaymentReceived::class => [
            AwardReferralBonus::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
