<?php

namespace App\Services;

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Models\User;
use App\Notifications\ReclassificationStatusNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ReclassificationNotificationService
{
    private function submissionWindowLabel(ReclassificationPeriod $period): ?string
    {
        $startAt = $period->start_at instanceof Carbon ? $period->start_at : null;
        $endAt = $period->end_at instanceof Carbon ? $period->end_at : null;

        if (!$startAt && !$endAt) {
            return null;
        }

        $format = 'M d, Y h:i A';

        if ($startAt && $endAt) {
            return "Submission window: {$startAt->format($format)} to {$endAt->format($format)}.";
        }

        if ($startAt) {
            return "Submission starts on {$startAt->format($format)}.";
        }

        return "Submission closes on {$endAt->format($format)}.";
    }

    private function periodLabel(?ReclassificationPeriod $period, ?string $fallbackCycle = null): string
    {
        if ($period) {
            $label = trim((string) ($period->name ?? ''));
            if ($label !== '') {
                return $label;
            }

            $cycle = trim((string) ($period->cycle_year ?? ''));
            if ($cycle !== '') {
                return "CY {$cycle}";
            }
        }

        $cycle = trim((string) $fallbackCycle);
        if ($cycle !== '') {
            return "CY {$cycle}";
        }

        return 'the current cycle';
    }

    private function roleLabel(string $role): string
    {
        return match (strtolower($role)) {
            'hr' => 'HR',
            'vpaa' => 'VPAA',
            'dean' => 'Dean',
            'president' => 'President',
            default => ucfirst(strtolower($role)),
        };
    }

    private function activeUsersByRoles(array $roles): Collection
    {
        return User::query()
            ->whereIn('role', $roles)
            ->whereNotNull('email')
            ->when(
                Schema::hasColumn('users', 'status'),
                fn ($query) => $query->where('status', 'active')
            )
            ->get();
    }

    private function facultyUsers(): Collection
    {
        return $this->activeUsersByRoles(['faculty']);
    }

    private function usersForRole(string $role): Collection
    {
        return $this->activeUsersByRoles([strtolower($role)]);
    }

    private function send(Collection $users, ReclassificationStatusNotification $notification): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if (empty($user->email)) {
                continue;
            }
            $user->notify($notification);
            $sent++;
        }

        return $sent;
    }

    private function sendOncePerUser(
        Collection $users,
        callable $factory,
        string $eventKey,
        Carbon $expiresAt
    ): int {
        $sent = 0;
        $expiresAt = $expiresAt->greaterThan(now())
            ? $expiresAt
            : now()->addDay();

        foreach ($users as $user) {
            if (empty($user->email)) {
                continue;
            }
            $cacheKey = "reclassification:email:{$eventKey}:user:{$user->id}";
            if (!Cache::add($cacheKey, true, $expiresAt)) {
                continue;
            }

            $notification = $factory($user);
            if (!$notification instanceof ReclassificationStatusNotification) {
                continue;
            }

            $user->notify($notification);
            $sent++;
        }

        return $sent;
    }

    public function notifySubmissionOpened(ReclassificationPeriod $period): int
    {
        $periodLabel = $this->periodLabel($period);
        $faculty = $this->facultyUsers();
        $windowLabel = $this->submissionWindowLabel($period);
        $message = "You may now submit your reclassification for {$periodLabel}.";
        if ($windowLabel) {
            $message .= " {$windowLabel}";
        }

        return $this->send($faculty, new ReclassificationStatusNotification(
            subject: 'Reclassification Submission Is Open',
            title: 'Reclassification submission is now open.',
            message: $message,
            actionUrl: route('reclassification.show'),
            actionLabel: 'Open reclassification form',
            eventKey: "period:{$period->id}:submission_open",
            meta: [
                'period_id' => $period->id,
                'cycle_year' => $period->cycle_year,
                'type' => 'period_submission_open',
            ],
        ));
    }

    public function notifySubmissionClosed(ReclassificationPeriod $period): int
    {
        $periodLabel = $this->periodLabel($period);
        $faculty = $this->facultyUsers();
        $windowLabel = $this->submissionWindowLabel($period);
        $message = "Submission for {$periodLabel} is now closed.";
        if ($windowLabel) {
            $message .= " {$windowLabel}";
        }

        return $this->send($faculty, new ReclassificationStatusNotification(
            subject: 'Reclassification Submission Is Closed',
            title: 'Reclassification submission is now closed.',
            message: $message,
            actionUrl: route('faculty.dashboard'),
            actionLabel: 'Open dashboard',
            eventKey: "period:{$period->id}:submission_closed",
            meta: [
                'period_id' => $period->id,
                'cycle_year' => $period->cycle_year,
                'type' => 'period_submission_closed',
            ],
        ));
    }

    public function notifyPeriodEnded(ReclassificationPeriod $period): int
    {
        $periodLabel = $this->periodLabel($period);
        $faculty = $this->facultyUsers();

        return $this->send($faculty, new ReclassificationStatusNotification(
            subject: 'Reclassification Period Ended',
            title: 'The reclassification period has ended.',
            message: "{$periodLabel} has been marked as ended.",
            actionUrl: route('faculty.dashboard'),
            actionLabel: 'Open dashboard',
            eventKey: "period:{$period->id}:ended",
            meta: [
                'period_id' => $period->id,
                'cycle_year' => $period->cycle_year,
                'type' => 'period_ended',
            ],
        ));
    }

    public function notifyApplicationForwardedToRole(ReclassificationApplication $application, string $targetRole): int
    {
        $targetRole = strtolower($targetRole);
        $targetLabel = $this->roleLabel($targetRole);
        $application->loadMissing(['faculty', 'period']);

        $faculty = $application->faculty;
        $facultyName = $faculty?->name ?? 'A faculty member';
        $periodLabel = $this->periodLabel($application->period, (string) ($application->cycle_year ?? ''));

        $reviewers = $this->usersForRole($targetRole);
        $facultyRecipient = $faculty ? collect([$faculty]) : collect();

        $sent = 0;

        $sent += $this->send($reviewers, new ReclassificationStatusNotification(
            subject: "Submission forwarded to {$targetLabel} review",
            title: "A submission is now in {$targetLabel} review.",
            message: "{$facultyName}'s reclassification for {$periodLabel} was forwarded to {$targetLabel}.",
            actionUrl: route('reclassification.review.queue'),
            actionLabel: 'Open review queue',
            eventKey: "application:{$application->id}:forwarded_to:{$targetRole}",
            meta: [
                'application_id' => $application->id,
                'target_role' => $targetRole,
                'type' => 'application_forwarded',
            ],
        ));

        $sent += $this->send($facultyRecipient, new ReclassificationStatusNotification(
            subject: "Your submission moved to {$targetLabel} review",
            title: "Your reclassification is now in {$targetLabel} review.",
            message: "Your reclassification for {$periodLabel} has been forwarded to {$targetLabel}.",
            actionUrl: route('faculty.dashboard'),
            actionLabel: 'Open dashboard',
            eventKey: "application:{$application->id}:faculty_forwarded_to:{$targetRole}",
            meta: [
                'application_id' => $application->id,
                'target_role' => $targetRole,
                'type' => 'application_stage_update',
            ],
        ));

        return $sent;
    }

    public function notifyApplicationReturnedToFaculty(ReclassificationApplication $application, string $fromRole): int
    {
        $fromRole = strtolower(trim($fromRole));
        $fromLabel = $this->roleLabel($fromRole !== '' ? $fromRole : 'reviewer');
        $application->loadMissing(['faculty', 'period']);

        $faculty = $application->faculty;
        if (!$faculty || empty($faculty->email)) {
            return 0;
        }

        $periodLabel = $this->periodLabel($application->period, (string) ($application->cycle_year ?? ''));

        return $this->send(collect([$faculty]), new ReclassificationStatusNotification(
            subject: "Your submission was returned by {$fromLabel}",
            title: 'Your reclassification was returned for revision.',
            message: "Your reclassification for {$periodLabel} was returned by {$fromLabel}. Please review comments and resubmit.",
            actionUrl: route('reclassification.show'),
            actionLabel: 'Open reclassification form',
            eventKey: "application:{$application->id}:returned_from:{$fromRole}",
            meta: [
                'application_id' => $application->id,
                'from_role' => $fromRole,
                'type' => 'application_returned',
            ],
        ));
    }

    public function notifyAddedToVpaaApprovedList(ReclassificationApplication $application): int
    {
        $application->loadMissing(['faculty', 'period']);
        $faculty = $application->faculty;
        $facultyName = $faculty?->name ?? 'A faculty member';
        $periodLabel = $this->periodLabel($application->period, (string) ($application->cycle_year ?? ''));

        $sent = 0;

        $sent += $this->send($this->usersForRole('vpaa'), new ReclassificationStatusNotification(
            subject: 'Submission added to VPAA approved list',
            title: 'A submission has been added to VPAA approved list.',
            message: "{$facultyName}'s reclassification for {$periodLabel} is now in the VPAA approved list.",
            actionUrl: route('reclassification.review.approved'),
            actionLabel: 'Open approved list',
            eventKey: "application:{$application->id}:vpaa_approved_list",
            meta: [
                'application_id' => $application->id,
                'type' => 'vpaa_approved_list',
            ],
        ));

        $sent += $this->send($faculty ? collect([$faculty]) : collect(), new ReclassificationStatusNotification(
            subject: 'Your submission passed VPAA review',
            title: 'Your reclassification is now in the VPAA approved list.',
            message: "Your reclassification for {$periodLabel} has been added to the VPAA approved list.",
            actionUrl: route('faculty.dashboard'),
            actionLabel: 'Open dashboard',
            eventKey: "application:{$application->id}:faculty_vpaa_approved_list",
            meta: [
                'application_id' => $application->id,
                'type' => 'vpaa_approved_list',
            ],
        ));

        return $sent;
    }

    public function notifyApprovedListForwardedToPresident(Collection $applications, ReclassificationPeriod $period): int
    {
        $applications = $applications->filter()->values();
        if ($applications->isEmpty()) {
            return 0;
        }

        $periodLabel = $this->periodLabel($period);
        $count = $applications->count();
        $presidents = $this->usersForRole('president');
        $faculties = $applications
            ->pluck('faculty')
            ->filter()
            ->unique('id')
            ->values();

        $sent = 0;

        $sent += $this->send($presidents, new ReclassificationStatusNotification(
            subject: 'VPAA forwarded approved reclassification list',
            title: 'VPAA forwarded the approved list for President approval.',
            message: "{$count} submissions for {$periodLabel} are now awaiting President approval.",
            actionUrl: route('reclassification.review.approved'),
            actionLabel: 'Open approval list',
            eventKey: "period:{$period->id}:forwarded_to_president",
            meta: [
                'period_id' => $period->id,
                'applications_count' => $count,
                'type' => 'approved_list_forwarded',
            ],
        ));

        $sent += $this->send($faculties, new ReclassificationStatusNotification(
            subject: 'Your submission is awaiting President approval',
            title: 'Your reclassification has been forwarded to the President.',
            message: "Your submission for {$periodLabel} is now in the President approval list.",
            actionUrl: route('faculty.dashboard'),
            actionLabel: 'Open dashboard',
            eventKey: "period:{$period->id}:faculty_forwarded_to_president",
            meta: [
                'period_id' => $period->id,
                'type' => 'faculty_forwarded_to_president',
            ],
        ));

        return $sent;
    }

    private function buildReminderPayloads(ReclassificationPeriod $period): array
    {
        $endAt = $period->end_at;
        if (!$endAt instanceof Carbon) {
            return [];
        }

        $hoursLeft = now()->diffInHours($endAt, false);
        if ($hoursLeft <= 0) {
            return [];
        }

        $daysLeft = (int) floor($hoursLeft / 24);
        $payloads = [];

        if (in_array($daysLeft, [28, 21, 14, 7], true)) {
            $payloads[] = [
                'key' => "weekly_{$daysLeft}",
                'message' => "{$daysLeft} days left before submission closes.",
            ];
        }

        if (in_array($daysLeft, [3, 2], true)) {
            $payloads[] = [
                'key' => "final_{$daysLeft}_days",
                'message' => "Last {$daysLeft} days before submission closes.",
            ];
        }

        if ($hoursLeft <= 24) {
            $payloads[] = [
                'key' => 'final_24_hours',
                'message' => 'Last 24 hours before submission closes.',
            ];
        }

        return $payloads;
    }

    public function sendDeadlineReminders(): int
    {
        $query = ReclassificationPeriod::query()
            ->where('is_open', true)
            ->whereNotNull('end_at');

        if (Schema::hasColumn('reclassification_periods', 'status')) {
            $query->where('status', 'active');
        }

        $periods = $query->get();
        if ($periods->isEmpty()) {
            return 0;
        }

        $faculty = $this->facultyUsers();
        if ($faculty->isEmpty()) {
            return 0;
        }

        $sent = 0;
        foreach ($periods as $period) {
            $payloads = $this->buildReminderPayloads($period);
            if (empty($payloads)) {
                continue;
            }

            $periodLabel = $this->periodLabel($period);
            $expiresAt = ($period->end_at instanceof Carbon)
                ? $period->end_at->copy()->addDays(2)
                : now()->addDays(2);

            foreach ($payloads as $payload) {
                $eventKey = "period:{$period->id}:deadline:{$payload['key']}";
                $message = (string) ($payload['message'] ?? 'Submission deadline is approaching.');

                $sent += $this->sendOncePerUser(
                    $faculty,
                    fn ($user) => new ReclassificationStatusNotification(
                        subject: 'Reclassification Submission Deadline Reminder',
                        title: 'Submission deadline reminder',
                        message: "{$message} ({$periodLabel})",
                        actionUrl: route('reclassification.show'),
                        actionLabel: 'Open reclassification form',
                        eventKey: $eventKey,
                        meta: [
                            'period_id' => $period->id,
                            'cycle_year' => $period->cycle_year,
                            'type' => 'submission_deadline_reminder',
                        ],
                    ),
                    $eventKey,
                    $expiresAt
                );
            }
        }

        return $sent;
    }
}
