<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class SetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'setup' => 1,
        ]);

        return (new MailMessage())
            ->subject('Set Your Password')
            ->line('Your account has been created.')
            ->line('Click the button below to set your password and activate your account.')
            ->action('Set Password', $url)
            ->line('If you did not expect this, you may ignore this email.');
    }
}
