<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Support\ReclassificationEligibility;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationSection;
use App\Models\ReclassificationSectionEntry;
use App\Models\ReclassificationEvidence;
use App\Models\ReclassificationPeriod;
use App\Models\ReclassificationRowComment;
use App\Models\ReclassificationChangeLog;

class ReclassificationFormController extends Controller
{
    /**
     * Get the user's latest application.
     * If none exists, auto-create draft + default sections.
     */
    private function getOrCreateDraft(Request $request): ReclassificationApplication
    {
        $user = $request->user();
        $activePeriod = $this->activePeriod();
        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
        $cycleYear = $this->currentCycleYear($activePeriod);

        // If there is no active period, faculty can still create/edit drafts.
        // These drafts are not tied to an active period yet.
        if (!$activePeriod) {
            $app = ReclassificationApplication::where('faculty_user_id', $user->id)
                ->whereIn('status', ['draft', 'returned_to_faculty'])
                ->whereNull('cycle_year')
                ->when($hasPeriodId, fn ($query) => $query->whereNull('period_id'))
                ->latest('updated_at')
                ->first();

            if (!$app) {
                $payload = [
                    'faculty_user_id' => $user->id,
                    'cycle_year' => null,
                    'status' => 'draft',
                    'current_step' => 'faculty',
                    'returned_from' => null,
                    'submitted_at' => null,
                    'finalized_at' => null,
                ];
                if ($hasPeriodId) {
                    $payload['period_id'] = null;
                }

                $app = ReclassificationApplication::create($payload);

                $sections = [
                    ['section_code' => '1', 'title' => 'Section I'],
                    ['section_code' => '2', 'title' => 'Section II'],
                    ['section_code' => '3', 'title' => 'Section III'],
                    ['section_code' => '4', 'title' => 'Section IV'],
                    ['section_code' => '5', 'title' => 'Section V'],
                ];

                foreach ($sections as $s) {
                    ReclassificationSection::create([
                        'reclassification_application_id' => $app->id,
                        'section_code' => $s['section_code'],
                        'title' => $s['title'],
                        'is_complete' => false,
                        'points_total' => 0,
                    ]);
                }
            }

            return $app->load('sections');
        }

        $sameCycle = function ($query) use ($cycleYear, $activePeriod) {
            $query->where('cycle_year', $cycleYear);
            if (!$activePeriod) {
                $query->orWhereNull('cycle_year');
            }
        };

        // If there is an active review in this cycle, do NOT auto-create a new draft.
        $activeReview = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->where($sameCycle)
            ->whereIn('status', ['dean_review', 'hr_review', 'vpaa_review', 'vpaa_approved', 'president_review'])
            ->latest()
            ->first();
        if ($activeReview) {
            $updates = [];
            if (!$activeReview->cycle_year) {
                $updates['cycle_year'] = $cycleYear;
            }
            if ($hasPeriodId && $activePeriod && !$activeReview->period_id) {
                $updates['period_id'] = $activePeriod->id;
            }
            if (!empty($updates)) {
                $activeReview->update($updates);
            }
            return $activeReview->load('sections');
        }

        // Prioritize returned applications over plain drafts so faculty always lands
        // on the returned paper that must be revised.
        $app = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->where($sameCycle)
            ->where('status', 'returned_to_faculty')
            ->latest('updated_at')
            ->first();

        if (!$app) {
            $app = ReclassificationApplication::where('faculty_user_id', $user->id)
                ->where($sameCycle)
                ->where('status', 'draft')
                ->latest('updated_at')
                ->first();
        }

        // If no same-cycle draft exists yet, re-use an unassigned draft/returned draft
        // created while no period was active (cycle_year/period_id are null).
        if (!$app) {
            $app = ReclassificationApplication::where('faculty_user_id', $user->id)
                ->where('status', 'returned_to_faculty')
                ->whereNull('cycle_year')
                ->when($hasPeriodId, fn ($query) => $query->whereNull('period_id'))
                ->latest('updated_at')
                ->first();
        }

        if (!$app) {
            $app = ReclassificationApplication::where('faculty_user_id', $user->id)
                ->where('status', 'draft')
                ->whereNull('cycle_year')
                ->when($hasPeriodId, fn ($query) => $query->whereNull('period_id'))
                ->latest('updated_at')
                ->first();
        }

        // If this cycle already has a finalized/rejected terminal app, do NOT auto-create a new draft.
        if (!$app) {
            $terminal = ReclassificationApplication::where('faculty_user_id', $user->id)
                ->where($sameCycle)
                ->whereIn('status', ['finalized', 'rejected_final'])
                ->latest()
                ->first();

            if ($terminal) {
                $updates = [];
                if (!$terminal->cycle_year) {
                    $updates['cycle_year'] = $cycleYear;
                }
                if ($hasPeriodId && $activePeriod && !$terminal->period_id) {
                    $updates['period_id'] = $activePeriod->id;
                }
                if (!empty($updates)) {
                    $terminal->update($updates);
                }
                return $terminal->load('sections');
            }
        }

        if (!$app) {
            $payload = [
                'faculty_user_id' => $user->id,
                'cycle_year' => $cycleYear,
                'status' => 'draft',
                'current_step' => 'faculty',
                'returned_from' => null,
                'submitted_at' => null,
                'finalized_at' => null,
            ];
            if ($hasPeriodId) {
                $payload['period_id'] = $activePeriod?->id;
            }

            $app = ReclassificationApplication::create($payload);

            // Create default sections 1..5
            $sections = [
                ['section_code' => '1', 'title' => 'Section I'],
                ['section_code' => '2', 'title' => 'Section II'],
                ['section_code' => '3', 'title' => 'Section III'],
                ['section_code' => '4', 'title' => 'Section IV'],
                ['section_code' => '5', 'title' => 'Section V'],
            ];

            foreach ($sections as $s) {
                ReclassificationSection::create([
                    'reclassification_application_id' => $app->id,
                    'section_code' => $s['section_code'],
                    'title' => $s['title'],
                    'is_complete' => false,
                    'points_total' => 0,
                ]);
            }
        }

        $updates = [];
        if (!$app->cycle_year) {
            $updates['cycle_year'] = $cycleYear;
        }
        if ($hasPeriodId && $activePeriod && !$app->period_id) {
            $updates['period_id'] = $activePeriod->id;
        }
        if (!empty($updates)) {
            $app->update($updates);
        }

        return $app->load('sections');
    }

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

    private function currentCycleYear(?ReclassificationPeriod $period = null): string
    {
        if ($period && !empty($period->cycle_year)) {
            return (string) $period->cycle_year;
        }
        return (string) config('reclassification.cycle_year', '2023-2026');
    }

    private function isCurrentSubmissionForFaculty(ReclassificationApplication $application, ?ReclassificationPeriod $activePeriod): bool
    {
        $appPeriodId = (int) ($application->period_id ?? 0);
        $appCycleYear = trim((string) ($application->cycle_year ?? ''));

        if ($activePeriod) {
            $activePeriodId = (int) $activePeriod->id;
            $activeCycleYear = trim((string) ($activePeriod->cycle_year ?? ''));

            if ($appPeriodId === $activePeriodId) {
                return true;
            }

            return $appPeriodId === 0
                && $activeCycleYear !== ''
                && $appCycleYear === $activeCycleYear;
        }

        return $appPeriodId === 0 && $appCycleYear === '';
    }

    private function submissionWindowInfo(ReclassificationApplication $application): array
    {
        if (!Schema::hasTable('reclassification_periods')) {
            return [
                'submissionWindowOpen' => true,
                'submissionWindowTitle' => null,
                'submissionWindowMessage' => null,
            ];
        }

        $openPeriodQuery = ReclassificationPeriod::query();
        if (Schema::hasColumn('reclassification_periods', 'status')) {
            $openPeriodQuery->where('status', 'active')->where('is_open', true);
        } else {
            $openPeriodQuery->where('is_open', true);
        }
        $openPeriod = $openPeriodQuery
            ->orderByDesc('created_at')
            ->first();

        if (!$openPeriod) {
            return [
                'submissionWindowOpen' => false,
                'submissionWindowTitle' => 'No ongoing reclassification submission',
                'submissionWindowMessage' => 'Submissions are currently closed. You can still save your draft.',
            ];
        }

        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
        $matchesOpenPeriod = true;

        if ($hasPeriodId && !empty($application->period_id)) {
            $matchesOpenPeriod = (int) $application->period_id === (int) $openPeriod->id;
        } else {
            $periodCycle = trim((string) ($openPeriod->cycle_year ?? ''));
            $appCycle = trim((string) ($application->cycle_year ?? ''));
            if ($periodCycle !== '' && $appCycle !== '' && $periodCycle !== $appCycle) {
                $matchesOpenPeriod = false;
            }
        }

        if (!$matchesOpenPeriod) {
            return [
                'submissionWindowOpen' => false,
                'submissionWindowTitle' => 'No ongoing reclassification submission',
                'submissionWindowMessage' => 'This draft belongs to a different cycle than the currently open submission period.',
            ];
        }

        if ($openPeriod->end_at && now()->greaterThan($openPeriod->end_at)) {
            return [
                'submissionWindowOpen' => false,
                'submissionWindowTitle' => 'Reclassification is closed',
                'submissionWindowMessage' => 'Submission deadline has passed. You can still save your draft.',
            ];
        }

        if ($openPeriod->end_at) {
            $daysLeft = now()->startOfDay()->diffInDays($openPeriod->end_at->copy()->startOfDay(), false);
            if ($daysLeft > 1) {
                $message = "{$daysLeft} days left to submit.";
            } elseif ($daysLeft === 1) {
                $message = '1 day left to submit.';
            } elseif ($daysLeft === 0) {
                $message = 'Last day to submit.';
            } else {
                $message = 'Submission period is open.';
            }
        } else {
            $message = 'Submission period is open.';
        }

        return [
            'submissionWindowOpen' => true,
            'submissionWindowTitle' => 'Reclassification is open',
            'submissionWindowMessage' => $message,
        ];
    }

    private function buildInitialSections(ReclassificationApplication $application): array
    {
        $maxByCode = [
            '1' => 140,
            '2' => 120,
            '3' => 70,
            '4' => 40,
            '5' => 30,
        ];

        return $application->sections->mapWithKeys(function ($section) use ($maxByCode) {
            $code = (string) $section->section_code;
            return [
                $code => [
                    'points' => (float) $section->points_total,
                    'max' => $maxByCode[$code] ?? 0,
                ],
            ];
        })->all();
    }

    private function returnOrdinalLabel(int $index): string
    {
        return match ($index) {
            1 => 'First',
            2 => 'Second',
            3 => 'Third',
            4 => 'Fourth',
            5 => 'Fifth',
            6 => 'Sixth',
            7 => 'Seventh',
            8 => 'Eighth',
            9 => 'Ninth',
            10 => 'Tenth',
            default => "{$index}th",
        };
    }

    private function buildCommentSnapshots(ReclassificationApplication $application, $commentThreads): array
    {
        $threads = collect($commentThreads)->values();
        if ($threads->isEmpty()) {
            return [];
        }

        $threadActivityAt = function ($thread) {
            $latest = $thread->created_at ?? null;
            $children = collect($thread->children ?? []);
            foreach ($children as $child) {
                if (!$child?->created_at) {
                    continue;
                }
                if (!$latest || $child->created_at->gt($latest)) {
                    $latest = $child->created_at;
                }
            }

            return $latest;
        };

        if (!Schema::hasTable('reclassification_status_trails')) {
            return [[
                'key' => 'snapshot-current',
                'label' => 'Current comments',
                'subtitle' => null,
                'threads' => $threads,
            ]];
        }

        $trails = $application->statusTrails()
            ->orderBy('created_at')
            ->get();

        $returns = $trails->filter(fn ($trail) => (string) ($trail->action ?? '') === 'return_to_faculty')->values();
        $resubmits = $trails->filter(fn ($trail) => (string) ($trail->action ?? '') === 'resubmit')->values();

        if ($returns->isEmpty()) {
            return [[
                'key' => 'snapshot-current',
                'label' => 'Current comments',
                'subtitle' => null,
                'threads' => $threads,
            ]];
        }

        $snapshots = [];
        foreach ($returns as $index => $returnTrail) {
            $returnedAt = $returnTrail->created_at;
            $previousResubmit = $resubmits
                ->filter(fn ($resubmit) => $resubmit->created_at && $returnedAt && $resubmit->created_at->lt($returnedAt))
                ->last();
            $startAt = optional($previousResubmit)->created_at;

            $threadsForSnapshot = $threads
                ->filter(function ($thread) use ($startAt, $returnedAt, $threadActivityAt) {
                    $activityAt = $threadActivityAt($thread);
                    if (!$activityAt) {
                        return false;
                    }
                    if ($startAt && !$activityAt->gt($startAt)) {
                        return false;
                    }
                    if ($returnedAt && !$activityAt->lte($returnedAt)) {
                        return false;
                    }

                    return true;
                })
                ->sortByDesc(function ($thread) use ($threadActivityAt) {
                    return optional($threadActivityAt($thread))->timestamp ?? 0;
                })
                ->values();

            if ($threadsForSnapshot->isEmpty()) {
                continue;
            }

            $actorRole = strtolower(trim((string) ($returnTrail->actor_role ?: data_get($returnTrail->meta, 'returned_from', 'reviewer'))));
            $actorLabel = match ($actorRole) {
                'dean' => 'Dean',
                'hr' => 'HR',
                'vpaa' => 'VPAA',
                'president' => 'President',
                default => 'Reviewer',
            };
            $ordinal = $this->returnOrdinalLabel($index + 1);

            $snapshots[] = [
                'key' => 'snapshot-' . ($returnTrail->id ?? ($index + 1)),
                'label' => "{$ordinal} Returned - {$actorLabel}",
                'subtitle' => optional($returnedAt)->format('M d, Y g:i A'),
                'threads' => $threadsForSnapshot,
            ];
        }

        if (!empty($snapshots)) {
            return $snapshots;
        }

        return [[
            'key' => 'snapshot-current',
            'label' => 'Current comments',
            'subtitle' => null,
            'threads' => $threads,
        ]];
    }

