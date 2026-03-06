<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class ReclassificationPromotedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $applicationId,
        public string $fromRank,
        public string $toRank,
        public string $cycleYear = '',
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
        $summaryUrl = route('reclassification.submitted-summary.show', $this->applicationId);

        return (new MailMessage())
            ->subject('Reclassification Approved - Promotion Notice')
            ->line('Congratulations! Your reclassification has been approved.')
            ->line("You have been promoted from {$this->fromRank} to {$this->toRank}.")
            ->action('View Submitted Summary', $summaryUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Congratulations! You have been promoted.',
            'message' => "You have been promoted from {$this->fromRank} to {$this->toRank}.",
            'application_id' => $this->applicationId,
            'from_rank' => $this->fromRank,
            'to_rank' => $this->toRank,
            'cycle_year' => $this->cycleYear,
        ];
    }
}
