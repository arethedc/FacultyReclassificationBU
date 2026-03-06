<?php

namespace App\Http\Controllers;

use App\Models\RankLevel;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Models\ReclassificationStatusTrail;
use App\Notifications\ReclassificationPromotedNotification;
use App\Services\ReclassificationNotificationService;
use App\Support\ReclassificationEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReclassificationWorkflowController extends Controller
{
    private function activePeriod(): ?ReclassificationPeriod
    {
        if (!Schema::hasTable('reclassification_periods')) {
            return null;
        }

        $query = ReclassificationPeriod::query();
        if (Schema::hasColumn('reclassification_periods', 'status')) {
            $query->where('status', 'active');
        } else {
            $query->where('is_open', true);
        }

        return $query->orderByDesc('created_at')->first();
    }

    private function openSubmissionPeriod(): ?ReclassificationPeriod
    {
        if (!Schema::hasTable('reclassification_periods')) {
            return null;
        }

        $query = ReclassificationPeriod::query()->where('is_open', true);
        if (Schema::hasColumn('reclassification_periods', 'status')) {
            $query->where('status', 'active');
        }

        $period = $query->orderByDesc('created_at')->first();
        if (!$period) {
            return null;
        }

        if ($period->start_at && now()->lt($period->start_at)) {
            return null;
        }

        if ($period->end_at && now()->gt($period->end_at)) {
            return null;
        }

        return $period;
    }

    private function stepLabel(string $step): string
    {
        return match ($step) {
            'faculty' => 'Faculty',
            'dean' => 'Dean',
            'hr' => 'HR',
            'vpaa' => 'VPAA',
            'president' => 'President',
            'finalized' => 'Finalized',
            default => ucfirst(str_replace('_', ' ', $step)),
        };
    }

    private function expectedReviewerRoleForStatus(string $status): ?string
    {
        return match ($status) {
            'dean_review' => 'dean',
            'hr_review' => 'hr',
            'vpaa_review', 'vpaa_approved' => 'vpaa',
            'president_review' => 'president',
            default => null,
        };
    }

    private function assertReviewerOwnsCurrentStage(Request $request, ReclassificationApplication $application): void
    {
        $role = strtolower((string) ($request->user()->role ?? ''));
        $status = (string) ($application->status ?? '');
        $expectedRole = $this->expectedReviewerRoleForStatus($status);

        abort_unless($expectedRole && $role === $expectedRole, 403);

        if ($role === 'dean') {
            $application->loadMissing('faculty');
            $userDepartmentId = $request->user()->department_id;
            abort_unless($userDepartmentId && $application->faculty?->department_id === $userDepartmentId, 403);
        }
    }

    private function isApplicationInActivePeriodScope(ReclassificationApplication $application): bool
    {
        $activePeriod = $this->activePeriod();
        if (!$activePeriod) {
            return false;
        }

        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
        if (!$hasPeriodId) {
            return !empty($activePeriod->cycle_year)
                && (string) ($application->cycle_year ?? '') === (string) $activePeriod->cycle_year;
        }

        if ((int) ($application->period_id ?? 0) === (int) $activePeriod->id) {
            return true;
        }

        return empty($application->period_id)
            && !empty($activePeriod->cycle_year)
            && (string) ($application->cycle_year ?? '') === (string) $activePeriod->cycle_year;
    }

    private function recordStatusTrail(
        ReclassificationApplication $application,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $fromStep,
        ?string $toStep,
        string $action,
        ?string $note = null,
        array $meta = [],
        $actor = null
    ): void {
        if (!Schema::hasTable('reclassification_status_trails')) {
            return;
        }
        if (!$toStatus) {
            return;
        }

        ReclassificationStatusTrail::create([
            'reclassification_application_id' => $application->id,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor ? strtolower((string) ($actor->role ?? '')) : null,
            'from_status' => $fromStatus ?: null,
            'to_status' => $toStatus,
            'from_step' => $fromStep ?: null,
            'to_step' => $toStep ?: null,
            'action' => $action,
            'note' => $note,
            'meta' => !empty($meta) ? $meta : null,
        ]);
    }

    private function hasReturnActionItems(ReclassificationApplication $application): bool
    {
        if ($this->hasPendingFacultyReturnRequest($application)) {
            return true;
        }

        return $this->countRequiredFacultyComments($application, 'open') > 0;
    }

    private function isEntryRemoved($entry): bool
    {
        if (!$entry) {
            return false;
        }

        $data = is_array($entry->data) ? $entry->data : [];
        $value = $data['is_removed'] ?? false;

        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function countRequiredFacultyComments(ReclassificationApplication $application, ?string $statusMode = null): int
    {
        $commentQuery = $application->rowComments()
            ->with('entry')
            ->where('visibility', 'faculty_visible');

        if (Schema::hasColumn('reclassification_row_comments', 'action_type')) {
            $commentQuery->where('action_type', 'requires_action');
        }
        if (Schema::hasColumn('reclassification_row_comments', 'parent_id')) {
            $commentQuery->whereNull('parent_id');
        }
        if (Schema::hasColumn('reclassification_row_comments', 'status')) {
            if ($statusMode === 'open') {
                $commentQuery->where('status', 'open');
            } elseif ($statusMode === 'unresolved') {
                $commentQuery->where('status', '!=', 'resolved');
            }
        }

        return $commentQuery
            ->get()
            ->filter(fn ($comment) => !$this->isEntryRemoved($comment->entry))
            ->count();
    }

    private function countRequiredFacultyCommentsByReviewerRole(
        ReclassificationApplication $application,
        string $reviewerRole,
        ?string $statusMode = null
    ): int {
        $role = strtolower(trim($reviewerRole));
        if ($role === '') {
            return 0;
        }

        $commentQuery = $application->rowComments()
            ->with('entry')
            ->where('visibility', 'faculty_visible')
            ->whereHas('author', function ($query) use ($role) {
                $query->whereRaw('LOWER(role) = ?', [$role]);
            });

        if (Schema::hasColumn('reclassification_row_comments', 'action_type')) {
            $commentQuery->where('action_type', 'requires_action');
        }
        if (Schema::hasColumn('reclassification_row_comments', 'parent_id')) {
            $commentQuery->whereNull('parent_id');
        }
        if (Schema::hasColumn('reclassification_row_comments', 'status')) {
            if ($statusMode === 'open') {
                $commentQuery->where('status', 'open');
            } elseif ($statusMode === 'unresolved') {
                $commentQuery->where('status', '!=', 'resolved');
            }
        }

        return $commentQuery
            ->get()
            ->filter(fn ($comment) => !$this->isEntryRemoved($comment->entry))
            ->count();
    }

    private function hasPendingFacultyReturnRequest(ReclassificationApplication $application): bool
    {
        if (!Schema::hasColumn('reclassification_applications', 'faculty_return_requested_at')) {
            return false;
        }

        return !is_null($application->faculty_return_requested_at);
    }

    private function clearFacultyReturnRequestPayload(): array
    {
        $payload = [];
        if (Schema::hasColumn('reclassification_applications', 'faculty_return_requested_at')) {
            $payload['faculty_return_requested_at'] = null;
        }
        if (Schema::hasColumn('reclassification_applications', 'faculty_return_requested_by_user_id')) {
            $payload['faculty_return_requested_by_user_id'] = null;
        }

        return $payload;
    }

    private function currentRankLabel($profile): string
    {
        if (!$profile) {
            return 'Instructor C';
        }

        if (Schema::hasTable('rank_levels') && !empty($profile->rank_level_id)) {
            $title = DB::table('rank_levels')->where('id', $profile->rank_level_id)->value('title');
            if ($title) {
                return (string) $title;
            }
        }

        $base = trim((string) ($profile->teaching_rank ?? ''));
        $step = strtoupper(trim((string) ($profile->rank_step ?? '')));

        if ($base === '') {
            return 'Instructor C';
        }

        if (preg_match('/\b(A|B|C)\b$/i', $base)) {
            return $base;
        }

        if (in_array($step, ['A', 'B', 'C'], true)) {
            return "{$base} {$step}";
        }

        return $base;
    }

    private function parseRank(?string $label): ?array
    {
        $raw = strtolower(trim((string) $label));
        if ($raw === '') {
            return null;
        }

        $track = match (true) {
            str_contains($raw, 'full professor') => 'full',
            str_contains($raw, 'associate professor') => 'associate',
            str_contains($raw, 'assistant professor') => 'assistant',
            str_contains($raw, 'instructor') => 'instructor',
            default => null,
        };

        if (!$track) {
            return null;
        }

        $letter = 'C';
        if (preg_match('/\b([abc])\b$/i', $raw, $match)) {
            $letter = strtoupper($match[1]);
        }

        return [
            'track' => $track,
            'letter' => $letter,
        ];
    }

    private function rankLabel(string $track, string $letter): string
    {
        $trackLabel = match ($track) {
            'full' => 'Full Professor',
            'associate' => 'Associate Professor',
            'assistant' => 'Assistant Professor',
            default => 'Instructor',
        };

        return "{$trackLabel} {$letter}";
    }

    private function rankScore(string $label): int
    {
        $parsed = $this->parseRank($label);
        if (!$parsed) {
            return 0;
        }

        $trackBase = [
            'instructor' => 0,
            'assistant' => 3,
            'associate' => 6,
            'full' => 9,
        ];
        $letterScore = [
            'C' => 1,
            'B' => 2,
            'A' => 3,
        ];

        return ($trackBase[$parsed['track']] ?? 0) + ($letterScore[$parsed['letter']] ?? 0);
    }

    private function pointsRankFromEquivalent(float $eqPercent): ?array
    {
        $ranges = [
            'full' => [
                ['letter' => 'A', 'min' => 95.87, 'max' => 100.00],
                ['letter' => 'B', 'min' => 91.50, 'max' => 95.86],
                ['letter' => 'C', 'min' => 87.53, 'max' => 91.49],
            ],
            'associate' => [
                ['letter' => 'A', 'min' => 83.34, 'max' => 87.52],
                ['letter' => 'B', 'min' => 79.19, 'max' => 83.33],
                ['letter' => 'C', 'min' => 75.02, 'max' => 79.18],
            ],
            'assistant' => [
                ['letter' => 'A', 'min' => 70.85, 'max' => 75.01],
                ['letter' => 'B', 'min' => 66.68, 'max' => 70.84],
                ['letter' => 'C', 'min' => 62.51, 'max' => 66.67],
            ],
            'instructor' => [
                ['letter' => 'A', 'min' => 58.34, 'max' => 62.50],
                ['letter' => 'B', 'min' => 54.14, 'max' => 58.33],
                ['letter' => 'C', 'min' => 50.00, 'max' => 54.16],
            ],
        ];

        foreach (['full', 'associate', 'assistant', 'instructor'] as $track) {
            foreach ($ranges[$track] as $band) {
                if ($eqPercent >= $band['min'] && $eqPercent <= $band['max']) {
                    return [
                        'track' => $track,
                        'letter' => $band['letter'],
                    ];
                }
            }
        }

        return null;
    }

    private function nextTrack(string $track): string
    {
        return match ($track) {
            'instructor' => 'assistant',
            'assistant' => 'associate',
            'associate' => 'full',
            default => 'full',
        };
    }

    private function trackOrder(string $track): int
    {
        return match ($track) {
            'instructor' => 1,
            'assistant' => 2,
            'associate' => 3,
            'full' => 4,
            default => 0,
        };
    }

    private function buildApprovalResult(ReclassificationApplication $application): array
    {
        $storedCurrent = trim((string) ($application->current_rank_label_at_approval ?? ''));
        $storedApproved = trim((string) ($application->approved_rank_label ?? ''));
        if ($storedCurrent !== '' && $storedApproved !== '') {
            return [
                'current_rank_label' => $storedCurrent,
                'approved_rank_label' => $storedApproved,
            ];
        }

        $application->loadMissing([
            'sections.entries',
            'faculty.facultyProfile',
            'faculty.facultyHighestDegree',
        ]);

        $faculty = $application->faculty;
        $profile = $faculty?->facultyProfile;
        $currentLabel = $this->currentRankLabel($profile);
        $current = $this->parseRank($currentLabel) ?? ['track' => 'instructor', 'letter' => 'C'];

        $totalPoints = (float) $application->sections->sum('points_total');
        $eqPercent = $totalPoints / 4;
        $pointsRank = $this->pointsRankFromEquivalent($eqPercent);

        $degree = strtolower((string) ($faculty?->facultyHighestDegree?->highest_degree ?? ''));
        $hasMasters = in_array($degree, ['masters', 'doctorate'], true);
        $hasDoctorate = $degree === 'doctorate';

        $section3 = $application->sections->firstWhere('section_code', '3');
        $hasResearchEquivalent = $section3
            ? ($section3->entries->count() > 0 || (float) $section3->points_total > 0)
            : false;
        $hasAcceptedResearchOutput = $hasResearchEquivalent;

        $approvedLabel = $currentLabel;

        if ($pointsRank && $hasMasters && $hasResearchEquivalent) {
            $desiredTrack = $pointsRank['track'];
            $desiredLetter = $pointsRank['letter'];

            $maxAllowedTrack = ($hasDoctorate && $hasAcceptedResearchOutput) ? 'full' : 'associate';
            if ($this->trackOrder($desiredTrack) > $this->trackOrder($maxAllowedTrack)) {
                $desiredTrack = $maxAllowedTrack;
                $desiredLetter = 'A';
            }

            $oneStepTrack = $this->nextTrack($current['track']);
            if ($this->trackOrder($desiredTrack) > $this->trackOrder($oneStepTrack)) {
                $desiredTrack = $oneStepTrack;
                $desiredLetter = 'A';
            }

            $candidate = $this->rankLabel($desiredTrack, $desiredLetter);
            if ($this->rankScore($candidate) > $this->rankScore($currentLabel)) {
                $approvedLabel = $candidate;
            }
        }

        return [
            'current_rank_label' => $currentLabel,
            'approved_rank_label' => $approvedLabel,
        ];
    }

    private function applyFacultyRankIfHigher(ReclassificationApplication $application, string $approvedRankLabel): void
    {
        $faculty = $application->faculty;
        $profile = $faculty?->facultyProfile;

        if (!$profile) {
            return;
        }

        $currentLabel = $this->currentRankLabel($profile);
        if ($this->rankScore($approvedRankLabel) <= $this->rankScore($currentLabel)) {
            return;
        }

        $updates = [
            'teaching_rank' => $approvedRankLabel,
        ];

        if (Schema::hasColumn('faculty_profiles', 'rank_step')) {
            $updates['rank_step'] = null;
        }

        if (Schema::hasTable('rank_levels') && Schema::hasColumn('faculty_profiles', 'rank_level_id')) {
            $rankLevelId = RankLevel::where('title', $approvedRankLabel)->value('id');
            if ($rankLevelId) {
                $updates['rank_level_id'] = $rankLevelId;
            }
        }

        $profile->update($updates);
    }

    private function finalizeApplication(ReclassificationApplication $application, $approver): void
    {
        $fromStatus = (string) ($application->status ?? '');
        $fromStep = (string) ($application->current_step ?? '');
        $approval = $this->buildApprovalResult($application);

        $application->update([
            'status' => 'finalized',
            'current_step' => 'finalized',
            'finalized_at' => now(),
            'returned_from' => null,
            ...$this->clearFacultyReturnRequestPayload(),
            'current_rank_label_at_approval' => $approval['current_rank_label'],
            'approved_rank_label' => $approval['approved_rank_label'],
            'approved_by_user_id' => $approver?->id,
            'approved_at' => now(),
        ]);

        $this->recordStatusTrail(
            $application,
            $fromStatus,
            'finalized',
            $fromStep,
            'finalized',
            'finalize',
            'Approved by President from approved list.',
            [
                'from_rank' => $approval['current_rank_label'] ?? null,
                'to_rank' => $approval['approved_rank_label'] ?? null,
            ],
            $approver
        );

        $this->applyFacultyRankIfHigher($application, $approval['approved_rank_label']);
        $this->notifyFacultyPromotion($application, $approval['current_rank_label'], $approval['approved_rank_label']);
    }

    public function finalizeForApproval(ReclassificationApplication $application, $approver): void
    {
        $this->finalizeApplication($application, $approver);
    }

    public function previewApprovalResult(ReclassificationApplication $application): array
    {
        return $this->buildApprovalResult($application);
    }

    public function requestReturn(Request $request, ReclassificationApplication $application)
    {
        abort_unless((int) $request->user()->id === (int) $application->faculty_user_id, 403);

        abort_unless(in_array((string) $application->status, [
            'dean_review',
            'hr_review',
            'vpaa_review',
            'vpaa_approved',
        ], true), 422);

        if ($this->hasPendingFacultyReturnRequest($application)) {
            return back()->withErrors([
                'request_return' => 'Return request is already pending review.',
            ]);
        }

        $updates = [];
        if (Schema::hasColumn('reclassification_applications', 'faculty_return_requested_at')) {
            $updates['faculty_return_requested_at'] = now();
        }
        if (Schema::hasColumn('reclassification_applications', 'faculty_return_requested_by_user_id')) {
            $updates['faculty_return_requested_by_user_id'] = $request->user()->id;
        }
        if (!empty($updates)) {
            $application->update($updates);
        }

        $currentStage = ucfirst(str_replace('_', ' ', (string) $application->status));
        $this->recordStatusTrail(
            $application,
            (string) $application->status,
            (string) $application->status,
            (string) ($application->current_step ?? ''),
            (string) ($application->current_step ?? ''),
            'faculty_request_return',
            "Faculty requested return while in {$currentStage}.",
            [],
            $request->user()
        );

        return back()->with('success', 'Return request sent. Please wait for reviewer action.');
    }

    private function notifyFacultyPromotion(
        ReclassificationApplication $application,
        string $fromRank,
        string $toRank
    ): void {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $faculty = $application->faculty;
        if (!$faculty) {
            return;
        }

        $faculty->notify(new ReclassificationPromotedNotification(
            applicationId: $application->id,
            fromRank: $fromRank,
            toRank: $toRank,
            cycleYear: (string) ($application->cycle_year ?? ''),
        ));
    }

    public function submit(Request $request, ReclassificationApplication $application)
    {
        // Only owner faculty can submit
        abort_unless($request->user()->id === $application->faculty_user_id, 403);

        // Only draft/returned can submit
        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 422);
        $isReturnedSubmission = (string) $application->status === 'returned_to_faculty';

        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
        $hasPeriodsTable = Schema::hasTable('reclassification_periods');
        $openSubmissionPeriod = $this->openSubmissionPeriod();
        $activePeriod = $this->activePeriod();
        if ($hasPeriodsTable && !$openSubmissionPeriod && !$isReturnedSubmission) {
            return back()->withErrors([
                'submit' => 'Submissions are currently closed. Please wait for HR to open a submission period.',
            ]);
        }

        $targetPeriod = $openSubmissionPeriod ?? $activePeriod;

        if (
            $hasPeriodId
            && $targetPeriod
            && !$application->period_id
            && (string) $application->cycle_year === (string) $targetPeriod->cycle_year
        ) {
            $application->update(['period_id' => $targetPeriod->id]);
        }

        if (
            !$isReturnedSubmission
            && $hasPeriodId
            && $targetPeriod
            && (int) ($application->period_id ?? 0) !== (int) $targetPeriod->id
        ) {
            return back()->withErrors([
                'submit' => 'This draft belongs to a different cycle. Please open your active-cycle draft.',
            ]);
        }

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());
        if (!($eligibility['canSubmit'] ?? false)) {
            return back()->withErrors([
                'submit' => implode(' ', $eligibility['missing'] ?? ['Submission requirements are not met.']),
            ]);
        }

        $openComments = $this->countRequiredFacultyComments($application, 'open');
        if ($openComments > 0) {
            return back()->withErrors([
                'submit' => 'Please address all action-required reviewer\'s comments before submitting.',
            ]);
        }

        $resumeMap = [
            'dean' => ['status' => 'dean_review', 'step' => 'dean'],
            'hr' => ['status' => 'hr_review', 'step' => 'hr'],
            'vpaa' => ['status' => 'vpaa_review', 'step' => 'vpaa'],
            'president' => ['status' => 'president_review', 'step' => 'president'],
        ];

        $resumeTo = $resumeMap[strtolower((string) $application->returned_from)] ?? [
            'status' => 'dean_review',
            'step' => 'dean',
        ];
        $fromStatus = (string) ($application->status ?? '');
        $fromStep = (string) ($application->current_step ?? '');
        $resumeFrom = strtolower((string) ($application->returned_from ?? ''));

        $application->update([
            'status' => $resumeTo['status'],
            'current_step' => $resumeTo['step'],
            'returned_from' => null,
            'submitted_at' => now(),
            ...$this->clearFacultyReturnRequestPayload(),
        ]);

        $this->recordStatusTrail(
            $application,
            $fromStatus,
            $resumeTo['status'],
            $fromStep,
            $resumeTo['step'],
            $fromStatus === 'returned_to_faculty' ? 'resubmit' : 'submit',
            $fromStatus === 'returned_to_faculty'
                ? ('Resubmitted after return from ' . strtoupper($resumeFrom ?: 'dean') . '.')
                : 'Submitted by faculty.',
            [
                'resumed_from_role' => $resumeFrom ?: null,
            ],
            $request->user()
        );

        app(ReclassificationNotificationService::class)
            ->notifyApplicationForwardedToRole($application, (string) ($resumeTo['step'] ?? 'dean'));

        $application->loadMissing('period');
        $periodLabel = trim((string) ($application->period?->name ?? ''));
        if ($periodLabel === '') {
            $periodLabel = trim((string) ($application->cycle_year ?? ''));
            if ($periodLabel !== '') {
                $periodLabel = "CY {$periodLabel}";
            } else {
                $periodLabel = 'the current cycle';
            }
        }

        return redirect()
            ->route('faculty.dashboard')
            ->with('success', "You have submitted your reclassification for {$periodLabel}.");
    }

    public function returnToFaculty(Request $request, ReclassificationApplication $application)
    {
        if (!in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true)) {
            return redirect()
                ->route('reclassification.review.queue')
                ->withErrors([
                    'queue' => 'You are not authorized to return this submission.',
                ]);
        }

        if (!in_array($application->status, ['dean_review', 'hr_review', 'vpaa_review', 'vpaa_approved', 'president_review'], true)) {
            return redirect()
                ->route('reclassification.review.queue')
                ->withErrors([
                    'queue' => 'This submission can no longer be returned from its current stage.',
                ]);
        }

        $this->assertReviewerOwnsCurrentStage($request, $application);
        if (!$this->isApplicationInActivePeriodScope($application)) {
            return redirect()
                ->route('reclassification.review.queue')
                ->withErrors([
                    'queue' => 'This submission is outside the active period queue.',
                ]);
        }

        if (!$this->hasReturnActionItems($application)) {
            return back()->withErrors([
                'return' => 'Add at least one action-required comment before returning to faculty.',
            ]);
        }

        $fromStatus = (string) ($application->status ?? '');
        $fromStep = (string) ($application->current_step ?? '');
        $actorRole = strtolower((string) ($request->user()->role ?? ''));
        $application->update([
            'status' => 'returned_to_faculty',
            'current_step' => 'faculty',
            'returned_from' => $actorRole,
            ...$this->clearFacultyReturnRequestPayload(),
        ]);

        $this->recordStatusTrail(
            $application,
            $fromStatus,
            'returned_to_faculty',
            $fromStep,
            'faculty',
            'return_to_faculty',
            'Returned to faculty for revision.',
            [
                'returned_from' => $actorRole,
            ],
            $request->user()
        );

        app(ReclassificationNotificationService::class)
            ->notifyApplicationReturnedToFaculty($application, $actorRole);

        $target = $this->stepLabel('faculty');
        $facultyName = trim((string) ($application->faculty?->name ?? ''));
        if ($facultyName === '') {
            $facultyName = 'Faculty';
        }
        return redirect()
            ->route('reclassification.review.queue')
            ->with('success', "Reclassification of {$facultyName} has been returned to {$target}.");
    }

    public function forward(Request $request, ReclassificationApplication $application)
    {
        if (!in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true)) {
            return redirect()
                ->route('reclassification.review.queue')
                ->withErrors([
                    'queue' => 'You are not authorized to forward this submission.',
                ]);
        }
        $application->loadMissing('sections.entries');

        // Map forward chain
        $map = [
            'dean_review' => ['next_status' => 'hr_review', 'next_step' => 'hr'],
            'hr_review' => ['next_status' => 'vpaa_review', 'next_step' => 'vpaa'],
            'vpaa_review' => ['next_status' => 'vpaa_approved', 'next_step' => 'vpaa'],
        ];

        if ($application->status === 'finalized') {
            return redirect()
                ->route('reclassification.review.queue')
                ->with('success', 'The form is already finalized.');
        }

        if ($application->status === 'rejected_final') {
            return redirect()
                ->route('reclassification.review.queue')
                ->with('success', 'The form is already final rejected.');
        }

        if ($application->status === 'vpaa_approved') {
            return redirect()
                ->route('reclassification.review.approved')
                ->with('success', 'The form is already approved by VPAA and waiting in VPAA Approved List.');
        }

        if ($application->status === 'president_review') {
            return redirect()
                ->route('reclassification.review.approved')
                ->withErrors([
                    'approved_list' => 'President approval is done from Approved List (batch).',
                ]);
        }

        $this->assertReviewerOwnsCurrentStage($request, $application);
        if (!$this->isApplicationInActivePeriodScope($application)) {
            return redirect()
                ->route('reclassification.review.queue')
                ->withErrors([
                    'queue' => 'This submission is outside the active period queue.',
                ]);
        }

        $reviewerRole = strtolower((string) ($request->user()->role ?? ''));
        $unresolvedCurrentReviewerComments = $this->countRequiredFacultyCommentsByReviewerRole(
            $application,
            $reviewerRole,
            'unresolved'
        );
        if ($unresolvedCurrentReviewerComments > 0) {
            return redirect()
                ->route('reclassification.review.show', $application)
                ->withErrors([
                    'forward' => 'Cannot forward yet. Resolve all your action-required comments first.',
                ]);
        }

        if ($application->status === 'dean_review') {
            $section2 = $application->sections->firstWhere('section_code', '2');
            $section2Complete = (bool) ($section2?->is_complete);

            if (!$section2Complete) {
                return redirect()
                    ->route('reclassification.review.show', $application)
                    ->withErrors([
                        'forward' => 'Section II (Dean Input) must be completed and saved before forwarding to HR.',
                    ]);
            }
        }

        if (!isset($map[$application->status])) {
            return redirect()
                ->route('reclassification.review.queue')
                ->withErrors([
                    'queue' => 'This submission can no longer be forwarded from its current stage.',
                ]);
        }

        $fromStatus = (string) $application->status;
        $fromStep = (string) ($application->current_step ?? '');
        $next = $map[$application->status];
        $updatePayload = [
            'status' => $next['next_status'],
            'current_step' => $next['next_step'],
            'returned_from' => null,
            ...$this->clearFacultyReturnRequestPayload(),
        ];

        // Freeze rank snapshots once VPAA approves to list so they remain stable
        // until President batch approval/finalization.
        if ($fromStatus === 'vpaa_review') {
            $approval = $this->buildApprovalResult($application);
            $updatePayload['current_rank_label_at_approval'] = $approval['current_rank_label'] ?? null;
            $updatePayload['approved_rank_label'] = $approval['approved_rank_label'] ?? null;
        }

        $application->update($updatePayload);
        $application->refresh();

        $this->recordStatusTrail(
            $application,
            $fromStatus,
            $next['next_status'],
            $fromStep,
            $next['next_step'],
            $fromStatus === 'vpaa_review' ? 'approve_to_vpaa_list' : 'forward',
            $fromStatus === 'vpaa_review'
                ? 'Approved to VPAA approved list.'
                : ('Forwarded to ' . strtoupper($next['next_step']) . '.'),
            [],
            $request->user()
        );

        $notifier = app(ReclassificationNotificationService::class);
        if ($next['next_status'] === 'hr_review') {
            $notifier->notifyApplicationForwardedToRole($application, 'hr');
        } elseif ($next['next_status'] === 'vpaa_review') {
            $notifier->notifyApplicationForwardedToRole($application, 'vpaa');
        } elseif ($next['next_status'] === 'vpaa_approved') {
            $notifier->notifyAddedToVpaaApprovedList($application);
        }

        if ($fromStatus === 'vpaa_review') {
            $facultyName = trim((string) ($application->faculty?->name ?? ''));
            if ($facultyName === '') {
                $facultyName = 'Faculty';
            }
            return redirect()
                ->route('reclassification.review.queue')
                ->with('success', "Reclassification of {$facultyName} is approved by VPAA and added to VPAA Approved List.");
        }

        $target = $this->stepLabel($next['next_step']);
        $facultyName = trim((string) ($application->faculty?->name ?? ''));
        if ($facultyName === '') {
            $facultyName = 'Faculty';
        }
        return redirect()
            ->route('reclassification.review.queue')
            ->with('success', "Reclassification of {$facultyName} has been forwarded to {$target}.");
    }
}