    private function buildCommentReturnSnapshotResolver(ReclassificationApplication $application): callable
    {
        if (!Schema::hasTable('reclassification_status_trails')) {
            return fn ($createdAt) => [
                'label' => 'Current Review',
                'date_label' => null,
            ];
        }

        $trails = $application->relationLoaded('statusTrails')
            ? collect($application->statusTrails)->sortBy('created_at')->values()
            : $application->statusTrails()->orderBy('created_at')->get();
        $returns = $trails
            ->filter(fn ($trail) => (string) ($trail->action ?? '') === 'return_to_faculty')
            ->values();
        $resubmits = $trails
            ->filter(fn ($trail) => (string) ($trail->action ?? '') === 'resubmit')
            ->values();

        if ($returns->isEmpty()) {
            return fn ($createdAt) => [
                'label' => 'Current Review',
                'date_label' => null,
            ];
        }

        $ranges = $returns->map(function ($returnTrail, $index) use ($resubmits) {
            $returnedAt = $returnTrail->created_at;
            $previousResubmit = $resubmits
                ->filter(fn ($resubmit) => $resubmit->created_at && $returnedAt && $resubmit->created_at->lt($returnedAt))
                ->last();
            $startAt = optional($previousResubmit)->created_at;
            $label = $this->returnOrdinalLabel(((int) $index) + 1) . ' Return';

            return [
                'start_at' => $startAt,
                'end_at' => $returnedAt,
                'label' => $label,
                'date_label' => optional($returnedAt)->format('M d, Y g:i A'),
            ];
        })->values();

        $lastReturnedAt = optional($returns->last())->created_at;

        return function ($createdAt) use ($ranges, $lastReturnedAt) {
            if (!$createdAt) {
                return [
                    'label' => 'Current Review',
                    'date_label' => null,
                ];
            }

            foreach ($ranges as $range) {
                $startAt = $range['start_at'] ?? null;
                $endAt = $range['end_at'] ?? null;
                if ($startAt && !$createdAt->gt($startAt)) {
                    continue;
                }
                if ($endAt && !$createdAt->lte($endAt)) {
                    continue;
                }

                return [
                    'label' => (string) ($range['label'] ?? 'Current Review'),
                    'date_label' => (string) ($range['date_label'] ?? ''),
                ];
            }

            if ($lastReturnedAt && $createdAt->gt($lastReturnedAt)) {
                return [
                    'label' => 'Current Review',
                    'date_label' => null,
                ];
            }

            return [
                'label' => 'Before First Return',
                'date_label' => null,
            ];
        };
    }

    public function show(Request $request)
    {
        $application = $this->getOrCreateDraft($request);
        if (!in_array($application->status, ['draft', 'returned_to_faculty'], true)) {
            return redirect()->route('reclassification.submitted');
        }

        $active = (int) $request->route('number', 1);
        if ($active < 1 || $active > 5) $active = 1;

        $section = $application->sections
            ->firstWhere('section_code', (string) $active);

        $commentReturnSnapshotResolver = $this->buildCommentReturnSnapshotResolver($application);
        $sectionsData = $application->sections->mapWithKeys(function ($sec) use ($commentReturnSnapshotResolver) {
            return [$sec->section_code => $this->buildSectionData($sec, $commentReturnSnapshotResolver)];
        })->all();

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $globalEvidence = $this->buildGlobalEvidence($application);

        $initialSections = $this->buildInitialSections($application);
        $commentThreadsQuery = $application->rowComments()
            ->where('visibility', 'faculty_visible')
            ->with(['author', 'entry.section', 'children.author'])
            ->orderByDesc('created_at');
        if (Schema::hasColumn('reclassification_row_comments', 'parent_id')) {
            $commentThreadsQuery->whereNull('parent_id');
        }
        $commentThreads = $commentThreadsQuery->get()->values();
        $commentSnapshots = $this->buildCommentSnapshots($application, $commentThreads);

        $user = $request->user()->load('facultyProfile');
        $profile = $user->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = $this->resolveRankLabel($profile, $eligibility['currentRank'] ?? null);
        $submissionWindow = $this->submissionWindowInfo($application);
        $submissionWindowOpen = (bool) ($submissionWindow['submissionWindowOpen'] ?? true);
        $submissionWindowTitle = $submissionWindow['submissionWindowTitle'] ?? null;
        $submissionWindowMessage = $submissionWindow['submissionWindowMessage'] ?? null;

        return view('reclassification.show', compact(
            'application',
            'section',
            'sectionsData',
            'eligibility',
            'globalEvidence',
            'initialSections',
            'commentThreads',
            'commentSnapshots',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile',
            'submissionWindowOpen',
            'submissionWindowTitle',
            'submissionWindowMessage'
        ));
    }

    public function section(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);
        if (!in_array($application->status, ['draft', 'returned_to_faculty'], true)) {
            return redirect()->route('reclassification.submitted');
        }

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        $commentReturnSnapshotResolver = $this->buildCommentReturnSnapshotResolver($application);
        $sectionsData = $application->sections->mapWithKeys(function ($sec) use ($commentReturnSnapshotResolver) {
            return [$sec->section_code => $this->buildSectionData($sec, $commentReturnSnapshotResolver)];
        })->all();

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $globalEvidence = $this->buildGlobalEvidence($application);

        $initialSections = $this->buildInitialSections($application);
        $commentThreadsQuery = $application->rowComments()
            ->where('visibility', 'faculty_visible')
            ->with(['author', 'entry.section', 'children.author'])
            ->orderByDesc('created_at');
        if (Schema::hasColumn('reclassification_row_comments', 'parent_id')) {
            $commentThreadsQuery->whereNull('parent_id');
        }
        $commentThreads = $commentThreadsQuery->get()->values();
        $commentSnapshots = $this->buildCommentSnapshots($application, $commentThreads);

        $user = $request->user()->load('facultyProfile');
        $profile = $user->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = $this->resolveRankLabel($profile, $eligibility['currentRank'] ?? null);
        $submissionWindow = $this->submissionWindowInfo($application);
        $submissionWindowOpen = (bool) ($submissionWindow['submissionWindowOpen'] ?? true);
        $submissionWindowTitle = $submissionWindow['submissionWindowTitle'] ?? null;
        $submissionWindowMessage = $submissionWindow['submissionWindowMessage'] ?? null;

