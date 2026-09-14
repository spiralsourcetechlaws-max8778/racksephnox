<?php

namespace App\Notifications\Lottery;

use App\Models\LotterySpin;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BigWinNotification extends Notification
{
    use Queueable;

    public function __construct(public LotterySpin $spin, public float $amount) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title'  => '🎉 Big Win!',
            'body'   => 'You won KES ' . number_format($this->amount, 2) . ' in the lottery!',
            'amount' => $this->amount,
            'spin_id'=> $this->spin->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Big Win on Racksephnox Lottery!')
            ->line('You just won KES ' . number_format($this->amount, 2) . '!')
            ->action('View Lottery', url('/lottery'));
    }
}
