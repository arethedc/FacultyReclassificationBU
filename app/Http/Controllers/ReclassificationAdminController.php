<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Models\ReclassificationStatusTrail;
use App\Services\ReclassificationNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ReclassificationAdminController extends Controller
{
    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
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

    private function applyDepartmentFilter($query, $departmentId): void
    {
        if ($departmentId === null || $departmentId === '') {
            return;
        }

        $query->whereHas('faculty.department', function ($builder) use ($departmentId) {
            if (is_numeric($departmentId)) {
                $builder->where('id', (int) $departmentId);
                return;
            }

            $builder->where('code', (string) $departmentId)
                ->orWhere('name', (string) $departmentId);
        });
    }

    private function applyRankLevelFilter($query, $rankLevelId): void
    {
        if ($rankLevelId === null || $rankLevelId === '') {
            return;
        }

        $query->whereHas('faculty.facultyProfile', function ($builder) use ($rankLevelId) {
            $builder->where('rank_level_id', $rankLevelId);
        });
    }

    private function applyFacultySearch($query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $like = $this->likeOperator();
        $query->where(function ($builder) use ($q, $like) {
            $builder->whereHas('faculty', function ($faculty) use ($q, $like) {
                $faculty->where('name', $like, "%{$q}%")
                    ->orWhere('email', $like, "%{$q}%");
            })->orWhereHas('faculty.facultyProfile', function ($profile) use ($q, $like) {
                $profile->where('employee_no', $like, "%{$q}%");
            });
        });
    }

    private function reviewerRole(Request $request): string
    {
        return strtolower((string) $request->user()->role);
    }

    private function ensureReviewerHistoryRole(Request $request): array
    {
        $role = $this->reviewerRole($request);
        abort_unless(in_array($role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $departmentId = null;
        if ($role === 'dean') {
            $departmentId = $request->user()->department_id;
            abort_unless($departmentId, 403);
        }

        return [$role, $departmentId];
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

    private function applyPeriodScope($query, ?ReclassificationPeriod $period): void
    {
        if (!$period) {
            $query->whereRaw('1 = 0');
            return;
        }

        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');

        if (!$hasPeriodId) {
            if (!empty($period->cycle_year)) {
                $query->where('cycle_year', $period->cycle_year);
            } else {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        $query->where(function ($builder) use ($period) {
            $builder->where('period_id', $period->id);
            if (!empty($period->cycle_year)) {
                $builder->orWhere(function ($fallback) use ($period) {
                    $fallback->whereNull('period_id')
                        ->where('cycle_year', $period->cycle_year);
                });
            }
        });
    }

    private function applyApprovedFilters($query, string $q = '', $departmentId = null, $cycleYear = null, $rankLevelId = null)
    {
        $this->applyDepartmentFilter($query, $departmentId);

        if (!empty($cycleYear)) {
            $query->where('cycle_year', $cycleYear);
        }

        $this->applyRankLevelFilter($query, $rankLevelId);

        $this->applyFacultySearch($query, $q);
    }

    private function attachRankPreviews($paginator): void
    {
        if (!$paginator || !method_exists($paginator, 'getCollection')) {
            return;
        }

        $workflow = app(ReclassificationWorkflowController::class);

        $paginator->setCollection(
            $paginator->getCollection()->map(function (ReclassificationApplication $app) use ($workflow) {
                if (!empty($app->current_rank_label_at_approval) && !empty($app->approved_rank_label)) {
                    return $app;
                }

                try {
                    $preview = $workflow->previewApprovalResult($app);
                    $app->current_rank_label_preview = $preview['current_rank_label'] ?? null;
                    $app->approved_rank_label_preview = $preview['approved_rank_label'] ?? null;
                } catch (\Throwable $e) {
                    $app->current_rank_label_preview = $app->current_rank_label_preview ?? null;
                    $app->approved_rank_label_preview = $app->approved_rank_label_preview ?? null;
                }

                return $app;
            })
        );
    }

    public function index(Request $request)
    {
        $role = strtolower((string) $request->user()->role);
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all');
        $activity = $request->get('activity', 'active');
        $departmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $activePeriod = $this->activePeriod();
        $hasActivePeriod = (bool) $activePeriod;

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'faculty.facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
            ]);

        if ($status === 'submitted') {
            $query->whereIn('status', [
                'dean_review',
                'hr_review',
                'vpaa_review',
                'vpaa_approved',
                'president_review',
                'finalized',
                'rejected_final',
            ]);
        } elseif ($status === 'all') {
            $query->where('status', '!=', 'draft');
        } else {
            $query->where('status', $status);
        }

        if ($activity === 'rejected') {
            $query->where('status', 'rejected_final');
        } elseif ($activity === 'active') {
            $query->where('status', '!=', 'rejected_final');
        }

        $this->applyDepartmentFilter($query, $departmentId);
        $this->applyPeriodScope($query, $activePeriod);
        $this->applyRankLevelFilter($query, $rankLevelId);

        if ($q !== '') {
            $like = $this->likeOperator();
            $query->where(function ($builder) use ($q, $like) {
                $builder->whereHas('faculty', function ($faculty) use ($q, $like) {
                    $faculty->where('name', $like, "%{$q}%")
                        ->orWhere('email', $like, "%{$q}%");
                })->orWhereHas('faculty.facultyProfile', function ($profile) use ($q, $like) {
                    $profile->where('employee_no', $like, "%{$q}%");
                });
            });
        }

        $applications = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends([
                'q' => $q,
                'status' => $status,
                'activity' => $activity,
                'department_id' => $departmentId,
                'rank_level_id' => $rankLevelId,
            ]);

        $departments = Department::orderBy('name')->get();
        $cycleYears = collect();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();
        $indexRoute = $role === 'hr'
            ? route('reclassification.admin.submissions')
            : route('reclassification.review.submissions');

        return view('reclassification.admin.index', compact(
            'applications',
            'departments',
            'cycleYears',
            'rankLevels',
            'q',
            'status',
            'activity',
            'departmentId',
            'rankLevelId',
            'activePeriod',
            'hasActivePeriod',
            'indexRoute',
        ));
    }

    public function toggleReject(Request $request, ReclassificationApplication $application)
    {
        abort_unless(strtolower((string) $request->user()->role) === 'hr', 403);
        abort_unless($application->status !== 'draft', 422);

        if ($application->status === 'rejected_final') {
            $application->update([
                'status' => 'hr_review',
                'current_step' => 'hr',
                'returned_from' => null,
            ]);

            $message = 'Submission is now active and returned to HR review.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message]);
            }

            return back()->with('success', $message);
        }

        $application->update([
            'status' => 'rejected_final',
            'current_step' => 'finalized',
            'returned_from' => null,
        ]);

        $message = 'Submission marked as rejected.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function destroySubmission(Request $request, ReclassificationApplication $application)
    {
        abort_unless(strtolower((string) $request->user()->role) === 'hr', 403);

        $applicationId = (int) $application->id;
        $previousUrl = (string) url()->previous();
        $filesToDelete = [];

        try {
            DB::transaction(function () use ($application, &$filesToDelete) {
                $applicationId = (int) $application->id;
                $sectionIds = [];
                $entryIds = [];
                $evidenceIds = [];

                if (Schema::hasTable('reclassification_sections')) {
                    $sectionIds = DB::table('reclassification_sections')
                        ->where('reclassification_application_id', $applicationId)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }

                if (!empty($sectionIds) && Schema::hasTable('reclassification_section_entries')) {
                    $entryIds = DB::table('reclassification_section_entries')
                        ->whereIn('reclassification_section_id', $sectionIds)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }

                if (Schema::hasTable('reclassification_evidences')) {
                    $evidences = DB::table('reclassification_evidences')
                        ->where('reclassification_application_id', $applicationId)
                        ->get(['id', 'disk', 'path']);

                    $evidenceIds = $evidences
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    $filesToDelete = $evidences
                        ->map(function ($item) {
                            return [
                                'disk' => (string) ($item->disk ?: 'public'),
                                'path' => (string) ($item->path ?? ''),
                            ];
                        })
                        ->filter(fn ($item) => $item['path'] !== '')
                        ->values()
                        ->all();
                }

                if (Schema::hasTable('reclassification_evidence_links')) {
                    $linkQuery = DB::table('reclassification_evidence_links');
                    $hasCondition = false;

                    if (!empty($evidenceIds)) {
                        $linkQuery->whereIn('reclassification_evidence_id', $evidenceIds);
                        $hasCondition = true;
                    }
                    if (!empty($entryIds)) {
                        $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                        $linkQuery->{$method}('reclassification_section_entry_id', $entryIds);
                        $hasCondition = true;
                    }
                    if (!empty($sectionIds)) {
                        $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                        $linkQuery->{$method}('reclassification_section_id', $sectionIds);
                        $hasCondition = true;
                    }

                    if ($hasCondition) {
                        $linkQuery->delete();
                    }
                }

                if (Schema::hasTable('reclassification_row_comments')) {
                    DB::table('reclassification_row_comments')
                        ->where('reclassification_application_id', $applicationId)
                        ->delete();
                }

                if (Schema::hasTable('reclassification_change_logs')) {
                    DB::table('reclassification_change_logs')
                        ->where('reclassification_application_id', $applicationId)
                        ->delete();
                }

                if (Schema::hasTable('reclassification_status_trails')) {
                    DB::table('reclassification_status_trails')
                        ->where('reclassification_application_id', $applicationId)
                        ->delete();
                }

                if (Schema::hasTable('reclassification_events')) {
                    DB::table('reclassification_events')
                        ->where('reclassification_application_id', $applicationId)
                        ->delete();
                }

                if (Schema::hasTable('reclassification_comments')) {
                    DB::table('reclassification_comments')
                        ->where('reclassification_application_id', $applicationId)
                        ->delete();
                }

                if (Schema::hasTable('reclassification_move_requests')) {
                    DB::table('reclassification_move_requests')
                        ->where('reclassification_application_id', $applicationId)
                        ->delete();
                }

                if (!empty($evidenceIds) && Schema::hasTable('reclassification_evidences')) {
                    DB::table('reclassification_evidences')
                        ->whereIn('id', $evidenceIds)
                        ->delete();
                }

                if (!empty($entryIds) && Schema::hasTable('reclassification_section_entries')) {
                    DB::table('reclassification_section_entries')
                        ->whereIn('id', $entryIds)
                        ->delete();
                }

                if (!empty($sectionIds) && Schema::hasTable('reclassification_sections')) {
                    DB::table('reclassification_sections')
                        ->whereIn('id', $sectionIds)
                        ->delete();
                }

                DB::table('reclassification_applications')
                    ->where('id', $applicationId)
                    ->delete();
            });
        } catch (\Throwable $e) {
            report($e);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unable to delete this submission right now. Please try again.',
                ], 422);
            }

            return back()->withErrors([
                'delete' => 'Unable to delete this submission right now. Please try again.',
            ]);
        }

        foreach ($filesToDelete as $file) {
            try {
                Storage::disk((string) ($file['disk'] ?: 'public'))
                    ->delete((string) ($file['path'] ?? ''));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (
            str_contains($previousUrl, '/reclassification/review/')
            || str_contains($previousUrl, '/reclassification/dean/review/')
            || str_contains($previousUrl, '/reclassification/review-queue')
            || str_contains($previousUrl, '/reclassification/dean/review')
        ) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => "Submission #{$applicationId} deleted.",
                    'redirect' => route('reclassification.review.queue'),
                ]);
            }

            return redirect()
                ->route('reclassification.review.queue')
                ->with('success', "Submission #{$applicationId} deleted.");
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => "Submission #{$applicationId} deleted.",
            ]);
        }

        return back()->with('success', "Submission #{$applicationId} deleted.");
    }

    public function deanIndex(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all');
        $rankLevelId = $request->get('rank_level_id');
        $activePeriod = $this->activePeriod();
        $hasActivePeriod = (bool) $activePeriod;

        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403);

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'faculty.facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
            ]);
        $this->applyDepartmentFilter($query, $departmentId);

        if ($status === 'submitted') {
            $query->whereIn('status', [
                'dean_review',
                'hr_review',
                'vpaa_review',
                'vpaa_approved',
                'president_review',
                'finalized',
                'rejected_final',
            ]);
        } elseif ($status === 'all') {
            $query->where('status', '!=', 'draft');
        } else {
            $query->where('status', $status);
        }
        $this->applyPeriodScope($query, $activePeriod);
        $this->applyRankLevelFilter($query, $rankLevelId);

        if ($q !== '') {
            $like = $this->likeOperator();
            $query->where(function ($builder) use ($q, $like) {
                $builder->whereHas('faculty', function ($faculty) use ($q, $like) {
                    $faculty->where('name', $like, "%{$q}%")
                        ->orWhere('email', $like, "%{$q}%");
                })->orWhereHas('faculty.facultyProfile', function ($profile) use ($q, $like) {
                    $profile->where('employee_no', $like, "%{$q}%");
                });
            });
        }

        $applications = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends([
                'q' => $q,
                'status' => $status,
                'rank_level_id' => $rankLevelId,
            ]);

        $cycleYears = collect();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();

        return view('reclassification.dean.submissions', compact(
            'applications',
            'cycleYears',
            'rankLevels',
            'q',
            'status',
            'rankLevelId',
            'activePeriod',
            'hasActivePeriod',
        ));
    }

    public function approved(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $activePeriod = $this->activePeriod();
        $hasActivePeriod = (bool) $activePeriod;

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'approvedBy',
            ])
            ->where('status', 'finalized');
        $this->applyPeriodScope($query, $activePeriod);

        $this->applyApprovedFilters($query, $q, $departmentId, null, $rankLevelId);

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('finalized_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'department_id' => $departmentId,
                'rank_level_id' => $rankLevelId,
            ]);

        $departments = Department::orderBy('name')->get();
        $cycleYears = collect();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();

        $title = 'Approved Reclassifications';
        $subtitle = 'Active-period applications finalized after final approval.';
        $indexRoute = route('reclassification.admin.approved');
        $backRoute = route('dashboard');
        $showDepartmentFilter = true;
        $showCycleFilter = false;
        $showVpaaActions = false;
        $showPresidentActions = false;
        $enforceActivePeriod = true;
        $exportPeriodId = null;
        $batchReadyCount = 0;
        $batchBlockingCount = 0;
        $cycleYear = null;

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'rankLevels',
            'q',
            'departmentId',
            'rankLevelId',
            'cycleYear',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showCycleFilter',
            'showVpaaActions',
            'showPresidentActions',
            'enforceActivePeriod',
            'exportPeriodId',
            'activePeriod',
            'hasActivePeriod',
            'batchReadyCount',
            'batchBlockingCount'
        ));
    }

    public function deanApproved(Request $request)
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403);

        $q = trim((string) $request->get('q', ''));
        $rankLevelId = $request->get('rank_level_id');
        $activePeriod = $this->activePeriod();
        $hasActivePeriod = (bool) $activePeriod;

        $query = ReclassificationApplication::query()
            ->with(['faculty.department', 'approvedBy'])
            ->where('status', 'finalized');
        $this->applyDepartmentFilter($query, $departmentId);
        $this->applyPeriodScope($query, $activePeriod);

        $this->applyApprovedFilters($query, $q, $departmentId, null, $rankLevelId);

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('finalized_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'rank_level_id' => $rankLevelId,
            ]);

        $departments = Department::where('id', $departmentId)->get();
        $cycleYears = collect();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();

        $title = 'Approved Reclassifications';
        $subtitle = 'Active-period finalized applications in your assigned department.';
        $indexRoute = route('dean.approved');
        $backRoute = route('dashboard');
        $showDepartmentFilter = false;
        $showCycleFilter = false;
        $showVpaaActions = false;
        $showPresidentActions = false;
        $enforceActivePeriod = true;
        $exportPeriodId = null;
        $batchReadyCount = 0;
        $batchBlockingCount = 0;
        $cycleYear = null;

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'rankLevels',
            'q',
            'departmentId',
            'rankLevelId',
            'cycleYear',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showCycleFilter',
            'showVpaaActions',
            'showPresidentActions',
            'enforceActivePeriod',
            'exportPeriodId',
            'activePeriod',
            'hasActivePeriod',
            'batchReadyCount',
            'batchBlockingCount'
        ));
    }

    public function reviewerApproved(Request $request)
    {
        $role = strtolower((string) $request->user()->role);
        abort_unless(in_array($role, ['vpaa', 'president'], true), 403);

        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $activePeriod = $this->activePeriod();

        $statusScope = $role === 'vpaa'
            ? ['vpaa_approved']
            : ['president_review'];

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'faculty.facultyProfile',
                'faculty.facultyHighestDegree',
                'sections.entries',
                'approvedBy',
            ])
            ->whereIn('status', $statusScope);
        $this->applyPeriodScope($query, $activePeriod);

        $this->applyApprovedFilters($query, $q, $departmentId, null, $rankLevelId);

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'department_id' => $departmentId,
                'rank_level_id' => $rankLevelId,
            ]);
        $this->attachRankPreviews($applications);

        $departments = Department::orderBy('name')->get();
        $cycleYears = collect();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();

        $title = $role === 'vpaa'
            ? 'VPAA Endorsement List'
            : 'President Approval List';
        $subtitle = $role === 'vpaa'
            ? 'Submissions approved by VPAA and pending batch forward to President.'
            : 'Finalize active-cycle submissions approved by VPAA.';
        $indexRoute = route('reclassification.review.approved');
        $backRoute = route('dashboard');
        $showDepartmentFilter = true;
        $showCycleFilter = false;
        $showVpaaActions = $role === 'vpaa';
        $showPresidentActions = $role === 'president';
        $allowExportActions = false;
        $enforceActivePeriod = true;
        $exportPeriodId = null;

        $batchReadyCount = 0;
        $batchBlockingCount = 0;
        if ($activePeriod) {
            if ($role === 'vpaa') {
                $readyQuery = ReclassificationApplication::query()
                    ->where('status', 'vpaa_approved');
                $this->applyPeriodScope($readyQuery, $activePeriod);
                $batchReadyCount = $readyQuery->count();

                $blockingQuery = ReclassificationApplication::query()
                    ->whereIn('status', ['dean_review', 'hr_review', 'vpaa_review', 'returned_to_faculty']);
                $this->applyPeriodScope($blockingQuery, $activePeriod);
                $batchBlockingCount = $blockingQuery->count();
            } else {
                $readyQuery = ReclassificationApplication::query()
                    ->where('status', 'president_review');
                $this->applyPeriodScope($readyQuery, $activePeriod);
                $batchReadyCount = $readyQuery->count();

                $blockingQuery = ReclassificationApplication::query()
                    ->whereIn('status', ['dean_review', 'hr_review', 'vpaa_review', 'returned_to_faculty']);
                $this->applyPeriodScope($blockingQuery, $activePeriod);
                $batchBlockingCount = $blockingQuery->count();
            }
        }

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'rankLevels',
            'q',
            'departmentId',
            'rankLevelId',
            'activePeriod',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showCycleFilter',
            'showVpaaActions',
            'showPresidentActions',
            'allowExportActions',
            'enforceActivePeriod',
            'exportPeriodId',
            'batchReadyCount',
            'batchBlockingCount'
        ));
    }

    public function reviewerFinalized(Request $request)
    {
        $role = strtolower((string) $request->user()->role);
        abort_unless(in_array($role, ['vpaa', 'president'], true), 403);

        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $activePeriod = $this->activePeriod();
        $hasActivePeriod = (bool) $activePeriod;

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'approvedBy',
            ])
            ->where('status', 'finalized');
        $this->applyPeriodScope($query, $activePeriod);
        $this->applyApprovedFilters($query, $q, $departmentId, null, $rankLevelId);

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('finalized_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'department_id' => $departmentId,
                'rank_level_id' => $rankLevelId,
            ]);

        $departments = Department::orderBy('name')->get();
        $cycleYears = collect();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();

        $title = 'Approved Reclassification';
        $subtitle = 'President-approved submissions for the active period.';
        $indexRoute = route('reclassification.review.finalized');
        $backRoute = route('dashboard');
        $showDepartmentFilter = true;
        $showCycleFilter = false;
        $showVpaaActions = false;
        $showPresidentActions = false;
        $allowExportActions = true;
        $enforceActivePeriod = true;
        $exportPeriodId = null;
        $batchReadyCount = 0;
        $batchBlockingCount = 0;
        $cycleYear = null;

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'cycleYears',
            'rankLevels',
            'q',
            'departmentId',
            'rankLevelId',
            'cycleYear',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showCycleFilter',
            'showVpaaActions',
            'showPresidentActions',
            'allowExportActions',
            'enforceActivePeriod',
            'exportPeriodId',
            'activePeriod',
            'hasActivePeriod',
            'batchReadyCount',
            'batchBlockingCount'
        ));
    }

    public function forwardApprovedToPresident(Request $request)
    {
        abort_unless(strtolower((string) $request->user()->role) === 'vpaa', 403);

        $activePeriod = $this->activePeriod();
        if (!$activePeriod) {
            return back()->withErrors([
                'approved_list' => 'No active submission period found. Open a period first.',
            ]);
        }

        $readyQuery = ReclassificationApplication::query()
            ->where('status', 'vpaa_approved');
        $this->applyPeriodScope($readyQuery, $activePeriod);
        $readyApps = (clone $readyQuery)
            ->with(['faculty', 'period'])
            ->get();
        $readyCount = $readyApps->count();
        if ($readyCount === 0) {
            return back()->withErrors([
                'approved_list' => 'No VPAA-approved submissions found in VPAA Approved List.',
            ]);
        }

        foreach ($readyApps as $app) {
            $fromStatus = (string) ($app->status ?? '');
            $fromStep = (string) ($app->current_step ?? '');
            $app->update([
                'status' => 'president_review',
                'current_step' => 'president',
                'returned_from' => null,
            ]);
            $this->recordStatusTrail(
                $app,
                $fromStatus,
                'president_review',
                $fromStep,
                'president',
                'forward_approved_list',
                'Forwarded to President from VPAA approved list.',
                [],
                $request->user()
            );
        }

        app(ReclassificationNotificationService::class)
            ->notifyApprovedListForwardedToPresident($readyApps, $activePeriod);

        return back()->with('success', "{$readyCount} active-cycle submissions forwarded to President.");
    }

    public function finalizeApprovedByPresident(Request $request)
    {
        abort_unless(strtolower((string) $request->user()->role) === 'president', 403);

        $activePeriod = $this->activePeriod();
        if (!$activePeriod) {
            return back()->withErrors([
                'approved_list' => 'No active submission period found. Open a period first.',
            ]);
        }

        $appsQuery = ReclassificationApplication::query()
            ->where('status', 'president_review')
            ->with(['sections.entries', 'faculty.facultyProfile', 'faculty.facultyHighestDegree'])
            ;
        $this->applyPeriodScope($appsQuery, $activePeriod);
        $apps = $appsQuery->get();

        if ($apps->isEmpty()) {
            return back()->withErrors([
                'approved_list' => 'No President-review submissions found to finalize.',
            ]);
        }

        $workflow = app(ReclassificationWorkflowController::class);
        foreach ($apps as $app) {
            $workflow->finalizeForApproval($app, $request->user());
        }

        $wasSubmissionOpen = (bool) $activePeriod->is_open;
        if (Schema::hasColumn('reclassification_periods', 'is_open')) {
            $activePeriod->forceFill([
                'is_open' => false,
            ])->save();
        }

        if ($wasSubmissionOpen) {
            app(ReclassificationNotificationService::class)
                ->notifySubmissionClosed($activePeriod->fresh());
        }

        return back()->with('success', "{$apps->count()} active-cycle submissions finalized and promotions applied. Submission is now closed for this period.");
    }

    public function approvedExportCsv(Request $request)
    {
        [$role, $departmentId] = $this->ensureReviewerHistoryRole($request);
        $q = trim((string) $request->get('q', ''));
        $requestedDepartmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $periodId = $request->get('period_id');

        $period = null;
        if (!empty($periodId)) {
            $period = ReclassificationPeriod::find($periodId);
        }
        if (!$period) {
            $period = $this->activePeriod();
        }

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'faculty.facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
            ])
            ->where('status', 'finalized');

        $this->applyPeriodScope($query, $period);

        $this->applyDepartmentFilter($query, $role === 'dean' ? $departmentId : $requestedDepartmentId);

        $this->applyFacultySearch($query, $q);

        $this->applyRankLevelFilter($query, $rankLevelId);

        $rows = $query
            ->orderBy('approved_at')
            ->orderBy('id')
            ->get();

        $filenameCycle = trim((string) ($period?->cycle_year ?? 'no-active-period'));
        $filenameDate = now()->format('Ymd_His');
        $filename = "approved_reclassifications_{$filenameCycle}_{$filenameDate}.csv";

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Name',
                'Department',
                'Current Rank',
                'Approved Rank',
            ]);

            foreach ($rows as $app) {
                $profile = $app->faculty?->facultyProfile;
                $fallbackCurrentRank = $profile?->rankLevel?->title
                    ?: trim((string) (($profile?->teaching_rank ?? '') . (($profile?->rank_step ?? '') !== '' ? ' - ' . $profile->rank_step : '')));
                $currentRank = $app->current_rank_label_at_approval ?: ($fallbackCurrentRank ?: '-');
                $approvedRank = $app->approved_rank_label ?: $currentRank;

                fputcsv($handle, [
                    (string) ($app->faculty?->name ?? 'Faculty'),
                    (string) ($app->faculty?->department?->name ?? '-'),
                    (string) $currentRank,
                    (string) $approvedRank,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function approvedPrint(Request $request)
    {
        [$role, $departmentId] = $this->ensureReviewerHistoryRole($request);
        $q = trim((string) $request->get('q', ''));
        $requestedDepartmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $periodId = $request->get('period_id');

        $period = null;
        if (!empty($periodId)) {
            $period = ReclassificationPeriod::find($periodId);
        }
        if (!$period) {
            $period = $this->activePeriod();
        }

        $query = ReclassificationApplication::query()
            ->with([
                'faculty.department',
                'faculty.facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
            ])
            ->where('status', 'finalized');

        $this->applyPeriodScope($query, $period);

        $this->applyDepartmentFilter($query, $role === 'dean' ? $departmentId : $requestedDepartmentId);

        $this->applyFacultySearch($query, $q);

        $this->applyRankLevelFilter($query, $rankLevelId);

        $applications = $query
            ->orderBy('approved_at')
            ->orderBy('id')
            ->get();
        $filterDepartmentId = $role === 'dean' ? $departmentId : $requestedDepartmentId;
        $filterDepartmentLabel = null;
        if (!empty($filterDepartmentId)) {
            $filterDepartmentLabel = Department::where('id', $filterDepartmentId)->value('name');
        }
        $filterRankLabel = null;
        if (!empty($rankLevelId) && Schema::hasTable('rank_levels')) {
            $filterRankLabel = \App\Models\RankLevel::where('id', $rankLevelId)->value('title');
        }

        return view('reclassification.admin.approved-print', compact(
            'applications',
            'period',
            'filterDepartmentLabel',
            'filterRankLabel',
            'q'
        ));
    }

    public function history(Request $request)
    {
        [$role, $departmentId] = $this->ensureReviewerHistoryRole($request);
        $q = trim((string) $request->get('q', ''));
        $requestedDepartmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $filterDepartmentId = $role === 'dean' ? $departmentId : $requestedDepartmentId;
        $periods = collect();
        if (Schema::hasTable('reclassification_periods')) {
            $hasCycleYear = Schema::hasColumn('reclassification_periods', 'cycle_year');
            $hasStatus = Schema::hasColumn('reclassification_periods', 'status');
            $like = $this->likeOperator();
            $periods = ReclassificationPeriod::query()
                ->when(
                    $hasStatus,
                    fn ($query) => $query->where('status', 'ended'),
                    fn ($query) => $query->where('is_open', false)
                )
                ->when($q !== '', function ($query) use ($q, $hasCycleYear, $like) {
                    $query->where(function ($builder) use ($q, $hasCycleYear, $like) {
                        $builder->where('name', $like, "%{$q}%");
                        if ($hasCycleYear) {
                            $builder->orWhere('cycle_year', $like, "%{$q}%");
                        }
                    });
                })
                ->orderByDesc('created_at')
                ->get()
                ->map(function (ReclassificationPeriod $period) use ($filterDepartmentId, $rankLevelId) {
                    $approvedQuery = ReclassificationApplication::query()
                        ->where('status', 'finalized');
                    $this->applyPeriodScope($approvedQuery, $period);
                    $this->applyDepartmentFilter($approvedQuery, $filterDepartmentId);
                    $this->applyRankLevelFilter($approvedQuery, $rankLevelId);
                    $approvedCount = (clone $approvedQuery)->count();

                    $submissionQuery = ReclassificationApplication::query()
                        ->where('status', '!=', 'draft');
                    $this->applyPeriodScope($submissionQuery, $period);
                    $this->applyDepartmentFilter($submissionQuery, $filterDepartmentId);
                    $this->applyRankLevelFilter($submissionQuery, $rankLevelId);
                    $submissionCount = (clone $submissionQuery)->count();

                    $period->approved_count = $approvedCount;
                    $period->submission_count = $submissionCount;
                    return $period;
                });

            if (!$hasCycleYear) {
                $periods = $periods->map(function (ReclassificationPeriod $period) {
                    if (empty($period->cycle_year)) {
                        $period->cycle_year = '-';
                    }
                    return $period;
                });
            }
        }

        $departments = $role === 'dean'
            ? Department::where('id', $departmentId)->get()
            : Department::orderBy('name')->get();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();
        $showDepartmentFilter = $role !== 'dean';

        $title = 'Reclassification History';
        $subtitle = $role === 'dean'
            ? 'Ended period history for your department.'
            : 'Ended reclassification periods and approved outputs.';
        $indexRoute = route('reclassification.history');
        $backRoute = route('dashboard');

        return view('reclassification.admin.history', compact(
            'periods',
            'q',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'departments',
            'rankLevels',
            'filterDepartmentId',
            'rankLevelId',
            'showDepartmentFilter',
        ));
    }

    public function historyPeriod(Request $request, ReclassificationPeriod $period)
    {
        [$role, $departmentId] = $this->ensureReviewerHistoryRole($request);
        $q = trim((string) $request->get('q', ''));
        $requestedDepartmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');
        $filterDepartmentId = $role === 'dean' ? $departmentId : $requestedDepartmentId;

        if (Schema::hasColumn('reclassification_periods', 'status')) {
            abort_unless((string) $period->status === 'ended', 404);
        } else {
            abort_unless((bool) $period->is_open === false, 404);
        }

        $query = ReclassificationApplication::query()
            ->with(['faculty.department', 'approvedBy'])
            ->where('status', 'finalized');
        $this->applyPeriodScope($query, $period);

        $this->applyDepartmentFilter($query, $filterDepartmentId);
        $this->applyRankLevelFilter($query, $rankLevelId);

        $this->applyFacultySearch($query, $q);

        $applications = $query
            ->orderByDesc('approved_at')
            ->orderByDesc('finalized_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends([
                'q' => $q,
                'department_id' => $requestedDepartmentId,
                'rank_level_id' => $rankLevelId,
            ]);

        $title = 'Approved List by Period';
        $cycleLabel = trim((string) ($period->cycle_year ?? '')) !== '' ? $period->cycle_year : 'No cycle set';
        $subtitle = "{$period->name} ({$cycleLabel})";
        $indexRoute = route('reclassification.history.period', $period);
        $backRoute = route('reclassification.history');
        $showDepartmentFilter = false;
        $showCycleFilter = false;
        $showVpaaActions = false;
        $showPresidentActions = false;
        $enforceActivePeriod = false;
        $exportPeriodId = $period->id;
        $batchReadyCount = 0;
        $batchBlockingCount = 0;
        $departments = $role === 'dean'
            ? Department::where('id', $departmentId)->get()
            : Department::orderBy('name')->get();
        $rankLevels = Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();
        $cycleYears = collect();
        $showDepartmentFilter = $role !== 'dean';
        $departmentId = $requestedDepartmentId;
        $cycleYear = null;
        $activePeriod = null;

        return view('reclassification.admin.approved', compact(
            'applications',
            'departments',
            'rankLevels',
            'cycleYears',
            'q',
            'departmentId',
            'rankLevelId',
            'cycleYear',
            'title',
            'subtitle',
            'indexRoute',
            'backRoute',
            'showDepartmentFilter',
            'showCycleFilter',
            'showVpaaActions',
            'showPresidentActions',
            'enforceActivePeriod',
            'exportPeriodId',
            'batchReadyCount',
            'batchBlockingCount',
            'activePeriod'
        ));
    }
}