        return view('reclassification.show', compact(
            'application',
            'section',
            'sectionsData',
            'eligibility',
            'globalEvidence',
            'initialSections',
            'commentThreads',
            'commentSnapshots',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile',
            'submissionWindowOpen',
            'submissionWindowTitle',
            'submissionWindowMessage'
        ));
    }

    public function review(Request $request)
    {
        return redirect()->route('reclassification.show', ['tab' => 'review']);
    }

    public function reviewSave(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $application->touch();

        return back()->with('success', 'Draft saved.');
    }

    public function resetSection(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        DB::transaction(function () use ($section) {
            $section->entries()->delete();
            $this->detachSectionEvidence($section);
            $section->update([
                'points_total' => 0,
                'is_complete' => false,
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Section reset.',
            ]);
        }

        return redirect()
            ->route('reclassification.section', $number)
            ->with('success', 'Section reset.');
    }

    public function resetApplication(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        DB::transaction(function () use ($application) {
            foreach ($application->sections as $section) {
                $section->entries()->delete();
                $this->detachSectionEvidence($section);
                $section->update([
                    'points_total' => 0,
                    'is_complete' => false,
                ]);
            }
            $application->touch();
        });

        $active = (int) $request->input('active', 1);
        if ($active < 1 || $active > 5) $active = 1;

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Reclassification reset.',
            ]);
        }

        return redirect()
            ->route('reclassification.section', $active)
            ->with('success', 'Reclassification reset.');
    }

    public function destroyDraft(Request $request, ReclassificationApplication $application)
    {
        $user = $request->user();
        abort_unless((int) $application->faculty_user_id === (int) $user->id, 403);
        abort_unless((string) $application->status === 'draft', 422);

        $activePeriod = $this->activePeriod();
        if ($this->isCurrentSubmissionForFaculty($application, $activePeriod)) {
            return back()->withErrors([
                'draft_delete' => 'Current draft cannot be deleted from history.',
            ]);
        }

        DB::transaction(function () use ($application) {
            $evidences = ReclassificationEvidence::query()
                ->where('reclassification_application_id', $application->id)
                ->get();

            foreach ($evidences as $evidence) {
                if (!empty($evidence->path)) {
                    Storage::disk((string) ($evidence->disk ?: 'public'))->delete((string) $evidence->path);
                }
            }

            $application->delete();
        });

        return back()->with('success', 'Old draft deleted.');
    }

    public function submitted(Request $request)
    {
        return redirect()->route('faculty.dashboard');
    }

    public function submittedSummary(Request $request)
    {
        $user = $request->user();

        $application = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->whereNotIn('status', ['draft', 'returned_to_faculty'])
            ->latest()
            ->first();

        if (!$application) {
            return redirect()->route('reclassification.show');
        }

        $application->load([
            'sections.entries',
            'sections.evidences',
        ]);

        $sectionsByCode = $application->sections->keyBy('section_code');
        $section2Review = $this->buildSectionTwoReview($sectionsByCode->get('2'));

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $user = $request->user()->load('facultyProfile');
        $profile = $user->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = trim((string) ($application->current_rank_label_at_approval ?? ''));
        if ($currentRankLabel === '') {
            $currentRankLabel = $this->resolveRankLabel($profile);
        }

        return view('reclassification.submitted-summary', compact(
            'application',
            'section2Review',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile',
            'eligibility'
        ));
    }

    public function submittedSummaryShow(Request $request, ReclassificationApplication $application)
    {
        $user = $request->user();

        abort_unless($application->faculty_user_id === $user->id, 403);
        if (
            in_array((string) $application->status, ['draft', 'returned_to_faculty'], true)
            && $this->isCurrentSubmissionForFaculty($application, $this->activePeriod())
        ) {
            return redirect()->route('reclassification.show');
        }

        $application->load([
            'sections.entries',
            'sections.evidences',
        ]);

        $sectionsByCode = $application->sections->keyBy('section_code');
        $section2Review = $this->buildSectionTwoReview($sectionsByCode->get('2'));

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $profile = $application->faculty?->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = trim((string) ($application->current_rank_label_at_approval ?? ''));
        if ($currentRankLabel === '') {
            $currentRankLabel = $this->resolveRankLabel($profile, $profile?->teaching_rank);
        }

        return view('reclassification.submitted-summary', compact(
            'application',
            'section2Review',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile',
            'eligibility'
        ));
    }

    public function draftSummaryShow(Request $request, ReclassificationApplication $application)
    {
        $user = $request->user();

        abort_unless((int) $application->faculty_user_id === (int) $user->id, 403);
        abort_unless((string) $application->status === 'draft', 404);

        $activePeriod = $this->activePeriod();
        if ($this->isCurrentSubmissionForFaculty($application, $activePeriod)) {
            return redirect()->route('reclassification.show');
        }

        $application->load([
            'sections.entries',
            'sections.evidences',
        ]);

        $sectionsByCode = $application->sections->keyBy('section_code');
        $section2Review = $this->buildSectionTwoReview($sectionsByCode->get('2'));

        $eligibility = ReclassificationEligibility::evaluate($application, $request->user());

        $profile = $application->faculty?->facultyProfile;
        $appointmentDate = $profile?->original_appointment_date
            ? $profile->original_appointment_date->format('M d, Y')
            : null;
        $yearsService = $profile?->original_appointment_date
            ? $profile->original_appointment_date->diffInYears(now())
            : null;
        $currentRankLabel = trim((string) ($application->current_rank_label_at_approval ?? ''));
        if ($currentRankLabel === '') {
            $currentRankLabel = $this->resolveRankLabel($profile, $profile?->teaching_rank);
        }

        $summaryMode = 'draft_history';

        return view('reclassification.submitted-summary', compact(
            'application',
            'section2Review',
            'appointmentDate',
            'yearsService',
            'currentRankLabel',
            'profile',
            'eligibility',
            'summaryMode',
        ));
    }

    public function detachEvidence(Request $request, ReclassificationEvidence $evidence)
    {
        $user = $request->user();

        $application = ReclassificationApplication::where('id', $evidence->reclassification_application_id)
            ->where('faculty_user_id', $user->id)
            ->firstOrFail();

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        DB::table('reclassification_evidence_links')
            ->where('reclassification_evidence_id', $evidence->id)
            ->delete();

        $evidence->update([
            'reclassification_section_entry_id' => null,
            'reclassification_section_id' => null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function uploadEvidence(Request $request)
    {
        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $request->validate([
            // Accept either a single file or an array of files.
            'evidence_files' => ['required'],
            'evidence_files.*' => $this->evidenceFileRules(),
        ]);

        $this->storeGlobalEvidenceFiles($request, $application, $request->user()->id, 'evidence_files');

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'evidence' => $this->buildGlobalEvidence($application),
            ]);
        }

        return back()->with('success', 'Evidence uploaded.');
    }

    public function deleteEvidence(Request $request, ReclassificationEvidence $evidence)
    {
        $user = $request->user();

        $application = ReclassificationApplication::where('id', $evidence->reclassification_application_id)
            ->where('faculty_user_id', $user->id)
            ->firstOrFail();

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $hasLinks = DB::table('reclassification_evidence_links')
            ->where('reclassification_evidence_id', $evidence->id)
            ->exists();

        if ($evidence->reclassification_section_entry_id || $hasLinks) {
            return response()->json(['message' => 'Evidence is attached. Detach first.'], 422);
        }

        if ($evidence->path) {
            Storage::disk($evidence->disk ?: 'public')->delete($evidence->path);
        }

        $evidence->delete();

        return response()->json(['ok' => true]);
    }

    public function restoreEntry(Request $request, ReclassificationApplication $application, ReclassificationSectionEntry $entry)
    {
        abort_unless((int) $request->user()->id === (int) $application->faculty_user_id, 403);
        abort_unless(in_array((string) $application->status, ['draft', 'returned_to_faculty'], true), 422);

        $entry->loadMissing('section');
        abort_unless((int) ($entry->section?->reclassification_application_id ?? 0) === (int) $application->id, 404);

        $data = is_array($entry->data) ? $entry->data : [];
        if (!$this->isRowRemoved($data)) {
            return $this->respondRestoration($request, 'Entry is already active.');
        }

        $restoredPoints = (float) ($data['removed_points_backup'] ?? 0);
        $data['is_removed'] = false;
        $data['points'] = $restoredPoints;
        unset($data['removed_points_backup']);

        $entry->update([
            'points' => $restoredPoints,
            'data' => $data,
        ]);

        $this->recomputeSectionPointsTotal($entry->section);

        $commentQuery = ReclassificationRowComment::query()
            ->where('reclassification_application_id', $application->id)
            ->where('reclassification_section_entry_id', $entry->id)
            ->where('visibility', 'faculty_visible')
            ->where('status', 'addressed');
        if (Schema::hasColumn('reclassification_row_comments', 'action_type')) {
            $commentQuery->where('action_type', 'requires_action');
        }
        if (Schema::hasColumn('reclassification_row_comments', 'parent_id')) {
            $commentQuery->whereNull('parent_id');
        }
        $commentQuery->update([
            'status' => 'open',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return $this->respondRestoration($request, 'Entry restored.');
    }

    public function saveSection(Request $request, int $number)
    {
        abort_unless($number >= 1 && $number <= 5, 404);

        $application = $this->getOrCreateDraft($request);

        abort_unless(in_array($application->status, ['draft', 'returned_to_faculty'], true), 403);

        $section = $application->sections
            ->firstWhere('section_code', (string) $number);

        abort_unless($section, 404);

        $shouldLogReturnedChanges = (string) $application->status === 'returned_to_faculty';
        $beforeSnapshot = $shouldLogReturnedChanges
            ? $this->buildSectionChangeSnapshot($section->id)
            : null;

        DB::transaction(function () use ($request, $application, $section, $number) {
            if ($number === 1) {
                $this->saveSectionOne($request, $application, $section);
                return;
            }
            if ($number === 3) {
                $this->saveSectionThree($request, $application, $section);
                return;
            }
            if ($number === 4) {
                $this->saveSectionFour($request, $application, $section);
                return;
            }
            if ($number === 5) {
                $this->saveSectionFive($request, $application, $section);
                return;
            }

            throw ValidationException::withMessages([
                'section' => 'Saving for this section is not implemented.',
            ]);
        });

        if ($shouldLogReturnedChanges) {
            $afterSnapshot = $this->buildSectionChangeSnapshot($section->id);
            $this->recordSectionChangeLogs(
                $application,
                $beforeSnapshot,
                $afterSnapshot,
                (int) $request->user()->id
            );
        }

        return redirect()
            ->route('reclassification.section', $number)
            ->with('success', 'Section saved.');
    }

    private function buildSectionChangeSnapshot(int $sectionId): array
    {
        $section = ReclassificationSection::with(['entries.evidences'])->find($sectionId);
        if (!$section) {
            return [
                'section_id' => null,
                'section_code' => null,
                'section_points' => 0.0,
                'entries' => [],
            ];
        }

        $entries = [];
        foreach ($section->entries as $entry) {
            $data = is_array($entry->data) ? $entry->data : [];
            $entries[(string) $entry->id] = [
                'entry_id' => (int) $entry->id,
                'criterion_key' => (string) $entry->criterion_key,
                'points' => (float) $entry->points,
                'is_removed' => $this->isRowRemoved($data),
                'data' => $this->normalizeChangePayload($data),
                'evidence' => $entry->evidences
                    ->sortBy('id')
                    ->values()
                    ->map(function ($evidence) {
                        $fallbackName = trim((string) basename((string) ($evidence->path ?? '')));
                        return [
                            'id' => (int) $evidence->id,
                            'name' => (string) ($evidence->original_name ?: $fallbackName),
                        ];
                    })
                    ->all(),
            ];
        }

        ksort($entries);

        return [
            'section_id' => (int) $section->id,
            'section_code' => (string) $section->section_code,
            'section_points' => (float) $section->points_total,
            'entries' => $entries,
        ];
    }

    private function normalizeChangePayload($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->normalizeChangePayload($item), $value);
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if ((string) $key === 'comments') {
                continue;
            }
            $normalized[(string) $key] = $this->normalizeChangePayload($item);
        }
        ksort($normalized);

        return $normalized;
    }

    private function recordSectionChangeLogs(
        ReclassificationApplication $application,
        array $before,
        array $after,
        int $actorUserId
    ): void {
        $sectionId = $after['section_id'] ?? $before['section_id'] ?? null;
        $sectionCode = (string) ($after['section_code'] ?? $before['section_code'] ?? '');
        $beforeEntries = is_array($before['entries'] ?? null) ? $before['entries'] : [];
        $afterEntries = is_array($after['entries'] ?? null) ? $after['entries'] : [];
        $remainingBefore = $beforeEntries;
        $remainingAfter = $afterEntries;

        $fingerprint = function (array $entry): string {
            $criterion = (string) ($entry['criterion_key'] ?? '');
            $isRemoved = (bool) ($entry['is_removed'] ?? false);
            $data = $this->sanitizeChangeDataForFingerprint($entry['data'] ?? []);
            $evidence = collect($entry['evidence'] ?? [])
                ->map(fn ($item) => trim((string) ($item['name'] ?? '')))
                ->filter()
                ->values()
                ->sort()
                ->values()
                ->all();

            return json_encode([
                'criterion_key' => $criterion,
                'is_removed' => $isRemoved,
                'data' => $data,
                'evidence' => $evidence,
            ]);
        };

        $createLog = function (?array $beforeEntry, ?array $afterEntry, string $changeType, string $summary) use (
            $application,
            $sectionId,
            $sectionCode,
            $actorUserId
        ) {
            ReclassificationChangeLog::create([
                'reclassification_application_id' => $application->id,
                'reclassification_section_id' => $sectionId,
                'reclassification_section_entry_id' => (int) ($afterEntry['entry_id'] ?? $beforeEntry['entry_id'] ?? 0) ?: null,
                'section_code' => $sectionCode,
                'criterion_key' => (string) ($afterEntry['criterion_key'] ?? $beforeEntry['criterion_key'] ?? ''),
                'change_type' => $changeType,
                'summary' => $summary,
                'before_data' => $beforeEntry,
                'after_data' => $afterEntry,
                'actor_user_id' => $actorUserId,
            ]);
        };

        $sharedIds = array_intersect(array_keys($beforeEntries), array_keys($afterEntries));
        foreach ($sharedIds as $entryId) {
            $beforeEntry = $beforeEntries[$entryId];
            $afterEntry = $afterEntries[$entryId];

            if ($fingerprint($beforeEntry) === $fingerprint($afterEntry)) {
                unset($remainingBefore[$entryId], $remainingAfter[$entryId]);
                continue;
            }

            $criterion = strtoupper((string) ($afterEntry['criterion_key'] ?? '?'));
            $beforeRemoved = (bool) ($beforeEntry['is_removed'] ?? false);
            $afterRemoved = (bool) ($afterEntry['is_removed'] ?? false);

            $changeType = 'update';
            $summary = "Updated entry for {$criterion}.";
            if (!$beforeRemoved && $afterRemoved) {
                $changeType = 'remove';
                $summary = "Marked entry as removed for {$criterion}.";
            } elseif ($beforeRemoved && !$afterRemoved) {
                $changeType = 'restore';
                $summary = "Restored entry for {$criterion}.";
            } elseif (($beforeEntry['evidence'] ?? []) !== ($afterEntry['evidence'] ?? [])) {
                $summary = "Updated entry and evidence for {$criterion}.";
            }

            $createLog($beforeEntry, $afterEntry, $changeType, $summary);
            unset($remainingBefore[$entryId], $remainingAfter[$entryId]);
        }

        // Suppress technical row-ID churn: if an old row and a new row have the same
        // criterion and exact same payload/evidence, treat it as no actual change.
        foreach ($remainingAfter as $afterId => $afterEntry) {
            $criterionKey = (string) ($afterEntry['criterion_key'] ?? '');
            $afterSignature = $fingerprint($afterEntry);
            $matchedBeforeId = null;

            foreach ($remainingBefore as $beforeId => $beforeEntry) {
                if ((string) ($beforeEntry['criterion_key'] ?? '') !== $criterionKey) {
                    continue;
                }
                if ($fingerprint($beforeEntry) === $afterSignature) {
                    $matchedBeforeId = (string) $beforeId;
                    break;
                }
            }

            if ($matchedBeforeId !== null) {
                unset($remainingBefore[$matchedBeforeId], $remainingAfter[$afterId]);
            }
        }

        // Anything still remaining is a real add/remove. Avoid criterion-only pairing
        // to prevent false "update" logs and missing remove records.
        foreach ($remainingAfter as $afterId => $afterEntry) {
            $criterion = strtoupper((string) ($afterEntry['criterion_key'] ?? '?'));
            $createLog(null, $afterEntry, 'create', "Added entry for {$criterion}.");
            unset($remainingAfter[$afterId]);
        }

        foreach ($remainingBefore as $entryId => $beforeEntry) {
            $criterion = strtoupper((string) ($beforeEntry['criterion_key'] ?? '?'));
            $missingEntryId = (int) ($beforeEntry['entry_id'] ?? 0);
            $entryExists = $missingEntryId > 0
                ? ReclassificationSectionEntry::query()->whereKey($missingEntryId)->exists()
                : false;
            $beforePayload = $beforeEntry;
            if (!$entryExists) {
                $beforePayload['entry_id'] = null;
            }
            $createLog($beforePayload, null, 'remove', "Deleted entry for {$criterion}.");
        }

        $beforePoints = (float) ($before['section_points'] ?? 0);
        $afterPoints = (float) ($after['section_points'] ?? 0);
        if (abs($beforePoints - $afterPoints) > 0.0001) {
            ReclassificationChangeLog::create([
                'reclassification_application_id' => $application->id,
                'reclassification_section_id' => $sectionId,
                'reclassification_section_entry_id' => null,
                'section_code' => $sectionCode,
                'criterion_key' => null,
                'change_type' => 'section_total',
                'summary' => sprintf(
                    'Section %s total points changed: %.2f -> %.2f.',
                    $sectionCode ?: '-',
                    $beforePoints,
                    $afterPoints
                ),
                'before_data' => ['section_points' => $beforePoints],
                'after_data' => ['section_points' => $afterPoints],
                'actor_user_id' => $actorUserId,
            ]);
        }
    }

    private function sanitizeChangeDataForFingerprint($value)
    {
        if (!is_array($value)) {
            if (is_float($value) || is_int($value)) {
                return round((float) $value, 4);
            }
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->sanitizeChangeDataForFingerprint($item), $value);
        }

        $ignoreKeys = ['id', 'comments', 'counted', 'points', 'evidence', 'is_removed'];
        $out = [];
        foreach ($value as $key => $item) {
            if (in_array((string) $key, $ignoreKeys, true)) {
                continue;
            }
            $out[(string) $key] = $this->sanitizeChangeDataForFingerprint($item);
        }
        ksort($out);

        return $out;
    }

    private function buildSectionData(ReclassificationSection $section, ?callable $commentReturnSnapshotResolver = null): array
    {
        $section->loadMissing(['entries.evidences', 'entries.rowComments.author', 'evidences']);

        $code = $section->section_code;
        $evidenceByEntry = $section->evidences->groupBy('reclassification_section_entry_id');
        $resolveEvidence = function ($entryId) use ($evidenceByEntry, $section) {
            if (!$entryId) return [];
            $values = [];

            $entry = $section->entries->firstWhere('id', $entryId);
            if ($entry && $entry->relationLoaded('evidences')) {
                foreach ($entry->evidences as $ev) {
                    $values[] = 'e:' . $ev->id;
                }
            }

            $items = $evidenceByEntry->get($entryId);
            if ($items && $items->isNotEmpty()) {
                foreach ($items as $ev) {
                    $values[] = 'e:' . $ev->id;
                }
            }

            return array_values(array_unique($values));
        };
        $resolveComments = function ($entry) use ($commentReturnSnapshotResolver) {
            if (!$entry || !$entry->relationLoaded('rowComments')) return [];
            return $entry->rowComments
                ->where('visibility', 'faculty_visible')
                ->sortByDesc('created_at')
                ->map(function ($comment) use ($commentReturnSnapshotResolver) {
                    $snapshotMeta = is_callable($commentReturnSnapshotResolver)
                        ? $commentReturnSnapshotResolver($comment->created_at)
                        : ['label' => 'Current Review', 'date_label' => null];
                    return [
                        'id' => $comment->id,
                        'author_id' => $comment->user_id,
                        'author_role' => strtolower((string) ($comment->author?->role ?? '')),
                        'body' => $comment->body,
                        'author' => $comment->author?->name ?? 'Reviewer',
                        'parent_id' => $comment->parent_id,
                        'action_type' => $comment->action_type ?? 'requires_action',
                        'status' => $comment->status ?? 'open',
                        'return_label' => (string) ($snapshotMeta['label'] ?? 'Current Review'),
                        'return_date_label' => (string) ($snapshotMeta['date_label'] ?? ''),
                        'created_at' => optional($comment->created_at)->toDateTimeString(),
                        'created_at_label' => optional($comment->created_at)->format('M d, Y g:i A'),
                        'reply_url' => is_null($comment->parent_id)
                            ? route('reclassification.row-comments.reply', $comment)
                            : null,
                        'address_url' => is_null($comment->parent_id)
                            ? route('reclassification.row-comments.address', $comment)
                            : null,
                        'update_reply_url' => !is_null($comment->parent_id)
                            ? route('reclassification.row-comments.reply.update', $comment)
                            : null,
                    ];
                })
                ->values()
                ->all();
        };

        if ($code === '1') {
            $data = [
                'a1' => ['id' => null, 'honors' => null, 'evidence' => [], 'comments' => []],
                'a2' => [],
                'a3' => [],
                'a4' => [],
                'a5' => [],
                'a6' => [],
                'a7' => [],
                'a8' => [],
                'a9' => [],
                'b' => [],
                'c' => [],
                'b_prev' => '',
                'b_prev_id' => null,
                'b_prev_comments' => [],
                'c_prev' => '',
                'c_prev_id' => null,
                'c_prev_comments' => [],
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;

                if ($entry->criterion_key === 'a1') {
                    $data['a1']['id'] = $entry->id;
                    $data['a1']['honors'] = $row['honors'] ?? null;
                    $data['a1']['evidence'] = $resolveEvidence($entry->id);
                    $data['a1']['comments'] = $resolveComments($entry);
                    continue;
                }

                if ($entry->criterion_key === 'b_prev') {
                    $data['b_prev_id'] = $entry->id;
                    $data['b_prev'] = $row['value'] ?? $row['points'] ?? '';
                    $data['b_prev_comments'] = $resolveComments($entry);
                    continue;
                }

                if ($entry->criterion_key === 'c_prev') {
                    $data['c_prev_id'] = $entry->id;
                    $data['c_prev'] = $row['value'] ?? $row['points'] ?? '';
                    $data['c_prev_comments'] = $resolveComments($entry);
                    continue;
                }

                if (array_key_exists($entry->criterion_key, $data)) {
                    $row['evidence'] = $resolveEvidence($entry->id);
                    $row['comments'] = $resolveComments($entry);
                    $data[$entry->criterion_key][] = $row;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        if ($code === '2') {
            $data = [
                'ratings' => [
                    'dean' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
                    'chair' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
                    'student' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
                ],
                'previous_points' => 0,
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];

                if ($entry->criterion_key === 'ratings' && isset($row['ratings']) && is_array($row['ratings'])) {
                    $data['ratings'] = array_replace_recursive($data['ratings'], $row['ratings']);
                    continue;
                }

                if ($entry->criterion_key === 'previous_points') {
                    $data['previous_points'] = (float) ($row['value'] ?? $row['points'] ?? 0);
                }
            }

            return $data;
        }

        if ($code === '3') {
            $data = [
                'c1' => [],
                'c2' => [],
                'c3' => [],
                'c4' => [],
                'c5' => [],
                'c6' => [],
                'c7' => [],
                'c8' => [],
                'c9' => [],
                'previous_points' => '',
                'previous_points_id' => null,
                'previous_points_comments' => [],
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;

                if ($entry->criterion_key === 'previous_points') {
                    $data['previous_points_id'] = $entry->id;
                    $data['previous_points'] = $row['value'] ?? $row['points'] ?? '';
                    $data['previous_points_comments'] = $resolveComments($entry);
                    continue;
                }

                if (array_key_exists($entry->criterion_key, $data)) {
                    $row['evidence'] = $resolveEvidence($entry->id);
                    $row['comments'] = $resolveComments($entry);
                    $data[$entry->criterion_key][] = $row;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        if ($code === '4') {
            $data = [
                'a1_years' => 0,
                'a2_years' => 0,
                'b_years' => 0,
                'a1_id' => null,
                'a2_id' => null,
                'b_id' => null,
                'a1_evidence' => [],
                'a2_evidence' => [],
                'b_evidence' => [],
                'a1_comments' => [],
                'a2_comments' => [],
                'b_comments' => [],
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;
                if ($entry->criterion_key === 'a1') {
                    $data['a1_id'] = $entry->id;
                    $data['a1_years'] = $row['years'] ?? 0;
                    $data['a1_evidence'] = $resolveEvidence($entry->id);
                    $data['a1_comments'] = $resolveComments($entry);
                    continue;
                }
                if ($entry->criterion_key === 'a2') {
                    $data['a2_id'] = $entry->id;
                    $data['a2_years'] = $row['years'] ?? 0;
                    $data['a2_evidence'] = $resolveEvidence($entry->id);
                    $data['a2_comments'] = $resolveComments($entry);
                    continue;
                }
                if ($entry->criterion_key === 'b') {
                    $data['b_id'] = $entry->id;
                    $data['b_years'] = $row['years'] ?? 0;
                    $data['b_evidence'] = $resolveEvidence($entry->id);
                    $data['b_comments'] = $resolveComments($entry);
                    continue;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        if ($code === '5') {
            $data = [
                'a' => [],
                'b' => [],
                'c1' => [],
                'c2' => [],
                'c3' => [],
                'd' => [],
                'b_prev' => '',
                'b_prev_id' => null,
                'b_prev_comments' => [],
                'c_prev' => '',
                'c_prev_id' => null,
                'c_prev_comments' => [],
                'd_prev' => '',
                'd_prev_id' => null,
                'd_prev_comments' => [],
                'previous_points' => '',
                'previous_points_id' => null,
                'previous_points_comments' => [],
                'existingEvidence' => [],
            ];

            foreach ($section->entries as $entry) {
                $row = is_array($entry->data) ? $entry->data : [];
                $row['id'] = $entry->id;

                if ($entry->criterion_key === 'b_prev') {
                    $data['b_prev_id'] = $entry->id;
                    $data['b_prev'] = $row['value'] ?? $row['points'] ?? '';
                    $data['b_prev_comments'] = $resolveComments($entry);
                    continue;
                }

                if ($entry->criterion_key === 'c_prev') {
                    $data['c_prev_id'] = $entry->id;
                    $data['c_prev'] = $row['value'] ?? $row['points'] ?? '';
                    $data['c_prev_comments'] = $resolveComments($entry);
                    continue;
                }

                if ($entry->criterion_key === 'd_prev') {
                    $data['d_prev_id'] = $entry->id;
                    $data['d_prev'] = $row['value'] ?? $row['points'] ?? '';
                    $data['d_prev_comments'] = $resolveComments($entry);
                    continue;
                }

                if ($entry->criterion_key === 'previous_points') {
                    $data['previous_points_id'] = $entry->id;
                    $data['previous_points'] = $row['value'] ?? $row['points'] ?? '';
                    $data['previous_points_comments'] = $resolveComments($entry);
                    continue;
                }

                if (array_key_exists($entry->criterion_key, $data)) {
                    $row['evidence'] = $resolveEvidence($entry->id);
                    $row['comments'] = $resolveComments($entry);
                    $data[$entry->criterion_key][] = $row;
                }
            }

            foreach ($section->evidences as $evidence) {
                $data['existingEvidence'][] = [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'status' => $evidence->status ?? 'pending',
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                ];
            }

            return $data;
        }

        return [];
    }

    private function resolveRankLabel($profile, ?string $fallback = null): string
    {
        if ($profile && isset($profile->rank_level_id) && $profile->rank_level_id && Schema::hasTable('rank_levels')) {
            $title = DB::table('rank_levels')
                ->where('id', $profile->rank_level_id)
                ->value('title');
            if ($title) return $title;
        }

        if ($profile && $profile->teaching_rank) return $profile->teaching_rank;
        if ($fallback) return $fallback;
        return 'Instructor';
    }

    private function buildSectionTwoReview(?ReclassificationSection $section): array
    {
        if (!$section) return [];
        $section->loadMissing('entries');

        $ratings = [
            'dean' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
            'chair' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
            'student' => ['i1' => null, 'i2' => null, 'i3' => null, 'i4' => null],
        ];
        $previous = 0;

        foreach ($section->entries as $entry) {
            $data = is_array($entry->data) ? $entry->data : [];
            if ($entry->criterion_key === 'ratings' && isset($data['ratings'])) {
                $ratings = array_replace_recursive($ratings, $data['ratings']);
            }
            if ($entry->criterion_key === 'previous_points') {
                $previous = (float) ($data['value'] ?? $data['points'] ?? 0);
            }
        }

        $deanPts = $this->sumRaterPoints($ratings['dean'] ?? []);
        $chairPts = $this->sumRaterPoints($ratings['chair'] ?? []);
        $studentPts = $this->sumRaterPoints($ratings['student'] ?? []);
        $weighted = ($deanPts * 0.4) + ($chairPts * 0.3) + ($studentPts * 0.3);
        $total = $weighted + ($previous / 3);
        if ($total > 120) $total = 120;

        return [
            'ratings' => $ratings,
            'points' => [
                'dean' => $deanPts,
                'chair' => $chairPts,
                'student' => $studentPts,
                'weighted' => $weighted,
                'total' => $total,
                'previous' => $previous,
            ],
        ];
    }

    private function sumRaterPoints(array $ratings): float
    {
        return $this->pointsForItem(1, $ratings)
            + $this->pointsForItem(2, $ratings)
            + $this->pointsForItem(3, $ratings)
            + $this->pointsForItem(4, $ratings);
    }

    private function pointsForItem(int $itemNo, array $ratings): float
    {
        $key = 'i' . $itemNo;
        $rating = $this->normalizeRating($ratings[$key] ?? null);
        if ($rating === null) return 0;

        if ($itemNo === 1) return $this->pointsFromRatingItem1($rating);
        if ($itemNo === 2) return $this->pointsFromRatingItem2($rating);
        return $this->pointsFromRatingItem34($rating);
    }

    private function normalizeRating($value): ?float
    {
        if ($value === null || $value === '') return null;
        $num = (float) $value;
        return $num > 0 ? $num : null;
    }

    private function pointsFromRatingItem1(float $r): float
    {
        if ($r >= 3.72) return 40;
        if ($r >= 3.42) return 36;
        if ($r >= 3.12) return 32;
        if ($r >= 2.82) return 28;
        if ($r >= 2.52) return 24;
        if ($r >= 2.22) return 20;
        if ($r >= 1.92) return 16;
        if ($r >= 1.62) return 12;
        if ($r >= 1.31) return 8;
        return 4;
    }

    private function pointsFromRatingItem2(float $r): float
    {
        if ($r >= 3.72) return 30;
        if ($r >= 3.42) return 27;
        if ($r >= 3.12) return 24;
        if ($r >= 2.82) return 21;
        if ($r >= 2.52) return 18;
        if ($r >= 2.22) return 15;
        if ($r >= 1.92) return 12;
        if ($r >= 1.62) return 9;
        if ($r >= 1.31) return 6;
        return 3;
    }

    private function pointsFromRatingItem34(float $r): float
    {
        if ($r >= 3.72) return 25;
        if ($r >= 3.42) return 22.5;
        if ($r >= 3.12) return 20;
        if ($r >= 2.82) return 17.5;
        if ($r >= 2.52) return 15;
        if ($r >= 2.22) return 12.5;
        if ($r >= 1.92) return 10;
        if ($r >= 1.62) return 7.5;
        if ($r >= 1.31) return 5;
        return 2.5;
    }

    private function buildGlobalEvidence(ReclassificationApplication $application): array
    {
        $application->loadMissing(['sections']);
        $sectionMap = $application->sections->keyBy('id');
        $evidenceIds = ReclassificationEvidence::where('reclassification_application_id', $application->id)
            ->pluck('id')
            ->all();

        $linkStats = [];
        if (!empty($evidenceIds)) {
            $linkStats = DB::table('reclassification_evidence_links')
                ->select(
                    'reclassification_evidence_id',
                    DB::raw('count(*) as entry_count'),
                    DB::raw('min(reclassification_section_entry_id) as entry_id'),
                    DB::raw('min(reclassification_section_id) as section_id')
                )
                ->whereIn('reclassification_evidence_id', $evidenceIds)
                ->groupBy('reclassification_evidence_id')
                ->get()
                ->keyBy('reclassification_evidence_id')
                ->all();
        }

        return ReclassificationEvidence::where('reclassification_application_id', $application->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($evidence) use ($sectionMap, $linkStats) {
                $stats = $linkStats[$evidence->id] ?? null;
                $linkedEntryId = $stats?->entry_id ?? $evidence->reclassification_section_entry_id;
                $linkedSectionId = $stats?->section_id ?? $evidence->reclassification_section_id;
                $section = $sectionMap->get($linkedSectionId);
                $entryCount = $stats?->entry_count ?? ($evidence->reclassification_section_entry_id ? 1 : 0);
                return [
                    'id' => $evidence->id,
                    'name' => $evidence->original_name ?: $evidence->path,
                    'url' => $evidence->path
                        ? Storage::disk($evidence->disk ?: 'public')->url($evidence->path)
                        : null,
                    'mime_type' => $evidence->mime_type,
                    'size_bytes' => $evidence->size_bytes,
                    'uploaded_at' => optional($evidence->created_at)->toDateTimeString(),
                    'section_code' => $section?->section_code,
                    'section_title' => $section?->title,
                    'entry_id' => $linkedEntryId,
                    'entry_count' => $entryCount,
                ];
            })
            ->values()
            ->all();
    }

    private function saveSectionOne(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section1' => ['array'],
            'section1.evidence_files.*' => $this->evidenceFileRules(),
        ]);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 1, $request->user()->id, 'section1.evidence_files');

        $input = $request->input('section1', []);
        $action = $request->input('action', 'draft');

        $aBase = 0;
        $a8Sum = 0;
        $a9Sum = 0;
        $bSum = 0;
        $cSum = 0;

        $a1Honors = data_get($input, 'a1.honors');
        $a1Evidence = data_get($input, 'a1.evidence', []);

        $touchedIds = [];

        if ($a1Honors && $a1Honors !== 'none') {
            if ($action === 'submit') {
                $this->ensureEvidence([['evidence' => $a1Evidence]], 'section1.a1', $uploaded, $section, $application);
            }

            $points = $this->pointsA1($a1Honors);
            $aBase += $points;
            $a1Id = (int) data_get($input, 'a1.id', 0);
            $entryId = $this->createEntry($section, $application, 'a1', [
                'id' => $a1Id,
                'honors' => $a1Honors,
                'evidence' => $a1Evidence,
            ], $points, $a1Evidence, $uploaded);
            $touchedIds[] = $entryId;
        }

        $rowsA2 = $this->normalizeRows($input['a2'] ?? []);
        $rowsA3 = $this->normalizeRows($input['a3'] ?? []);
        $rowsA4 = $this->normalizeRows($input['a4'] ?? []);
        $rowsA5 = $this->normalizeRows($input['a5'] ?? []);
        $rowsA6 = $this->normalizeRows($input['a6'] ?? []);
        $rowsA7 = $this->normalizeRows($input['a7'] ?? []);
        $rowsA8 = $this->normalizeRows($input['a8'] ?? []);
        $rowsA9 = $this->normalizeRows($input['a9'] ?? []);
        $rowsB = $this->normalizeRows($input['b'] ?? []);
        $rowsC = $this->normalizeRows($input['c'] ?? []);

        if ($action === 'submit') {
            $this->ensureEvidence($rowsA2, 'section1.a2', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA3, 'section1.a3', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA4, 'section1.a4', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA5, 'section1.a5', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA6, 'section1.a6', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA7, 'section1.a7', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA8, 'section1.a8', $uploaded, $section, $application);
            $this->ensureEvidence($rowsA9, 'section1.a9', $uploaded, $section, $application);
            $this->ensureEvidence($rowsB, 'section1.b', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC, 'section1.c', $uploaded, $section, $application);
        }

        $rowsA2 = $this->bucketOnce($rowsA2, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a2', $r));
        $rowsA3 = $this->bucketOnce($rowsA3, fn ($r) => ($r['option'] ?? '') ?: (($r['category'] ?? '') . '|' . ($r['thesis'] ?? '')), fn ($r) => $this->pointsA('a3', $r));
        $rowsA4 = $this->bucketOnce($rowsA4, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a4', $r));
        $rowsA5 = $this->bucketOnce($rowsA5, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a5', $r));
        $rowsA6 = $this->bucketOnce($rowsA6, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a6', $r));
        $rowsA7 = $this->bucketOnce($rowsA7, fn ($r) => $r['category'] ?? '', fn ($r) => $this->pointsA('a7', $r));
        $rowsA8 = $this->bucketOnce($rowsA8, fn ($r) => $r['relation'] ?? '', fn ($r) => $this->pointsA('a8', $r));
        $rowsA9 = $this->bucketOnce($rowsA9, fn ($r) => $r['level'] ?? '', fn ($r) => $this->pointsA('a9', $r));
        $rowsB = $this->bucketOnce($rowsB, fn ($r) => $r['hours'] ?? '', fn ($r) => $this->pointsB($r));
        $rowsC = $this->bucketOnce($rowsC, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsC($r));

        foreach ($rowsA2 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $entryId = $this->createEntry($section, $application, 'a2', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsA3 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $entryId = $this->createEntry($section, $application, 'a3', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsA4 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $entryId = $this->createEntry($section, $application, 'a4', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsA5 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $entryId = $this->createEntry($section, $application, 'a5', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsA6 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $entryId = $this->createEntry($section, $application, 'a6', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsA7 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $aBase += $points;
            $entryId = $this->createEntry($section, $application, 'a7', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsA8 as $row) {
            $points = min((float) ($row['points'] ?? 0), 5);
            $a8Sum += $points;
            $entryId = $this->createEntry($section, $application, 'a8', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsA9 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $a9Sum += $points;
            $entryId = $this->createEntry($section, $application, 'a9', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsB as $row) {
            $points = (float) ($row['points'] ?? 0);
            $bSum += $points;
            $entryId = $this->createEntry($section, $application, 'b', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        foreach ($rowsC as $row) {
            $points = (float) ($row['points'] ?? 0);
            $cSum += $points;
            $entryId = $this->createEntry($section, $application, 'c', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $bPrev = (float) ($input['b_prev'] ?? 0);
        $cPrev = (float) ($input['c_prev'] ?? 0);
        $bPrevId = $this->resolveExistingSingletonEntryId($section, 'b_prev', (int) ($input['b_prev_id'] ?? 0));
        $cPrevId = $this->resolveExistingSingletonEntryId($section, 'c_prev', (int) ($input['c_prev_id'] ?? 0));

        if ($bPrev > 0 || $bPrevId > 0) {
            $entryId = $this->createEntry($section, $application, 'b_prev', ['id' => $bPrevId, 'value' => $bPrev], $bPrev / 3, null, $uploaded);
            $touchedIds[] = $entryId;
        }
        if ($cPrev > 0 || $cPrevId > 0) {
            $entryId = $this->createEntry($section, $application, 'c_prev', ['id' => $cPrevId, 'value' => $cPrev], $cPrev / 3, null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $this->reconcileMissingEntries(
            $section,
            $touchedIds,
            ['a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'b', 'c', 'b_prev', 'c_prev'],
            (string) $application->status
        );

        $aTotal = $aBase + min($a8Sum, 15) + min($a9Sum, 10);
        $bTotal = min($bSum + ($bPrev / 3), 20);
        $cTotal = min($cSum + ($cPrev / 3), 20);

        $sectionTotal = min($aTotal + $bTotal + $cTotal, 140);

        $section->update([
            'points_total' => $sectionTotal,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function saveSectionThree(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section3' => ['array'],
            'section3.evidence_files.*' => $this->evidenceFileRules(),
        ]);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 3, $request->user()->id, 'section3.evidence_files');
        $input = $request->input('section3', []);
        $action = $request->input('action', 'draft');
        $touchedIds = [];

        $rowsC1 = $this->normalizeRows($input['c1'] ?? []);
        $rowsC2 = $this->normalizeRows($input['c2'] ?? []);
        $rowsC3 = $this->normalizeRows($input['c3'] ?? []);
        $rowsC4 = $this->normalizeRows($input['c4'] ?? []);
        $rowsC5 = $this->normalizeRows($input['c5'] ?? []);
        $rowsC6 = $this->normalizeRows($input['c6'] ?? []);
        $rowsC7 = $this->normalizeRows($input['c7'] ?? []);
        $rowsC8 = $this->normalizeRows($input['c8'] ?? []);
        $rowsC9 = $this->normalizeRows($input['c9'] ?? []);

        if ($action === 'submit') {
            $this->ensureEvidence($rowsC1, 'section3.c1', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC2, 'section3.c2', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC3, 'section3.c3', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC4, 'section3.c4', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC5, 'section3.c5', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC6, 'section3.c6', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC7, 'section3.c7', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC8, 'section3.c8', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC9, 'section3.c9', $uploaded, $section, $application);
        }

        $rowsC1 = $this->bucketOnce($rowsC1, fn ($r) => ($r['authorship'] ?? '') . '|' . ($r['edition'] ?? '') . '|' . ($r['publisher'] ?? ''), fn ($r) => $this->pointsBook($r));
        $rowsC2 = $this->bucketOnce($rowsC2, fn ($r) => ($r['authorship'] ?? '') . '|' . ($r['edition'] ?? '') . '|' . ($r['publisher'] ?? ''), fn ($r) => $this->pointsWorkbook($r));
        $rowsC3 = $this->bucketOnce($rowsC3, fn ($r) => ($r['authorship'] ?? '') . '|' . ($r['edition'] ?? '') . '|' . ($r['publisher'] ?? ''), fn ($r) => $this->pointsCompilation($r));
        $rowsC4 = $this->bucketOnce($rowsC4, fn ($r) => ($r['kind'] ?? '') . '|' . ($r['authorship'] ?? '') . '|' . ($r['scope'] ?? ''), fn ($r) => $this->pointsArticle($r));
        $rowsC5 = $this->bucketOnce($rowsC5, fn ($r) => $r['level'] ?? '', fn ($r) => $this->pointsConference($r));
        $rowsC6 = $this->bucketOnce($rowsC6, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsCompleted($r));
        $rowsC7 = $this->bucketOnce($rowsC7, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsProposal($r));
        $rowsC9 = $this->bucketOnce($rowsC9, fn ($r) => $r['service'] ?? '', fn ($r) => $this->pointsEditorial($r));

        $sum = 0;

        foreach ($rowsC1 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c1', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC2 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c2', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC3 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c3', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC4 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c4', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC5 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c5', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC6 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c6', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC7 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c7', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC8 as $row) {
            $points = 5;
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c8', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }
        foreach ($rowsC9 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sum += $points;
            $entryId = $this->createEntry($section, $application, 'c9', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $prev = (float) ($input['previous_points'] ?? 0);
        $prevId = $this->resolveExistingSingletonEntryId($section, 'previous_points', (int) ($input['previous_points_id'] ?? 0));
        if ($prev > 0 || $prevId > 0) {
            $entryId = $this->createEntry($section, $application, 'previous_points', ['id' => $prevId, 'value' => $prev], $prev / 3, null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $this->reconcileMissingEntries(
            $section,
            $touchedIds,
            ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9', 'previous_points'],
            (string) $application->status
        );

        $total = $sum + ($prev / 3);
        if ($total > 70) $total = 70;

        $section->update([
            'points_total' => $total,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function saveSectionFour(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section4' => ['array'],
            'section4.evidence_files.*' => $this->evidenceFileRules(),
        ]);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 4, $request->user()->id, 'section4.evidence_files');

        $input = $request->input('section4', []);
        $action = $request->input('action', 'draft');

        $a1Years = (float) ($input['a']['a1_years'] ?? 0);
        $a2Years = (float) ($input['a']['a2_years'] ?? 0);
        $bYears = (float) ($input['b']['years'] ?? 0);
        $a1Id = $this->resolveExistingSingletonEntryId($section, 'a1', (int) ($input['a']['a1_id'] ?? 0));
        $a2Id = $this->resolveExistingSingletonEntryId($section, 'a2', (int) ($input['a']['a2_id'] ?? 0));
        $bId = $this->resolveExistingSingletonEntryId($section, 'b', (int) ($input['b']['id'] ?? 0));
        $touchedIds = [];

        $a1Evidence = $input['a']['a1_evidence'] ?? [];
        $a2Evidence = $input['a']['a2_evidence'] ?? [];
        $bEvidence = $input['b']['evidence'] ?? [];

        $a1Rows = [['evidence' => $a1Evidence]];
        $a2Rows = [['evidence' => $a2Evidence]];
        $bRows = [['evidence' => $bEvidence]];

        if ($action === 'submit') {
            if ($a1Years > 0) $this->ensureEvidence($a1Rows, 'section4.a.a1', $uploaded, $section, $application);
            if ($a2Years > 0) $this->ensureEvidence($a2Rows, 'section4.a.a2', $uploaded, $section, $application);
        }

        $bUnlocked = ($a2Years >= 3 && $a1Years >= 2) || $a2Years >= 5;
        if (!$bUnlocked) {
            $bYears = 0;
            $bEvidence = [];
            $bRows = [['evidence' => []]];
        }
        if ($bYears > 0 && $bUnlocked && $action === 'submit') {
            $this->ensureEvidence($bRows, 'section4.b', $uploaded, $section, $application);
        }

        $a1Raw = $a1Years * 2;
        $a2Raw = $a2Years * 3;
        $a1Capped = min($a1Raw, 20); // keep full per-entry points
        $a2Capped = min($a2Raw, 40); // keep full per-entry points
        $aTotal = min($a1Capped + $a2Capped, 40); // full A track points

        $bRaw = $bYears * 2;
        $bCapped = min($bRaw, 20); // keep full per-entry points

        $employmentType = $request->user()->facultyProfile?->employment_type
            ?? data_get($request->user(), 'employment_type', 'full_time');
        $isPartTime = $employmentType === 'part_time';

        // For part-time faculty, deduction is applied on the final counted track only.
        $finalRaw = max($aTotal, $bCapped);
        $final = $isPartTime ? ($finalRaw / 2) : $finalRaw;
        if ($final > 40) $final = 40;

        $entryId = $this->createEntry($section, $application, 'a1', [
            'id' => $a1Id,
            'years' => $a1Years,
            'evidence' => $a1Evidence,
        ], $a1Capped, $a1Evidence, $uploaded);
        $touchedIds[] = $entryId;

        $entryId = $this->createEntry($section, $application, 'a2', [
            'id' => $a2Id,
            'years' => $a2Years,
            'evidence' => $a2Evidence,
        ], $a2Capped, $a2Evidence, $uploaded);
        $touchedIds[] = $entryId;

        $entryId = $this->createEntry($section, $application, 'b', [
            'id' => $bId,
            'years' => $bYears,
            'evidence' => $bEvidence,
        ], $bUnlocked ? $bCapped : 0, $bEvidence, $uploaded);
        $touchedIds[] = $entryId;

        $this->reconcileMissingEntries($section, $touchedIds, ['a1', 'a2', 'b'], (string) $application->status);

        $section->update([
            'points_total' => $final,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function saveSectionFive(Request $request, ReclassificationApplication $application, ReclassificationSection $section): void
    {
        $request->validate([
            'section5' => ['array'],
            'section5.evidence_files.*' => $this->evidenceFileRules(),
        ]);

        $uploaded = $this->storeEvidenceFiles($request, $application, $section, 5, $request->user()->id, 'section5.evidence_files');
        $input = $request->input('section5', []);
        $action = $request->input('action', 'draft');
        $touchedIds = [];

        $rowsA = $this->normalizeRows($input['a'] ?? []);
        $rowsB = $this->normalizeRows($input['b'] ?? []);
        $rowsC1 = $this->normalizeRows($input['c1'] ?? []);
        $rowsC2 = $this->normalizeRows($input['c2'] ?? []);
        $rowsC3 = $this->normalizeRows($input['c3'] ?? []);
        $rowsD = $this->normalizeRows($input['d'] ?? []);

        if ($action === 'submit') {
            $this->ensureEvidence($rowsA, 'section5.a', $uploaded, $section, $application);
            $this->ensureEvidence($rowsB, 'section5.b', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC1, 'section5.c1', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC2, 'section5.c2', $uploaded, $section, $application);
            $this->ensureEvidence($rowsC3, 'section5.c3', $uploaded, $section, $application);
            $this->ensureEvidence($rowsD, 'section5.d', $uploaded, $section, $application);
        }

        $rowsA = $this->bucketOnce($rowsA, fn ($r) => ($r['kind'] ?? '') . '|' . (($r['kind'] ?? '') === 'scholarship' ? ($r['grant'] ?? '') : ($r['level'] ?? '')), fn ($r) => $this->pointsS5A($r));
        $rowsB = $this->bucketOnce($rowsB, fn ($r) => ($r['role'] ?? '') . '|' . ($r['level'] ?? ''), fn ($r) => $this->pointsS5B($r));
        $rowsC1 = array_map(function ($row) {
            $points = $this->pointsS5C1($row);
            $row['points'] = $points;
            $row['counted'] = $points > 0;
            return $row;
        }, $rowsC1);
        $rowsC2 = $this->bucketOnce($rowsC2, fn ($r) => $r['type'] ?? '', fn ($r) => $this->pointsS5C2($r));
        $rowsC3 = $this->bucketOnce($rowsC3, fn ($r) => $r['role'] ?? '', fn ($r) => $this->pointsS5C3($r));
        $rowsD = $this->bucketOnce($rowsD, fn ($r) => $r['role'] ?? '', fn ($r) => $this->pointsS5D($r));

        $sumA = 0;
        foreach ($rowsA as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumA += $points;
            $entryId = $this->createEntry($section, $application, 'a', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $sumB = 0;
        foreach ($rowsB as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumB += $points;
            $entryId = $this->createEntry($section, $application, 'b', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $sumC1 = 0;
        foreach ($rowsC1 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumC1 += $points;
            $entryId = $this->createEntry($section, $application, 'c1', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $sumC2 = 0;
        foreach ($rowsC2 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumC2 += $points;
            $entryId = $this->createEntry($section, $application, 'c2', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $sumC3 = 0;
        foreach ($rowsC3 as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumC3 += $points;
            $entryId = $this->createEntry($section, $application, 'c3', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $sumD = 0;
        foreach ($rowsD as $row) {
            $points = (float) ($row['points'] ?? 0);
            $sumD += $points;
            $entryId = $this->createEntry($section, $application, 'd', $row, $points, $row['evidence'] ?? null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $bPrev = (float) ($input['b_prev'] ?? 0);
        $cPrev = (float) ($input['c_prev'] ?? 0);
        $dPrev = (float) ($input['d_prev'] ?? 0);
        $prev = (float) ($input['previous_points'] ?? 0);
        $bPrevId = $this->resolveExistingSingletonEntryId($section, 'b_prev', (int) ($input['b_prev_id'] ?? 0));
        $cPrevId = $this->resolveExistingSingletonEntryId($section, 'c_prev', (int) ($input['c_prev_id'] ?? 0));
        $dPrevId = $this->resolveExistingSingletonEntryId($section, 'd_prev', (int) ($input['d_prev_id'] ?? 0));
        $prevId = $this->resolveExistingSingletonEntryId($section, 'previous_points', (int) ($input['previous_points_id'] ?? 0));

        if ($bPrev > 0 || $bPrevId > 0) {
            $entryId = $this->createEntry($section, $application, 'b_prev', ['id' => $bPrevId, 'value' => $bPrev], $bPrev / 3, null, $uploaded);
            $touchedIds[] = $entryId;
        }
        if ($cPrev > 0 || $cPrevId > 0) {
            $entryId = $this->createEntry($section, $application, 'c_prev', ['id' => $cPrevId, 'value' => $cPrev], $cPrev / 3, null, $uploaded);
            $touchedIds[] = $entryId;
        }
        if ($dPrev > 0 || $dPrevId > 0) {
            $entryId = $this->createEntry($section, $application, 'd_prev', ['id' => $dPrevId, 'value' => $dPrev], $dPrev / 3, null, $uploaded);
            $touchedIds[] = $entryId;
        }
        if ($prev > 0 || $prevId > 0) {
            $entryId = $this->createEntry($section, $application, 'previous_points', ['id' => $prevId, 'value' => $prev], $prev / 3, null, $uploaded);
            $touchedIds[] = $entryId;
        }

        $this->reconcileMissingEntries(
            $section,
            $touchedIds,
            ['a', 'b', 'c1', 'c2', 'c3', 'd', 'b_prev', 'c_prev', 'd_prev', 'previous_points'],
            (string) $application->status
        );

        $sumA = min($sumA, 5);
        $sumB = min($sumB + ($bPrev / 3), 10);
        $sumC1 = min($sumC1, 10);
        $sumC2 = min($sumC2, 5);
        $sumC3 = min($sumC3, 10);
        $sumC = min($sumC1 + $sumC2 + $sumC3 + ($cPrev / 3), 15);
        $sumD = min($sumD + ($dPrev / 3), 10);

        $total = $sumA + $sumB + $sumC + $sumD + ($prev / 3);
        if ($total > 30) $total = 30;

        $section->update([
            'points_total' => $total,
            'is_complete' => $action === 'submit',
        ]);
    }

    private function storeEvidenceFiles(
        Request $request,
        ReclassificationApplication $application,
        ReclassificationSection $section,
        int $sectionNumber,
        int $userId,
        string $inputKey
    ): array
    {
        $files = $request->file($inputKey, []);
        if ($files && !is_array($files)) {
            $files = [$files];
        }
        $uploaded = [];

        foreach ($files as $index => $file) {
            if (!$file) continue;

            $path = $file->store("reclassification/{$application->id}/section{$sectionNumber}", 'public');

            $uploaded[$index] = ReclassificationEvidence::create([
                'reclassification_application_id' => $application->id,
                'reclassification_section_id' => $section->id,
                'reclassification_section_entry_id' => null,
                'uploaded_by_user_id' => $userId,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'label' => "Section {$sectionNumber} upload",
                'status' => 'pending',
            ]);
        }

        return $uploaded;
    }

    private function evidenceFileRules(): array
    {
        return [
            'file',
            'mimes:pdf,jpg,jpeg,png,gif,webp,bmp,svg,tif,tiff,heic,heif',
            'max:20480',
        ];
    }

    private function storeGlobalEvidenceFiles(
        Request $request,
        ReclassificationApplication $application,
        int $userId,
        string $inputKey
    ): array {
        $files = $request->file($inputKey, []);
        if ($files && !is_array($files)) {
            $files = [$files];
        }
        $uploaded = [];

        foreach ($files as $index => $file) {
            if (!$file) continue;

            $path = $file->store("reclassification/{$application->id}/global", 'public');

            $uploaded[$index] = ReclassificationEvidence::create([
                'reclassification_application_id' => $application->id,
                'reclassification_section_id' => null,
                'reclassification_section_entry_id' => null,
                'uploaded_by_user_id' => $userId,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'label' => 'Global upload',
                'status' => 'pending',
            ]);
        }

        return $uploaded;
    }

    private function resolveExistingSingletonEntryId(
        ReclassificationSection $section,
        string $criterionKey,
        int $preferredId = 0
    ): int {
        // 1) Trust posted id when it still belongs to this section+criterion.
        if ($preferredId > 0) {
            $preferred = ReclassificationSectionEntry::query()
                ->where('id', $preferredId)
                ->where('reclassification_section_id', $section->id)
                ->where('criterion_key', $criterionKey)
                ->first();
            if ($preferred) {
                return (int) $preferred->id;
            }
        }

        // 2) If duplicates exist, prefer an active (not soft-removed) row.
        $candidates = ReclassificationSectionEntry::query()
            ->where('reclassification_section_id', $section->id)
            ->where('criterion_key', $criterionKey)
            ->orderByDesc('id')
            ->get(['id', 'data']);

        foreach ($candidates as $candidate) {
            $data = is_array($candidate->data) ? $candidate->data : [];
            if (!$this->isRowRemoved($data)) {
                return (int) $candidate->id;
            }
        }

        // 3) Fallback to the newest row for this criterion.
        return (int) ($candidates->first()->id ?? 0);
    }

    private function createEntry(
        ReclassificationSection $section,
        ReclassificationApplication $application,
        string $key,
        array $row,
        float $points,
        $evidenceIndex,
        array $uploaded
    ): int {
        $entryId = isset($row['id']) ? (int) $row['id'] : 0;
        $isRemoved = $this->isRowRemoved($row);
        $points = $isRemoved ? 0.0 : $points;

        $payloadRow = $row;
        unset($payloadRow['id'], $payloadRow['comments']);
        $payloadRow['is_removed'] = $isRemoved;
        $payloadRow['points'] = $points;

        $entry = null;
        if ($entryId > 0) {
            $entry = ReclassificationSectionEntry::where('id', $entryId)
                ->where('reclassification_section_id', $section->id)
                ->first();
        }

        $entryPayload = [
            'criterion_key' => $key,
            'title' => $payloadRow['title'] ?? $payloadRow['text'] ?? null,
            'description' => null,
            'evidence_note' => null,
            'points' => $points,
            'is_validated' => false,
            'data' => $payloadRow,
        ];

        if ($entry) {
            $entry->update($entryPayload);
        } else {
            $entry = ReclassificationSectionEntry::create([
                'reclassification_section_id' => $section->id,
                ...$entryPayload,
            ]);
        }

        $this->syncEntryEvidence($entry, $section, $application, $key, $evidenceIndex, $uploaded, $isRemoved);

        return $entry->id;
    }

    private function syncEntryEvidence(
        ReclassificationSectionEntry $entry,
        ReclassificationSection $section,
        ReclassificationApplication $application,
        string $key,
        $evidenceIndex,
        array $uploaded,
        bool $isRemoved
    ): void {
        DB::table('reclassification_evidence_links')
            ->where('reclassification_section_entry_id', $entry->id)
            ->delete();

        ReclassificationEvidence::where('reclassification_application_id', $application->id)
            ->where('reclassification_section_entry_id', $entry->id)
            ->update([
                'reclassification_section_entry_id' => null,
                'reclassification_section_id' => null,
            ]);

        if ($isRemoved) {
            return;
        }

        $values = $this->parseEvidenceValues($evidenceIndex);
        if (count($values) === 0) {
            return;
        }

        foreach ($values as $value) {
            if (str_starts_with($value, 'n:')) {
                $index = (int) substr($value, 2);
                if (!isset($uploaded[$index])) continue;
                $this->attachExistingEvidence($uploaded[$index]->id, $entry, $section, $application, $key);
                continue;
            }

            if (str_starts_with($value, 'e:')) {
                $id = (int) substr($value, 2);
                $this->attachExistingEvidence($id, $entry, $section, $application, $key);
                continue;
            }

            if (is_numeric($value)) {
                $index = (int) $value;
                if (!isset($uploaded[$index])) continue;
                $this->attachExistingEvidence($uploaded[$index]->id, $entry, $section, $application, $key);
            }
        }
    }

    private function reconcileMissingEntries(
        ReclassificationSection $section,
        array $touchedIds,
        array $criterionKeys,
        string $applicationStatus
    ): void
    {
        $criteria = array_values(array_unique($criterionKeys));
        if (count($criteria) === 0) {
            return;
        }

        $query = $section->entries()->whereIn('criterion_key', $criteria);
        if (count($touchedIds) > 0) {
            $query->whereNotIn('id', array_values(array_unique(array_map('intval', $touchedIds))));
        }

        $missingEntries = $query->get();
        if ($missingEntries->isEmpty()) {
            return;
        }

        // Draft flow: hard remove omitted rows.
        if ($applicationStatus !== 'returned_to_faculty') {
            $this->hardDeleteEntries($section, $missingEntries->pluck('id')->all());
            return;
        }

        // Returned flow: always soft-remove omitted rows so reviewers can still audit
        // what changed and faculty can restore when needed.
        $missingEntries->each(function (ReclassificationSectionEntry $entry) use ($section) {
            $data = is_array($entry->data) ? $entry->data : [];
            if (!$this->isRowRemoved($data) && !array_key_exists('removed_points_backup', $data)) {
                $data['removed_points_backup'] = (float) ($entry->points ?? 0);
            }
            $data['is_removed'] = true;
            $data['points'] = 0;
            $entry->update([
                'points' => 0,
                'data' => $data,
            ]);

            // Auto-mark open action-required reviewer comments as addressed when removed.
            $commentQuery = ReclassificationRowComment::query()
                ->where('reclassification_application_id', $section->reclassification_application_id)
                ->where('reclassification_section_entry_id', $entry->id)
                ->where('visibility', 'faculty_visible')
                ->where('status', 'open');
            if (Schema::hasColumn('reclassification_row_comments', 'action_type')) {
                $commentQuery->where('action_type', 'requires_action');
            }
            if (Schema::hasColumn('reclassification_row_comments', 'parent_id')) {
                $commentQuery->whereNull('parent_id');
            }
            $commentQuery->update([
                'status' => 'addressed',
                'resolved_by_user_id' => null,
                'resolved_at' => null,
            ]);
        });
    }

    private function recomputeSectionPointsTotal(?ReclassificationSection $section): void
    {
        if (!$section) {
            return;
        }

        $section->loadMissing('entries');
        $sum = (float) $section->entries->sum('points');
        $maxByCode = [
            '1' => 140,
            '2' => 120,
            '3' => 70,
            '4' => 40,
            '5' => 30,
        ];
        $code = (string) ($section->section_code ?? '');
        if (array_key_exists($code, $maxByCode)) {
            $sum = min($sum, (float) $maxByCode[$code]);
        }

        $section->update([
            'points_total' => $sum,
        ]);
    }

    private function respondRestoration(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    private function hardDeleteEntries(ReclassificationSection $section, array $entryIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $entryIds)));
        if (count($ids) === 0) {
            return;
        }

        DB::table('reclassification_evidence_links')
            ->whereIn('reclassification_section_entry_id', $ids)
            ->delete();

        ReclassificationEvidence::where('reclassification_section_id', $section->id)
            ->whereIn('reclassification_section_entry_id', $ids)
            ->update([
                'reclassification_section_entry_id' => null,
                'reclassification_section_id' => null,
            ]);

        ReclassificationSectionEntry::whereIn('id', $ids)->delete();
    }

    private function parseEvidenceValues($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];
        $out = [];

        foreach ($values as $val) {
            $val = trim((string) $val);
            if ($val === '') {
                continue;
            }
            $out[] = $val;
        }

        return array_values(array_unique($out));
    }

    private function attachExistingEvidence(
        int $evidenceId,
        ReclassificationSectionEntry $entry,
        ReclassificationSection $section,
        ReclassificationApplication $application,
        string $label
    ): void {
        $evidence = ReclassificationEvidence::where('id', $evidenceId)
            ->where('reclassification_application_id', $application->id)
            ->first();

        if (!$evidence) {
            return;
        }

        DB::table('reclassification_evidence_links')->updateOrInsert(
            [
                'reclassification_evidence_id' => $evidence->id,
                'reclassification_section_entry_id' => $entry->id,
            ],
            [
                'reclassification_section_id' => $section->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (!$evidence->label) {
            $evidence->update(['label' => $label]);
        }
    }

    private function detachSectionEvidence(ReclassificationSection $section): void
    {
        $entryIds = $section->entries()->pluck('id')->all();
        if (!empty($entryIds)) {
            DB::table('reclassification_evidence_links')
                ->whereIn('reclassification_section_entry_id', $entryIds)
                ->delete();
        }

        ReclassificationEvidence::where('reclassification_section_id', $section->id)
            ->update([
                'reclassification_section_entry_id' => null,
                'reclassification_section_id' => null,
            ]);
    }

    private function normalizeRows($rows): array
    {
        if (!is_array($rows)) return [];

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            if (array_key_exists('id', $row)) {
                $row['id'] = (int) $row['id'];
            }
            if (array_key_exists('is_removed', $row)) {
                $row['is_removed'] = $this->isRowRemoved($row);
            }
            if (array_key_exists('evidence', $row)) {
                if (is_array($row['evidence'])) {
                    $row['evidence'] = array_values(array_filter(array_map('strval', $row['evidence']), fn ($v) => $v !== ''));
                } else {
                    $raw = trim((string) $row['evidence']);
                    if ($raw === '') {
                        $row['evidence'] = [];
                    } elseif (str_contains($raw, ',')) {
                        $row['evidence'] = array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
                    } else {
                        $row['evidence'] = [$raw];
                    }
                }
            }
            if ($this->isRowEmpty($row)) continue;
            $out[] = $row;
        }

        return $out;
    }

    private function ensureEvidence(
        array $rows,
        string $fieldBase,
        array $uploaded,
        ReclassificationSection $section,
        ReclassificationApplication $application
    ): void
    {
        $existingIds = ReclassificationEvidence::where('reclassification_application_id', $application->id)
            ->pluck('id')
            ->map(fn ($id) => 'e:' . $id)
            ->all();

        $existingSet = array_flip($existingIds);

        foreach ($rows as $index => $row) {
            if ($this->isRowRemoved($row)) {
                continue;
            }

            $hasEvidence = false;
            if (array_key_exists('evidence', $row)) {
                $values = $this->parseEvidenceValues($row['evidence']);
                $hasEvidence = count($values) > 0;
            }
            if (!$hasEvidence) {
                throw ValidationException::withMessages([
                    "{$fieldBase}.{$index}.evidence" => 'Evidence is required for each filled entry.',
                ]);
            }

            $values = $this->parseEvidenceValues($row['evidence']);
            foreach ($values as $value) {
                if (str_starts_with($value, 'e:')) {
                    if (!isset($existingSet[$value])) {
                        throw ValidationException::withMessages([
                            "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is missing. Please re-upload your evidence files.',
                        ]);
                    }
                    continue;
                }

                if (str_starts_with($value, 'n:')) {
                    $single = (int) substr($value, 2);
                    if (!isset($uploaded[$single])) {
                        throw ValidationException::withMessages([
                            "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is missing. Please re-upload your evidence files.',
                        ]);
                    }
                    continue;
                }

                if (is_numeric($value)) {
                    $single = (int) $value;
                    if (!isset($uploaded[$single])) {
                        throw ValidationException::withMessages([
                            "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is missing. Please re-upload your evidence files.',
                        ]);
                    }
                    continue;
                }

                throw ValidationException::withMessages([
                    "{$fieldBase}.{$index}.evidence" => 'Selected evidence file is invalid.',
                ]);
            }
        }
    }

    private function isRowRemoved(array $row): bool
    {
        $value = $row['is_removed'] ?? false;
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function isCommentEntryRemoved($entry): bool
    {
        if (!$entry) {
            return false;
        }

        $data = is_array($entry->data) ? $entry->data : [];
        return $this->isRowRemoved($data);
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $key => $value) {
            if (in_array((string) $key, ['id', 'comments', 'is_removed', 'points', 'counted'], true)) {
                continue;
            }
            if (is_string($value) && trim($value) !== '') return false;
            if (is_numeric($value) && (float) $value !== 0.0) return false;
            if (is_array($value) && !empty($value)) return false;
            if (is_bool($value) && $value === true) return false;
        }

        return true;
    }

    private function pointsA1(string $honors): float
    {
        if ($honors === 'summa') return 3;
        if ($honors === 'magna') return 2;
        if ($honors === 'cum') return 1;
        return 0;
    }

    private function pointsA(string $key, array $row): float
    {
        $cat = $row['category'] ?? '';
        $thesis = $row['thesis'] ?? '';
        $rel = $row['relation'] ?? '';
        $lvl = $row['level'] ?? '';
        $units = $row['units'] ?? null;
        if ($units === null || $units === '') {
            if (isset($row['blocks']) && $row['blocks'] !== '') {
                $units = ((float) $row['blocks']) * 9;
            } else {
                $units = 0;
            }
        }
        $hasNineUnits = ((float) $units) >= 9;

        if ($key === 'a2') {
            if ($cat === 'teaching') return 10;
            if ($cat === 'not_teaching') return 5;
            return 0;
        }

        if ($key === 'a3') {
            $opt = (string) ($row['option'] ?? '');
            if ($opt === 'teaching_with_thesis') return 100;
            if ($opt === 'teaching_without_thesis') return 90;
            if ($opt === 'not_teaching_with_thesis') return 80;
            if ($opt === 'not_teaching_without_thesis') return 70;

            if ($cat === 'teaching' && $thesis === 'with') return 100;
            if ($cat === 'teaching' && $thesis === 'without') return 90;
            if ($cat === 'not_teaching' && $thesis === 'with') return 80;
            if ($cat === 'not_teaching' && $thesis === 'without') return 70;
            return 0;
        }

        if ($key === 'a4') {
            if ($cat === 'specialization') return $hasNineUnits ? 4 : 0;
            if ($cat === 'other') return $hasNineUnits ? 3 : 0;
            return 0;
        }

        if ($key === 'a5') {
            if ($cat === 'teaching') return 15;
            if ($cat === 'not_teaching') return 10;
            return 0;
        }

        if ($key === 'a6') {
            if ($cat === 'specialization') return $hasNineUnits ? 5 : 0;
            if ($cat === 'other') return $hasNineUnits ? 4 : 0;
            return 0;
        }

        if ($key === 'a7') {
            if ($cat === 'teaching') return 140;
            if ($cat === 'not_teaching') return 120;
            return 0;
        }

        if ($key === 'a8') {
            if ($rel === 'direct') return 10;
            if ($rel === 'not_direct') return 5;
            return 0;
        }

        if ($key === 'a9') {
            if ($lvl === 'international') return 5;
            if ($lvl === 'national') return 3;
            return 0;
        }

        return 0;
    }

    private function pointsB(array $row): float
    {
        $h = (string) ($row['hours'] ?? '');
        if ($h === '120') return 15;
        if ($h === '80') return 10;
        if ($h === '50') return 6;
        if ($h === '20') return 4;
        return 0;
    }

    private function pointsC(array $row): float
    {
        $role = trim((string) ($row['role'] ?? ''));
        $level = trim((string) ($row['level'] ?? ''));

        $minMap = [
            'speaker' => [
                'international' => 13,
                'national' => 11,
                'regional' => 9,
                'provincial' => 7,
                'municipal' => 4,
                'school' => 1,
            ],
            'resource' => [
                'international' => 11,
                'national' => 9,
                'regional' => 7,
                'provincial' => 5,
                'municipal' => 3,
                'school' => 1,
            ],
            'participant' => [
                'international' => 9,
                'national' => 7,
                'regional' => 5,
                'provincial' => 3,
                'municipal' => 2,
                'school' => 1,
            ],
        ];

        return (float) ($minMap[$role][$level] ?? 0);
    }

    private function bucketOnce(array $rows, callable $keyFn, callable $pointsFn): array
    {
        $seen = [];
        return array_map(function ($row) use ($keyFn, $pointsFn, &$seen) {
            if ($this->isRowRemoved($row)) {
                $row['points'] = 0;
                $row['counted'] = false;
                return $row;
            }
            $key = (string) $keyFn($row);
            $points = (float) $pointsFn($row);
            if ($key === '' || $points <= 0) {
                $row['points'] = 0;
                $row['counted'] = false;
                return $row;
            }
            if (isset($seen[$key])) {
                $row['points'] = 0;
                $row['counted'] = false;
                return $row;
            }
            $seen[$key] = true;
            $row['points'] = $points;
            $row['counted'] = true;
            return $row;
        }, $rows);
    }

    private function pointsBook(array $row): float
    {
        $a = $row['authorship'] ?? '';
        $ed = $row['edition'] ?? '';
        $pub = $row['publisher'] ?? '';
        if (!$a || !$ed || !$pub) return 0;

        $map = [
            'sole' => [
                'new' => ['registered' => 20, 'printed_approved' => 18],
                'revised' => ['registered' => 16, 'printed_approved' => 14],
            ],
            'co' => [
                'new' => ['registered' => 14, 'printed_approved' => 12],
                'revised' => ['registered' => 10, 'printed_approved' => 8],
            ],
        ];

        return (float) ($map[$a][$ed][$pub] ?? 0);
    }

    private function pointsWorkbook(array $row): float
    {
        $a = $row['authorship'] ?? '';
        $ed = $row['edition'] ?? '';
        $pub = $row['publisher'] ?? '';
        if (!$a || !$ed || !$pub) return 0;

        $map = [
            'sole' => [
                'new' => ['registered' => 15, 'printed_approved' => 13],
                'revised' => ['registered' => 11, 'printed_approved' => 9],
            ],
            'co' => [
                'new' => ['registered' => 9, 'printed_approved' => 8],
                'revised' => ['registered' => 7, 'printed_approved' => 6],
            ],
        ];

        return (float) ($map[$a][$ed][$pub] ?? 0);
    }

    private function pointsCompilation(array $row): float
    {
        $a = $row['authorship'] ?? '';
        $ed = $row['edition'] ?? '';
        $pub = $row['publisher'] ?? '';
        if (!$a || !$ed || !$pub) return 0;

        $map = [
            'sole' => [
                'new' => ['registered' => 12, 'printed_approved' => 11],
                'revised' => ['registered' => 10, 'printed_approved' => 9],
            ],
            'co' => [
                'new' => ['registered' => 8, 'printed_approved' => 7],
                'revised' => ['registered' => 6, 'printed_approved' => 5],
            ],
        ];

        return (float) ($map[$a][$ed][$pub] ?? 0);
    }

    private function pointsArticle(array $row): float
    {
        $kind = $row['kind'] ?? '';
        $scope = $row['scope'] ?? '';
        if (!$kind || !$scope) return 0;

        if ($kind === 'otherpub') {
            $other = [
                'national_periodicals' => 5,
                'local_periodicals' => 4,
                'university_newsletters' => 3,
            ];
            return (float) ($other[$scope] ?? 0);
        }

        $auth = $row['authorship'] ?? '';
        if (!$auth) return 0;

        $key = "{$kind}_{$auth}_{$scope}";
        $map = [
            'refereed_sole_international' => 40,
            'refereed_co_international' => 36,
            'refereed_sole_national' => 38,
            'refereed_co_national' => 34,
            'refereed_sole_university' => 36,
            'refereed_co_university' => 32,
            'nonrefereed_sole_international' => 30,
            'nonrefereed_co_international' => 24,
            'nonrefereed_sole_national' => 28,
            'nonrefereed_co_national' => 22,
            'nonrefereed_sole_university' => 20,
            'nonrefereed_co_university' => 20,
        ];

        return (float) ($map[$key] ?? 0);
    }

    private function pointsConference(array $row): float
    {
        $map = [
            'international' => 15,
            'national' => 13,
            'regional' => 11,
            'institutional' => 9,
        ];
        return (float) ($map[$row['level'] ?? ''] ?? 0);
    }

    private function pointsCompleted(array $row): float
    {
        $principal = ['international' => 20, 'national' => 18, 'regional' => 16, 'institutional' => 14];
        $team = ['international' => 15, 'national' => 13, 'regional' => 11, 'institutional' => 9];
        $role = $row['role'] ?? '';
        $level = $row['level'] ?? '';
        $map = $role === 'team' ? $team : $principal;
        return (float) ($map[$level] ?? 0);
    }

    private function pointsProposal(array $row): float
    {
        $principal = ['international' => 15, 'national' => 13, 'regional' => 11, 'institutional' => 9];
        $team = ['international' => 11, 'national' => 9, 'regional' => 7, 'institutional' => 5];
        $role = $row['role'] ?? '';
        $level = $row['level'] ?? '';
        $map = $role === 'team' ? $team : $principal;
        return (float) ($map[$level] ?? 0);
    }

    private function pointsEditorial(array $row): float
    {
        $map = ['chief' => 15, 'editor' => 10, 'consultant' => 5];
        return (float) ($map[$row['service'] ?? ''] ?? 0);
    }

    private function pointsS5A(array $row): float
    {
        $kind = $row['kind'] ?? '';
        if (!$kind) return 0;

        if ($kind !== 'scholarship') {
            $lvl = $row['level'] ?? '';
            $map = ['international' => 5, 'national' => 4, 'regional' => 3, 'local' => 2, 'school' => 1];
            return (float) ($map[$lvl] ?? 0);
        }

        $grant = $row['grant'] ?? '';
        $map = ['full' => 5, 'partial_4' => 4, 'partial_3' => 3, 'travel_2' => 2, 'travel_1' => 1];
        return (float) ($map[$grant] ?? 0);
    }

    private function pointsS5B(array $row): float
    {
        $role = $row['role'] ?? '';
        $level = $row['level'] ?? '';
        if (!$role || !$level) return 0;

        $officer = ['international' => 10, 'national' => 8, 'regional' => 6, 'local' => 4, 'school' => 2];
        $chairman = ['international' => 5, 'national' => 4, 'regional' => 3, 'local' => 2, 'school' => 1];
        $committee = ['international' => 4, 'national' => 3, 'regional' => 2, 'local' => 1.5, 'school' => 1];
        $member = ['international' => 3, 'national' => 2.5, 'regional' => 2, 'local' => 1, 'school' => 0.5];

        $mapByRole = [
            'officer' => $officer,
            'chairman' => $chairman,
            'member_committee' => $committee,
            'member' => $member,
        ];

        return (float) ($mapByRole[$role][$level] ?? 0);
    }

    private function pointsS5C1(array $row): float
    {
        $role = $row['role'] ?? '';
        if (!$role) return 0;
        $per = ['overall' => 7, 'chairman' => 5, 'member' => 2];
        return $per[$role] ?? 0;
    }

    private function pointsS5C2(array $row): float
    {
        $type = $row['type'] ?? '';
        if (!$type) return 0;
        $per = ['campus' => 5, 'department' => 3, 'class' => 1];
        return $per[$type] ?? 0;
    }

    private function pointsS5C3(array $row): float
    {
        $role = $row['role'] ?? '';
        if (!$role) return 0;
        $per = ['overall' => 5, 'chairman' => 3, 'member' => 1];
        return $per[$role] ?? 0;
    }

    private function pointsS5D(array $row): float
    {
        $role = $row['role'] ?? '';
        if (!$role) return 0;
        $per = ['chairman' => 5, 'coordinator' => 3, 'participant' => 1];
        return $per[$role] ?? 0;
    }
}
