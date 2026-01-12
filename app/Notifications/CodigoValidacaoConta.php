<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CodigoValidacaoConta extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $nome,
        private readonly string $codigo,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Validate account - SAW')
            ->greeting('Hello '.$this->nome.',')
            ->line('Thanks for registering.')
            ->line('Your validation code is: '.$this->codigo)
            ->line('Enter this code on the account validation page to activate your account.')
            ->action('Validate account', url('/validate-account'))
            ->line('If you did not register, you can ignore this email.');
    }
}
