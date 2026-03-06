<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class ReclassificationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $eventKey = null,
        public array $meta = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (Schema::hasTable('notifications')) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->subject)
            ->line($this->title)
            ->line($this->message);

        if (!empty($this->actionUrl)) {
            $mail->action($this->actionLabel ?: 'View details', $this->actionUrl);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'key' => $this->eventKey,
        ], $this->meta);
    }
}

