<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class AccountVerificationOptions extends VerifyEmail
{
    public function __construct(private readonly string $code)
    {
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('Verify your account'))
            ->greeting(Lang::get('Hello!'))
            ->line(Lang::get('To activate your account, you can use either option below:'))
            ->action(Lang::get('Verify Email'), $verificationUrl)
            ->line(Lang::get('Or validate your account with this code: :code', ['code' => $this->code]))
            ->line(Lang::get('You can enter the code at: :url', ['url' => url('/validate-account')]))
            ->line(Lang::get('If you did not create an account, no further action is required.'));
    }
}
