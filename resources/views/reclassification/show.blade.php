<x-app-layout>
    @php
        $activeParam = request()->get('tab');
        $active = $activeParam === 'review'
            ? 'review'
            : (int) request()->route('number', 1);
        if ($active !== 'review' && ($active < 1 || $active > 5)) $active = 1;

        $allCommentThreads = collect($commentThreads ?? [])->values();
        $requiredCommentThreads = $allCommentThreads
            ->filter(fn ($thread) => (string) ($thread->action_type ?? 'requires_action') === 'requires_action')
            ->values();
        $infoCommentThreads = $allCommentThreads
            ->filter(fn ($thread) => (string) ($thread->action_type ?? 'requires_action') === 'info')
            ->values();
        $totalCommentThreadCount = $requiredCommentThreads->count() + $infoCommentThreads->count();

        $currentReviewerRole = strtolower(trim((string) ($application->returned_from ?? '')));
        $snapshotThreadsForTracker = collect(collect($commentSnapshots ?? [])->last()['threads'] ?? [])->values();
        $trackerThreads = $snapshotThreadsForTracker->isNotEmpty() ? $snapshotThreadsForTracker : $allCommentThreads;
        if ($currentReviewerRole !== '') {
            $trackerThreads = $trackerThreads
                ->filter(function ($thread) use ($currentReviewerRole) {
                    return strtolower((string) ($thread->author?->role ?? '')) === $currentReviewerRole;
                })
                ->values();
        }

        $trackerRequiredCommentThreads = $trackerThreads
            ->filter(fn ($thread) => (string) ($thread->action_type ?? 'requires_action') === 'requires_action')
            ->values();
        $trackerInfoCommentThreads = $trackerThreads
            ->filter(fn ($thread) => (string) ($thread->action_type ?? 'requires_action') === 'info')
            ->values();

        $openRequiredCommentCount = $trackerRequiredCommentThreads
            ->filter(fn ($thread) => (string) ($thread->status ?? 'open') === 'open')
            ->count();
        $addressedRequiredCommentCount = $trackerRequiredCommentThreads
            ->filter(fn ($thread) => (string) ($thread->status ?? 'open') === 'addressed')
            ->count();
        $resolvedRequiredCommentCount = $trackerRequiredCommentThreads
            ->filter(fn ($thread) => (string) ($thread->status ?? 'open') === 'resolved')
            ->count();
        $totalRequiredCommentCount = $trackerRequiredCommentThreads->count();
        $completedRequiredCommentCount = $addressedRequiredCommentCount + $resolvedRequiredCommentCount;
        $requiredCommentProgress = $totalRequiredCommentCount > 0
            ? (int) round(($completedRequiredCommentCount / $totalRequiredCommentCount) * 100)
            : 100;
        $criterionLabels = [
            '1' => [
                'a1' => 'A1. Bachelor\'s Degree (Latin honors)',
                'a2' => 'A2. Additional Bachelor\'s Degree',
                'a3' => 'A3. Master\'s Degree',
                'a4' => 'A4. Master\'s Degree Units',
                'a5' => 'A5. Additional Master\'s Degree',
                'a6' => 'A6. Doctoral Units',
                'a7' => 'A7. Doctor\'s Degree',
                'a8' => 'A8. Qualifying Government Examinations',
                'a9' => 'A9. International/National Certifications',
                'b' => 'B. Advanced/Specialized Training',
                'c' => 'C. Short-term Workshops/Seminars',
                'b_prev' => 'B. Previous Reclassification (1/3)',
                'c_prev' => 'C. Previous Reclassification (1/3)',
            ],
            '2' => [
                'ratings' => 'Instructional Competence Ratings',
                'previous_points' => 'Previous Reclassification (1/3)',
            ],
            '3' => [
                'c1' => 'C1. Book Authorship',
                'c2' => 'C2. Workbook/Module',
                'c3' => 'C3. Instructional Materials',
                'c4' => 'C4. Refereed Articles',
                'c5' => 'C5. Research Papers',
                'c6' => 'C6. Research Inventions/Patents',
                'c7' => 'C7. Artistic Works',
                'c8' => 'C8. Editorial Work',
                'c9' => 'C9. Professional Output',
                'previous_points' => 'Previous Reclassification (1/3)',
            ],
            '4' => [
                'a1' => 'A1. Actual Services Outside BU',
                'a2' => 'A2. Actual Services at BU',
                'b' => 'B. Industrial/Professional Experience',
            ],
            '5' => [
                'a' => 'A. Membership/Leadership',
                'b' => 'B. Awards/Recognition',
                'c1' => 'C1. Curriculum Development',
                'c2' => 'C2. Extension/Outreach',
                'c3' => 'C3. University Activities',
                'd' => 'D. Community Involvement',
                'b_prev' => 'B. Previous Reclassification (1/3)',
                'c_prev' => 'C. Previous Reclassification (1/3)',
                'd_prev' => 'D. Previous Reclassification (1/3)',
                'previous_points' => 'Previous Reclassification (1/3)',
            ],
        ];
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Faculty Reclassification</h2>
                <p class="text-sm text-gray-500">
                    Review and complete your reclassification requirements.
                </p>
            </div>

            @php
                $status = $application->status ?? 'draft';
                $returnedFrom = strtolower(trim((string) ($application->returned_from ?? '')));
                $returnedFromLabel = match($returnedFrom) {
                    'dean' => 'Dean',
                    'hr' => 'HR',
                    'vpaa' => 'VPAA',
                    'president' => 'President',
                    default => 'Reviewer',
                };
                $statusLabel = match($status) {
                    'draft' => 'Draft',
                    'returned_to_faculty' => "Returned by {$returnedFromLabel}",
                    'dean_review' => 'Dean',
                    'hr_review' => 'HR',
                    'vpaa_review' => 'VPAA',
                    'vpaa_approved' => 'VPAA Approved',
                    'president_review' => 'President',
                    'finalized' => 'Finalized',
                    'rejected_final' => 'Rejected',
                    default => ucfirst(str_replace('_',' ', $status)),
                };

                $statusClass = match($status) {
                    'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
                    'returned_to_faculty' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'finalized' => 'bg-green-50 text-green-700 border-green-200',
                    'rejected_final' => 'bg-red-50 text-red-700 border-red-200',
                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                };

                $canEdit = in_array($status, ['draft', 'returned_to_faculty'], true);
                $canSubmitByPeriod = $status === 'returned_to_faculty'
                    ? true
                    : (bool) ($submissionWindowOpen ?? true);
                $resubmitLockedByComments = $status === 'returned_to_faculty' && $openRequiredCommentCount > 0;
            @endphp

            <div id="faculty-header-actions"
                 data-current-status="{{ (string) $status }}"
                 data-can-edit="{{ $canEdit ? '1' : '0' }}"
                 class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

                @if($canEdit)
                    <div class="inline-flex items-center gap-2">
                        <button type="button"
                                id="header-save-draft-btn"
                                onclick="window.saveDraftAll && window.saveDraftAll()"
                                class="inline-flex items-center px-3 py-2 rounded-xl border border-gray-200 text-sm font-semibold
                                       text-gray-700 hover:bg-gray-50 transition disabled:opacity-60 disabled:cursor-not-allowed">
                            <span id="header-save-draft-label">Save Draft</span>
                        </button>
                        <span id="header-save-draft-status"
                              class="hidden inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[11px] font-semibold text-green-700">
                            Saved
                        </span>
                    </div>

                    @if($status === 'returned_to_faculty')
                        <button type="button"
                                @disabled(!$canSubmitByPeriod || $resubmitLockedByComments)
                                onclick="window.reclassificationFinalSubmit && window.reclassificationFinalSubmit()"
                                title="{{ $resubmitLockedByComments ? 'Please address all action-required reviewer comments first.' : '' }}"
                                class="inline-flex items-center px-3 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft transition {{ ($canSubmitByPeriod && !$resubmitLockedByComments) ? 'hover:bg-bu-dark' : 'opacity-60 cursor-not-allowed' }}">
                            Resubmit
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $initialSections = $application->sections->keyBy('section_code')->map(function ($s) {
            return [
                'points' => (float) $s->points_total,
                'max' => $s->section_code === '1'
                    ? 140
                    : ($s->section_code === '2'
                        ? 120
                        : ($s->section_code === '3'
                            ? 70
                            : ($s->section_code === '4' ? 40 : 30))),
            ];
        });
    @endphp

    <div id="faculty-reclassification-root"
         data-async-state-keys="active,showScores,commentsPanelOpen,commentsSidebarCollapsed"
         class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="reclassificationWizard()"
         x-init="init()"
         @review-nav.window="navTo($event.detail.target)">

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif
        @if (($application->status ?? '') === 'returned_to_faculty')
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ "Returned by {$returnedFromLabel}: all fields are now editable. Your changes are logged for reviewer verification." }}
            </div>
        @endif
        @if (($application->status ?? '') === 'rejected_final')
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                This application has been final rejected for this period and can no longer be submitted.
            </div>
        @endif
        @if(!($submissionWindowOpen ?? true) && ($application->status ?? '') === 'draft')
            <div x-show="active === 'review'" x-cloak class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold">
                    Final submission is closed
                </div>
                <div>
                    {{ $submissionWindowTitle ?? 'No ongoing reclassification submission' }}.
                    {{ $submissionWindowMessage ?? 'You can still save your draft.' }}
                </div>
            </div>
        @endif
        @if(!($submissionWindowOpen ?? true) && ($application->status ?? '') === 'returned_to_faculty')
            <div x-show="active === 'review'" x-cloak class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <div class="font-semibold">Resubmission is allowed for returned applications</div>
                <div>
                    Submission window is closed for new submissions, but this returned paper can still be resubmitted.
                </div>
            </div>
        @endif
        @if($totalCommentThreadCount > 0)
            <button type="button"
                    id="faculty-comment-fab"
                    @click="commentsPanelOpen = true; if (window.__facultyCommentsPanel) { window.__facultyCommentsPanel.setFilter('open'); }"
                    class="group fixed top-20 sm:top-24 right-6 z-[52] inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50"
                    aria-label="Open reviewer comments"
                    title="Open reviewer comments">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M18 10c0 3.866-3.582 7-8 7a8.94 8.94 0 01-3.705-.77L2 17.5l1.346-3.364A6.735 6.735 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" />
                </svg>
                <span class="absolute -top-1 -right-1 inline-flex min-w-5 justify-center rounded-full bg-bu px-1.5 py-0.5 text-[11px] text-white">
                    {{ $totalCommentThreadCount }}
                </span>
                <span class="pointer-events-none absolute right-0 -top-8 whitespace-nowrap rounded-md bg-black px-2 py-1 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                    Open comments
                </span>
            </button>
        @endif

        <div id="faculty-comments-panel"
             x-show="commentsPanelOpen"
             x-bind:data-open="commentsPanelOpen ? '1' : '0'"
             x-cloak
             class="fixed top-0 right-0 h-screen z-[60] w-full max-w-lg bg-white shadow-2xl border-l border-gray-200 flex flex-col transition-all duration-200">
            <div class="px-3 py-3 border-b bg-gray-50 flex items-center justify-between gap-2">
                <div>
                    <div class="text-sm font-semibold text-gray-900">Reviewer's Comments</div>
                    <div class="text-xs text-gray-500">Track feedback while completing your paper.</div>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button"
                            @click="commentsPanelOpen = false"
                            class="px-2.5 py-1 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-100">
                        Close
                    </button>
                </div>
            </div>

            <div id="faculty-comment-threads"
                 class="flex-1 overflow-y-auto p-4 space-y-4"
                 data-async-state-keys="filterMode,openGroups"
                 x-data="{
                    filterStorageKey: 'faculty_comments_filter_{{ (int) $application->id }}',
                    filterMode: 'open',
                    openGroups: {},
                    setFilter(mode = 'open') {
                        this.filterMode = String(mode || 'open');
                        try {
                            window.localStorage.setItem(this.filterStorageKey, this.filterMode);
                        } catch (error) {}
                    },
                    init() {
                        try {
                            const saved = window.localStorage.getItem(this.filterStorageKey);
                            if (saved) {
                                const normalized = String(saved);
                                this.filterMode = normalized === 'all' ? 'open' : normalized;
                            }
                        } catch (error) {}

                        window.__facultyCommentsPanel = {
                            setFilter: (mode = 'open') => {
                                this.setFilter(mode);
                            },
                        };
                        this.$nextTick(() => {
                            window.dispatchEvent(new CustomEvent('faculty-comments-toggle-resolved', {
                                detail: { enabled: true }
                            }));
                        });
                    },
                    matchesFilter(type, status) {
                        const t = String(type || 'requires_action');
                        const s = String(status || 'open');
                        if (this.filterMode === 'notes') return t === 'info';
                        if (this.filterMode === 'addressed') return t !== 'info' && s === 'addressed';
                        if (this.filterMode === 'resolved') return t !== 'info' && s === 'resolved';
                        if (this.filterMode === 'open') return t !== 'info' && s === 'open';
                        return false;
                    },
                    hasVisibleInGroup(items) {
                        return (items || []).some((item) => this.matchesFilter(item.type, item.status));
                    },
                    countFor(mode, items) {
                        return (items || []).filter((item) => {
                            if (mode === 'notes') return item.type === 'info';
                            if (mode === 'addressed') return item.type !== 'info' && item.status === 'addressed';
                            if (mode === 'resolved') return item.type !== 'info' && item.status === 'resolved';
                            if (mode === 'open') return item.type !== 'info' && item.status === 'open';
                            return false;
                        }).length;
                    },
                    groupKey(snapshotIndex, sectionCode) {
                        return `${String(snapshotIndex)}::${String(sectionCode)}`;
                    },
                    isGroupOpen(snapshotIndex, sectionCode) {
                        return !!this.openGroups[this.groupKey(snapshotIndex, sectionCode)];
                    },
                    toggleGroup(snapshotIndex, sectionCode) {
                        const key = this.groupKey(snapshotIndex, sectionCode);
                        this.openGroups[key] = !this.isGroupOpen(snapshotIndex, sectionCode);
                    }
                 }">
                <div id="faculty-comment-overview"
                     data-open-required-count="{{ (int) $openRequiredCommentCount }}"
                     class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                    <div class="font-semibold text-slate-900">Comments tracker</div>
                    <div class="text-xs text-slate-600 mt-0.5">
                        Open: <span data-tracker-open>{{ $openRequiredCommentCount }}</span> &middot;
                        Addressed: <span data-tracker-addressed>{{ $addressedRequiredCommentCount }}</span> &middot;
                        Resolved: <span data-tracker-resolved>{{ $resolvedRequiredCommentCount }}</span> &middot;
                        Notes: <span data-tracker-notes>{{ $trackerInfoCommentThreads->count() }}</span>.
                        <span data-tracker-hint>
                            @if($openRequiredCommentCount === 0)
                                You can resubmit.
                            @endif
                        </span>
                    </div>
                    <div class="mt-2">
                        <div class="mb-1 flex items-center justify-between text-[11px] text-slate-600">
                            <span>Action-required progress</span>
                            <span data-tracker-progress-label>{{ $completedRequiredCommentCount }}/{{ $totalRequiredCommentCount }} ({{ $requiredCommentProgress }}%)</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full bg-bu transition-all duration-300"
                                 data-tracker-progress-bar
                                 style="width: {{ $requiredCommentProgress }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-2">
                    @php
                        $commentItemsForCount = collect($commentThreads ?? [])->map(function ($thread) {
                            return [
                                'type' => (string) ($thread->action_type ?? 'requires_action'),
                                'status' => (string) ($thread->status ?? 'open'),
                            ];
                        })->values();
                    @endphp
                    <div class="grid grid-cols-4 gap-2 text-xs">
                        <button type="button"
                                @click="setFilter('open')"
                                :class="filterMode === 'open' ? 'bg-bu text-white border-bu' : 'bg-white text-gray-700 border-gray-300'"
                                class="px-3 py-2 rounded-lg border font-semibold transition">
                            Action Required (<span x-text="countFor('open', @js($commentItemsForCount->all()))"></span>)
                        </button>
                        <button type="button"
                                @click="setFilter('addressed')"
                                :class="filterMode === 'addressed' ? 'bg-bu text-white border-bu' : 'bg-white text-gray-700 border-gray-300'"
                                class="px-3 py-2 rounded-lg border font-semibold transition">
                            Addressed (<span x-text="countFor('addressed', @js($commentItemsForCount->all()))"></span>)
                        </button>
                        <button type="button"
                                @click="setFilter('resolved')"
                                :class="filterMode === 'resolved' ? 'bg-bu text-white border-bu' : 'bg-white text-gray-700 border-gray-300'"
                                class="px-3 py-2 rounded-lg border font-semibold transition">
                            Resolved (<span x-text="countFor('resolved', @js($commentItemsForCount->all()))"></span>)
                        </button>
                        <button type="button"
                                @click="setFilter('notes')"
                                :class="filterMode === 'notes' ? 'bg-bu text-white border-bu' : 'bg-white text-gray-700 border-gray-300'"
                                class="px-3 py-2 rounded-lg border font-semibold transition">
                            Notes (<span x-text="countFor('notes', @js($commentItemsForCount->all()))"></span>)
                        </button>
                    </div>
                </div>

                @php
                    $criterionSortToken = function ($thread) {
                        $rawKey = strtoupper((string) ($thread->entry?->criterion_key ?? ''));
                        $normalized = preg_replace('/[^A-Z0-9]/', '', $rawKey);
                        if (!preg_match('/^([A-Z]+)(\d+)?(.*)$/', (string) $normalized, $match)) {
                            return 'ZZ|9999|' . $normalized;
                        }

                        $prefix = (string) ($match[1] ?? 'ZZ');
                        $number = (int) ($match[2] ?? 0);
                        $suffix = (string) ($match[3] ?? '');

                        return $prefix . '|' . str_pad((string) $number, 4, '0', STR_PAD_LEFT) . '|' . $suffix;
                    };
                    $commentSnapshotsCollection = collect($commentSnapshots ?? []);
                    $lastSnapshotIndex = max($commentSnapshotsCollection->count() - 1, 0);
                    $stageSortIndex = function (string $role): int {
                        return match (strtolower(trim($role))) {
                            'dean' => 10,
                            'hr' => 20,
                            'vpaa' => 30,
                            'president' => 40,
                            default => 90,
                        };
                    };
                    $stageLabel = function (string $role): string {
                        return match (strtolower(trim($role))) {
                            'dean' => 'Dean Stage',
                            'hr' => 'HR Stage',
                            'vpaa' => 'VPAA Stage',
                            'president' => 'President Stage',
                            default => 'Reviewer Stage',
                        };
                    };
                    $resolvedStageSnapshots = collect($commentThreads ?? [])
                        ->filter(fn ($thread) => (string) ($thread->action_type ?? 'requires_action') === 'requires_action')
                        ->filter(fn ($thread) => (string) ($thread->status ?? 'open') === 'resolved')
                        ->groupBy(function ($thread) {
                            return strtolower((string) ($thread->author?->role ?? 'reviewer'));
                        })
                        ->map(function ($threads, $roleKey) use ($stageLabel, $criterionSortToken) {
                            $sortedThreads = collect($threads)
                                ->sortBy(function ($thread) use ($criterionSortToken) {
                                    $criterionToken = $criterionSortToken($thread);
                                    $timestamp = optional($thread->created_at)->timestamp ?? 0;
                                    return $criterionToken . '|' . str_pad((string) $timestamp, 12, '0', STR_PAD_LEFT);
                                })
                                ->values();

                            $sectionGroups = $sortedThreads
                                ->groupBy(function ($thread) {
                                    $code = (string) ($thread->entry?->section?->section_code ?? '');
                                    return $code !== '' ? $code : 'other';
                                })
                                ->sortKeys(SORT_NATURAL)
                                ->map(function ($sectionThreads) {
                                    return collect($sectionThreads)->values();
                                });

                            return [
                                'role' => (string) $roleKey,
                                'label' => $stageLabel((string) $roleKey),
                                'threads' => $sortedThreads,
                                'section_groups' => $sectionGroups,
                            ];
                        })
                        ->sortBy(fn ($group) => $stageSortIndex((string) ($group['role'] ?? '')))
                        ->values();
                @endphp

                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600"
                     x-show="filterMode === 'open' && countFor('open', @js($commentItemsForCount->all())) === 0"
                     x-cloak>
                    All action-required comments are addressed.
                </div>

                <div class="space-y-2" x-show="filterMode === 'resolved'" x-cloak>
                    @forelse($resolvedStageSnapshots as $resolvedStageIndex => $resolvedStage)
                        @php
                            $resolvedStageThreads = collect($resolvedStage['threads'] ?? [])->values();
                        @endphp
                        <div class="space-y-2">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-xs font-semibold text-slate-800">
                                        {{ $resolvedStage['label'] ?? 'Reviewer Stage Resolved' }}
                                    </div>
                                    <div class="text-[11px] text-slate-600">
                                        {{ $resolvedStageThreads->count() }} resolved
                                    </div>
                                </div>
                            </div>

                            @foreach(($resolvedStage['section_groups'] ?? []) as $sectionCode => $sectionThreads)
                                @php
                                    $sectionItemsMeta = collect($sectionThreads)->map(function ($thread) {
                                        return [
                                            'type' => (string) ($thread->action_type ?? 'requires_action'),
                                            'status' => (string) ($thread->status ?? 'open'),
                                        ];
                                    })->values();
                                @endphp
                                <div class="rounded-lg border border-gray-200 bg-white overflow-hidden"
                                     x-show="hasVisibleInGroup(@js($sectionItemsMeta->all()))"
                                     x-cloak>
                                    <button type="button"
                                            @click="toggleGroup('resolved-{{ $resolvedStageIndex }}', '{{ $sectionCode }}')"
                                            class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50">
                                        <div class="min-w-0">
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                                                <span>{{ $sectionCode === 'other' ? 'Section Unmapped' : "Section {$sectionCode}" }}</span>
                                                <span class="text-gray-500">
                                                    (
                                                    <span x-text="countFor('resolved', @js($sectionItemsMeta->all()))"></span>
                                                    )
                                                </span>
                                            </div>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="h-4 w-4 text-gray-500 transition-transform"
                                             :class="isGroupOpen('resolved-{{ $resolvedStageIndex }}', '{{ $sectionCode }}') ? 'rotate-180' : ''"
                                             viewBox="0 0 20 20"
                                             fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <div x-show="isGroupOpen('resolved-{{ $resolvedStageIndex }}', '{{ $sectionCode }}')" class="px-3 pb-3 space-y-2">
                                        @foreach($sectionThreads as $thread)
                                            @php
                                                $commentType = (string) ($thread->action_type ?? 'requires_action');
                                                $status = (string) ($thread->status ?? 'open');
                                                $statusClass = 'bg-green-50 text-green-700 border-green-200';
                                                $statusLabel = 'Resolved by reviewer';
                                                $threadSection = $thread->entry?->section?->section_code;
                                                $threadSectionTarget = is_numeric((string) $threadSection)
                                                    ? (int) $threadSection
                                                    : (is_numeric((string) $sectionCode) ? (int) $sectionCode : 0);
                                                $threadCriterion = strtoupper((string) ($thread->entry?->criterion_key ?? ''));
                                                $threadCriterionKey = strtolower((string) ($thread->entry?->criterion_key ?? ''));
                                                $threadSectionCode = (string) ($thread->entry?->section?->section_code ?? $sectionCode);
                                                $threadCriterionLabel = $criterionLabels[$threadSectionCode][$threadCriterionKey] ?? ($threadCriterion ?: '-');
                                                $threadEntryId = (int) ($thread->entry?->id ?? 0);
                                            @endphp
                                            <div class="rounded-lg border border-gray-200 bg-white p-3 text-left space-y-2 cursor-pointer hover:border-bu/40 transition"
                                                 x-show="matchesFilter('{{ $commentType }}', '{{ $status }}')"
                                                 x-cloak
                                                 @click="openCommentTarget({ section: {{ $threadSectionTarget }}, entryId: {{ $threadEntryId }}, criterion: '{{ strtolower((string) ($thread->entry?->criterion_key ?? '')) }}' })">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-semibold text-gray-800 truncate">{{ $threadCriterionLabel }}</div>
                                                        <div class="text-xs text-gray-500">
                                                            {{ $thread->author?->name ?? 'Reviewer' }} - {{ optional($thread->created_at)->format('M d, Y g:i A') }}
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] {{ $statusClass }}">
                                                            {{ $statusLabel }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="text-[11px] font-semibold text-gray-500">Reviewer Comment:</div>
                                                <div class="text-sm leading-5 text-gray-800 break-words">{{ $thread->body }}</div>

                                                @if($thread->children->isNotEmpty())
                                                    <div class="rounded-md border-t border-gray-200 pt-1.5 space-y-1.5" @click.stop>
                                                        @foreach($thread->children->sortBy('created_at')->values() as $reply)
                                                            @php
                                                                $isFacultyReply = (int) ($reply->user_id ?? 0) === (int) ($application->faculty_user_id ?? 0);
                                                                $replyLabel = $isFacultyReply ? 'Faculty Reply' : 'Reviewer Comment';
                                                            @endphp
                                                            <div class="text-xs text-gray-700 @if(!$loop->first) border-t border-gray-200 pt-2 @endif">
                                                                <div class="font-semibold text-gray-800">
                                                                    {{ $reply->author?->name ?? 'Faculty' }} - {{ optional($reply->created_at)->format('M d, Y g:i A') }}
                                                                </div>
                                                                <div class="mt-0.5 text-[11px] font-semibold text-gray-500">{{ $replyLabel }}:</div>
                                                                <div class="mt-0.5 break-words text-sm leading-5 text-gray-800">{{ $reply->body }}</div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600">
                            No resolved comments yet.
                        </div>
                    @endforelse
                </div>

                @forelse($commentSnapshotsCollection as $snapshotIndex => $snapshot)
                    @php
                        $snapshotThreads = collect($snapshot['threads'] ?? [])->values();
                        $snapshotOpenCount = $snapshotThreads
                            ->filter(fn ($thread) => (string) ($thread->action_type ?? 'requires_action') === 'requires_action')
                            ->filter(fn ($thread) => (string) ($thread->status ?? 'open') === 'open')
                            ->count();
                        $snapshotItemsMeta = $snapshotThreads->map(function ($thread) {
                            return [
                                'type' => (string) ($thread->action_type ?? 'requires_action'),
                                'status' => (string) ($thread->status ?? 'open'),
                            ];
                        })->values();
                        $snapshotSectionGroups = $snapshotThreads
                            ->groupBy(function ($thread) {
                                $code = (string) ($thread->entry?->section?->section_code ?? '');
                                return $code !== '' ? $code : 'other';
                            })
                            ->sortKeys(SORT_NATURAL)
                            ->map(function ($sectionThreads) use ($criterionSortToken) {
                                return $sectionThreads
                                    ->sortBy(function ($thread) use ($criterionSortToken) {
                                        $criterionToken = $criterionSortToken($thread);
                                        $timestamp = optional($thread->created_at)->timestamp ?? 0;
                                        return $criterionToken . '|' . str_pad((string) $timestamp, 12, '0', STR_PAD_LEFT);
                                    })
                                    ->values();
                            });
                    @endphp
                    <div class="space-y-2"
                         x-show="
                            filterMode !== 'resolved' &&
                            (filterMode !== 'open' || countFor('open', @js($snapshotItemsMeta->all())) > 0) &&
                            (
                                (filterMode === 'open' || filterMode === 'addressed')
                                    ? {{ $snapshotIndex === $lastSnapshotIndex ? 'true' : 'false' }}
                                    : (filterMode === 'notes'
                                        ? (countFor('notes', @js($snapshotItemsMeta->all())) > 0)
                                        : true)
                            )
                         "
                         x-cloak>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <div>
                                <div class="text-xs font-semibold text-slate-800">{{ $snapshot['label'] ?? 'Comments snapshot' }}</div>
                                @if(!empty($snapshot['subtitle']))
                                    <div class="text-[11px] text-slate-600">{{ $snapshot['subtitle'] }}</div>
                                @endif
                            </div>
                        </div>

                        @forelse($snapshotSectionGroups as $sectionCode => $sectionThreads)
                            @php
                                $sectionItemsMeta = $sectionThreads->map(function ($thread) {
                                    return [
                                        'type' => (string) ($thread->action_type ?? 'requires_action'),
                                        'status' => (string) ($thread->status ?? 'open'),
                                    ];
                                })->values();
                            @endphp
                            <div class="rounded-lg border border-gray-200 bg-white overflow-hidden"
                                 x-show="hasVisibleInGroup(@js($sectionItemsMeta->all()))"
                                 x-cloak>
                                <button type="button"
                                        @click="toggleGroup({{ $snapshotIndex }}, '{{ $sectionCode }}')"
                                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50">
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                                            <span>{{ $sectionCode === 'other' ? 'Section Unmapped' : "Section {$sectionCode}" }}</span>
                                            <span class="text-gray-500">
                                                (
                                                <span x-text="countFor(filterMode, @js($sectionItemsMeta->all()))"></span>
                                                )
                                            </span>
                                        </div>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-4 w-4 text-gray-500 transition-transform"
                                         :class="isGroupOpen({{ $snapshotIndex }}, '{{ $sectionCode }}') ? 'rotate-180' : ''"
                                         viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="isGroupOpen({{ $snapshotIndex }}, '{{ $sectionCode }}')" class="px-3 pb-3 space-y-2">
                                    @foreach($sectionThreads as $thread)
                                        @php
                                            $commentType = (string) ($thread->action_type ?? 'requires_action');
                                            $status = (string) ($thread->status ?? 'open');
                                            $statusClass = match($status) {
                                                'resolved' => 'bg-green-50 text-green-700 border-green-200',
                                                'addressed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                default => 'bg-amber-50 text-amber-700 border-amber-200',
                                            };
                                            $statusLabel = match($status) {
                                                'resolved' => 'Resolved by reviewer',
                                                'addressed' => 'Addressed by faculty',
                                                default => 'Action required',
                                            };
                                            $commentTypeClass = $commentType === 'info'
                                                ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                : 'bg-amber-50 text-amber-700 border-amber-200';
                                            $commentTypeLabel = $commentType === 'info' ? 'Note' : 'Action required';
                                            $threadSection = $thread->entry?->section?->section_code;
                                            $threadSectionTarget = is_numeric((string) $threadSection)
                                                ? (int) $threadSection
                                                : (is_numeric((string) $sectionCode) ? (int) $sectionCode : 0);
                                            $threadCriterion = strtoupper((string) ($thread->entry?->criterion_key ?? ''));
                                            $threadCriterionKey = strtolower((string) ($thread->entry?->criterion_key ?? ''));
                                            $threadSectionCode = (string) ($thread->entry?->section?->section_code ?? $sectionCode);
                                            $threadCriterionLabel = $criterionLabels[$threadSectionCode][$threadCriterionKey] ?? ($threadCriterion ?: '-');
                                            $threadEntryId = (int) ($thread->entry?->id ?? 0);
                                            $threadEntryData = is_array($thread->entry?->data) ? $thread->entry->data : [];
                                            $threadEntryRemovedRaw = $threadEntryData['is_removed'] ?? false;
                                            $threadEntryRemoved = is_bool($threadEntryRemovedRaw)
                                                ? $threadEntryRemovedRaw
                                                : (is_numeric($threadEntryRemovedRaw)
                                                    ? ((int) $threadEntryRemovedRaw === 1)
                                                    : in_array(strtolower(trim((string) $threadEntryRemovedRaw)), ['1', 'true', 'yes', 'on'], true));
                                            if ($threadEntryRemoved && $commentType === 'requires_action' && $status === 'addressed') {
                                                $statusClass = 'bg-slate-50 text-slate-700 border-slate-200';
                                                $statusLabel = 'Addressed (auto: removed)';
                                            }
                                        @endphp
                                        <div class="rounded-lg border border-gray-200 bg-white p-3 text-left space-y-2 cursor-pointer hover:border-bu/40 transition"
                                             x-show="matchesFilter('{{ $commentType }}', '{{ $status }}')"
                                             x-cloak
                                             @click="openCommentTarget({ section: {{ $threadSectionTarget }}, entryId: {{ $threadEntryId }}, criterion: '{{ strtolower((string) ($thread->entry?->criterion_key ?? '')) }}' })">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="text-xs font-semibold text-gray-800 truncate">{{ $threadCriterionLabel }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $thread->author?->name ?? 'Reviewer' }} - {{ optional($thread->created_at)->format('M d, Y g:i A') }}
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    @if($threadEntryRemoved)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] bg-slate-50 text-slate-700 border-slate-200">
                                                            Soft removed
                                                        </span>
                                                    @endif
                                                    @if(!($status === 'addressed' && $commentType !== 'info'))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] {{ $commentTypeClass }}">
                                                            {{ $commentTypeLabel }}
                                                        </span>
                                                    @endif
                                                    @if($status !== 'open')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] {{ $statusClass }}">
                                                            {{ $statusLabel }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="text-[11px] font-semibold text-gray-500">Reviewer Comment:</div>
                                            <div class="text-sm leading-5 text-gray-800 break-words">{{ $thread->body }}</div>

                                            @if($thread->children->isNotEmpty())
                                                <div class="rounded-md border-t border-gray-200 pt-1.5 space-y-1.5" @click.stop>
                                                    @foreach($thread->children->sortBy('created_at')->values() as $reply)
                                                        @php
                                                            $isFacultyReply = (int) ($reply->user_id ?? 0) === (int) ($application->faculty_user_id ?? 0);
                                                            $replyLabel = $isFacultyReply ? 'Faculty Reply' : 'Reviewer Comment';
                                                        @endphp
                                                        <div class="text-xs text-gray-700 @if(!$loop->first) border-t border-gray-200 pt-2 @endif">
                                                            <div class="font-semibold text-gray-800">
                                                                {{ $reply->author?->name ?? 'Faculty' }} - {{ optional($reply->created_at)->format('M d, Y g:i A') }}
                                                            </div>
                                                            <div class="mt-0.5 text-[11px] font-semibold text-gray-500">{{ $replyLabel }}:</div>
                                                            <div class="mt-0.5 break-words text-sm leading-5 text-gray-800">{{ $reply->body }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if($threadEntryRemoved)
                                                <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 space-y-1.5" @click.stop>
                                                    <div>This entry was soft removed from scoring.</div>
                                                    @if($commentType === 'requires_action' && $status === 'addressed')
                                                        <div class="text-[11px] text-gray-500">
                                                            Auto-addressed because this entry was removed.
                                                        </div>
                                                    @endif
                                                    @if(($application->status ?? '') === 'returned_to_faculty')
                                                        <form method="POST"
                                                              action="{{ route('reclassification.entries.restore', [$application, $threadEntryId]) }}"
                                                              data-async-action
                                                              data-async-refresh-target="#faculty-section-nav-summary,#faculty-section-content-card,#faculty-comment-overview,#faculty-comment-fab,#faculty-comment-threads"
                                                              data-loading-text="Restoring..."
                                                              data-loading-message="Restoring entry and reopening action-required comments..."
                                                              class="inline-block"
                                                              @click.stop>
                                                            @csrf
                                                            <button type="submit"
                                                                    class="inline-flex items-center px-2.5 py-1 rounded-md border border-green-200 bg-green-50 text-[11px] font-semibold text-green-700 hover:bg-green-100"
                                                                    @click.stop>
                                                                Restore entry
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                No comments in this snapshot.
                            </div>
                        @endforelse
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600"
                         x-show="filterMode !== 'notes'"
                         x-cloak>
                        No reviewer comments available.
                    </div>
                @endforelse

                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600"
                     x-show="filterMode === 'notes' && countFor('notes', @js($commentItemsForCount->all())) === 0"
                     x-cloak>
                    No comment yet.
                </div>
            </div>
        </div>
        <div id="faculty-section-nav-summary" class="sticky top-20 z-30">
            <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-2xl shadow-card">
                <div class="px-4 py-3 flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">Sections</div>
                        <div class="text-xs text-gray-500">Navigate and monitor scores.</div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-xs text-gray-500">
                            Total: <span class="font-semibold text-gray-800" x-text="totalPoints().toFixed(0)"></span>
                        </div>
                        <button type="button"
                                @click="showScores = !showScores"
                                class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                            <span x-text="showScores ? 'Hide scores' : 'Show scores'"></span>
                        </button>
                    </div>
                </div>

                <div class="px-4 pb-3">
                    <div class="flex flex-wrap gap-2">
                        @for($i = 1; $i <= 5; $i++)
                            @php $isLocked = $i === 2; @endphp
                            <button type="button" data-section-nav
                                    @click="navTo({{ $i }})"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border text-sm transition"
                                    :class="active === {{ $i }} ? 'border-bu bg-bu/5 text-gray-800' : 'border-gray-200 hover:bg-gray-50 text-gray-700'">
                                <span class="font-semibold">Section {{ $i }}</span>
                                @if($isLocked)
                                    <span class="text-[11px] text-gray-500">(View-only)</span>
                                @endif
                                <span x-show="showScores"
                                      class="text-[11px] px-2 py-0.5 rounded-full border"
                                      :class="scoreChipClass({{ $i }})"
                                      x-text="scoreChip({{ $i }})"></span>
                            </button>
                        @endfor

                        <button type="button" data-section-nav
                                @click="navTo('review')"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm text-gray-700">
                            <span class="font-semibold">Review</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="faculty-section-content-card" class="mt-6 bg-white border border-gray-200 rounded-2xl shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        <template x-if="active !== 'review'">
                            <span>Section <span x-text="active"></span></span>
                        </template>
                        <template x-if="active === 'review'">
                            <span>Review Summary</span>
                        </template>
                        <template x-if="active === 2">
                            <span class="ml-2 text-sm font-medium text-gray-500">(View-only)</span>
                        </template>
                    </h3>
                    <p class="text-sm text-gray-500">
                        <template x-if="active === 'review'">
                            <span>Read-only summary and final submit.</span>
                        </template>
                        <template x-if="active === 2">
                            <span>This section is completed by the Dean. Faculty can view only.</span>
                        </template>
                        <template x-if="active !== 2 && active !== 'review'">
                            <span>Fill out the required information and attach evidences.</span>
                        </template>
                    </p>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $canEdit ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                    {{ $canEdit ? 'Editable' : 'Read-only (Submitted)' }}
                </span>
            </div>

            <div class="p-6">
                @for ($i = 1; $i <= 5; $i++)
                    <section data-section-pane data-section-index="{{ $i }}" :class="active === {{ $i }} ? 'block is-active' : 'hidden'">
                        @if($i === 2)
                            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm">
                                Section 2 is for Dean's evaluation and is view-only for faculty.
                            </div>
                            <div class="opacity-70 pointer-events-none">
                                @include("reclassification.section{$i}", [
                                    'application' => $application,
                                    'section' => $application->sections->firstWhere('section_code', '2'),
                                    'sectionData' => $sectionsData['2'] ?? [],
                                    'globalEvidence' => $globalEvidence ?? [],
                                    'readOnly' => true,
                                    'embedded' => true,
                                ])
                            </div>
                        @else
                            @include("reclassification.section{$i}", [
                                'application' => $application,
                                'section' => $application->sections->firstWhere('section_code', (string) $i),
                                'sectionData' => $sectionsData[(string) $i] ?? [],
                                'globalEvidence' => $globalEvidence ?? [],
                            ])
                        @endif
                    </section>
                @endfor

                @php
                    $sectionsByCode = $application->sections->keyBy('section_code');
                    $sectionTotals = [
                        '1' => (float) optional($sectionsByCode->get('1'))->points_total,
                        '2' => (float) optional($sectionsByCode->get('2'))->points_total,
                        '3' => (float) optional($sectionsByCode->get('3'))->points_total,
                        '4' => (float) optional($sectionsByCode->get('4'))->points_total,
                        '5' => (float) optional($sectionsByCode->get('5'))->points_total,
                    ];
                    $currentRank = $currentRankLabel ?? ($eligibility['currentRank'] ?? 'Instructor');
                    $trackKey = match (strtolower(trim($currentRank))) {
                        'full professor', 'full' => 'full',
                        'associate professor', 'associate' => 'associate',
                        'assistant professor', 'assistant' => 'assistant',
                        default => 'instructor',
                    };
                @endphp

                <section id="review-summary" data-section-pane :class="active === 'review' ? 'block' : 'hidden'">
                    <div class="space-y-6">
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                            <div class="px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold text-gray-800">My Information</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs text-gray-500">Name</div>
                                        <div class="text-sm font-semibold text-gray-800">{{ auth()->user()->name ?? 'Faculty' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Date of Original Appointment</div>
                                        <div class="text-sm font-semibold text-gray-800">{{ $appointmentDate ?? '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Total Years of Service (BU)</div>
                                        <div class="text-sm font-semibold text-gray-800">
                                            {{ $yearsService !== null ? (int) $yearsService . ' years' : '-' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Employment Type</div>
                                        <div class="text-sm font-semibold text-gray-800">
                                            {{ $profile?->employment_type === 'part_time' ? 'Part-time' : 'Full-time' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs text-gray-500">Current Teaching Rank</div>
                                        <div class="text-sm font-semibold text-gray-800">{{ $currentRank }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Rank Based on Points</div>
                                        <div class="text-sm font-semibold text-gray-800" x-text="isSection2Pending() ? 'Not yet available' : (pointsRankLabel() || '-')"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Allowed Rank (Rules Applied)</div>
                                        <div class="text-sm font-semibold text-gray-800" x-text="isSection2Pending() ? 'Not yet available' : (allowedRankLabel() || 'Not eligible')"></div>
                                    </div>
                                    <template x-if="isSection2Pending()">
                                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                            Section II is not yet answered. Rank outputs are provisional and may change after Dean ratings.
                                        </div>
                                    </template>
                                </div>

                                                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 space-y-2">
                                    <div class="font-semibold text-amber-800">Eligibility checklist</div>
                                    <template x-for="item in eligibilityChecklist()" :key="item.label">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 h-4 w-4 rounded-full flex items-center justify-center text-[10px]"
                                                  :class="item.ok ? 'bg-green-100 text-green-700' : (item.optional ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                                  x-text="item.ok ? 'Y' : (item.optional ? '!' : 'X')"></span>
                                            <span x-text="item.label"></span>
                                        </div>
                                    </template>
                                    <div class="text-[11px] text-amber-700">Only one rank step per cycle.</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                            <div class="px-6 py-4 border-b flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Total Points Summary</h3>
                                    <p class="text-sm text-gray-500">Counted totals per section (caps already applied).</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">TOTAL</p>
                                    <p class="text-lg font-semibold text-gray-900" x-text="totalPoints().toFixed(2)"></p>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm border rounded-lg overflow-hidden">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left">Section</th>
                                                <th class="px-4 py-3 text-right">Counted Points</th>
                                                <th class="px-4 py-3 text-right">Quick Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section I - Academic Preparation & Professional Development</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(1).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 1 })" class="text-bu text-xs font-medium hover:underline">View Section I</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section II - Instructional Competence</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(2).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 2 })" class="text-bu text-xs font-medium hover:underline">View Section II</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section III - Research Competence & Productivity</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(3).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 3 })" class="text-bu text-xs font-medium hover:underline">View Section III</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section IV - Teaching / Professional / Administrative Experience</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(4).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 4 })" class="text-bu text-xs font-medium hover:underline">View Section IV</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-3 font-medium">Section V - Professional & Community Leadership Service</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="sectionPoints(5).toFixed(2)"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="$dispatch('review-nav', { target: 5 })" class="text-bu text-xs font-medium hover:underline">View Section V</button>
                                                </td>
                                            </tr>
                                            <tr class="bg-gray-50">
                                                <td class="px-4 py-3 font-semibold">TOTAL POINTS</td>
                                                <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="totalPoints().toFixed(2)"></td>
                                                <td class="px-4 py-3"></td>
                                            </tr>
                                            <tr class="bg-green-600 text-white">
                                                <td class="px-4 py-3 font-semibold">EQUIVALENT PERCENTAGE (Total / 4)</td>
                                                <td class="px-4 py-3 text-right font-semibold" x-text="eqPercent().toFixed(2)"></td>
                                                <td class="px-4 py-3"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                            <div class="px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold text-gray-800">Notes to the Rater</h3>
                            </div>
                            <div class="p-6 text-sm text-gray-700 space-y-2">
                                <p>No faculty member can be promoted to the rank of Full Professor who has not earned a doctorate degree in his field of teaching assignment or allied field of discipline and has produced at least one accepted research output; or recognition of outstanding accomplishments in arts and sciences; attainment of higher responsible position in government service, business and industry.</p>
                                <p>No faculty member can be promoted to more than one rank (not step) during any one reclassification term.</p>
                                <p>Normally, a new faculty member starts as a probationary instructor, but he may be appointed to a higher rank depending on his credentials.</p>
                                <p>A faculty member cannot be ranked if he does not have a master's degree.</p>
                                <p>A faculty member cannot be ranked without any research or its equivalent.</p>
                                <p>A faculty member who has just earned his/her master's degree can be classified even if it is not within the reclassification term in the University.</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <div id="faculty-review-actions" class="flex items-center gap-3">
                                <button type="button"
                                        @click="saveDraftAll()"
                                        :disabled="savingDraft"
                                        class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50 transition"
                                        :class="savingDraft ? 'opacity-60 cursor-not-allowed' : ''"
                                        :title="savingDraft ? 'Saving draft...' : 'Save all sections as draft.'">
                                    <span x-text="savingDraft ? 'Saving draft...' : 'Save Draft'"></span>
                                </button>

                                <form id="final-submit-form"
                                      method="POST"
                                      action="{{ route('reclassification.submit', $application->id) }}"
                                      @submit.prevent="submitFinal($event)">
                                    @csrf
                                    <button type="submit"
                                            class="px-6 py-2.5 rounded-xl bg-bu text-white font-semibold"
                                            :disabled="!canFinalSubmit() || finalSubmitting"
                                            :class="(!canFinalSubmit() || finalSubmitting) ? 'opacity-60 cursor-not-allowed' : ''"
                                            :title="finalSubmitWarning()">
                                        {{ ($application->status ?? '') === 'returned_to_faculty' ? 'Resubmit' : 'Final Submit' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        {{-- GLOBAL EVIDENCE LIBRARY --}}
        <div id="global-evidence-library" class="mt-6 bg-white border border-gray-200 rounded-2xl shadow-card">
            <div class="px-6 py-4 border-b flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Evidence Library</h3>
                    <p class="text-sm text-gray-500">Upload once, then attach per row using Select Evidence.</p>
                </div>
            </div>

            <div class="p-6 space-y-5">
                <div class="rounded-2xl border border-dashed border-gray-300 bg-bu-muted/30 p-6">
                    <div class="flex flex-col items-center text-center gap-2">
                        <div class="text-sm font-semibold text-gray-800">Upload Evidence</div>
                        <div class="text-xs text-gray-500">
                            Add multiple files. They upload automatically after selection and can be attached from any section.
                        </div>
                        <div class="text-xs text-amber-700">
                            Allowed file types: PDF and image files (JPG, JPEG, PNG, GIF, WEBP, BMP, SVG, TIFF, HEIC/HEIF).
                        </div>
                        <button type="button"
                                @click="requestEvidenceUpload()"
                                class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark">
                            <span>Select files</span>
                        </button>
                        <input id="global-evidence-picker-input" type="file" multiple accept=".pdf,image/*" class="sr-only" @change="addUploadFiles($event)">

                        <div class="text-xs text-gray-500">
                            Uploaded: <span class="font-semibold text-gray-800" x-text="evidenceCount()"></span>
                            <span class="mx-2 text-gray-300"></span>
                            Pending: <span class="font-semibold text-gray-800" x-text="pendingUploads.length"></span>
                        </div>
                    </div>

                    <template x-if="pendingUploads.length">
                        <div class="mt-4 space-y-2">
                            <div class="text-xs font-semibold text-gray-700">Pending uploads</div>
                            <div class="space-y-2">
                                <template x-for="(item, idx) in pendingItems()" :key="item.id">
                                    <div class="flex items-center justify-between rounded-xl border bg-white px-3 py-2 text-xs">
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 truncate" x-text="item.name"></div>
                                            <div class="text-gray-500" x-text="item.typeLabel"></div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="openLibraryPreview(item)" class="text-bu hover:underline">
                                                Preview
                                            </button>
                                            <button type="button"
                                                    @click="removePendingUpload(idx)"
                                                    :disabled="uploading"
                                                    class="text-red-600 hover:underline disabled:opacity-50 disabled:cursor-not-allowed">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div>
                    <div class="text-sm font-semibold text-gray-800">Uploaded Files</div>
                    <div class="text-xs text-gray-500">Preview, detach, or remove files (unattached only).</div>
                </div>

                <template x-if="normalizedEvidence().length === 0">
                    <div class="rounded-2xl border border-dashed p-6 text-center text-sm text-gray-500">
                        No uploaded evidence yet.
                    </div>
                </template>

                <template x-if="normalizedEvidence().length > 0">
                    <div class="overflow-hidden rounded-2xl border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left">
                                <tr>
                                    <th class="px-4 py-2">File</th>
                                    <th class="px-4 py-2">Used</th>
                                    <th class="px-4 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template x-for="item in normalizedEvidence()" :key="item.id">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-800 truncate" x-text="item.name"></div>
                                            <div class="text-xs text-gray-500 flex items-center gap-2">
                                                <span x-text="item.typeLabel"></span>
                                                <span class="text-gray-300"></span>
                                                <span x-text="item.uploaded_at || '-'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border"
                                                  :class="item.entry_count > 0 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600 border-gray-200'">
                                                <span x-text="item.entry_count ? `${item.entry_count} linked` : 'Not used'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right space-x-3">
                                            <button type="button" @click="openLibraryPreview(item)" class="text-xs text-bu hover:underline">
                                                Preview
                                            </button>
                                            <button type="button"
                                                    x-show="item.entry_id"
                                                    @click="detachLibraryEvidence(item)"
                                                    class="text-xs text-red-600 hover:underline">
                                                Detach
                                            </button>
                                            <button type="button"
                                                    x-show="!item.entry_id"
                                                    @click="deleteLibraryEvidence(item)"
                                                    class="text-xs text-gray-600 hover:underline">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-500">Use the top bar to jump between sections.</div>
            <div class="flex gap-2">
                <button type="button"
                        x-show="active !== 'review' && active > 1"
                        @click="navTo(active - 1)"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
                    &larr; Previous
                </button>

                <button type="button"
                        x-show="active !== 'review' && active < 5"
                        @click="navTo(active + 1)"
                        class="px-4 py-2 rounded-xl bg-bu text-white hover:bg-bu-dark transition shadow-soft">
                    Next &rarr;
                </button>

                <button type="button"
                        x-show="active === 5"
                        @click="navTo('review')"
                        class="px-4 py-2 rounded-xl bg-bu text-white hover:bg-bu-dark transition shadow-soft">
                    Go to Review &rarr;
                </button>

                <button type="button"
                        x-show="active === 'review'"
                        @click="navTo(5)"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
                    Back to Section V
                </button>
            </div>
        </div>

    @php
        $pendingCommentTargetsPayload = collect($commentThreads ?? [])
            ->filter(function ($thread) {
                return (string) ($thread->action_type ?? 'requires_action') === 'requires_action';
            })
            ->filter(function ($thread) {
                return (string) ($thread->status ?? 'open') !== 'resolved';
            })
            ->map(function ($thread) {
                return [
                    'section_code' => (string) ($thread->entry?->section?->section_code ?? ''),
                    'criterion_key' => (string) ($thread->entry?->criterion_key ?? ''),
                ];
            })
            ->filter(function ($item) {
                return ($item['section_code'] ?? '') !== '' && ($item['criterion_key'] ?? '') !== '';
            })
            ->unique(function ($item) {
                return ($item['section_code'] ?? '') . '|' . ($item['criterion_key'] ?? '');
            })
            ->values();

    @endphp

    <script>
        function reclassificationWizard() {
            const initial = @json($initialSections);
            const sectionUrls = @json(collect(range(1, 5))->mapWithKeys(fn ($i) => [$i => route('reclassification.section', $i)]));
            const reviewUrl = @json(route('reclassification.show', ['tab' => 'review']));
            const applicationId = @json((int) $application->id);
            const globalEvidence = @json($globalEvidence ?? []);
            const detachBase = @json(url('/reclassification/evidences'));
            const deleteBase = @json(url('/reclassification/evidences'));
            const uploadUrl = @json(route('reclassification.evidence.upload'));
            const rankTrack = @json($trackKey ?? 'instructor');
            const rankTrackLabel = @json($currentRank ?? 'Instructor');
            const pendingCommentTargets = @json($pendingCommentTargetsPayload);
            const commentsPanelDefaultOpen = @json((($application->status ?? '') === 'returned_to_faculty') && ($requiredCommentThreads->count() > 0));
            const eligibility = {
                hasMasters: @json(($eligibility['hasMasters'] ?? false)),
                hasDoctorate: @json(($eligibility['hasDoctorate'] ?? false)),
                hasResearchEquivalent: @json(($eligibility['hasResearchEquivalent'] ?? false)),
                hasAcceptedResearchOutput: @json(($eligibility['hasAcceptedResearchOutput'] ?? false)),
                hasMinBuYears: @json(($eligibility['hasMinBuYears'] ?? false)),
            };

            return {
                active: @json($active),
                sections: initial,
                 showScores: true,
                 sectionUrls,
                 reviewUrl,
                 globalEvidence,
                 libraryPreviewOpen: false,
                 libraryPreviewItem: null,
                 libraryToast: { show: false, message: '', type: 'info' },
                 toastTimer: null,
                 showBackToTop: false,
                 draftSaveInFlight: false,
                 autosaveTimer: null,
                 autosaveDelayMs: 8000,
                 localDraftTimers: {},
                 dirtySections: {},
                 restoringLocalDraft: false,
                pendingUploads: [],
                savingDraft: false,
                finalSubmitting: false,
                savedIndicatorTimer: null,
                uploading: false,
                deletingEvidenceIds: {},
                commentsPanelOpen: commentsPanelDefaultOpen,
                commentsSidebarCollapsed: false,
                commentsPanelStorageKey: `faculty_comments_panel:${Number(applicationId || 0)}`,
                submissionWindowOpen: @json($submissionWindowOpen ?? true),
                canSubmitByPeriod: @json($canSubmitByPeriod ?? true),
                track: rankTrack,
                trackLabel: rankTrackLabel,
                hasMasters: eligibility.hasMasters,
                hasDoctorate: eligibility.hasDoctorate,
                hasResearchEquivalent: eligibility.hasResearchEquivalent,
                hasAcceptedResearchOutput: eligibility.hasAcceptedResearchOutput,
                hasMinBuYears: eligibility.hasMinBuYears,
                returnedLockMode: false,
                pendingCommentTargets,

                init() {
                    this.loadCommentPanelState();
                    this.$watch('commentsPanelOpen', () => this.saveCommentPanelState());
                    this.$watch('commentsSidebarCollapsed', () => this.saveCommentPanelState());

                    if (!this.sections['1']) {
                        this.sections = {
                            '1': { points: 0, max: 140 },
                            '2': { points: 0, max: 120 },
                            '3': { points: 0, max: 70 },
                            '4': { points: 0, max: 40 },
                            '5': { points: 0, max: 30 },
                        };
                    }

                    document.addEventListener('section-score', (event) => {
                        const detail = event.detail || {};
                        const key = String(detail.section || '');
                        if (this.sections[key]) {
                            this.sections[key].points = Number(detail.points || 0);
                        }
                    });

                    window.saveDraftAll = this.saveDraftAll.bind(this);
                    window.reclassificationCanLeaveCurrentSection = this.validateCurrentSectionBeforeLeave.bind(this);
                    window.reclassificationFinalSubmit = () => {
                        const form = document.getElementById('final-submit-form');
                        if (!form) return;
                        this.submitFinal({ target: form });
                    };
                    window.reclassificationToast = (message, type = 'info', timeout = 3000) => {
                        this.showToast(message, type, timeout);
                    };
                    window.reclassificationRequestEvidenceUpload = (onConfirm = null, onCancel = null) => {
                        if (typeof onConfirm === 'function') {
                            onConfirm();
                            return;
                        }
                        this.requestEvidenceUpload();
                    };
                    window.reclassificationDeleteEvidence = (evidenceId) => {
                        const id = Number(evidenceId || 0);
                        if (!id) return;
                        const item = (this.globalEvidence || []).find((ev) => Number(ev.id || 0) === id);
                        if (!item) return;
                        this.deleteLibraryEvidence(item);
                    };

                    if (window.__reclassificationRemoveRequestHandler) {
                        window.removeEventListener('evidence-remove-request', window.__reclassificationRemoveRequestHandler);
                    }
                    window.__reclassificationRemoveRequestHandler = (event) => {
                        const id = Number(event?.detail?.id || 0);
                        if (!id) return;
                        window.reclassificationDeleteEvidence(id);
                    };
                    window.addEventListener('evidence-remove-request', window.__reclassificationRemoveRequestHandler);

                    if (window.__reclassificationSectionNavGuard) {
                        document.removeEventListener('click', window.__reclassificationSectionNavGuard, true);
                    }
                    window.__reclassificationSectionNavGuard = (event) => {
                        const link = event?.target?.closest?.('[data-section-nav]');
                        if (!link) return;
                        if (typeof window.reclassificationCanLeaveCurrentSection !== 'function') return;
                        const ok = window.reclassificationCanLeaveCurrentSection();
                        if (ok) return;
                        event.preventDefault();
                        event.stopImmediatePropagation();
                    };
                    document.addEventListener('click', window.__reclassificationSectionNavGuard, true);

                    this.bindDraftProtection();
                    this.updateBackToTopVisibility();
                    window.addEventListener('scroll', () => this.updateBackToTopVisibility(), { passive: true });

                    this.$nextTick(() => {
                        this.restoreLocalDrafts();
                        if (this.returnedLockMode) {
                            this.applyReturnedLocks();
                        }
                    });
                },
                loadCommentPanelState() {
                    try {
                        const raw = window.localStorage.getItem(this.commentsPanelStorageKey);
                        if (!raw) return;
                        const saved = JSON.parse(raw);
                        if (!saved || typeof saved !== 'object') return;
                        if (typeof saved.commentsPanelOpen === 'boolean') {
                            this.commentsPanelOpen = saved.commentsPanelOpen;
                        }
                        if (typeof saved.commentsSidebarCollapsed === 'boolean') {
                            this.commentsSidebarCollapsed = saved.commentsSidebarCollapsed;
                        }
                    } catch (error) {}
                },
                saveCommentPanelState() {
                    try {
                        window.localStorage.setItem(this.commentsPanelStorageKey, JSON.stringify({
                            commentsPanelOpen: !!this.commentsPanelOpen,
                            commentsSidebarCollapsed: !!this.commentsSidebarCollapsed,
                        }));
                    } catch (error) {}
                },

                showToast(message, type = 'info', timeout = 2500) {
                    this.libraryToast = { show: true, message, type };
                    if (this.toastTimer) {
                        clearTimeout(this.toastTimer);
                        this.toastTimer = null;
                    }
                    if (timeout > 0) {
                        this.toastTimer = setTimeout(() => {
                            this.libraryToast.show = false;
                            this.toastTimer = null;
                        }, timeout);
                    }
                },

                setHeaderSavedIndicator(visible, text = 'Saved') {
                    const badge = document.getElementById('header-save-draft-status');
                    if (!badge) return;
                    badge.textContent = text;
                    badge.classList.toggle('hidden', !visible);

                    if (this.savedIndicatorTimer) {
                        clearTimeout(this.savedIndicatorTimer);
                        this.savedIndicatorTimer = null;
                    }

                    if (visible) {
                        this.savedIndicatorTimer = setTimeout(() => {
                            badge.classList.add('hidden');
                            this.savedIndicatorTimer = null;
                        }, 2400);
                    }
                },

                bindDraftProtection() {
                    const onFieldChange = (event) => {
                        if (this.restoringLocalDraft) return;
                        const target = event?.target;
                        if (!target || typeof target.closest !== 'function') return;
                        const form = target.closest('form[data-validate-evidence]');
                        if (!form || form.dataset.viewOnly === 'true') return;
                        this.markSectionDirty(form);
                        this.scheduleLocalFormSnapshot(form);
                        this.scheduleAutosave();
                    };

                    document.addEventListener('input', onFieldChange, true);
                    document.addEventListener('change', onFieldChange, true);

                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) return;
                        this.persistAllLocalDrafts();
                        this.saveDirtySectionsDraft('auto');
                    });

                    window.addEventListener('beforeunload', (event) => {
                        if (!this.hasDirtyChanges()) return;
                        this.persistAllLocalDrafts();
                        event.preventDefault();
                        event.returnValue = '';
                    });
                },

                editableForms() {
                    return Array.from(document.querySelectorAll('form[data-validate-evidence]'))
                        .filter((form) => form.dataset.viewOnly !== 'true');
                },

                draftStorageKey(sectionCode) {
                    return `reclassification:draft:${applicationId}:section:${sectionCode}`;
                },

                formSnapshot(form) {
                    const formData = new FormData(form);
                    this.appendDisabledFormValues(form, formData);
                    const values = {};
                    for (const [name, raw] of formData.entries()) {
                        if (!this.shouldPersistDraftField(name)) continue;
                        if (raw instanceof File) continue;
                        if (!values[name]) values[name] = [];
                        values[name].push(String(raw ?? ''));
                    }
                    return values;
                },

                shouldPersistDraftField(name) {
                    const field = String(name || '');
                    if (!field) return false;
                    if (field === '_token' || field === '_method') return false;
                    // Keep DB row IDs from server state, not from local draft snapshots.
                    if (/\[id\]$/.test(field)) return false;
                    return true;
                },

                applyFormSnapshot(form, values) {
                    if (!form || !values || typeof values !== 'object') return;
                    const grouped = {};
                    Array.from(form.querySelectorAll('[name]')).forEach((field) => {
                        if (!field.name) return;
                        if (!this.shouldPersistDraftField(field.name)) return;
                        if (!grouped[field.name]) grouped[field.name] = [];
                        grouped[field.name].push(field);
                    });

                    Object.entries(grouped).forEach(([name, fields]) => {
                        if (!Object.prototype.hasOwnProperty.call(values, name)) return;
                        const savedValues = Array.isArray(values[name]) ? values[name].map(String) : [String(values[name])];
                        const first = fields[0];
                        const type = String(first?.type || '').toLowerCase();
                        const tag = String(first?.tagName || '').toLowerCase();

                        if (type === 'file') return;

                        if (type === 'checkbox' || type === 'radio') {
                            fields.forEach((field) => {
                                const value = String(field.value ?? 'on');
                                field.checked = savedValues.includes(value);
                            });
                            return;
                        }

                        if (tag === 'select' && first.multiple) {
                            const selected = new Set(savedValues);
                            Array.from(first.options || []).forEach((opt) => {
                                opt.selected = selected.has(String(opt.value));
                            });
                            return;
                        }

                        fields.forEach((field, index) => {
                            field.value = savedValues[index] ?? savedValues[0] ?? '';
                        });
                    });

                    form.dispatchEvent(new Event('input', { bubbles: true }));
                    form.dispatchEvent(new Event('change', { bubbles: true }));
                },

                markSectionDirty(form) {
                    const sectionCode = this.formSectionCode(form);
                    if (!sectionCode) return;
                    this.dirtySections[sectionCode] = true;
                },

                clearSectionDirty(form) {
                    const sectionCode = this.formSectionCode(form);
                    if (!sectionCode) return;
                    delete this.dirtySections[sectionCode];
                },

                hasDirtyChanges() {
                    return Object.keys(this.dirtySections || {}).length > 0;
                },

                scheduleLocalFormSnapshot(form) {
                    const sectionCode = this.formSectionCode(form);
                    if (!sectionCode) return;
                    if (this.localDraftTimers[sectionCode]) {
                        clearTimeout(this.localDraftTimers[sectionCode]);
                    }
                    this.localDraftTimers[sectionCode] = setTimeout(() => {
                        this.persistLocalFormDraft(form);
                        delete this.localDraftTimers[sectionCode];
                    }, 450);
                },

                persistLocalFormDraft(form) {
                    const sectionCode = this.formSectionCode(form);
                    if (!sectionCode) return;
                    try {
                        const payload = {
                            saved_at: Date.now(),
                            values: this.formSnapshot(form),
                        };
                        window.localStorage.setItem(this.draftStorageKey(sectionCode), JSON.stringify(payload));
                    } catch (error) {
                        // ignore storage errors
                    }
                },

                persistAllLocalDrafts() {
                    this.editableForms().forEach((form) => this.persistLocalFormDraft(form));
                },

                clearLocalFormDraft(form) {
                    const sectionCode = this.formSectionCode(form);
                    if (!sectionCode) return;
                    try {
                        window.localStorage.removeItem(this.draftStorageKey(sectionCode));
                    } catch (error) {
                        // ignore storage errors
                    }
                },

                restoreLocalDrafts() {
                    let restoredCount = 0;
                    this.restoringLocalDraft = true;
                    this.editableForms().forEach((form) => {
                        const sectionCode = this.formSectionCode(form);
                        if (!sectionCode) return;
                        try {
                            const raw = window.localStorage.getItem(this.draftStorageKey(sectionCode));
                            if (!raw) return;
                            const parsed = JSON.parse(raw);
                            if (!parsed?.values || typeof parsed.values !== 'object') return;
                            const beforeSnapshot = this.formSnapshot(form);
                            this.applyFormSnapshot(form, parsed.values);
                            const afterSnapshot = this.formSnapshot(form);
                            if (JSON.stringify(beforeSnapshot) !== JSON.stringify(afterSnapshot)) {
                                this.dirtySections[sectionCode] = true;
                                restoredCount += 1;
                            } else {
                                delete this.dirtySections[sectionCode];
                            }
                        } catch (error) {
                            // ignore broken payloads
                        }
                    });
                    this.restoringLocalDraft = false;

                    if (restoredCount > 0) {
                        this.showToast('Recovered unsaved local changes from this device.', 'info', 3200);
                        this.scheduleAutosave();
                    }
                },

                scheduleAutosave() {
                    if (this.autosaveTimer) {
                        clearTimeout(this.autosaveTimer);
                    }
                    this.autosaveTimer = setTimeout(() => {
                        this.saveDirtySectionsDraft('auto');
                    }, this.autosaveDelayMs);
                },

                updateBackToTopVisibility() {
                    this.showBackToTop = window.scrollY > 320;
                },

                scrollToTop() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                activeSectionPane() {
                    let pane = document.querySelector('[data-section-pane].is-active');
                    if (!pane && this.active !== 'review') {
                        pane = document.querySelector(`[data-section-pane][data-section-index="${this.active}"]`);
                    }
                    if (!pane && this.active === 'review') {
                        pane = document.getElementById('review-summary');
                    }
                    return pane;
                },

                currentSectionForm() {
                    const pane = this.activeSectionPane();
                    return pane ? pane.querySelector('form[data-validate-evidence]') : null;
                },

                validateCurrentSectionBeforeLeave() {
                    const pane = this.activeSectionPane();
                    if (!pane) return true;
                    if (this.active === 'review') return true;

                    const form = pane.querySelector('form[data-validate-evidence]');
                    if (form && window.validateFormRows && !window.validateFormRows(form)) {
                        return false;
                    }

                    const sectionIndex = String(pane.getAttribute('data-section-index') || this.active || '');
                    const markMissingEvidence = (key, message) => {
                        alert(message);
                        const target = pane.querySelector(`[data-evidence-block="${key}"]`);
                        if (target) {
                            target.classList.add('ring-2', 'ring-red-400', 'rounded-xl');
                            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            setTimeout(() => target.classList.remove('ring-2', 'ring-red-400', 'rounded-xl'), 2000);
                        }
                        return false;
                    };

                    if (sectionIndex === '1') {
                        const honors = String(
                            pane.querySelector('input[name="section1[a1][honors]"]:checked')?.value || ''
                        );
                        const a1EvidenceCount = pane.querySelectorAll('input[type="hidden"][name="section1[a1][evidence][]"]').length;
                        if (honors && honors !== 'none' && a1EvidenceCount === 0) {
                            return markMissingEvidence('a1', 'Please attach evidence for Section I-A1 before leaving this section.');
                        }
                    }

                    if (sectionIndex === '4') {
                        const toNumber = (selector) => {
                            const raw = String(pane.querySelector(selector)?.value || '0');
                            const parsed = parseFloat(raw);
                            return Number.isNaN(parsed) ? 0 : parsed;
                        };
                        const hiddenCount = (name) =>
                            pane.querySelectorAll(`input[type="hidden"][name="${name}"]`).length;

                        const a1Years = toNumber('[name="section4[a][a1_years]"]');
                        const a2Years = toNumber('[name="section4[a][a2_years]"]');
                        const bYears = toNumber('[name="section4[b][years]"]');
                        const bUnlocked = (a2Years >= 3 && a1Years >= 2) || a2Years >= 5;

                        if (a1Years > 0 && hiddenCount('section4[a][a1_evidence][]') === 0) {
                            return markMissingEvidence('a1', 'Please attach evidence for Section IV-A1 before leaving this section.');
                        }
                        if (a2Years > 0 && hiddenCount('section4[a][a2_evidence][]') === 0) {
                            return markMissingEvidence('a2', 'Please attach evidence for Section IV-A2 before leaving this section.');
                        }
                        if (bYears > 0 && bUnlocked && hiddenCount('section4[b][evidence][]') === 0) {
                            return markMissingEvidence('b', 'Please attach evidence for Section IV-B before leaving this section.');
                        }
                    }

                    return true;
                },

                navTo(target) {
                    if (!this.validateCurrentSectionBeforeLeave()) {
                        return;
                    }
                    this.persistAllLocalDrafts();
                    this.saveDirtySectionsDraft('auto');

                    if (target === 'review') {
                        this.active = 'review';
                        if (this.reviewUrl) {
                            window.history.replaceState({}, '', this.reviewUrl);
                        }
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        this.$nextTick(() => this.applyReturnedLocks());
                        return;
                    }

                    this.active = Number(target);
                    if (this.sectionUrls && this.sectionUrls[this.active]) {
                        window.history.replaceState({}, '', this.sectionUrls[this.active]);
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    this.$nextTick(() => this.applyReturnedLocks());
                },

                openCommentTarget(target) {
                    const section = Number(target?.section || 0);
                    if (!section) return;

                    // Keep comments panel open while navigating to the target.
                    this.commentsPanelOpen = true;

                    this.persistAllLocalDrafts();
                    this.saveDirtySectionsDraft('auto');
                    this.active = section;
                    if (this.sectionUrls && this.sectionUrls[this.active]) {
                        window.history.replaceState({}, '', this.sectionUrls[this.active]);
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    this.$nextTick(() => {
                        this.applyReturnedLocks();
                        this.focusCommentTargetWithRetry({
                            section,
                            entryId: Number(target?.entryId || 0),
                            criterion: String(target?.criterion || '').toLowerCase(),
                        }, 8);
                    });
                },

                focusCommentTargetWithRetry(target, retries = 6) {
                    const tryFocus = (remaining) => {
                        const ok = this.focusCommentTarget(target);
                        if (ok || remaining <= 0) return;
                        setTimeout(() => tryFocus(remaining - 1), 120);
                    };
                    tryFocus(retries);
                },

                focusCommentTarget(target) {
                    const section = String(target?.section || '');
                    const entryId = Number(target?.entryId || 0);
                    const criterion = String(target?.criterion || '').toLowerCase();
                    if (!section) return false;

                    const pane = document.querySelector(`[data-section-pane][data-section-index="${section}"]`);
                    if (!pane) return false;

                    let focusNode = null;
                    if (entryId > 0) {
                        focusNode = pane.querySelector(`input[type="hidden"][name^="section${section}"][name*="id"][value="${entryId}"]`);
                    }
                    if (!focusNode && criterion) {
                        focusNode = pane.querySelector(`[data-comment-anchor="${section}:${criterion}"]`);
                    }
                    if (!focusNode && criterion) {
                        focusNode = Array.from(pane.querySelectorAll('input[name], select[name], textarea[name]')).find((field) =>
                            this.nameMatchesCriterion(field.name || '', section, criterion)
                        ) || null;
                    }
                    if (!focusNode && section === '1' && criterion === 'a1') {
                        focusNode = pane.querySelector('[name="section1[a1][honors]"]')
                            || pane.querySelector('[data-comment-anchor="1:a1"]');
                    }
                    if (!focusNode) return false;

                    let block = focusNode.closest('tr');
                    if (!block) {
                        block = focusNode.closest('.rounded-xl')
                            || focusNode.closest('.border')
                            || focusNode.closest('div');
                    }

                    const targetNode = block || focusNode;
                    targetNode.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    const focusable = targetNode.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])')
                        || (focusNode.matches('input,select,textarea,button') ? focusNode : null);
                    if (focusable && typeof focusable.focus === 'function') {
                        try {
                            focusable.focus({ preventScroll: true });
                        } catch (e) {
                            focusable.focus();
                        }
                    }

                    targetNode.classList.add('ring-2', 'ring-bu', 'ring-offset-2');
                    setTimeout(() => {
                        targetNode.classList.remove('ring-2', 'ring-bu', 'ring-offset-2');
                    }, 1300);
                    return true;
                },

                isLockableControl(control) {
                    if (!control) return false;
                    if (control.closest('[data-return-lock-ignore]')) return false;
                    const tag = (control.tagName || '').toLowerCase();
                    if (tag === 'button') return true;
                    if (!control.name) return false;
                    if (control.matches('input[type=\"hidden\"]')) return false;
                    if (control.name === '_token' || control.name === '_method') return false;
                    return true;
                },

                setControlLocked(control, locked) {
                    if (!this.isLockableControl(control)) return;
                    control.disabled = !!locked;
                    control.classList.toggle('bg-gray-100', !!locked);
                    control.classList.toggle('cursor-not-allowed', !!locked);
                    control.classList.toggle('opacity-70', !!locked);
                    control.dataset.returnLock = locked ? '1' : '0';
                },

                findUnlockScopeFromCommentHeader(header, form) {
                    if (!header) return null;
                    const candidates = ['tr', '.rounded-2xl', '.rounded-xl', '.border'];
                    for (const selector of candidates) {
                        let node = header.closest(selector);
                        while (node && node !== form) {
                            if (node.querySelector('input[name], select[name], textarea[name], button[name], button[type=\"button\"]')) {
                                return node;
                            }
                            node = node.parentElement ? node.parentElement.closest(selector) : null;
                        }
                    }
                    return header.parentElement;
                },

                nameMatchesCriterion(name, sectionCode, criterionKey) {
                    const section = String(sectionCode || '');
                    const criterion = String(criterionKey || '');
                    if (!name || !section || !criterion) return false;

                    if (section === '4') {
                        if (criterion === 'a1') return name.startsWith('section4[a][a1_');
                        if (criterion === 'a2') return name.startsWith('section4[a][a2_');
                        if (criterion === 'b') return name.startsWith('section4[b][');
                    }

                    return name.startsWith(`section${section}[${criterion}]`);
                },

                formSectionCode(form) {
                    const action = String(form?.getAttribute('action') || '');
                    const match = action.match(/\/reclassification\/section\/(\d+)/);
                    return match ? String(match[1]) : null;
                },

                criterionAliases(sectionCode, criterionKey) {
                    const section = String(sectionCode || '');
                    const key = String(criterionKey || '');
                    if (!key) return [];
                    const aliases = [key];
                    if (section === '5') {
                        if (key === 'a') aliases.push('aRows');
                        if (key === 'b') aliases.push('bRows');
                        if (key === 'd') aliases.push('dRows');
                    }
                    return Array.from(new Set(aliases.filter(Boolean)));
                },

                unlockButtonsForCriterion(form, sectionCode, criterionKey) {
                    const aliases = this.criterionAliases(sectionCode, criterionKey);
                    if (!form || !aliases.length) return;
                    const buttons = Array.from(form.querySelectorAll('button[type=\"button\"]'));
                    buttons.forEach((button) => {
                        const clickExpr = String(
                            button.getAttribute('@click')
                            || button.getAttribute('x-on:click')
                            || ''
                        );
                        if (!clickExpr) return;
                        const matches = aliases.some((alias) =>
                            clickExpr.includes(`'${alias}'`)
                            || clickExpr.includes(`\"${alias}\"`)
                            || clickExpr.includes(`${alias}.splice(`)
                            || clickExpr.includes(`${alias}.push(`)
                        );
                        if (matches) {
                            this.setControlLocked(button, false);
                        }
                    });
                },

                unlockEvidenceButtonsNearControl(control, form) {
                    if (!control || !form) return;
                    let node = control.parentElement;
                    while (node && node !== form) {
                        const proxies = Array.from(node.querySelectorAll('[data-evidence-proxy]'));
                        if (proxies.length) {
                            proxies.forEach((proxy) => {
                                const buttons = proxy.querySelectorAll('button[type=\"button\"]');
                                buttons.forEach((button) => this.setControlLocked(button, false));
                            });
                            return;
                        }
                        node = node.parentElement;
                    }
                },

                unlockEvidenceButtonsForCriterion(form, sectionCode, criterionKey) {
                    if (!form) return;
                    const fields = Array.from(form.querySelectorAll('input[name], select[name], textarea[name]'));
                    fields.forEach((field) => {
                        if (!this.nameMatchesCriterion(field.name || '', sectionCode, criterionKey)) {
                            return;
                        }
                        this.unlockEvidenceButtonsNearControl(field, form);
                    });
                },

                applyReturnedLocksToForm(form) {
                    if (!form) return;
                    const controls = Array.from(form.querySelectorAll('input[name], select[name], textarea[name], button[name], button[type=\"button\"]'));
                    controls.forEach((control) => this.setControlLocked(control, true));

                    // Always keep controls inside explicit ignore blocks interactive.
                    const ignoreControls = Array.from(
                        form.querySelectorAll('[data-return-lock-ignore] input[name], [data-return-lock-ignore] select[name], [data-return-lock-ignore] textarea[name], [data-return-lock-ignore] button[name], [data-return-lock-ignore] button[type=\"button\"]')
                    );
                    ignoreControls.forEach((control) => {
                        control.disabled = false;
                        control.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-70');
                        control.dataset.returnLock = '0';
                    });

                    const commentTargets = Array.isArray(this.pendingCommentTargets) ? this.pendingCommentTargets : [];
                    if (commentTargets.length) {
                        const formSection = this.formSectionCode(form);
                        controls.forEach((control) => {
                            const controlName = control.name || '';
                            const unlock = commentTargets.some((target) => {
                                if (formSection && target.section_code !== formSection) {
                                    return false;
                                }
                                return this.nameMatchesCriterion(controlName, target.section_code, target.criterion_key);
                            });
                            if (unlock) {
                                this.setControlLocked(control, false);
                                this.unlockEvidenceButtonsNearControl(control, form);
                            }
                        });

                        commentTargets.forEach((target) => {
                            if (!formSection || target.section_code !== formSection) return;
                            this.unlockButtonsForCriterion(form, target.section_code, target.criterion_key);
                            this.unlockEvidenceButtonsForCriterion(form, target.section_code, target.criterion_key);
                        });
                    }

                },

                applyReturnedLocks() {
                    if (!this.returnedLockMode) return;
                    const forms = Array.from(document.querySelectorAll('form[data-validate-evidence]'));
                    forms.forEach((form) => this.applyReturnedLocksToForm(form));
                },

                appendDisabledFormValues(form, formData) {
                    const disabledFields = Array.from(form.querySelectorAll('[name][disabled]'));
                    disabledFields.forEach((field) => {
                        if (!this.isLockableControl(field)) return;
                        if (!field.name) return;
                        const name = field.name;
                        const tag = (field.tagName || '').toLowerCase();
                        const type = (field.type || '').toLowerCase();

                        if (tag === 'select') {
                            if (field.multiple) {
                                Array.from(field.selectedOptions || []).forEach((opt) => formData.append(name, opt.value));
                            } else {
                                formData.append(name, field.value ?? '');
                            }
                            return;
                        }

                        if (type === 'checkbox' || type === 'radio') {
                            if (field.checked) {
                                formData.append(name, field.value ?? 'on');
                            }
                            return;
                        }

                        if (type === 'file') return;

                        formData.append(name, field.value ?? '');
                    });
                },

                totalPoints() {
                    return Object.values(this.sections).reduce((sum, s) => sum + Number(s.points || 0), 0);
                },

                sectionPoints(id) {
                    const s = this.sections[String(id)];
                    return Number(s?.points || 0);
                },

                hasResearchEquivalentNow() {
                    return this.hasResearchEquivalent || this.sectionPoints(3) > 0;
                },

                hasAcceptedResearchOutputNow() {
                    return this.hasAcceptedResearchOutput || this.hasResearchEquivalentNow();
                },

                getOpenRequiredCommentCount() {
                    const overview = document.getElementById('faculty-comment-overview');
                    if (!overview) return 0;
                    const raw = Number(overview.getAttribute('data-open-required-count') || 0);
                    return Number.isFinite(raw) ? raw : 0;
                },

                eqPercent() {
                    return this.totalPoints() / 4;
                },

                isSection2Pending() {
                    return this.sectionPoints(2) <= 0;
                },

                pointsRank() {
                    const p = this.eqPercent();
                    const ranges = {
                        full: [
                            { letter: 'A', min: 95.87, max: 100.0 },
                            { letter: 'B', min: 91.5, max: 95.86 },
                            { letter: 'C', min: 87.53, max: 91.49 },
                        ],
                        associate: [
                            { letter: 'A', min: 83.34, max: 87.52 },
                            { letter: 'B', min: 79.19, max: 83.33 },
                            { letter: 'C', min: 75.02, max: 79.18 },
                        ],
                        assistant: [
                            { letter: 'A', min: 70.85, max: 75.01 },
                            { letter: 'B', min: 66.68, max: 70.84 },
                            { letter: 'C', min: 62.51, max: 66.67 },
                        ],
                        instructor: [
                            { letter: 'A', min: 58.34, max: 62.5 },
                            { letter: 'B', min: 54.14, max: 58.33 },
                            { letter: 'C', min: 50.0, max: 54.16 },
                        ],
                    };
                    const order = ['full', 'associate', 'assistant', 'instructor'];
                    for (const key of order) {
                        const list = ranges[key];
                        const hit = list.find((r) => p >= r.min && p <= r.max);
                        if (hit) return { track: key, letter: hit.letter };
                    }
                    return null;
                },

                pointsRankLabel() {
                    const hit = this.pointsRank();
                    if (!hit) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    return `${labels[hit.track]} - ${hit.letter}`;
                },

                allowedRankLabel() {
                    if (!this.hasMasters || !this.hasResearchEquivalentNow()) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    const order = { instructor: 1, assistant: 2, associate: 3, full: 4 };
                    const nextRank = (key) => {
                        const target = order[key] + 1;
                        const hit = Object.keys(order).find((k) => order[k] === target);
                        return hit || key;
                    };

                    let desired = this.pointsRank()?.track || this.track;
                    const maxAllowed = (this.hasDoctorate && this.hasAcceptedResearchOutputNow()) ? 'full' : 'associate';
                    if (order[desired] > order[maxAllowed]) desired = maxAllowed;

                    const oneStep = nextRank(this.track);
                    if (order[desired] > order[oneStep]) desired = oneStep;

                    let letter = this.pointsRank()?.letter || '';
                    if (this.pointsRank()?.track && this.pointsRank()?.track !== desired) {
                        // If capped down from a higher points rank, show the highest letter in the allowed rank.
                        letter = 'A';
                    }

                    return letter ? `${labels[desired]} - ${letter}` : (labels[desired] || '');
                },

                eligibilityChecklist() {
                    const needsDoctorate = (this.pointsRank()?.track || this.track) === 'full';
                    return [
                        { label: 'Masters degree required', ok: this.hasMasters },
                        { label: 'At least 3 years of service in BU', ok: this.hasMinBuYears },
                        { label: 'At least one research output/equivalent', ok: this.hasResearchEquivalentNow() },
                        {
                            label: 'Doctorate + accepted research output for Full Professor',
                            ok: this.hasDoctorate && this.hasAcceptedResearchOutputNow(),
                            optional: !needsDoctorate,
                        },
                    ];
                },

                canFinalSubmit() {
                    if (!this.canSubmitByPeriod) return false;
                    if (!this.hasMasters) return false;
                    if (!this.hasMinBuYears) return false;
                    if (!this.hasResearchEquivalentNow()) return false;
                    if (this.getOpenRequiredCommentCount() > 0) return false;
                    return true;
                },

                finalSubmitWarning() {
                    if (this.finalSubmitting) return 'Submitting...';
                    if (!this.canSubmitByPeriod) return 'Submission window is closed. You can save as draft only.';
                    if (!this.hasMasters) return 'Cannot submit: Master\'s degree is required.';
                    if (!this.hasMinBuYears) return 'Cannot submit: At least 3 years of service in BU is required.';
                    if (!this.hasResearchEquivalentNow()) return 'Cannot submit: At least one research output/equivalent is required.';
                    if (this.getOpenRequiredCommentCount() > 0) return 'Cannot submit: Please address all action-required reviewer comments first.';
                    return 'Ready to submit. A confirmation prompt will appear.';
                },

                finalSubmitConfirmMessage() {
                    return "Are you sure you want to final submit this reclassification?\n\nPlease make sure all required documents are complete.\n\nOnly one submission is allowed per period, and you cannot fully revise after final submit unless a reviewer returns the form.";
                },

                evidenceCount() {
                    return this.normalizedEvidence().length;
                },

                fileTypeLabel(name, mime) {
                    if (mime) {
                        const parts = mime.split('/');
                        return (parts[1] || parts[0]).toUpperCase();
                    }
                    const ext = (name || '').split('.').pop();
                    return ext ? ext.toUpperCase() : 'FILE';
                },

                normalizedEvidence() {
                    return (this.globalEvidence || []).map((ev) => {
                        const typeLabel = this.fileTypeLabel(ev.name, ev.mime_type || '');
                        const isImage = (ev.mime_type || '').startsWith('image/') || /\.(png|jpe?g|gif|webp)$/i.test(ev.name || '');
                        const isPdf = (ev.mime_type || '') === 'application/pdf' || /\.pdf$/i.test(ev.name || '');
                        return {
                            ...ev,
                            entry_count: Number(ev.entry_count || 0),
                            typeLabel,
                            isImage,
                            isPdf,
                        };
                    });
                },

                isAllowedEvidenceFile(file) {
                    const allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tif', 'tiff', 'heic', 'heif'];
                    const name = String(file?.name || '').toLowerCase();
                    const ext = name.includes('.') ? name.split('.').pop() : '';
                    const mime = String(file?.type || '').toLowerCase();
                    if (mime === 'application/pdf') return true;
                    if (mime.startsWith('image/')) return true;
                    return allowedExt.includes(ext);
                },

                addUploadFiles(event) {
                    const files = Array.from(event.target.files || []);
                    if (!files.length) return;
                    const validFiles = files.filter((file) => this.isAllowedEvidenceFile(file));
                    const invalidCount = files.length - validFiles.length;
                    if (invalidCount > 0) {
                        this.showToast('Only PDF and image files are allowed.', 'error', 2600);
                    }
                    if (!validFiles.length) {
                        event.target.value = '';
                        return;
                    }
                    const existing = this.pendingUploads || [];
                    const signature = (file) => `${file.name}|${file.size}|${file.lastModified}`;
                    const map = new Set(existing.map(signature));
                    validFiles.forEach((file) => {
                        const sig = signature(file);
                        if (!map.has(sig)) {
                            map.add(sig);
                            existing.push(file);
                        }
                    });
                    this.pendingUploads = [...existing];
                    event.target.value = '';
                    this.uploadPendingEvidence();
                },

                pendingItems() {
                    return (this.pendingUploads || []).map((file, idx) => {
                        const mime = file.type || '';
                        const typeLabel = this.fileTypeLabel(file.name, mime);
                        const isImage = mime.startsWith('image/') || /\.(png|jpe?g|gif|webp)$/i.test(file.name || '');
                        const isPdf = mime === 'application/pdf' || /\.pdf$/i.test(file.name || '');
                        return {
                            id: `pending-${idx}`,
                            name: file.name,
                            mime_type: mime,
                            typeLabel,
                            isImage,
                            isPdf,
                            file,
                            url: null,
                        };
                    });
                },

                removePendingUpload(index) {
                    this.pendingUploads = (this.pendingUploads || []).filter((_, idx) => idx !== index);
                },

                uploadPendingEvidence() {
                    if (!this.pendingUploads.length || this.uploading) return;
                    this.uploading = true;
                    const signature = (file) => `${file.name}|${file.size}|${file.lastModified}`;
                    const batch = [...(this.pendingUploads || [])];
                    const batchSignatures = new Set(batch.map(signature));
                    let failed = false;
                    const formData = new FormData();
                    batch.forEach((file) => formData.append('evidence_files[]', file));
                    fetch(uploadUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                        .then((res) => {
                            return res.json()
                                .catch(() => ({}))
                                .then((data) => ({ ok: res.ok, status: res.status, data }));
                        })
                        .then(({ ok, status, data }) => {
                            if (!ok) {
                                const message = data?.message
                                    || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                    || (status === 419 ? 'Session expired. Refresh the page and try again.' : null)
                                    || `Upload failed (HTTP ${status}).`;
                                throw new Error(message);
                            }
                            if (Array.isArray(data?.evidence)) {
                                this.globalEvidence = data.evidence;
                                window.dispatchEvent(new CustomEvent('evidence-updated', { detail: { evidence: this.globalEvidence } }));
                            }
                            this.pendingUploads = (this.pendingUploads || []).filter(
                                (file) => !batchSignatures.has(signature(file))
                            );
                            this.showToast('Uploads saved.', 'success', 2500);
                        })
                        .catch((err) => {
                            failed = true;
                            this.showToast(err?.message || 'Upload failed.', 'error', 3500);
                        })
                        .finally(() => {
                            this.uploading = false;
                            if (!failed && this.pendingUploads.length) {
                                this.uploadPendingEvidence();
                            }
                        });
                },

                openLibraryPreview(item) {
                    if (!item) return;
                    const previewUrl = item.url || (item.file ? URL.createObjectURL(item.file) : null);
                    this.libraryPreviewItem = { ...item, previewUrl };
                    this.libraryPreviewOpen = true;
                },

                closeLibraryPreview() {
                    this.libraryPreviewOpen = false;
                    this.libraryPreviewItem = null;
                },

                requestEvidenceUpload() {
                    const picker = document.getElementById('global-evidence-picker-input');
                    if (!picker) {
                        this.showToast('Evidence picker unavailable.', 'error', 2200);
                        return;
                    }
                    picker.click();
                },

                detachLibraryEvidence(item) {
                    if (!item || !item.entry_id) return;
                    if (!confirm('Detach evidence from this criterion? The file will remain in your uploaded files.')) return;
                    fetch(`${detachBase}/${item.id}/detach`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .then((res) => {
                            if (!res.ok) throw new Error('Failed');
                            this.globalEvidence = (this.globalEvidence || []).map((ev) => {
                                if (ev.id !== item.id) return ev;
                                return { ...ev, entry_id: null, section_code: null };
                            });
                            this.showToast('Evidence detached.', 'success', 2000);
                            window.dispatchEvent(new CustomEvent('evidence-detached', { detail: { id: item.id } }));
                            window.dispatchEvent(new CustomEvent('evidence-updated', { detail: { evidence: this.globalEvidence } }));
                        })
                        .catch(() => {
                            this.showToast('Detach failed.', 'error', 2200);
                        });
                },

                deleteLibraryEvidence(item) {
                    if (!item || item.entry_id) return;
                    const id = Number(item.id || 0);
                    if (!id) return;
                    if (this.deletingEvidenceIds[id]) return;
                    if (!confirm('Remove this evidence file? This cannot be undone.')) return;
                    this.deletingEvidenceIds[id] = true;
                    fetch(`${deleteBase}/${item.id}`, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .then((res) => {
                            if (res.status === 404) {
                                return { alreadyMissing: true };
                            }
                            if (!res.ok) throw new Error('Failed');
                            return { alreadyMissing: false };
                        })
                        .then(({ alreadyMissing }) => {
                            this.globalEvidence = (this.globalEvidence || []).filter((ev) => ev.id !== item.id);
                            this.showToast(alreadyMissing ? 'Evidence already removed.' : 'Evidence removed.', alreadyMissing ? 'info' : 'success', 2000);
                            window.dispatchEvent(new CustomEvent('evidence-updated', { detail: { evidence: this.globalEvidence } }));
                            window.dispatchEvent(new CustomEvent('evidence-detached', { detail: { id: item.id } }));
                        })
                        .catch(() => {
                            this.showToast('Remove failed.', 'error', 2200);
                        })
                        .finally(() => {
                            delete this.deletingEvidenceIds[id];
                        });
                },

                scoreChip(sectionId) {
                    const s = this.sections[String(sectionId)];
                    if (!s) return '--/--';
                    return `${Number(s.points).toFixed(0)}/${s.max}`;
                },

                scoreChipClass(sectionId) {
                    const s = this.sections[String(sectionId)];
                    if (!s) return 'border-gray-200 text-gray-500';
                    return Number(s.points || 0) > 0
                        ? 'border-green-200 bg-green-50 text-green-700'
                        : 'border-gray-200 bg-gray-50 text-gray-600';
                },

                saveDirtySectionsDraft(mode = 'auto') {
                    if (this.draftSaveInFlight) return Promise.resolve(false);
                    const forms = this.editableForms().filter((form) => {
                        const sectionCode = this.formSectionCode(form);
                        return !!sectionCode && !!this.dirtySections[sectionCode];
                    });
                    if (!forms.length) return Promise.resolve(false);
                    return this.saveFormsDraft(forms, mode);
                },

                saveFormsDraft(forms, mode = 'manual') {
                    const isManualSave = mode === 'manual';
                    const isSubmitSave = mode === 'submit';
                    const saveForms = Array.isArray(forms)
                        ? forms.filter((form) => form && form.dataset?.viewOnly !== 'true')
                        : [];

                    if (!saveForms.length) {
                        if (isManualSave) {
                            this.showToast('Nothing to save.', 'info', 2000);
                        } else if (isSubmitSave) {
                            this.showToast('Nothing to submit.', 'error', 2200);
                        }
                        return Promise.resolve(false);
                    }

                    if (this.draftSaveInFlight) {
                        if (isManualSave) {
                            this.showToast('Saving is already in progress...', 'info', 1800);
                        } else if (isSubmitSave) {
                            this.showToast('Submitting is already in progress...', 'info', 1800);
                        }
                        return Promise.resolve(false);
                    }

                    const headerSaveBtn = document.getElementById('header-save-draft-btn');
                    const headerSaveLabel = document.getElementById('header-save-draft-label');

                    this.draftSaveInFlight = true;
                    if (isManualSave) {
                        this.savingDraft = true;
                        if (headerSaveBtn) headerSaveBtn.disabled = true;
                        if (headerSaveLabel) headerSaveLabel.textContent = 'Saving draft...';
                    }
                    this.setHeaderSavedIndicator(false);
                    this.showToast(isSubmitSave ? 'Preparing submission...' : 'Saving draft...', 'info', 0);

                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const requests = saveForms.map((form) => {
                        const formData = new FormData(form);
                        this.appendDisabledFormValues(form, formData);
                        formData.set('action', 'draft');
                        return fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });
                    });

                    return Promise.all(requests)
                        .then(async (responses) => {
                            const failed = responses.find((res) => !res.ok);
                            if (failed) {
                                let message = `Save failed (HTTP ${failed.status}).`;
                                try {
                                    const payload = await failed.clone().json();
                                    if (payload?.message) {
                                        message = payload.message;
                                    } else if (payload?.errors && typeof payload.errors === 'object') {
                                        const firstGroup = Object.values(payload.errors)[0];
                                        if (Array.isArray(firstGroup) && firstGroup[0]) {
                                            message = String(firstGroup[0]);
                                        }
                                    }
                                } catch (error) {
                                    // keep default message
                                }
                                throw new Error(message);
                            }
                            saveForms.forEach((form) => {
                                this.clearSectionDirty(form);
                                this.clearLocalFormDraft(form);
                            });
                            if (isManualSave) {
                                this.showToast('Draft saved.', 'success', 2800);
                            } else if (isSubmitSave) {
                                this.showToast('Saved. Submitting...', 'success', 1800);
                            } else {
                                this.showToast('Saved just now.', 'success', 1700);
                            }
                            this.setHeaderSavedIndicator(true, 'Saved');
                            return true;
                        })
                        .catch((err) => {
                            if (isManualSave) {
                                this.showToast(err?.message || 'Draft save failed.', 'error', 3500);
                            } else if (isSubmitSave) {
                                this.showToast(err?.message || 'Cannot submit because save failed.', 'error', 3500);
                            } else {
                                this.showToast('Autosave failed. Changes are kept locally.', 'error', 3200);
                            }
                            return false;
                        })
                        .finally(() => {
                            this.draftSaveInFlight = false;
                            if (isManualSave) {
                                this.savingDraft = false;
                                if (headerSaveBtn) headerSaveBtn.disabled = false;
                                if (headerSaveLabel) headerSaveLabel.textContent = 'Save Draft';
                            }
                        });
                },

                saveDraftAll() {
                    if (this.savingDraft || this.draftSaveInFlight) return Promise.resolve(false);
                    const forms = this.editableForms();
                    return this.saveFormsDraft(forms, 'manual');
                },

                submitFinal(event) {
                    if (this.finalSubmitting) return;
                    if (!this.canSubmitByPeriod) {
                        this.showToast('Submission window is closed. Save draft only.', 'error', 3000);
                        return;
                    }
                    if (!this.canFinalSubmit()) return;

                    const forms = this.editableForms();
                    if (window.validateFormRows) {
                        for (const candidate of forms) {
                            if (!window.validateFormRows(candidate)) {
                                const sectionCode = this.formSectionCode(candidate);
                                if (sectionCode) {
                                    this.active = Number(sectionCode);
                                    if (this.sectionUrls && this.sectionUrls[this.active]) {
                                        window.history.replaceState({}, '', this.sectionUrls[this.active]);
                                    }
                                    this.$nextTick(() => this.applyReturnedLocks());
                                }
                                this.showToast('Please attach required evidence before final submit.', 'error', 3000);
                                return;
                            }
                        }
                    }

                    if (!window.confirm(this.finalSubmitConfirmMessage())) return;

                    const form = event?.target;
                    if (!form) return;

                    this.finalSubmitting = true;
                    const formsToSave = this.editableForms();

                    if (!formsToSave.length) {
                        this.showToast('Submitting...', 'info', 1200);
                        form.submit();
                        this.finalSubmitting = false;
                        return;
                    }

                    this.saveFormsDraft(formsToSave, 'submit')
                        .then((ok) => {
                            if (!ok) {
                                this.showToast('Please fix save errors before final submit.', 'error', 3000);
                                return;
                            }
                            form.submit();
                        })
                        .finally(() => {
                            this.finalSubmitting = false;
                        });
                },
            };
        }
    </script>
    <script>
        function reviewSummary(init) {
            return {
                s1: Number(init.s1 || 0),
                s2: Number(init.s2 || 0),
                s3: Number(init.s3 || 0),
                s4: Number(init.s4 || 0),
                s5: Number(init.s5 || 0),

                track: init.track || 'instructor',
                trackLabel: init.trackLabel || 'Instructor',

                hasMasters: !!init.hasMasters,
                hasDoctorate: !!init.hasDoctorate,
                hasResearchEquivalent: !!init.hasResearchEquivalent,
                hasAcceptedResearchOutput: !!init.hasAcceptedResearchOutput,
                hasMinBuYears: !!init.hasMinBuYears,

                totalPoints() {
                    return Number(this.s1 + this.s2 + this.s3 + this.s4 + this.s5);
                },

                eqPercent() {
                    return this.totalPoints() / 4;
                },

                recommendedRank() {
                    const p = this.eqPercent();
                    const ranges = {
                        full: [
                            { letter:'A', min:95.87, max:100.00 },
                            { letter:'B', min:91.50, max:95.86 },
                            { letter:'C', min:87.53, max:91.49 },
                        ],
                        associate: [
                            { letter:'A', min:83.34, max:87.52 },
                            { letter:'B', min:79.19, max:83.33 },
                            { letter:'C', min:75.02, max:79.18 },
                        ],
                        assistant: [
                            { letter:'A', min:70.85, max:75.01 },
                            { letter:'B', min:66.68, max:70.84 },
                            { letter:'C', min:62.51, max:66.67 },
                        ],
                        instructor: [
                            { letter:'A', min:58.34, max:62.50 },
                            { letter:'B', min:54.14, max:58.33 },
                            { letter:'C', min:50.00, max:54.16 },
                        ],
                    };
                    const list = ranges[this.track] || [];
                    const hit = list.find(r => p >= r.min && p <= r.max);
                    if (!hit) return '';

                    const trackLabel = {
                        full:'Full Professor',
                        associate:'Associate Professor',
                        assistant:'Assistant Professor',
                        instructor:'Instructor',
                    }[this.track] || this.trackLabel;

                    return `${trackLabel} - ${hit.letter}`;
                },

                pointsRank() {
                    const p = this.eqPercent();
                    const ranges = {
                        full: [
                            { letter:'A', min:95.87, max:100.00 },
                            { letter:'B', min:91.50, max:95.86 },
                            { letter:'C', min:87.53, max:91.49 },
                        ],
                        associate: [
                            { letter:'A', min:83.34, max:87.52 },
                            { letter:'B', min:79.19, max:83.33 },
                            { letter:'C', min:75.02, max:79.18 },
                        ],
                        assistant: [
                            { letter:'A', min:70.85, max:75.01 },
                            { letter:'B', min:66.68, max:70.84 },
                            { letter:'C', min:62.51, max:66.67 },
                        ],
                        instructor: [
                            { letter:'A', min:58.34, max:62.50 },
                            { letter:'B', min:54.14, max:58.33 },
                            { letter:'C', min:50.00, max:54.16 },
                        ],
                    };
                    const order = ['full','associate','assistant','instructor'];
                    for (const key of order) {
                        const list = ranges[key];
                        const hit = list.find(r => p >= r.min && p <= r.max);
                        if (hit) return { track: key, letter: hit.letter };
                    }
                    return null;
                },

                pointsRankLabel() {
                    const hit = this.pointsRank();
                    if (!hit) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    return `${labels[hit.track]} - ${hit.letter}`;
                },

                hasResearchEquivalentNow() {
                    return this.hasResearchEquivalent || Number(this.s3 || 0) > 0;
                },

                hasAcceptedResearchOutputNow() {
                    return this.hasAcceptedResearchOutput || this.hasResearchEquivalentNow();
                },

                allowedRankLabel() {
                    if (!this.hasMasters || !this.hasResearchEquivalentNow()) return '';
                    const labels = {
                        full: 'Full Professor',
                        associate: 'Associate Professor',
                        assistant: 'Assistant Professor',
                        instructor: 'Instructor',
                    };
                    const order = { instructor: 1, assistant: 2, associate: 3, full: 4 };
                    const nextRank = (key) => {
                        const target = order[key] + 1;
                        const hit = Object.keys(order).find((k) => order[k] === target);
                        return hit || key;
                    };

                    let desired = this.pointsRank()?.track || this.track;
                    const maxAllowed = (this.hasDoctorate && this.hasAcceptedResearchOutputNow()) ? 'full' : 'associate';
                    if (order[desired] > order[maxAllowed]) desired = maxAllowed;

                    const oneStep = nextRank(this.track);
                    if (order[desired] > order[oneStep]) desired = oneStep;

                    let letter = this.pointsRank()?.letter || '';
                    if (this.pointsRank()?.track && this.pointsRank()?.track !== desired) {
                        // If capped down from a higher points rank, show the highest letter in the allowed rank.
                        letter = 'A';
                    }

                    return letter ? `${labels[desired]} - ${letter}` : (labels[desired] || '');
                },

                canFinalSubmit() {
                    if (!this.hasMasters) return false;
                    if (!this.hasMinBuYears) return false;
                    if (!this.hasResearchEquivalentNow()) return false;
                    return true;
                },
            }
        }
    </script>
    <script>
        window.validateFormRows = function (form) {
            if (!form) return true;
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                alert('Please complete all required fields first.');
                if (typeof firstInvalid.scrollIntoView === 'function') {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                if (typeof firstInvalid.reportValidity === 'function') {
                    firstInvalid.reportValidity();
                }
                if (typeof window.reclassificationToast === 'function') {
                    window.reclassificationToast('Please complete all required fields first.', 'error', 3000);
                }
                return false;
            }

            const action = form.getAttribute('action') || '';
            const pane = form.closest('[data-section-pane]');
            const paneSection = String(pane?.getAttribute('data-section-index') || '');
            const isSection1 = paneSection === '1' || /reclassification\/section\/1/.test(action);
            const isSection4 = paneSection === '4' || /reclassification\/section\/4/.test(action);
            if (!isSection1 && !isSection4) return true;

            const getNumber = (name) => {
                const formData = new FormData(form);
                const value = parseFloat(String(formData.get(name) ?? '0'));
                return Number.isNaN(value) ? 0 : value;
            };

            const getEvidence = (name) => {
                const formData = new FormData(form);
                return formData
                    .getAll(name)
                    .map((v) => String(v || '').trim())
                    .filter(Boolean);
            };

            let first = null;
            let message = '';

            if (isSection1) {
                const formData = new FormData(form);
                const honors = String(formData.get('section1[a1][honors]') || '');
                if (honors && honors !== 'none' && getEvidence('section1[a1][evidence][]').length === 0) {
                    first = 'a1';
                    message = 'Please attach evidence for Section I-A1 before going next.';
                }
            }

            if (!first && isSection4) {
                const a1Years = getNumber('section4[a][a1_years]');
                const a2Years = getNumber('section4[a][a2_years]');
                const bYears = getNumber('section4[b][years]');
                const bUnlocked = (a2Years >= 3 && a1Years >= 2) || a2Years >= 5;
                const missing = [];
                if (a1Years > 0 && getEvidence('section4[a][a1_evidence][]').length === 0) missing.push('a1');
                if (a2Years > 0 && getEvidence('section4[a][a2_evidence][]').length === 0) missing.push('a2');
                if (bYears > 0 && bUnlocked && getEvidence('section4[b][evidence][]').length === 0) missing.push('b');
                if (missing.length) {
                    first = missing[0];
                    const byKey = {
                        a1: 'Please attach evidence for Section IV-A1 before going next.',
                        a2: 'Please attach evidence for Section IV-A2 before going next.',
                        b: 'Please attach evidence for Section IV-B before going next.',
                    };
                    message = byKey[first] || 'Please attach evidence for Section IV before going next.';
                }
            }

            if (!first) return true;

            const target = form.querySelector(`[data-evidence-block="${first}"]`);
            alert(message || 'Please attach evidence before going next.');
            if (target) {
                target.classList.add('ring-2', 'ring-red-400', 'rounded-xl');
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => target.classList.remove('ring-2', 'ring-red-400', 'rounded-xl'), 2000);
            }
            return false;
        };
    </script>

    {{-- Library Preview --}}
    <div x-cloak x-show="libraryPreviewOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeLibraryPreview()"></div>
        <div class="relative bg-white w-full max-w-4xl mx-4 rounded-2xl shadow-xl border">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800" x-text="libraryPreviewItem?.name || 'Preview'"></h3>
                <button type="button" @click="closeLibraryPreview()" class="text-gray-500 hover:text-gray-700">Close</button>
            </div>
            <div class="p-6">
                <template x-if="libraryPreviewItem && libraryPreviewItem.isImage">
                    <img :src="libraryPreviewItem.previewUrl || libraryPreviewItem.url" alt="Preview" class="max-h-[70vh] mx-auto rounded-lg border" />
                </template>
                <template x-if="libraryPreviewItem && libraryPreviewItem.isPdf">
                    <iframe :src="libraryPreviewItem.previewUrl || libraryPreviewItem.url" class="w-full h-[70vh] rounded-lg border"></iframe>
                </template>
                <template x-if="libraryPreviewItem && !libraryPreviewItem.isImage && !libraryPreviewItem.isPdf">
                    <div class="text-sm text-gray-600 space-y-3">
                        <p>Preview is not available for this file type.</p>
                        <template x-if="libraryPreviewItem.url">
                            <a :href="libraryPreviewItem.url" target="_blank" class="text-bu hover:underline">Open in new tab</a>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <button type="button"
            x-cloak
            x-show="showBackToTop"
            @click="scrollToTop()"
            class="fixed bottom-6 right-6 z-50 inline-flex h-11 w-11 items-center justify-center rounded-full bg-bu text-white shadow-soft hover:bg-bu-dark transition"
            aria-label="Back to top"
            title="Back to top">
        <span aria-hidden="true">&uarr;</span>
    </button>

    <div x-cloak x-show="libraryToast.show" class="fixed bottom-6 left-6 z-50">
        <div class="px-4 py-2 rounded-lg shadow-lg text-sm text-white"
             :class="libraryToast.type === 'success' ? 'bg-green-600' : (libraryToast.type === 'error' ? 'bg-red-600' : 'bg-slate-800')">
            <span x-text="libraryToast.message"></span>
        </div>
    </div>
    @include('reclassification.partials.async-actions')
    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const statusNode = () => document.getElementById('faculty-header-actions');
            let lastKnownStatus = String(statusNode()?.dataset?.currentStatus || '').trim();
            let syncTimer = null;
            let syncBusy = false;

            const isCommentsPanelOpen = () => {
                const panel = document.getElementById('faculty-comments-panel');
                if (panel) {
                    if (panel.getAttribute('data-open') === '1') return true;
                    const display = window.getComputedStyle(panel).display;
                    if (display !== 'none') return true;
                }
                const root = document.getElementById('faculty-reclassification-root');
                const alpineData = root?.__x?.$data || null;
                return !!(alpineData && alpineData.commentsPanelOpen);
            };

            const refreshTargets = async () => {
                const selectors = [
                    '#faculty-header-actions',
                    '#faculty-comment-overview',
                    '#faculty-comment-fab',
                    '#faculty-comment-threads',
                    '#faculty-review-actions',
                ];
                if (window.AsyncActions?.refreshTargets) {
                    await window.AsyncActions.refreshTargets(selectors, { keepScroll: true });
                    if (window.AsyncActions?.bindAsyncForms) {
                        window.AsyncActions.bindAsyncForms(document);
                    }
                    if (window.BuUx?.bindActionLoading) {
                        window.BuUx.bindActionLoading(document);
                    }
                    return;
                }
            };

            const fetchPageDocument = async () => {
                const response = await fetch(window.location.href, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-UX-Background': '1',
                    },
                });
                if (!response.ok) {
                    throw new Error(`Refresh failed (HTTP ${response.status}).`);
                }
                const html = await response.text();
                return new DOMParser().parseFromString(html, 'text/html');
            };

            const syncOpenPanelCounters = async () => {
                const parsed = await fetchPageDocument();

                const currentOverview = document.getElementById('faculty-comment-overview');
                const incomingOverview = parsed.querySelector('#faculty-comment-overview');
                if (currentOverview && incomingOverview) {
                    const openCount = incomingOverview.getAttribute('data-open-required-count');
                    if (openCount !== null) {
                        currentOverview.setAttribute('data-open-required-count', openCount);
                    }
                    const copyText = (selector) => {
                        const currentNode = currentOverview.querySelector(selector);
                        const incomingNode = incomingOverview.querySelector(selector);
                        if (!currentNode || !incomingNode) return;
                        const next = String(incomingNode.textContent || '');
                        if (String(currentNode.textContent || '') !== next) {
                            currentNode.textContent = next;
                        }
                    };
                    copyText('[data-tracker-open]');
                    copyText('[data-tracker-addressed]');
                    copyText('[data-tracker-resolved]');
                    copyText('[data-tracker-notes]');
                    copyText('[data-tracker-hint]');
                    copyText('[data-tracker-progress-label]');

                    const currentBar = currentOverview.querySelector('[data-tracker-progress-bar]');
                    const incomingBar = incomingOverview.querySelector('[data-tracker-progress-bar]');
                    if (currentBar && incomingBar) {
                        const nextWidth = String(incomingBar.style.width || '');
                        if (String(currentBar.style.width || '') !== nextWidth) {
                            currentBar.style.width = nextWidth;
                        }
                    }
                }

                const currentFab = document.getElementById('faculty-comment-fab');
                const incomingFab = parsed.querySelector('#faculty-comment-fab');
                if (currentFab && incomingFab) {
                    const currentBadge = currentFab.querySelector('span.absolute');
                    const incomingBadge = incomingFab.querySelector('span.absolute');
                    if (currentBadge && incomingBadge) {
                        currentBadge.textContent = incomingBadge.textContent || '';
                    }
                    currentFab.classList.remove('hidden');
                } else if (currentFab && !incomingFab) {
                    currentFab.classList.add('hidden');
                }

                const incomingStatus = String(
                    parsed.querySelector('#faculty-header-actions')?.getAttribute('data-current-status') || ''
                ).trim();
                if (incomingStatus) {
                    handleStatusChange(incomingStatus);
                }
            };

            const post = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-UX-Background': '1',
                    },
                    body: payload,
                });
                let data = {};
                try {
                    data = await response.json();
                } catch (error) {
                    data = {};
                }
                if (!response.ok) {
                    const message = data?.message || `Request failed (HTTP ${response.status}).`;
                    throw new Error(message);
                }
                return data;
            };

            window.BuFacultyInlineComments = {
                async reply(comment, body) {
                    const url = String(comment?.reply_url || '').trim();
                    if (!url) throw new Error('Reply route is missing.');
                    const formData = new FormData();
                    formData.set('body', String(body || '').trim());
                    if (!formData.get('body')) throw new Error('Reply is required.');
                    window.AsyncActions?.setIndicator?.('Saving reply...', 'loading', true);
                    try {
                        const data = await post(url, formData);
                        await refreshTargets();
                        const message = data?.message || 'Reply sent.';
                        window.AsyncActions?.setIndicator?.(message, 'success', false);
                        window.BuUx?.toast?.(message, 'success', 2200);
                        return data;
                    } catch (error) {
                        const message = error?.message || 'Unable to send reply.';
                        window.AsyncActions?.setIndicator?.(message, 'error', false);
                        window.BuUx?.toast?.(message, 'error', 3200);
                        throw error;
                    }
                },
                async updateReply(reply, body) {
                    const url = String(reply?.update_reply_url || '').trim();
                    if (!url) throw new Error('Edit route is missing.');
                    const formData = new FormData();
                    formData.set('body', String(body || '').trim());
                    if (!formData.get('body')) throw new Error('Reply is required.');
                    window.AsyncActions?.setIndicator?.('Updating reply...', 'loading', true);
                    try {
                        const data = await post(url, formData);
                        await refreshTargets();
                        const message = data?.message || 'Reply updated.';
                        window.AsyncActions?.setIndicator?.(message, 'success', false);
                        window.BuUx?.toast?.(message, 'success', 2200);
                        return data;
                    } catch (error) {
                        const message = error?.message || 'Unable to update reply.';
                        window.AsyncActions?.setIndicator?.(message, 'error', false);
                        window.BuUx?.toast?.(message, 'error', 3200);
                        throw error;
                    }
                },
                async address(comment) {
                    const url = String(comment?.address_url || '').trim();
                    if (!url) throw new Error('Address route is missing.');
                    window.AsyncActions?.setIndicator?.('Marking addressed...', 'loading', true);
                    try {
                        const data = await post(url, new FormData());
                        await refreshTargets();
                        const message = data?.message || 'Comment marked as addressed.';
                        window.AsyncActions?.setIndicator?.(message, 'success', false);
                        window.BuUx?.toast?.(message, 'success', 2200);
                        return data;
                    } catch (error) {
                        const message = error?.message || 'Unable to mark as addressed.';
                        window.AsyncActions?.setIndicator?.(message, 'error', false);
                        window.BuUx?.toast?.(message, 'error', 3200);
                        throw error;
                    }
                },
            };

            const shouldSkipBackgroundSync = () => {
                const active = document.activeElement;
                if (!(active instanceof HTMLElement)) return false;
                const isTypingTarget = active.matches('input, textarea, select') || active.isContentEditable;
                if (!isTypingTarget) return false;
                // Do not refresh while typing to avoid input loss.
                if (active.closest('#faculty-comment-threads')) return true;
                if (active.closest('[data-inline-review-comments]')) return true;
                return false;
            };

            const handleStatusChange = (newStatus) => {
                const previous = String(lastKnownStatus || '').trim();
                const next = String(newStatus || '').trim();
                if (!next || previous === next) return;
                lastKnownStatus = next;

                if (next === 'returned_to_faculty') {
                    window.BuUx?.toast?.('This submission was returned to faculty.', 'info', 2200);
                    return;
                }

                window.BuUx?.toast?.('Submission stage updated.', 'info', 2000);
            };

            const runBackgroundSync = async () => {
                if (syncBusy || document.hidden || shouldSkipBackgroundSync()) return;

                syncBusy = true;
                try {
                    if (isCommentsPanelOpen()) {
                        await syncOpenPanelCounters();
                        return;
                    }

                    if (window.AsyncActions?.refreshTargets) {
                        await refreshTargets();
                        const currentStatus = String(statusNode()?.dataset?.currentStatus || '').trim();
                        handleStatusChange(currentStatus);
                        return;
                    }

                    const parsed = await fetchPageDocument();
                    const incomingStatus = String(
                        parsed.querySelector('#faculty-header-actions')?.getAttribute('data-current-status') || ''
                    ).trim();
                    handleStatusChange(incomingStatus);
                } catch (error) {
                    // silent background poll failure
                } finally {
                    syncBusy = false;
                }
            };

            const startBackgroundSync = () => {
                if (syncTimer) {
                    clearInterval(syncTimer);
                    syncTimer = null;
                }
                syncTimer = window.setInterval(runBackgroundSync, 12000);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        runBackgroundSync();
                    }
                });
            };

            startBackgroundSync();
        })();
    </script>
    </div>
</x-app-layout>
