<?php

use App\Services\ReclassificationNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reclassification:notify-deadlines', function () {
    $sent = app(ReclassificationNotificationService::class)->sendDeadlineReminders();

    $this->info("Reclassification deadline reminders sent: {$sent}");
})->purpose('Send periodic reclassification deadline reminder emails');

Schedule::command('reclassification:notify-deadlines')
    ->hourly()
    ->withoutOverlapping();
