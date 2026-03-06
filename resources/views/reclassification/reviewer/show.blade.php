<x-app-layout>
    @php
        $sections = $application->sections->sortBy('section_code');
        $statusLabel = match($application->status) {
            'dean_review' => 'Dean',
            'hr_review' => 'HR',
            'vpaa_review' => 'VPAA',
            'vpaa_approved' => 'VPAA Approved',
            'president_review' => 'President',
            'returned_to_faculty' => 'Returned',
            'finalized' => 'Finalized',
            'rejected_final' => 'Rejected',
            default => ucfirst(str_replace('_',' ', $application->status)),
        };
        $sectionsByCode = $sections->keyBy('section_code');
        $sectionTotals = [
            '1' => (float) optional($sectionsByCode->get('1'))->points_total,
            '2' => (float) optional($sectionsByCode->get('2'))->points_total,
            '3' => (float) optional($sectionsByCode->get('3'))->points_total,
            '4' => (float) optional($sectionsByCode->get('4'))->points_total,
            '5' => (float) optional($sectionsByCode->get('5'))->points_total,
        ];
        $totalPoints = array_sum($sectionTotals);
        $eqPercent = $totalPoints / 4;
        $trackKey = match (strtolower(trim((string) ($currentRankLabel ?? 'Instructor')))) {
            'full professor', 'full' => 'full',
            'associate professor', 'associate' => 'associate',
            'assistant professor', 'assistant' => 'assistant',
            default => 'instructor',
        };
        $rankLabels = [
            'full' => 'Full Professor',
            'associate' => 'Associate Professor',
            'assistant' => 'Assistant Professor',
            'instructor' => 'Instructor',
        ];
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
        $pointsRankTrack = null;
        $pointsRankLetter = null;
        foreach (['full', 'associate', 'assistant', 'instructor'] as $rank) {
            foreach ($ranges[$rank] as $band) {
                if ($eqPercent >= $band['min'] && $eqPercent <= $band['max']) {
                    $pointsRankTrack = $rank;
                    $pointsRankLetter = $band['letter'];
                    break 2;
                }
            }
        }
        $pointsRankLabel = $pointsRankTrack
            ? ($rankLabels[$pointsRankTrack] . ' - ' . $pointsRankLetter)
            : '-';

        $hasMasters = (bool) ($eligibility['hasMasters'] ?? false);
        $hasDoctorate = (bool) ($eligibility['hasDoctorate'] ?? false);
        $hasResearchEquivalent = (bool) ($eligibility['hasResearchEquivalent'] ?? false);
        $hasAcceptedResearchOutput = (bool) ($eligibility['hasAcceptedResearchOutput'] ?? false);

        $allowedRankLabel = 'Not eligible';
        if ($hasMasters && $hasResearchEquivalent) {
            $order = ['instructor' => 1, 'assistant' => 2, 'associate' => 3, 'full' => 4];
            $desired = $pointsRankTrack ?: $trackKey;
            $maxAllowed = ($hasDoctorate && $hasAcceptedResearchOutput) ? 'full' : 'associate';
            if (($order[$desired] ?? 0) > ($order[$maxAllowed] ?? 0)) {
                $desired = $maxAllowed;
            }
            $oneStepOrder = ($order[$trackKey] ?? 1) + 1;
            $oneStep = array_search($oneStepOrder, $order, true) ?: $trackKey;
            if (($order[$desired] ?? 0) > ($order[$oneStep] ?? 0)) {
                $desired = $oneStep;
            }
            $allowedLetter = $pointsRankLetter;
            if ($pointsRankTrack && $pointsRankTrack !== $desired) {
                // If capped down from a higher points rank, use highest letter in the allowed rank.
                $allowedLetter = 'A';
            }
            $allowedRankLabel = ($rankLabels[$desired] ?? 'Not eligible')
                . ($allowedLetter ? (' - ' . $allowedLetter) : '');
        }
        $criterionLabels = [
            '1' => [
                'a1' => 'A1. Bachelor’s Degree (Latin honors)',
                'a2' => 'A2. Additional Bachelor’s Degree',
                'a3' => 'A3. Master’s Degree',
                'a4' => 'A4. Master’s Degree Units',
                'a5' => 'A5. Additional Master’s Degree',
                'a6' => 'A6. Doctoral Units',
                'a7' => 'A7. Doctor’s Degree',
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
        $criterionOrder = [
            '1' => ['a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'b', 'b_prev', 'c', 'c_prev'],
            '2' => ['ratings', 'previous_points'],
            '3' => ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9', 'previous_points'],
            '4' => ['a1', 'a2', 'b'],
            '5' => ['a', 'b', 'b_prev', 'c1', 'c2', 'c3', 'c_prev', 'd', 'd_prev', 'previous_points'],
        ];
        $section1Ranges = [
            'speaker' => [
                'international' => [13, 15],
                'national' => [11, 12],
                'regional' => [9, 10],
                'provincial' => [7, 8],
                'municipal' => [4, 6],
                'school' => [1, 3],
            ],
            'resource' => [
                'international' => [11, 12],
                'national' => [9, 10],
                'regional' => [7, 8],
                'provincial' => [5, 6],
                'municipal' => [3, 4],
                'school' => [1, 2],
            ],
            'participant' => [
                'international' => [9, 10],
                'national' => [7, 8],
                'regional' => [5, 6],
                'provincial' => [3, 4],
                'municipal' => [2, 2],
                'school' => [1, 1],
            ],
        ];
        $reviewerRole = strtolower((string) (auth()->user()->role ?? ''));
        $isHrReviewer = $reviewerRole === 'hr';
        $hasPendingFacultyReturnRequest = !is_null($application->faculty_return_requested_at ?? null);
        $returnTrailsAsc = collect($application->statusTrails ?? [])
            ->filter(fn ($trail) => (string) ($trail->action ?? '') === 'return_to_faculty')
            ->sortBy('created_at')
            ->values();
        $resubmitTrailsAsc = collect($application->statusTrails ?? [])
            ->filter(fn ($trail) => (string) ($trail->action ?? '') === 'resubmit')
            ->sortBy('created_at')
            ->values();
        $ordinalReturnLabel = function (int $index): string {
            return match ($index) {
                1 => 'First Return',
                2 => 'Second Return',
                3 => 'Third Return',
                4 => 'Fourth Return',
                5 => 'Fifth Return',
                default => "{$index}th Return",
            };
        };
        $resolveCommentReturnLabel = function ($createdAt) use ($returnTrailsAsc, $resubmitTrailsAsc, $ordinalReturnLabel): string {
            if ($returnTrailsAsc->isEmpty()) {
                return 'Current Review';
            }

            foreach ($returnTrailsAsc as $index => $returnTrail) {
                $returnedAt = $returnTrail->created_at;
                $previousResubmit = $resubmitTrailsAsc
                    ->filter(fn ($resubmit) => $resubmit->created_at && $returnedAt && $resubmit->created_at->lt($returnedAt))
                    ->last();
                $startAt = optional($previousResubmit)->created_at;

                if (!$createdAt) {
                    continue;
                }
                if ($startAt && !$createdAt->gt($startAt)) {
                    continue;
                }
                if ($returnedAt && !$createdAt->lte($returnedAt)) {
                    continue;
                }

                return $ordinalReturnLabel($index + 1);
            }

            $lastReturnedAt = optional($returnTrailsAsc->last())->created_at;
            if ($createdAt && $lastReturnedAt && $createdAt->gt($lastReturnedAt)) {
                return 'Current Review';
            }

            return 'Before First Return';
        };
        $returnSnapshotMetaByLabel = $returnTrailsAsc
            ->values()
            ->mapWithKeys(function ($trail, $index) use ($ordinalReturnLabel) {
                $label = $ordinalReturnLabel($index + 1);
                $reviewer = $trail->actor?->name
                    ?: ucfirst((string) ($trail->actor_role ?? 'Reviewer'));
                return [
                    $label => [
                        'reviewer' => (string) $reviewer,
                        'date_label' => optional($trail->created_at)->format('M d, Y g:i A'),
                    ],
                ];
            });
        $returnSnapshotMetaById = $returnTrailsAsc
            ->values()
            ->mapWithKeys(function ($trail, $index) use ($ordinalReturnLabel) {
                $reviewer = $trail->actor?->name
                    ?: ucfirst((string) ($trail->actor_role ?? 'Reviewer'));
                return [
                    (int) $trail->id => [
                        'label' => $ordinalReturnLabel($index + 1),
                        'reviewer' => (string) $reviewer,
                        'date_label' => optional($trail->created_at)->format('M d, Y g:i A'),
                    ],
                ];
            });
        $latestEntryChangeById = collect($application->changeLogs ?? [])
            ->filter(fn ($log) => !is_null($log->reclassification_section_entry_id ?? null))
            ->sortByDesc('created_at')
            ->unique(fn ($log) => (int) ($log->reclassification_section_entry_id ?? 0))
            ->keyBy(fn ($log) => (int) ($log->reclassification_section_entry_id ?? 0));
        $commentCenterItems = $sections
            ->flatMap(function ($sec) use ($criterionLabels, $latestEntryChangeById, $changeLogDetails, $resolveCommentReturnLabel, $returnSnapshotMetaByLabel) {
                $sectionCode = (string) ($sec->section_code ?? '');
                return ($sec->entries ?? collect())->flatMap(function ($entry) use ($sectionCode, $criterionLabels, $latestEntryChangeById, $changeLogDetails, $resolveCommentReturnLabel, $returnSnapshotMetaByLabel) {
                    $criterionKey = (string) ($entry->criterion_key ?? '');
                    $criterionLabel = $criterionLabels[$sectionCode][$criterionKey] ?? strtoupper($criterionKey);
                    $entryId = (int) ($entry->id ?? 0);
                    $entryChange = $latestEntryChangeById->get($entryId);
                    $entryChangeType = $entryChange ? (string) ($entryChange->change_type ?? 'update') : null;
                    $entryChangeSummary = $entryChange ? (string) ($entryChange->summary ?? '') : '';
                    $entryChangeDetails = $entryChange
                        ? collect($changeLogDetails[$entryChange->id] ?? [])->take(2)->values()->all()
                        : [];
                    $entryData = is_array($entry->data) ? $entry->data : [];
                    $entryRemoved = in_array(
                        strtolower(trim((string) ($entryData['is_removed'] ?? ''))),
                        ['1', 'true', 'yes', 'on'],
                        true
                    );
                    return ($entry->rowComments ?? collect())
                        ->filter(fn ($comment) => is_null($comment->parent_id ?? null))
                        ->map(function ($comment) use (
                            $entry,
                            $sectionCode,
                            $criterionKey,
                            $criterionLabel,
                            $entryRemoved,
                            $entryChangeType,
                            $entryChangeSummary,
                            $entryChangeDetails,
                            $resolveCommentReturnLabel,
                            $returnSnapshotMetaByLabel
                        ) {
                            $returnLabel = $resolveCommentReturnLabel($comment->created_at);
                            $returnMeta = $returnSnapshotMetaByLabel->get($returnLabel, []);
                            return [
                                'id' => (int) $comment->id,
                                'entry_id' => (int) ($entry->id ?? 0),
                                'section_code' => $sectionCode,
                                'criterion_key' => strtoupper($criterionKey),
                                'criterion_label' => $criterionLabel,
                                'entry_removed' => $entryRemoved,
                                'entry_change_type' => $entryChangeType,
                                'entry_change_summary' => $entryChangeSummary,
                                'entry_change_details' => $entryChangeDetails,
                                'visibility' => (string) ($comment->visibility ?? 'internal'),
                                'action_type' => (string) ($comment->action_type ?? 'requires_action'),
                                'status' => (string) ($comment->status ?? 'open'),
                                'author_role' => strtolower((string) ($comment->author?->role ?? '')),
                                'return_label' => $returnLabel,
                                'return_reviewer' => (string) ($returnMeta['reviewer'] ?? ''),
                                'return_date_label' => (string) ($returnMeta['date_label'] ?? ''),
                                'body' => trim((string) ($comment->body ?? '')),
                                'author' => (string) ($comment->author?->name ?? 'Reviewer'),
                                'created_at' => optional($comment->created_at)->toDateTimeString(),
                                'created_at_label' => optional($comment->created_at)->format('M d, Y g:i A'),
                            ];
                        });
                });
            })
            ->sortByDesc('created_at')
            ->values();
        $commentCenterOpenCount = $commentCenterItems
            ->filter(fn ($item) => ($item['action_type'] ?? 'requires_action') === 'requires_action')
            ->filter(fn ($item) => ($item['status'] ?? 'open') !== 'resolved')
            ->count();
        $returnTrails = collect($application->statusTrails ?? [])
            ->filter(fn ($trail) => (string) ($trail->action ?? '') === 'return_to_faculty')
            ->sortByDesc('created_at')
            ->values();
        $revisionPanelItems = collect($application->changeLogs ?? [])
            ->sortByDesc('created_at')
            ->values()
            ->take(80)
            ->map(function ($log) use ($criterionLabels, $changeLogDetails, $returnTrails, $returnSnapshotMetaById) {
                $sectionCode = (string) ($log->section_code ?? '');
                $criterionKey = (string) ($log->criterion_key ?? '');
                $criterionLabel = $criterionKey !== ''
                    ? ($criterionLabels[$sectionCode][$criterionKey] ?? strtoupper($criterionKey))
                    : "Section {$sectionCode}";
                $matchingReturn = $returnTrails->first(function ($trail) use ($log) {
                    if (!$trail->created_at || !$log->created_at) {
                        return false;
                    }
                    return $trail->created_at->lessThanOrEqualTo($log->created_at);
                });
                $batchKey = $matchingReturn
                    ? ('return_' . (int) $matchingReturn->id)
                    : 'initial';
                $snapshotMeta = $matchingReturn
                    ? ($returnSnapshotMetaById->get((int) $matchingReturn->id) ?? null)
                    : null;
                $batchLabel = $matchingReturn
                    ? ((string) ($snapshotMeta['label'] ?? 'Return') . ' - ' . (string) ($snapshotMeta['reviewer'] ?? 'Reviewer'))
                    : 'Before first return';
                $batchDateLabel = $matchingReturn
                    ? (string) ($snapshotMeta['date_label'] ?? optional($matchingReturn->created_at)->format('M d, Y g:i A'))
                    : '';
                $batchSortAt = $matchingReturn?->created_at
                    ? $matchingReturn->created_at->toDateTimeString()
                    : optional($log->created_at)->toDateTimeString();
                return [
                    'id' => (int) $log->id,
                    'entry_id' => (int) ($log->reclassification_section_entry_id ?? 0),
                    'section_code' => $sectionCode,
                    'criterion_label' => $criterionLabel,
                    'summary' => (string) ($log->summary ?: 'Updated entry.'),
                    'change_type' => (string) ($log->change_type ?? 'update'),
                    'actor' => (string) ($log->actor?->name ?? 'Faculty'),
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                    'created_at_label' => optional($log->created_at)->format('M d, Y g:i A'),
                    'details' => collect($changeLogDetails[$log->id] ?? [])->take(5)->values()->all(),
                    'batch_key' => $batchKey,
                    'batch_label' => $batchLabel,
                    'batch_date_label' => $batchDateLabel,
                    'batch_sort_at' => $batchSortAt,
                ];
            })
            ->values();
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    Reclassification Review
                </h2>
                <p class="text-sm text-gray-500">{{ $statusLabel }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reclassification.review.queue') }}"
                   class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Back to Queue
                </a>
                @if($isHrReviewer)
                    <form method="POST"
                          action="{{ route('reclassification.admin.submissions.destroy', $application) }}"
                          onsubmit="return confirm('Delete this reclassification and all related records? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100">
                            Delete Reclassification
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div id="reviewer-content"
             data-async-state-keys="panelOpen,revisionPanelOpen,activeTab,activeRevisionTab,showDetailedRevisionLog,commentGroupsOpen,revisionGroupsOpen"
             x-data="reviewerCommentCenter(@js($commentCenterItems->all()), @js($commentCenterOpenCount), @js($revisionPanelItems->all()), @js($reviewerRole), @js((int) $application->id))"
             x-init="init()"
             class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="fixed top-20 sm:top-24 right-6 z-[90] group">
                <button type="button"
                        id="reviewer-comments-fab"
                        @click="panelOpen = true; revisionPanelOpen = false"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow-lg hover:bg-gray-50"
                        aria-label="Comments">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M18 10c0 3.866-3.582 7-8 7a8.94 8.94 0 01-3.705-.77L2 17.5l1.346-3.364A6.735 6.735 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" />
                    </svg>
                    <span x-show="trackerOpenRequiredCount() > 0"
                          class="absolute -top-1 -right-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white"
                          x-text="trackerOpenRequiredCount()">
                    </span>
                </button>
                <span class="pointer-events-none absolute right-14 top-1/2 -translate-y-1/2 whitespace-nowrap rounded-md bg-black px-2 py-1 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                    Open comments
                </span>
            </div>

            <div class="fixed top-[7.75rem] sm:top-[9.5rem] right-6 z-[90] group">
                <button type="button"
                        id="reviewer-revision-fab"
                        @click="revisionPanelOpen = true; panelOpen = false"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow-lg hover:bg-gray-50"
                        aria-label="Revision log">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3a1 1 0 00.293.707l2 2a1 1 0 101.414-1.414L11 9.586V7z" clip-rule="evenodd" />
                    </svg>
                    <span x-show="revisionCount > 0"
                          class="absolute -top-1 -right-1 inline-flex min-w-5 items-center justify-center rounded-full bg-slate-700 px-1.5 py-0.5 text-[10px] font-semibold text-white"
                          x-text="revisionCount">
                    </span>
                </button>
                <span class="pointer-events-none absolute right-14 top-1/2 -translate-y-1/2 whitespace-nowrap rounded-md bg-black px-2 py-1 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                    Revision log
                </span>
            </div>

            <div id="reviewer-comments-panel"
                 x-show="panelOpen"
                 x-cloak
                 class="fixed top-0 right-0 h-screen z-[95] w-full max-w-lg overflow-visible border-l border-gray-200 bg-white shadow-2xl flex flex-col transition-all duration-200">
                <div class="absolute -left-12 top-4 z-[96] group">
                    <button type="button"
                            @click="revisionPanelOpen = true; panelOpen = false"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow-lg hover:bg-gray-50"
                            aria-label="Open revision log">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3a1 1 0 00.293.707l2 2a1 1 0 101.414-1.414L11 9.586V7z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <span class="pointer-events-none absolute left-12 top-1/2 -translate-y-1/2 whitespace-nowrap rounded-md bg-black px-2 py-1 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                        Revision log
                    </span>
                </div>
                <div class="px-3 py-3 border-b bg-gray-50 flex items-center justify-between gap-2">
                    <div>
    <div class="text-sm font-semibold text-gray-800">Reviewer's Comments</div>
                        <div class="text-xs text-gray-500">Track feedback while reviewing the paper.</div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button"
                                @click="panelOpen = false"
                                class="px-2.5 py-1 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-100">
                            Close
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-semibold text-slate-900">Comments tracker</div>
                        <div class="mt-0.5 text-xs text-slate-600">
                            Action required: <span class="font-semibold" x-text="trackerOpenRequiredCount()"></span>
                            &middot;
                            Addressed: <span class="font-semibold" x-text="trackerAddressedCount()"></span>
                            &middot;
                            Resolved: <span class="font-semibold" x-text="trackerResolvedCount()"></span>
                            &middot;
                            Notes: <span class="font-semibold" x-text="notesCount()"></span>
                        </div>
                        <div class="mt-2" x-show="trackerHasResolutionProgress()">
                            <div class="mb-1 flex items-center justify-between text-[11px] text-slate-600">
                                <span>Resolved progress</span>
                                <span x-text="`${trackerResolvedCount()}/${trackerRequiredTotalCount()} (${trackerResolutionProgress()}%)`"></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full bg-bu transition-all duration-300"
                                     :style="`width: ${trackerResolutionProgress()}%`"></div>
                            </div>
                        </div>
                        <div class="mt-2 text-[11px] text-slate-600" x-show="trackerRequiredTotalCount() < 1">
                            No action-required comments yet for this stage.
                        </div>
                        <div class="mt-2 text-[11px] text-slate-600" x-show="trackerRequiredTotalCount() > 0 && !trackerHasResolutionProgress()">
                            Progress bar appears after faculty addresses at least one action-required comment in this stage.
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-2">
                        <div class="grid grid-cols-4 gap-2 text-xs">
                            <template x-for="tabItem in tabs" :key="tabItem.key">
                                <button type="button"
                                        @click="activeTab = tabItem.key"
                                        class="px-3 py-2 rounded-lg border font-semibold transition"
                                        :class="activeTab === tabItem.key
                                            ? 'bg-bu text-white border-bu'
                                            : 'bg-white text-gray-700 border-gray-300'">
                                    <span x-text="`${tabItem.label} (${countFor(tabItem.key)})`"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <template x-if="visibleItemCount() === 0">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-xs text-gray-500">
                            <span x-show="activeTab === 'notes'">No comments yet.</span>
                            <span x-show="activeTab === 'resolved'">No resolved comments yet.</span>
                            <span x-show="activeTab !== 'notes' && activeTab !== 'resolved'">No required actions yet.</span>
                        </div>
                    </template>

                    <template x-if="activeTab === 'open'">
                        <div class="space-y-2">
                            <template x-for="group in groupedCurrentSections()" :key="group.section">
                                <div x-show="hasVisibleInGroup(group.items)" x-cloak class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                                    <button type="button"
                                            @click="toggleCommentGroup('current', group.section)"
                                            class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50">
                                        <div class="min-w-0">
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-600"
                                                 x-text="`${group.sectionLabel} (${countFor(activeTab, group.items)})`"></div>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="h-4 w-4 text-gray-500 transition-transform"
                                             :class="isCommentGroupOpen('current', group.section) ? 'rotate-180' : ''"
                                             viewBox="0 0 20 20"
                                             fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div x-show="isCommentGroupOpen('current', group.section)" class="space-y-2 px-3 pb-3">
                                        <template x-for="item in group.items" :key="item.id">
                                            <button type="button"
                                                    x-show="matchesFilter(item.action_type, item.status, item.author_role)"
                                                    x-cloak
                                                    @click="jumpToEntry(item.entry_id)"
                                                    class="w-full rounded-lg border border-gray-200 bg-white p-3 text-left space-y-2 cursor-pointer hover:border-bu/40 transition">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-semibold text-gray-800 truncate" x-text="item.criterion_label"></div>
                                                        <div class="text-xs text-gray-500">
                                                            <span x-text="item.author"></span>
                                                            <span> - </span>
                                                            <span x-text="item.created_at_label || '-'"></span>
                                                        </div>
                                                        <template x-if="item.entry_removed">
                                                            <div class="mt-0.5 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                                                Entry removed by faculty
                                                            </div>
                                                        </template>
                                                        <template x-if="item.entry_change_type && !item.entry_removed">
                                                            <div class="mt-0.5 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                                 :class="revisionTypeClass({ change_type: item.entry_change_type })"
                                                                 x-text="`Entry ${revisionTypeLabel({ change_type: item.entry_change_type })}`">
                                                            </div>
                                                        </template>
                                                        <div class="text-[11px] font-semibold text-gray-500">Reviewer's Comment:</div>
                                                        <div class="text-sm leading-5 text-gray-800 break-words" x-text="item.body || 'No message'"></div>
                                                        <template x-if="item.entry_change_summary">
                                                            <div class="mt-1 text-[10px] text-gray-500 truncate" x-text="item.entry_change_summary"></div>
                                                        </template>
                                                        <template x-if="item.entry_change_details && item.entry_change_details.length">
                                                            <ul class="mt-1 list-disc pl-4 space-y-0.5 text-[10px] text-gray-600">
                                                                <template x-for="line in item.entry_change_details" :key="line">
                                                                    <li x-text="line"></li>
                                                                </template>
                                                            </ul>
                                                        </template>
                                                    </div>
                                                    <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                          :class="statusClass(item)"
                                                          x-text="statusLabel(item)">
                                                    </span>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="activeTab === 'resolved'">
                        <div class="space-y-2">
                            <template x-for="snapshot in groupedResolvedStageItems()" :key="snapshot.key">
                                <div class="space-y-2" x-show="snapshotHasVisible(snapshot)" x-cloak>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                        <div class="text-xs font-semibold text-slate-800" x-text="snapshot.label"></div>
                                        <div class="text-[11px] text-slate-600" x-text="`${snapshot.count} resolved`"></div>
                                    </div>
                                    <template x-for="group in snapshot.sections" :key="`${snapshot.key}-${group.section}`">
                                        <div x-show="hasVisibleInGroup(group.items)" x-cloak class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                                            <button type="button"
                                                    @click="toggleCommentGroup(snapshot.key, group.section)"
                                                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50">
                                                <div class="min-w-0">
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-600"
                                                         x-text="`${group.sectionLabel} (${countFor(activeTab, group.items)})`"></div>
                                                </div>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     class="h-4 w-4 text-gray-500 transition-transform"
                                                     :class="isCommentGroupOpen(snapshot.key, group.section) ? 'rotate-180' : ''"
                                                     viewBox="0 0 20 20"
                                                     fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <div x-show="isCommentGroupOpen(snapshot.key, group.section)" class="space-y-2 px-3 pb-3">
                                                <template x-for="item in group.items" :key="item.id">
                                                    <button type="button"
                                                            x-show="matchesFilter(item.action_type, item.status, item.author_role)"
                                                            x-cloak
                                                            @click="jumpToEntry(item.entry_id)"
                                                            class="w-full rounded-lg border border-gray-200 bg-white p-3 text-left space-y-2 cursor-pointer hover:border-bu/40 transition">
                                                        <div class="flex items-start justify-between gap-2">
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-semibold text-gray-800 truncate" x-text="item.criterion_label"></div>
                                                                <div class="text-xs text-gray-500">
                                                                    <span x-text="item.author"></span>
                                                                    <span> - </span>
                                                                    <span x-text="item.created_at_label || '-'"></span>
                                                                </div>
                                                                <template x-if="item.entry_removed">
                                                                    <div class="mt-0.5 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                                                        Entry removed by faculty
                                                                    </div>
                                                                </template>
                                                                <template x-if="item.entry_change_type && !item.entry_removed">
                                                                    <div class="mt-0.5 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                                         :class="revisionTypeClass({ change_type: item.entry_change_type })"
                                                                         x-text="`Entry ${revisionTypeLabel({ change_type: item.entry_change_type })}`">
                                                                    </div>
                                                                </template>
                                                                <div class="text-[11px] font-semibold text-gray-500">Reviewer Comment:</div>
                                                                <div class="text-sm leading-5 text-gray-800 break-words" x-text="item.body || 'No message'"></div>
                                                                <template x-if="item.entry_change_summary">
                                                                    <div class="mt-1 text-[10px] text-gray-500 truncate" x-text="item.entry_change_summary"></div>
                                                                </template>
                                                                <template x-if="item.entry_change_details && item.entry_change_details.length">
                                                                    <ul class="mt-1 list-disc pl-4 space-y-0.5 text-[10px] text-gray-600">
                                                                        <template x-for="line in item.entry_change_details" :key="line">
                                                                            <li x-text="line"></li>
                                                                        </template>
                                                                    </ul>
                                                                </template>
                                                            </div>
                                                            <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                                  :class="statusClass(item)"
                                                                  x-text="statusLabel(item)">
                                                            </span>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="activeTab === 'addressed' || activeTab === 'notes'">
                        <div class="space-y-2">
                            <template x-for="snapshot in groupedSnapshotItems()" :key="snapshot.key">
                                <div class="space-y-2" x-show="snapshotHasVisible(snapshot)" x-cloak>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                        <div class="text-xs font-semibold text-slate-800" x-text="snapshot.label"></div>
                                        <div class="text-[11px] text-slate-600"
                                             x-show="snapshot.reviewer || snapshot.dateLabel"
                                             x-text="snapshot.reviewer && snapshot.dateLabel ? `${snapshot.reviewer} - ${snapshot.dateLabel}` : (snapshot.reviewer || snapshot.dateLabel)">
                                        </div>
                                    </div>
                                    <template x-for="group in snapshot.sections" :key="`${snapshot.key}-${group.section}`">
                                        <div x-show="hasVisibleInGroup(group.items)" x-cloak class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                                            <button type="button"
                                                    @click="toggleCommentGroup(snapshot.key, group.section)"
                                                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50">
                                                <div class="min-w-0">
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-600"
                                                         x-text="`${group.sectionLabel} (${countFor(activeTab, group.items)})`"></div>
                                                </div>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     class="h-4 w-4 text-gray-500 transition-transform"
                                                     :class="isCommentGroupOpen(snapshot.key, group.section) ? 'rotate-180' : ''"
                                                     viewBox="0 0 20 20"
                                             fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <div x-show="isCommentGroupOpen(snapshot.key, group.section)" class="space-y-2 px-3 pb-3">
                                                <template x-for="item in group.items" :key="item.id">
                                                    <button type="button"
                                                            x-show="matchesFilter(item.action_type, item.status, item.author_role)"
                                                            x-cloak
                                                            @click="jumpToEntry(item.entry_id)"
                                                            class="w-full rounded-lg border border-gray-200 bg-white p-3 text-left space-y-2 cursor-pointer hover:border-bu/40 transition">
                                                        <div class="flex items-start justify-between gap-2">
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-semibold text-gray-800 truncate" x-text="item.criterion_label"></div>
                                                                <div class="text-xs text-gray-500">
                                                                    <span x-text="item.author"></span>
                                                                    <span> - </span>
                                                                    <span x-text="item.created_at_label || '-'"></span>
                                                                </div>
                                                                <template x-if="item.entry_removed">
                                                                    <div class="mt-0.5 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                                                        Entry removed by faculty
                                                                    </div>
                                                                </template>
                                                                <template x-if="item.entry_change_type && !item.entry_removed">
                                                                    <div class="mt-0.5 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                                         :class="revisionTypeClass({ change_type: item.entry_change_type })"
                                                                         x-text="`Entry ${revisionTypeLabel({ change_type: item.entry_change_type })}`">
                                                                    </div>
                                                                </template>
                                                                <div class="text-[11px] font-semibold text-gray-500">Reviewer Comment:</div>
                                                                <div class="text-sm leading-5 text-gray-800 break-words" x-text="item.body || 'No message'"></div>
                                                                <template x-if="item.entry_change_summary">
                                                                    <div class="mt-1 text-[10px] text-gray-500 truncate" x-text="item.entry_change_summary"></div>
                                                                </template>
                                                                <template x-if="item.entry_change_details && item.entry_change_details.length">
                                                                    <ul class="mt-1 list-disc pl-4 space-y-0.5 text-[10px] text-gray-600">
                                                                        <template x-for="line in item.entry_change_details" :key="line">
                                                                            <li x-text="line"></li>
                                                                        </template>
                                                                    </ul>
                                                                </template>
                                                            </div>
                                                            <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                                  :class="statusClass(item)"
                                                                  x-text="statusLabel(item)">
                                                            </span>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div id="reviewer-revision-panel"
                 x-show="revisionPanelOpen"
                 x-cloak
                 class="fixed top-0 right-0 h-screen z-[95] w-full max-w-lg overflow-visible border-l border-gray-200 bg-white shadow-2xl flex flex-col transition-all duration-200">
                <div class="absolute -left-12 top-4 z-[96] group">
                    <button type="button"
                            @click="panelOpen = true; revisionPanelOpen = false"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 shadow-lg hover:bg-gray-50"
                            aria-label="Open comments">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M18 10c0 3.866-3.582 7-8 7a8.94 8.94 0 01-3.705-.77L2 17.5l1.346-3.364A6.735 6.735 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" />
                        </svg>
                    </button>
                    <span class="pointer-events-none absolute left-12 top-1/2 -translate-y-1/2 whitespace-nowrap rounded-md bg-black px-2 py-1 text-[11px] font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                        Comment history
                    </span>
                </div>
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-gray-800">Revision Log</div>
                        <button type="button"
                                @click="revisionPanelOpen = false"
                                class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                            Close
                        </button>
                    </div>
                    <div class="mt-0.5 text-xs text-gray-500">Track faculty changes across returns.</div>
                </div>

                <div class="flex-1 overflow-y-auto px-3 py-3 space-y-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-2">
                        <div class="grid grid-cols-4 gap-1">
                            <template x-for="tabItem in revisionTabs" :key="tabItem.key">
                                <button type="button"
                                        @click="activeRevisionTab = tabItem.key"
                                        class="rounded-lg px-2 py-2 text-[11px] font-semibold border transition"
                                        :class="activeRevisionTab === tabItem.key
                                            ? 'border-bu bg-bu text-white shadow-soft'
                                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'">
                                    <span x-text="tabItem.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <template x-if="groupedRevisionBatches().length === 0">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-xs text-gray-500">
                            No revision logs yet.
                        </div>
                    </template>

                    <template x-for="batch in groupedRevisionBatches()" :key="batch.key">
                        <div class="space-y-2">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <div class="text-[11px] font-semibold text-slate-800" x-text="batch.label"></div>
                                <div class="text-[10px] text-slate-600"
                                     x-show="batch.dateLabel"
                                     x-text="batch.dateLabel">
                                </div>
                            </div>

                            <template x-for="group in batch.sections" :key="`${batch.key}-${group.section}`">
                                <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                                    <button type="button"
                                            @click="toggleRevisionGroup(batch.key, group.section)"
                                            class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50">
                                        <div class="min-w-0">
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-600"
                                                 x-text="`${group.sectionLabel} (${(group.items || []).length})`"></div>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="h-4 w-4 text-gray-500 transition-transform"
                                             :class="isRevisionGroupOpen(batch.key, group.section) ? 'rotate-180' : ''"
                                             viewBox="0 0 20 20"
                                             fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div x-show="isRevisionGroupOpen(batch.key, group.section)" class="space-y-2 px-3 pb-3">
                                        <template x-for="item in group.items" :key="item.id">
                                            <button type="button"
                                                    @click="jumpToRevision(item)"
                                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left hover:bg-slate-50">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-semibold text-gray-800 truncate" x-text="item.criterion_label"></div>
                                                        <div class="mt-0.5 text-[11px] text-gray-600 truncate" x-text="item.summary"></div>
                                                    </div>
                                                    <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                                                          :class="revisionTypeClass(item)"
                                                          x-text="revisionTypeLabel(item)">
                                                    </span>
                                                </div>
                                                <div class="mt-1 text-[10px] text-gray-500">
                                                    <span x-text="item.actor"></span>
                                                    <span>&middot;</span>
                                                    <span x-text="item.created_at_label || '-'"></span>
                                                </div>
                                                <template x-if="item.details && item.details.length">
                                                    <ul class="mt-1 list-disc pl-4 space-y-0.5 text-[10px] text-gray-600">
                                                        <template x-for="line in item.details.slice(0, 2)" :key="line">
                                                            <li x-text="line"></li>
                                                        </template>
                                                    </ul>
                                                </template>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500">Stage</div>
                        <div class="text-lg font-semibold text-gray-800">{{ $statusLabel }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @php
                            $currentRole = strtolower((string) (auth()->user()->role ?? ''));
                            $nextLabel = match($application->status) {
                                'dean_review' => 'Forward to HR',
                                'hr_review' => 'Forward to VPAA',
                                'vpaa_review' => 'Approve to VPAA List',
                                'president_review' => 'Use Approved List',
                                default => 'Forward',
                            };
                            $canReturnPerPaper = in_array($application->status, ['dean_review','hr_review','vpaa_review','vpaa_approved','president_review'], true);
                            $canForwardPerPaper = in_array($application->status, ['dean_review','hr_review','vpaa_review'], true);
                            $section2 = $application->sections->firstWhere('section_code', '2');
                            $section2Complete = (bool) ($section2?->is_complete);
                            $section2Blocked = $currentRole === 'dean'
                                && (string) $application->status === 'dean_review'
                                && !$section2Complete;
                            $isEntryRemoved = function ($entry): bool {
                                $data = is_array($entry?->data) ? $entry->data : [];
                                $value = $data['is_removed'] ?? false;
                                if (is_bool($value)) return $value;
                                if (is_numeric($value)) return (int) $value === 1;
                                return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
                            };
                            $openRequiredCommentEntries = $application->sections
                                ->flatMap(function ($sec) {
                                    return ($sec->entries ?? collect())
                                        ->map(function ($entry) use ($sec) {
                                            $openRequiredCount = ($entry->rowComments ?? collect())
                                                ->filter(function ($comment) {
                                                    if ((string) ($comment->visibility ?? '') !== 'faculty_visible') {
                                                        return false;
                                                    }
                                                    if (!is_null($comment->parent_id ?? null)) {
                                                        return false;
                                                    }
                                                    if ((string) ($comment->status ?? 'open') !== 'open') {
                                                        return false;
                                                    }
                                                    return (string) ($comment->action_type ?? 'requires_action') === 'requires_action';
                                                })
                                                ->count();

                                            if ($openRequiredCount < 1) {
                                                return null;
                                            }

                                            return [
                                                'entry_id' => (int) $entry->id,
                                                'section_code' => (string) ($sec->section_code ?? ''),
                                                'criterion_key' => strtoupper((string) ($entry->criterion_key ?? '')),
                                                'count' => $openRequiredCount,
                                            ];
                                        })
                                        ->filter()
                                        ->values();
                                })
                                ->values();
                            $unresolvedCurrentReviewerCommentEntries = $application->sections
                                ->flatMap(function ($sec) use ($currentRole, $isEntryRemoved) {
                                    return ($sec->entries ?? collect())
                                        ->map(function ($entry) use ($sec, $currentRole, $isEntryRemoved) {
                                            if ($isEntryRemoved($entry)) {
                                                return null;
                                            }

                                            $unresolvedCount = ($entry->rowComments ?? collect())
                                                ->filter(function ($comment) use ($currentRole) {
                                                    if ((string) ($comment->visibility ?? '') !== 'faculty_visible') {
                                                        return false;
                                                    }
                                                    if (!is_null($comment->parent_id ?? null)) {
                                                        return false;
                                                    }
                                                    if ((string) ($comment->action_type ?? 'requires_action') !== 'requires_action') {
                                                        return false;
                                                    }
                                                    if ((string) ($comment->status ?? 'open') === 'resolved') {
                                                        return false;
                                                    }
                                                    return strtolower((string) ($comment->author?->role ?? '')) === $currentRole;
                                                })
                                                ->count();

                                            if ($unresolvedCount < 1) {
                                                return null;
                                            }

                                            return [
                                                'entry_id' => (int) $entry->id,
                                                'section_code' => (string) ($sec->section_code ?? ''),
                                                'criterion_key' => strtoupper((string) ($entry->criterion_key ?? '')),
                                                'count' => $unresolvedCount,
                                            ];
                                        })
                                        ->filter()
                                        ->values();
                                })
                                ->values();
                            $unresolvedCurrentReviewerCommentsCount = (int) $unresolvedCurrentReviewerCommentEntries->sum('count');
                            $openRequiredCommentsCount = (int) $openRequiredCommentEntries->sum('count');
                            $returnBlocked = $canReturnPerPaper
                                && ($openRequiredCommentsCount < 1)
                                && !$hasPendingFacultyReturnRequest;
                            $returnActionLocked = $returnBlocked || $hasPendingFacultyReturnRequest;
                            $forwardBlocked = $canForwardPerPaper && ($unresolvedCurrentReviewerCommentsCount > 0 || $section2Blocked);
                        @endphp
                        @if($canReturnPerPaper || $canForwardPerPaper)
                            <div class="flex items-center gap-2">
                                @if($canReturnPerPaper)
                                    <form method="POST" action="{{ route('reclassification.return', $application) }}">
                                        @csrf
                                        <button type="submit"
                                                @disabled($returnActionLocked)
                                                class="px-4 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm font-semibold {{ $returnActionLocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                            Return to Faculty
                                        </button>
                                    </form>
                                @endif
                                @if($canForwardPerPaper)
                                    <form method="POST" action="{{ route('reclassification.forward', $application) }}">
                                        @csrf
                                        <button type="submit"
                                                @disabled($forwardBlocked)
                                                class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft {{ $forwardBlocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                            {{ $nextLabel }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                            @if($canReturnPerPaper && $returnBlocked)
                                <div class="w-full text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    Return blocked: add at least one action-required comment first.
                                </div>
                            @endif
                            @if($canReturnPerPaper && $hasPendingFacultyReturnRequest)
                                <div class="w-full text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    Return action locked: faculty already requested a return. Use <span class="font-semibold">Approve Request</span> in the card below.
                                </div>
                            @endif
                            @if($canForwardPerPaper && $unresolvedCurrentReviewerCommentsCount > 0)
                                <div class="w-full text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    Forward blocked: resolve all your action-required comments first.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            @if($unresolvedCurrentReviewerCommentsCount > 0)
                @php
                    $unresolvedCurrentReviewerBySection = $unresolvedCurrentReviewerCommentEntries
                        ->groupBy(fn ($item) => (string) ($item['section_code'] ?? '-'))
                        ->sortKeys(SORT_NATURAL)
                        ->map(function ($items, $sectionCode) {
                            return [
                                'section_code' => $sectionCode,
                                'section_label' => $sectionCode === '-' ? 'General' : ('Section ' . $sectionCode),
                                'count' => (int) $items->sum('count'),
                                'first_entry_id' => (int) ($items->first()['entry_id'] ?? 0),
                                'items' => $items->values(),
                            ];
                        })
                        ->values();
                @endphp
                <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4">
                    <div class="text-sm font-semibold text-amber-800">
                        {{ $unresolvedCurrentReviewerCommentsCount === 1
                            ? 'Forward blocked - 1 of your action-required comments is unresolved.'
                            : "Forward blocked - {$unresolvedCurrentReviewerCommentsCount} of your action-required comments are unresolved." }}
                    </div>
                    <p class="mt-2 text-xs font-semibold text-amber-900">Unresolved comments by section:</p>

                    @if($unresolvedCurrentReviewerBySection->isNotEmpty())
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                            @foreach($unresolvedCurrentReviewerBySection as $sectionGroup)
                                @if(($sectionGroup['first_entry_id'] ?? 0) > 0)
                                    <a href="#entry-comments-{{ $sectionGroup['first_entry_id'] }}"
                                       class="inline-flex items-center px-2 py-0.5 rounded border border-amber-300 bg-white text-amber-900 hover:bg-amber-100 font-medium">
                                        {{ $sectionGroup['section_label'] }} ({{ $sectionGroup['count'] }})
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if($unresolvedCurrentReviewerCommentEntries->isNotEmpty())
                        <div class="mt-3">
                            <button type="button"
                                    @click="openOpenCommentsPanel()"
                                    class="inline-flex items-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                                View detailed comments
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            @if($section2Blocked)
                <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-amber-800">Section II Required</div>
                            <p class="mt-1 text-sm text-amber-800">
                                Forward is blocked until Section II (Dean Input) is completed.
                            </p>
                        </div>
                        <a href="#section-2-dean-input"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-amber-300 bg-white text-amber-800 text-sm font-semibold hover:bg-amber-100">
                            Go to Section II
                        </a>
                    </div>
                </div>
            @endif

            @if($hasPendingFacultyReturnRequest)
                <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-amber-800">Faculty Return Request</div>
                            <p class="mt-1 text-sm text-amber-800">
                                {{ $application->faculty?->name ?? 'Faculty member' }} requested this paper to be returned for revision.
                            </p>
                            <p class="mt-1 text-xs text-amber-700">
                                Requested on {{ optional($application->faculty_return_requested_at)->format('M d, Y h:i A') ?? '-' }}.
                            </p>
                        </div>
                        @if($canReturnPerPaper)
                            <form method="POST" action="{{ route('reclassification.return', $application) }}">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl border border-amber-300 bg-white text-amber-800 text-sm font-semibold hover:bg-amber-100">
                                    Approve Request
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            @php
                $changeLogs = collect($application->changeLogs ?? [])
                    ->sortByDesc('created_at')
                    ->values()
                    ->take(40);
                $changeTypeLabels = [
                    'create' => 'Added',
                    'update' => 'Updated',
                    'remove' => 'Removed',
                    'restore' => 'Restored',
                    'section_total' => 'Section Total',
                ];
                $changeTypeClasses = [
                    'create' => 'bg-green-50 text-green-700 border-green-200',
                    'update' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'remove' => 'bg-red-50 text-red-700 border-red-200',
                    'restore' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'section_total' => 'bg-gray-50 text-gray-700 border-gray-200',
                ];
                $entryLabelForLog = function ($log) use ($criterionLabels) {
                    $sectionCode = (string) ($log->section_code ?? '');
                    $criterionKey = (string) ($log->criterion_key ?? '');
                    if ($sectionCode === '') {
                        return 'General update';
                    }

                    if ($criterionKey === '') {
                        return "Section {$sectionCode}";
                    }

                    $criterionText = $criterionLabels[$sectionCode][$criterionKey]
                        ?? strtoupper($criterionKey);

                    return "Section {$sectionCode} • {$criterionText}";
                };
            @endphp
            @if($changeLogs->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Detailed Revision Log</h3>
                            <p class="text-sm text-gray-500 mt-1">Audit trail of field-level faculty changes after return.</p>
                        </div>
                        <button type="button"
                                @click="showDetailedRevisionLog = !showDetailedRevisionLog"
                                class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            <span x-text="showDetailedRevisionLog ? 'Hide detailed log' : 'Show detailed log'"></span>
                        </button>
                    </div>

                    <div x-show="showDetailedRevisionLog" x-cloak class="mt-4 space-y-3">
                        @foreach($changeLogs as $log)
                            @php
                                $type = (string) ($log->change_type ?? 'update');
                                $typeLabel = $changeTypeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
                                $typeClass = $changeTypeClasses[$type] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                                $entryLabel = $entryLabelForLog($log);
                            @endphp
                            <div class="rounded-xl border border-gray-200 p-3">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                            {{ $entryLabel }}
                                        </div>
                                        <div class="text-sm font-medium text-gray-800">
                                            {{ $log->summary ?: 'Updated entry.' }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ optional($log->created_at)->format('M d, Y h:i A') ?? '-' }}
                                            @if($log->actor?->name)
                                                &middot; {{ $log->actor->name }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] {{ $typeClass }}">
                                        {{ $typeLabel }}
                                    </span>
                                </div>
                                @php
                                    $logDetails = collect($changeLogDetails[$log->id] ?? [])->take(8);
                                @endphp
                                @if($logDetails->isNotEmpty())
                                    <ul class="mt-2 text-xs text-gray-700 space-y-1.5 list-disc pl-4">
                                        @foreach($logDetails as $detail)
                                            <li>{{ $detail }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="mt-2 text-xs text-gray-500">
                                        No field-level details captured for this change.
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @php
                $statusTrailLabels = [
                    'draft' => 'Draft',
                    'dean_review' => 'Dean',
                    'hr_review' => 'HR',
                    'vpaa_review' => 'VPAA',
                    'vpaa_approved' => 'VPAA Approved List',
                    'president_review' => 'President',
                    'returned_to_faculty' => 'Returned to Faculty',
                    'finalized' => 'Finalized',
                    'rejected_final' => 'Rejected',
                ];
                $statusTrailActionLabels = [
                    'submit' => 'Submitted',
                    'resubmit' => 'Resubmitted',
                    'forward' => 'Forwarded',
                    'return_to_faculty' => 'Returned to Faculty',
                    'approve_to_vpaa_list' => 'Approved to VPAA List',
                    'forward_approved_list' => 'Forwarded Approved List',
                    'finalize' => 'Finalized',
                ];
                $statusTrails = collect($application->statusTrails ?? [])->sortByDesc('created_at')->values();
            @endphp
            @if($reviewerRole === 'vpaa' && $statusTrails->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800">Status Trail History</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Full routing trail of this submission (submit, return, resubmit, forward, and approvals).
                    </p>

                    <div class="mt-4 space-y-3">
                        @foreach($statusTrails as $trail)
                            @php
                                $fromLabel = $trail->from_status
                                    ? ($statusTrailLabels[$trail->from_status] ?? ucfirst(str_replace('_', ' ', (string) $trail->from_status)))
                                    : 'N/A';
                                $toLabel = $statusTrailLabels[$trail->to_status]
                                    ?? ucfirst(str_replace('_', ' ', (string) $trail->to_status));
                                $actionLabel = $statusTrailActionLabels[$trail->action]
                                    ?? ucfirst(str_replace('_', ' ', (string) $trail->action));
                                $actorName = $trail->actor?->name ?? 'System';
                                $actorRole = trim((string) ($trail->actor_role ?? ''));
                                $actorRoleLabel = $actorRole !== '' ? strtoupper($actorRole) : null;
                                $resumedFrom = trim((string) data_get($trail->meta, 'resumed_from_role', ''));
                            @endphp
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $actionLabel }}:
                                        <span class="text-gray-700">{{ $fromLabel }}</span>
                                        <span class="text-gray-400 mx-1">&rarr;</span>
                                        <span class="text-gray-900">{{ $toLabel }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ optional($trail->created_at)->format('M d, Y h:i A') }}
                                    </div>
                                </div>
                                <div class="mt-1 text-xs text-gray-600">
                                    By {{ $actorName }}@if($actorRoleLabel) ({{ $actorRoleLabel }})@endif
                                    @if($resumedFrom !== '')
                                        <span class="mx-1 text-gray-300">&middot;</span>
                                        Resubmitted back to {{ strtoupper($resumedFrom) }}
                                    @endif
                                </div>
                                @if($trail->note)
                                    <div class="mt-2 text-xs text-gray-700">
                                        {{ $trail->note }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-full bg-bu/10 text-bu flex items-center justify-center text-sm font-bold">
                            {{ strtoupper(substr((string) ($application->faculty?->name ?? 'F'), 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 leading-tight">Faculty Information</h3>
                            <div class="mt-1 text-sm font-semibold text-gray-800">
                                {{ $application->faculty?->name ?? 'Faculty' }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                Employee No. {{ $application->faculty?->facultyProfile?->employee_no ?? '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">

                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Department</div>
                        <div class="mt-1 text-sm font-semibold text-gray-800">{{ $application->faculty?->department?->name ?? '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Original Appointment</div>
                        <div class="mt-1 text-sm font-semibold text-gray-800">{{ $appointmentDate ?? '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">BU Service</div>
                        <div class="mt-1 text-sm font-semibold text-gray-800">{{ $yearsService !== null ? (int) $yearsService . ' years' : '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Current Rank</div>
                        <div class="mt-1 text-sm font-semibold text-gray-800">{{ $currentRankLabel ?? 'Instructor' }}</div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Rank Based on Points</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900">{{ $pointsRankLabel }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Allowed Rank</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900">{{ $allowedRankLabel }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Total Points</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900">{{ number_format((float) $totalPoints, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Equivalent %</div>
                            <div class="mt-1 text-sm font-semibold text-gray-900">{{ number_format((float) $eqPercent, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @foreach($sections as $section)
                @if($section->section_code === '2')
                    @continue
                @endif
                @php
                    $sectionCode = (string) ($section->section_code ?? '');
                    $orderIndex = collect($criterionOrder[$sectionCode] ?? [])->flip();
                    $entries = $section->entries
                        ->sortBy(function ($entry) use ($orderIndex) {
                            $key = (string) ($entry->criterion_key ?? '');
                            $idx = $orderIndex->has($key) ? (int) $orderIndex->get($key) : 999;
                            return sprintf('%03d|%06d', $idx, (int) ($entry->id ?? 0));
                        })
                        ->groupBy('criterion_key');
                @endphp

                <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                Section {{ $section->section_code }}
                            </h3>
                            <p class="text-sm text-gray-500">{{ $section->title ?? '' }}</p>
                        </div>
                        <div class="text-sm font-semibold text-gray-700">
                            Score: {{ number_format((float) $section->points_total, 2) }}
                        </div>
                    </div>

                    @php
                        $sectionMax = match((string) ($section->section_code ?? '')) {
                            '1' => 140,
                            '2' => 120,
                            '3' => 70,
                            '4' => 40,
                            '5' => 30,
                            default => null,
                        };
                        $sectionPoints = (float) ($section->points_total ?? 0);
                        $entryIsRemoved = function ($entry): bool {
                            $data = is_array($entry?->data) ? $entry->data : [];
                            return in_array(strtolower((string) ($data['is_removed'] ?? '')), ['1', 'true', 'yes', 'on'], true);
                        };
                        $activeEntriesFlat = collect($section->entries ?? [])->filter(fn ($entry) => !$entryIsRemoved($entry));
                        $scoresByKey = $activeEntriesFlat
                            ->groupBy('criterion_key')
                            ->map(fn ($rows) => (float) collect($rows)->sum(fn ($entry) => (float) ($entry->points ?? 0)));
                        $scoreFor = fn (string $key): float => (float) ($scoresByKey->get($key, 0));
                        $inputValueFor = function (string $key) use ($section): float {
                            $entry = collect($section->entries ?? [])->first(fn ($row) => (string) ($row->criterion_key ?? '') === $key);
                            if (!$entry) return 0;
                            $data = is_array($entry->data) ? $entry->data : [];
                            $raw = $data['value'] ?? null;
                            if (is_numeric($raw)) return (float) $raw;
                            if (in_array($key, ['b_prev', 'c_prev', 'd_prev', 'previous_points'], true)) {
                                return ((float) ($entry->points ?? 0)) * 3;
                            }
                            return is_numeric($entry->points ?? null) ? (float) $entry->points : 0;
                        };

                        $s1RawA8 = $scoreFor('a8');
                        $s1RawA9 = $scoreFor('a9');
                        $s1RawA = $scoreFor('a1') + $scoreFor('a2') + $scoreFor('a3') + $scoreFor('a4') + $scoreFor('a5') + $scoreFor('a6') + $scoreFor('a7') + min($s1RawA8, 15) + min($s1RawA9, 10);
                        $s1BPrevThird = $inputValueFor('b_prev') / 3;
                        $s1CPrevThird = $inputValueFor('c_prev') / 3;
                        $s1RawB = $scoreFor('b') + $s1BPrevThird;
                        $s1RawC = $scoreFor('c') + $s1CPrevThird;
                        $s1CountedA = min($s1RawA, 140);
                        $s1CountedB = min($s1RawB, 20);
                        $s1CountedC = min($s1RawC, 20);
                        $s1RawTotal = $s1RawA + $s1RawB + $s1RawC;
                        $s1CountedTotal = min($s1CountedA + $s1CountedB + $s1CountedC, 140);

                        $s3CriteriaKeys = collect(['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7', 'c8', 'c9']);
                        $s3Subtotal = (float) $s3CriteriaKeys->sum(fn ($key) => $scoreFor($key));
                        $s3PrevThird = $inputValueFor('previous_points') / 3;
                        $s3RawTotal = $s3Subtotal + $s3PrevThird;
                        $s3Counted = min($s3RawTotal, 70);
                        $s3CriteriaMet = (int) $s3CriteriaKeys->filter(fn ($key) => $scoreFor($key) > 0)->count();

                        $s4A1 = $scoreFor('a1');
                        $s4A2 = $scoreFor('a2');
                        $s4Teaching = min($s4A1 + $s4A2, 40);
                        $s4Industry = min($scoreFor('b'), 20);
                        $s4TrackIsA = $s4Teaching >= $s4Industry;
                        $s4TrackLabel = $s4TrackIsA ? 'A. Teaching Experience' : 'B. Industry/Admin Experience';
                        $s4IsPartTime = (($application->faculty?->facultyProfile?->employment_type ?? $application->faculty?->employment_type ?? 'full_time') === 'part_time');
                        $s4ModeLabel = $s4IsPartTime ? 'Part-time (50%)' : 'Full-time (100%)';
                        $s4RawCounted = max($s4Teaching, $s4Industry);
                        $s4Final = min($s4RawCounted * ($s4IsPartTime ? 0.5 : 1), 40);

                        $s5ARaw = $scoreFor('a');
                        $s5ACapped = min($s5ARaw, 5);
                        $s5PrevBThird = $inputValueFor('b_prev') / 3;
                        $s5PrevCThird = $inputValueFor('c_prev') / 3;
                        $s5PrevDThird = $inputValueFor('d_prev') / 3;
                        $s5PrevThird = $inputValueFor('previous_points') / 3;
                        $s5BRaw = $scoreFor('b') + $s5PrevBThird;
                        $s5BCapped = min($s5BRaw, 10);
                        $s5C1Raw = $scoreFor('c1');
                        $s5C2Raw = $scoreFor('c2');
                        $s5C3Raw = $scoreFor('c3');
                        $s5C1Capped = min($s5C1Raw, 10);
                        $s5C2Capped = min($s5C2Raw, 5);
                        $s5C3Capped = min($s5C3Raw, 10);
                        $s5CRaw = $s5C1Raw + $s5C2Raw + $s5C3Raw + $s5PrevCThird;
                        $s5CCapped = min($s5C1Capped + $s5C2Capped + $s5C3Capped + $s5PrevCThird, 15);
                        $s5DRaw = $scoreFor('d') + $s5PrevDThird;
                        $s5DCapped = min($s5DRaw, 10);
                        $s5Subtotal = $s5ACapped + $s5BCapped + $s5CCapped + $s5DCapped;
                        $s5RawTotal = $s5Subtotal + $s5PrevThird;
                        $s5Counted = min($s5RawTotal, 30);

                        $summaryRaw = $sectionPoints;
                        $summaryCounted = $sectionPoints;
                        $summaryLimit = $sectionMax;
                        $summaryWithinLimit = is_null($sectionMax) ? true : ($sectionPoints <= $sectionMax);
                        if ($sectionCode === '1') {
                            $summaryRaw = $s1RawTotal;
                            $summaryCounted = $s1CountedTotal;
                            $summaryLimit = 140;
                            $summaryWithinLimit = $s1RawTotal <= 140;
                        } elseif ($sectionCode === '3') {
                            $summaryRaw = $s3RawTotal;
                            $summaryCounted = $s3Counted;
                            $summaryLimit = 70;
                            $summaryWithinLimit = $s3RawTotal <= 70;
                        } elseif ($sectionCode === '4') {
                            $summaryRaw = $s4Final;
                            $summaryCounted = $s4Final;
                            $summaryLimit = 40;
                            $summaryWithinLimit = $s4Final <= 40;
                        } elseif ($sectionCode === '5') {
                            $summaryRaw = $s5RawTotal;
                            $summaryCounted = $s5Counted;
                            $summaryLimit = 30;
                            $summaryWithinLimit = $s5RawTotal <= 30;
                        }
                    @endphp

                    <div class="px-6 py-4 border-b bg-slate-50/70 space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Section {{ $sectionCode }} Score Summary</div>
                                <div class="mt-1 text-xs text-slate-600">
                                    @if($sectionCode === '4')
                                        Final: <span class="font-semibold text-slate-800">{{ number_format($summaryCounted, 2) }}</span>
                                        <span class="text-slate-400">/ {{ number_format((float) $summaryLimit, 2) }}</span>
                                        <span class="mx-1 text-slate-300">&middot;</span>
                                        Track: <span class="font-semibold text-slate-800">{{ $s4TrackLabel }}</span>
                                    @else
                                        Raw: <span class="font-semibold text-slate-800">{{ number_format($summaryRaw, 2) }}</span>
                                        <span class="text-slate-400">/ {{ number_format((float) $summaryLimit, 2) }}</span>
                                        <span class="mx-1 text-slate-300">&middot;</span>
                                        Counted: <span class="font-semibold text-slate-800">{{ number_format($summaryCounted, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $summaryWithinLimit ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                                    {{ $summaryWithinLimit ? 'Within limit' : 'Over limit' }}
                                </span>
                                @if($sectionCode === '3')
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $s3CriteriaMet >= 1 ? 'border-green-200 bg-green-50 text-green-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                        {{ $s3CriteriaMet >= 1 ? 'Minimum criteria met (1/1)' : 'Need at least 1 criterion' }}
                                    </span>
                                @endif
                                @if($sectionCode === '4')
                                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-medium text-gray-700">
                                        Counted track: <span class="ml-1 font-semibold">{{ $s4TrackLabel }}</span>
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700">
                                        Scoring mode: <span class="ml-1 font-semibold">{{ $s4ModeLabel }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Section Score</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($sectionPoints, 2) }}</div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Section Max</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $sectionMax !== null ? number_format((float) $sectionMax, 2) : '-' }}</div>
                            </div>
                        </div>

                        @if($sectionCode === '1')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A. Academic Degree Earned</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s1RawA, 2) }} <span class="text-sm text-gray-400">/ 140</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s1CountedA, 2) }}</span></div>
                                    <div class="mt-2 space-y-1 text-xs text-gray-500">
                                        <div class="flex items-center justify-between">
                                            <span>A8 Exams cap</span>
                                            <span><span class="font-medium text-gray-700">{{ number_format($s1RawA8, 2) }}</span> <span class="text-gray-400">/ 15</span></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>A9 Certifications cap</span>
                                            <span><span class="font-medium text-gray-700">{{ number_format($s1RawA9, 2) }}</span> <span class="text-gray-400">/ 10</span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500">B. Specialized Training</div>
                                        <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
                                    </div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s1RawB, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s1CountedB, 2) }}</span></div>
                                    <div class="mt-1 text-xs text-gray-500">Previous (1/3): {{ number_format($s1BPrevThird, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500">C. Seminars / Workshops</div>
                                        <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
                                    </div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s1RawC, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s1CountedC, 2) }}</span></div>
                                    <div class="mt-1 text-xs text-gray-500">Previous (1/3): {{ number_format($s1CPrevThird, 2) }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-600">
                                Raw total: <span class="font-semibold text-slate-800">{{ number_format($s1RawTotal, 2) }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Counted total: <span class="font-semibold text-slate-800">{{ number_format($s1CountedTotal, 2) }}</span>
                            </div>
                        @elseif($sectionCode === '3')
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Criteria Met</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ $s3CriteriaMet }} <span class="text-sm text-gray-400">/ 9</span></div>
                                    <div class="text-xs text-gray-500">Minimum required: {{ $s3CriteriaMet >= 1 ? '1/1 met' : '0/1' }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Total (No Previous)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s3Subtotal, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Previous Reclass (1/3)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s3PrevThird, 2) }}</div>
                                    <div class="text-xs text-gray-500">Input: {{ number_format($inputValueFor('previous_points'), 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Final</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s3RawTotal, 2) }} <span class="text-sm text-gray-400">/ 70</span></div>
                                    <div class="text-xs text-gray-500">Counted: <span class="font-medium text-gray-700">{{ number_format($s3Counted, 2) }}</span></div>
                                </div>
                            </div>
                        @elseif($sectionCode === '4')
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A1 (Before BU)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4A1, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">2 pts/year (capped)</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A2 (After BU)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4A2, 2) }} <span class="text-sm text-gray-400">/ 40</span></div>
                                    <div class="text-xs text-gray-500">3 pts/year (capped)</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Teaching Total (A)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4Teaching, 2) }} <span class="text-sm text-gray-400">/ 40</span></div>
                                    <div class="text-xs text-gray-500">A1 + A2, capped at 40</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Industry/Admin (B)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s4Industry, 2) }} <span class="text-sm text-gray-400">/ 20</span></div>
                                    <div class="text-xs text-gray-500">2 pts/year (capped)</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-600">
                                Raw counted track: <span class="font-semibold text-slate-800">{{ number_format($s4RawCounted, 2) }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Deduction rate: <span class="font-semibold text-slate-800">{{ $s4IsPartTime ? '50%' : '100%' }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Final counted score: <span class="font-semibold text-slate-800">{{ number_format($s4Final, 2) }}</span>
                                <span class="text-slate-400">/ 40</span>
                            </div>
                        @elseif($sectionCode === '5')
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">A (cap 5)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5ACapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5ARaw, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">B (cap 10)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5BCapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5BRaw, 2) }} &middot; Prev 1/3: {{ number_format($s5PrevBThird, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">C (cap 15)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5CCapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5CRaw, 2) }} &middot; Prev 1/3: {{ number_format($s5PrevCThird, 2) }}</div>
                                    <div class="mt-1 text-xs text-gray-500">C1: {{ number_format($s5C1Raw, 2) }} (cap 10) &middot; C2: {{ number_format($s5C2Raw, 2) }} (cap 5) &middot; C3: {{ number_format($s5C3Raw, 2) }} (cap 10)</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">D (cap 10)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5DCapped, 2) }}</div>
                                    <div class="text-xs text-gray-500">Raw: {{ number_format($s5DRaw, 2) }} &middot; Prev 1/3: {{ number_format($s5PrevDThird, 2) }}</div>
                                </div>
                                <div class="rounded-xl border p-4 bg-white">
                                    <div class="text-xs text-gray-500">Section 5 Previous (1/3)</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-800">{{ number_format($s5PrevThird, 2) }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-600">
                                Raw total: <span class="font-semibold text-slate-800">{{ number_format($s5RawTotal, 2) }}</span>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                Counted total: <span class="font-semibold text-slate-800">{{ number_format($s5Counted, 2) }}</span>
                            </div>
                        @endif

                    </div>

                    <div class="p-6 space-y-6">
                        @if($section->entries->isEmpty())
                            <p class="text-sm text-gray-500">No entries submitted for this section.</p>
                        @else
                            @foreach($entries as $criterionKey => $rows)
                                @php
                                    $label = $criterionLabels[$section->section_code][$criterionKey]
                                        ?? ($rows->first()?->title ?? strtoupper($criterionKey));
                                @endphp
                                <div class="space-y-2">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $label }}
                                    </div>

                                    <div class="overflow-x-auto border rounded-xl">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-50 text-gray-600">
                                                <tr>
                                                    <th class="px-4 py-2 text-left">Entry</th>
                                                    <th class="px-4 py-2 text-left">Details</th>
                                                    <th class="px-4 py-2 text-left">Evidence</th>
                                                    <th class="px-4 py-2 text-right">Points</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @foreach($rows as $entry)
                                                    @php
                                                        $data = is_array($entry->data) ? $entry->data : [];
                                                        $isRemoved = in_array(strtolower((string) ($data['is_removed'] ?? '')), ['1', 'true', 'yes', 'on'], true);
                                                        $title = $entry->title ?: ($data['text'] ?? $data['title'] ?? 'Entry');
                                                        $evidences = $entry->evidences ?? collect();
                                                        $rowComments = $entry->rowComments ?? collect();
                                                    @endphp
                                                    <tr data-review-entry-row="{{ (int) $entry->id }}"
                                                        class="{{ $isRemoved ? 'bg-gray-100/70' : '' }} cursor-pointer transition hover:bg-blue-50">
                                                        <td class="px-4 py-2 font-medium {{ $isRemoved ? 'text-gray-500' : 'text-gray-800' }}">
                                                            <div class="flex items-center gap-2">
                                                                <span>{{ $title }}</span>
                                                                @if($isRemoved)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border border-gray-300 bg-gray-200 text-[10px] uppercase tracking-wide text-gray-700">
                                                                        Removed by faculty
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-600">
                                                            <div class="space-y-1">
                                                                @foreach($data as $key => $value)
                                                                    @if(in_array((string) $key, ['evidence', 'id', 'is_removed', 'points', 'counted', 'comments'], true))
                                                                        @continue
                                                                    @endif
                                                                    <div>
                                                                        <span class="text-gray-400">{{ ucfirst(str_replace('_',' ', $key)) }}:</span>
                                                                        <span class="text-gray-700">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            @if($evidences->isEmpty())
                                                                <span class="text-gray-400">None</span>
                                                            @else
                                                                <div class="space-y-2">
                                                                    @foreach($evidences as $ev)
                                                                        @php
                                                                            $url = $ev->disk ? \Illuminate\Support\Facades\Storage::disk($ev->disk)->url($ev->path) : null;
                                                                        @endphp
                                                                        <div class="rounded-lg border p-3">
                                                                            <div class="flex items-center justify-between gap-3">
                                                                                <div class="min-w-0">
                                                                                    <div class="truncate font-medium text-gray-800">
                                                                                        {{ $ev->original_name ?? 'Evidence file' }}
                                                                                    </div>
                                                                                </div>
                                                                                <div class="shrink-0">
                                                                                    @if($url)
                                                                                        <a href="{{ $url }}"
                                                                                           target="_blank"
                                                                                           class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                                                            View
                                                                                        </a>
                                                                                    @else
                                                                                        <span class="text-xs text-gray-400">Unavailable</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-right">
                                                            @if($isRemoved)
                                                                <div class="font-semibold text-gray-500">0.00</div>
                                                            @elseif($section->section_code === '1' && $criterionKey === 'c')
                                                                @php
                                                                    $roleKey = $data['role'] ?? null;
                                                                    $levelKey = $data['level'] ?? null;
                                                                    $range = $section1Ranges[$roleKey][$levelKey] ?? null;
                                                                @endphp
                                                                @if($canEditSection1C && $range)
                                                                    <form method="POST"
                                                                          action="{{ route($section1cUpdateRoute, [$application, $entry]) }}"
                                                                          class="flex items-center justify-end gap-2">
                                                                        @csrf
                                                                        <input type="number"
                                                                               name="points"
                                                                               min="{{ $range[0] }}"
                                                                               max="{{ $range[1] }}"
                                                                               step="1"
                                                                               value="{{ (float) $entry->points }}"
                                                                               class="w-20 rounded border-gray-300 text-right text-sm">
                                                                        <button type="submit"
                                                                                class="px-2 py-1 rounded-lg border text-xs text-gray-700 hover:bg-gray-50">
                                                                            Update
                                                                        </button>
                                                                    </form>
                                                                    <div class="mt-1 text-[11px] text-gray-500 text-right">
                                                                        Range: {{ $range[0] }}-{{ $range[1] }}
                                                                    </div>
                                                                @else
                                                                    <div class="font-semibold text-gray-800">
                                                                        {{ number_format((float) $entry->points, 2) }}
                                                                    </div>
                                                                    @if($range)
                                                                        <div class="mt-1 text-[11px] text-gray-500 text-right">
                                                                            Range: {{ $range[0] }}-{{ $range[1] }}
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            @else
                                                                <div class="font-semibold text-gray-800">
                                                                    {{ number_format((float) $entry->points, 2) }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr class="bg-gray-50/50">
                                                        <td colspan="4" class="px-4 py-3">
                                                            <div id="entry-comments-{{ $entry->id }}"
                                                                 data-entry-comments-block="{{ (int) $entry->id }}"
                                                                 x-data="{
                                                                    showCommentForm: false,
                                                                    draftBody: '',
                                                                    draftVisibility: '',
                                                                    draftCommentType: '',
                                                                    canSubmitComment() {
                                                                        const hasBody = String(this.draftBody || '').trim() !== '';
                                                                        const visibility = String(this.draftVisibility || '').trim();
                                                                        if (!hasBody || visibility === '') return false;
                                                                        if (visibility === 'faculty_visible') {
                                                                            return String(this.draftCommentType || '').trim() !== '';
                                                                        }
                                                                        return true;
                                                                    },
                                                                    hasCommentInput() {
                                                                        return String(this.draftBody || '').trim() !== ''
                                                                            || String(this.draftVisibility || '').trim() !== ''
                                                                            || String(this.draftCommentType || '').trim() !== '';
                                                                    },
                                                                    openCommentForm() {
                                                                        this.showCommentForm = true;
                                                                    },
                                                                    resetCommentDraft() {
                                                                        this.draftBody = '';
                                                                        this.draftVisibility = '';
                                                                        this.draftCommentType = '';
                                                                    },
                                                                    closeCommentForm(force = false) {
                                                                        if (!force && this.hasCommentInput()) return;
                                                                        this.showCommentForm = false;
                                                                        this.resetCommentDraft();
                                                                    },
                                                                 }"
                                                                 @reviewer-toggle-previous-resolved.window="showPreviousReviewerResolved = !!($event.detail && $event.detail.enabled)"
                                                                 @click.window="if (showCommentForm && !$refs.commentForm?.contains($event.target) && !$refs.commentToggle?.contains($event.target)) closeCommentForm(false)"
                                                                 class="relative space-y-3 scroll-mt-28">
                                                                <div>
                                                                    <div class="flex items-center justify-between gap-2">
                                                                                           <div class="text-sm font-semibold text-gray-800">Reviewer's Comments</div>
                                                                        @unless($isRemoved)
                                                                            <div data-entry-comment-actions="{{ (int) $entry->id }}">
                                                                                <button type="button"
                                                                                        x-ref="commentToggle"
                                                                                        @click="openCommentForm()"
                                                                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                                                                    Add Comment
                                                                                </button>
                                                                            </div>
                                                                        @endunless
                                                                    </div>
                                                                    @unless($isRemoved)
                                                                        <div x-show="showCommentForm"
                                                                             x-cloak
                                                                             x-ref="commentForm"
                                                                             class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                                                            <form method="POST"
                                                                                  action="{{ route('reclassification.row-comments.store', [$application, $entry]) }}"
                                                                                  data-async-action
                                                                                  data-async-refresh-target="#reviewer-content"
                                                                                  data-loading-text="Saving..."
                                                                                  data-loading-message="Saving comment..."
                                                                                  class="grid grid-cols-1 gap-3 md:grid-cols-7">
                                                                                @csrf
                                                                                <div class="md:col-span-4">
                                                                                    <label class="text-xs text-gray-600">Add comment</label>
                                                                                    <textarea name="body" rows="2" required
                                                                                              x-model="draftBody"
                                                                                              class="mt-1 w-full rounded-lg border-gray-300 text-xs"
                                                                                              placeholder="Leave a note for the faculty..."></textarea>
                                                                                </div>
                                                                                <div>
                                                                                    <label class="text-xs text-gray-600">Visibility</label>
                                                                                    <select name="visibility"
                                                                                            x-model="draftVisibility"
                                                                                            required
                                                                                            class="mt-1 w-full rounded-lg border-gray-300 text-xs">
                                                                                        <option value="">Select visibility</option>
                                                                                        <option value="faculty_visible">Visible to faculty</option>
                                                                                        <option value="internal">Internal</option>
                                                                                    </select>
                                                                                </div>
                                                                                <template x-if="draftVisibility === 'faculty_visible'">
                                                                                    <div>
                                                                                        <label class="text-xs text-gray-600">Type</label>
                                                                                        <select name="action_type"
                                                                                                x-model="draftCommentType"
                                                                                                required
                                                                                                class="mt-1 w-full rounded-lg border-gray-300 text-xs">
                                                                                            <option value="">Select type</option>
                                                                                            <option value="requires_action">Action required</option>
                                                                                            <option value="info">No action required (FYI)</option>
                                                                                        </select>
                                                                                    </div>
                                                                                </template>
                                                                                <template x-if="draftVisibility === 'internal'">
                                                                                    <input type="hidden" name="action_type" value="info">
                                                                                </template>
                                                                                <div class="flex justify-end gap-2 pt-1 md:col-span-7">
                                                                                    <button type="submit"
                                                                                            x-bind:disabled="!canSubmitComment()"
                                                                                            x-bind:class="canSubmitComment()
                                                                                                ? 'bg-bu text-white hover:bg-bu-dark'
                                                                                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'"
                                                                                            class="rounded-lg px-4 py-2 text-xs font-semibold transition">
                                                                                        Comment
                                                                                    </button>
                                                                                    <button type="button"
                                                                                            @click="closeCommentForm(true)"
                                                                                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                                                        Cancel
                                                                                    </button>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    @endunless
                                                                    @php
                                                                        $rootComments = $rowComments
                                                                            ->whereNull('parent_id')
                                                                            ->sortBy('created_at')
                                                                            ->values();
                                                                        $openAndAddressedComments = $rootComments
                                                                            ->filter(fn ($comment) => (string) ($comment->status ?? 'open') !== 'resolved')
                                                                            ->values();
                                                                        $resolvedComments = $rootComments
                                                                            ->filter(fn ($comment) => (string) ($comment->status ?? 'open') === 'resolved')
                                                                            ->values();
                                                                    @endphp
                                                                    @if($rootComments->isEmpty())
                                                                        <div class="text-xs text-gray-500 mt-1">No comments yet.</div>
                                                                    @else
                                                                        <div class="mt-2 space-y-2">
                                                                            @foreach($openAndAddressedComments as $comment)
                                                                                @php
                                                                                    $commentReturnLabel = $resolveCommentReturnLabel($comment->created_at);
                                                                                    $commentReturnMeta = $returnSnapshotMetaByLabel->get($commentReturnLabel, []);
                                                                                @endphp
                                                                                @include('reclassification.partials.reviewer-entry-comment-card', [
                                                                                    'comment' => $comment,
                                                                                    'rowComments' => $rowComments,
                                                                                    'application' => $application,
                                                                                    'reviewerRole' => $reviewerRole,
                                                                                    'returnSnapshotLabel' => $commentReturnLabel,
                                                                                    'returnSnapshotDateLabel' => (string) ($commentReturnMeta['date_label'] ?? ''),
                                                                                ])
                                                                            @endforeach

                                                                            @php
                                                                                $resolvedHasCurrentReviewer = $resolvedComments->contains(function ($comment) use ($reviewerRole) {
                                                                                    return strtolower((string) ($comment->author?->role ?? '')) === strtolower((string) $reviewerRole);
                                                                                });
                                                                                $resolvedHasPreviousReviewer = $resolvedComments->contains(function ($comment) use ($reviewerRole) {
                                                                                    $authorRole = strtolower((string) ($comment->author?->role ?? ''));
                                                                                    return $authorRole !== '' && $authorRole !== strtolower((string) $reviewerRole);
                                                                                });
                                                                                $stageLabelForRole = function (string $role): string {
                                                                                    return match (strtolower($role)) {
                                                                                        'dean' => 'Dean Stage',
                                                                                        'hr' => 'HR Stage',
                                                                                        'vpaa' => 'VPAA Stage',
                                                                                        'president' => 'President Stage',
                                                                                        default => 'Reviewer Stage',
                                                                                    };
                                                                                };
                                                                                $stageSortForRole = function (string $role): int {
                                                                                    return match (strtolower($role)) {
                                                                                        'dean' => 10,
                                                                                        'hr' => 20,
                                                                                        'vpaa' => 30,
                                                                                        'president' => 40,
                                                                                        default => 90,
                                                                                    };
                                                                                };
                                                                                $ordinalReturnLabel = function (int $index): string {
                                                                                    $labels = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth'];
                                                                                    $prefix = $labels[$index - 1] ?? ($index . 'th');
                                                                                    return $prefix . ' Return';
                                                                                };

                                                                                $resolvedRoleSnapshots = $resolvedComments
                                                                                    ->groupBy(fn ($comment) => strtolower((string) ($comment->author?->role ?? 'reviewer')))
                                                                                    ->map(function ($comments, $roleKey) use (
                                                                                        $reviewerRole,
                                                                                        $resolveCommentReturnLabel,
                                                                                        $returnSnapshotMetaByLabel,
                                                                                        $stageLabelForRole,
                                                                                        $stageSortForRole,
                                                                                        $ordinalReturnLabel
                                                                                    ) {
                                                                                        $sortedComments = collect($comments)->sortBy('created_at')->values();
                                                                                        $buckets = [];

                                                                                        foreach ($sortedComments as $comment) {
                                                                                            $returnLabel = (string) $resolveCommentReturnLabel($comment->created_at);
                                                                                            $returnMeta = $returnSnapshotMetaByLabel->get($returnLabel, []);
                                                                                            $returnDateLabel = (string) ($returnMeta['date_label'] ?? '');
                                                                                            $bucketKey = trim($returnLabel . '|' . $returnDateLabel);

                                                                                            if (!array_key_exists($bucketKey, $buckets)) {
                                                                                                $buckets[$bucketKey] = [
                                                                                                    'key' => $bucketKey !== '' ? $bucketKey : ('snapshot-' . (count($buckets) + 1)),
                                                                                                    'date_label' => $returnDateLabel,
                                                                                                    'fallback_label' => $returnLabel,
                                                                                                    'sort_at' => optional($comment->created_at)->timestamp ?? 0,
                                                                                                    'comments' => collect(),
                                                                                                ];
                                                                                            }

                                                                                            $buckets[$bucketKey]['comments']->push($comment);
                                                                                        }

                                                                                        $snapshots = collect(array_values($buckets))
                                                                                            ->sortBy('sort_at')
                                                                                            ->values()
                                                                                            ->map(function ($snapshot, $index) use ($ordinalReturnLabel) {
                                                                                                $snapshot['label'] = $ordinalReturnLabel($index + 1);
                                                                                                $snapshot['comments'] = collect($snapshot['comments'])->sortByDesc('created_at')->values();
                                                                                                return $snapshot;
                                                                                            });

                                                                                        return [
                                                                                            'role' => (string) $roleKey,
                                                                                            'stage_label' => $stageLabelForRole((string) $roleKey),
                                                                                            'is_current_stage' => strtolower((string) $roleKey) === strtolower((string) $reviewerRole),
                                                                                            'sort_index' => $stageSortForRole((string) $roleKey),
                                                                                            'snapshots' => $snapshots,
                                                                                        ];
                                                                                    })
                                                                                    ->sortBy('sort_index')
                                                                                    ->values();
                                                                            @endphp
                                                                            @if($resolvedComments->isNotEmpty())
                                                                                <div class="space-y-2"
                                                                                     x-show="{{ $resolvedHasCurrentReviewer ? 'true' : 'false' }} || (showPreviousReviewerResolved && {{ $resolvedHasPreviousReviewer ? 'true' : 'false' }})"
                                                                                     x-cloak>
                                                                                    <div class="mb-2 flex items-center justify-between gap-2">
                                                                                           <div class="text-sm font-semibold text-gray-800">Resolved Comments</div>

                                                                                        @if($resolvedHasPreviousReviewer)
                                                                                            <div class="text-[10px] text-slate-500">
                                                                                                Previous reviewer's comments are read-only.
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="space-y-2">
                                                                                        @foreach($resolvedRoleSnapshots as $roleSnapshot)
                                                                                            <div x-show="{{ ($roleSnapshot['is_current_stage'] ?? false) ? 'true' : 'showPreviousReviewerResolved' }}" x-cloak class="rounded-lg border border-slate-200 bg-slate-50 p-2.5 space-y-2">
                                                                                                <div class="text-xs font-semibold text-slate-800">
                                                                                                    {{ $roleSnapshot['stage_label'] ?? 'Reviewer Stage' }}
                                                                                                </div>

                                                                                                @foreach(($roleSnapshot['snapshots'] ?? collect()) as $snapshot)
                                                                                                    <div class="rounded-md border border-slate-200 bg-white px-2.5 py-2 space-y-2">
                                                                                                        <div class="text-[11px] font-semibold text-slate-700">
                                                                                                            {{ $snapshot['label'] ?? 'Return' }}@if(!empty($snapshot['date_label'])) - {{ $snapshot['date_label'] }}@endif
                                                                                                        </div>

                                                                                                        @foreach(($snapshot['comments'] ?? collect()) as $comment)
                                                                                                            @php
                                                                                                                $commentReturnLabel = $resolveCommentReturnLabel($comment->created_at);
                                                                                                                $commentReturnMeta = $returnSnapshotMetaByLabel->get($commentReturnLabel, []);
                                                                                                            @endphp
                                                                                                            @include('reclassification.partials.reviewer-entry-comment-card', [
                                                                                                                'comment' => $comment,
                                                                                                                'rowComments' => $rowComments,
                                                                                                                'application' => $application,
                                                                                                                'reviewerRole' => $reviewerRole,
                                                                                                                'returnSnapshotLabel' => $commentReturnLabel,
                                                                                                                'returnSnapshotDateLabel' => (string) ($commentReturnMeta['date_label'] ?? ''),
                                                                                                            ])
                                                                                                        @endforeach
                                                                                                    </div>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                @if($isRemoved)
                                                                    <div class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-xs text-gray-700">
                                                                        This entry was removed by faculty. New comments are disabled.
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                @if($section->section_code === '1')
                    <div id="section-2-dean-input" class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 scroll-mt-28">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Section II (Dean Input)</h3>
                        @if(auth()->user()->role === 'dean')
                            @include('reclassification.section2', [
                                'sectionData' => $section2Data ?? [],
                                'actionRoute' => route('reclassification.review.section2.save', $application),
                                'readOnly' => false,
                                'asyncRefreshTarget' => '#reviewer-content',
                            ])
                        @else
                            @php
                                $ratings = $section2Review['ratings'] ?? [];
                                $points = $section2Review['points'] ?? [];
                                $rDe = $ratings['dean'] ?? [];
                                $rCh = $ratings['chair'] ?? [];
                                $rSt = $ratings['student'] ?? [];
                            @endphp
                            <div class="space-y-4">
                                <p class="text-sm text-gray-500">Read-only summary (filled by the Dean).</p>
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                    <div class="rounded-xl border p-4">
                                        <div class="text-sm font-semibold text-gray-800">Dean Ratings</div>
                                        <div class="mt-2 space-y-1 text-sm text-gray-700">
                                            <div>Item 1: {{ $rDe['i1'] ?? '—' }}</div>
                                            <div>Item 2: {{ $rDe['i2'] ?? '—' }}</div>
                                            <div>Item 3: {{ $rDe['i3'] ?? '—' }}</div>
                                            <div>Item 4: {{ $rDe['i4'] ?? '—' }}</div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['dean'] ?? 0), 2) }}</div>
                                    </div>

                                    <div class="rounded-xl border p-4">
                                        <div class="text-sm font-semibold text-gray-800">Chair Ratings</div>
                                        <div class="mt-2 space-y-1 text-sm text-gray-700">
                                            <div>Item 1: {{ $rCh['i1'] ?? '—' }}</div>
                                            <div>Item 2: {{ $rCh['i2'] ?? '—' }}</div>
                                            <div>Item 3: {{ $rCh['i3'] ?? '—' }}</div>
                                            <div>Item 4: {{ $rCh['i4'] ?? '—' }}</div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['chair'] ?? 0), 2) }}</div>
                                    </div>

                                    <div class="rounded-xl border p-4">
                                        <div class="text-sm font-semibold text-gray-800">Student Ratings</div>
                                        <div class="mt-2 space-y-1 text-sm text-gray-700">
                                            <div>Item 1: {{ $rSt['i1'] ?? '—' }}</div>
                                            <div>Item 2: {{ $rSt['i2'] ?? '—' }}</div>
                                            <div>Item 3: {{ $rSt['i3'] ?? '—' }}</div>
                                            <div>Item 4: {{ $rSt['i4'] ?? '—' }}</div>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['student'] ?? 0), 2) }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                    <div class="rounded-xl border p-4">
                                        <div class="text-xs text-gray-500">Weighted Total</div>
                                        <div class="text-lg font-semibold text-gray-800">{{ number_format((float) ($points['weighted'] ?? 0), 2) }}</div>
                                    </div>
                                    <div class="rounded-xl border p-4">
                                        <div class="text-xs text-gray-500">Previous Reclass (1/3)</div>
                                        <div class="text-lg font-semibold text-gray-800">{{ number_format((float) (($points['previous'] ?? 0) / 3), 2) }}</div>
                                    </div>
                                    <div class="rounded-xl border p-4">
                                        <div class="text-xs text-gray-500">Section II Total (Capped)</div>
                                        <div class="text-lg font-semibold text-gray-800">{{ number_format((float) ($points['total'] ?? 0), 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach

            @if($canForwardPerPaper)
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">Stage Actions</div>
                            <p class="text-xs text-gray-500">Quick access to return or forward at the bottom of the page.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('reclassification.return', $application) }}">
                                @csrf
                                <button type="submit"
                                        @disabled($returnActionLocked)
                                        class="px-4 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm font-semibold {{ $returnActionLocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                    Return to Faculty
                                </button>
                            </form>
                            <form method="POST" action="{{ route('reclassification.forward', $application) }}">
                                @csrf
                                <button type="submit"
                                        @disabled($forwardBlocked)
                                        class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft {{ $forwardBlocked ? 'opacity-60 cursor-not-allowed' : '' }}">
                                    {{ $nextLabel }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @if($returnBlocked)
                        <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            Return blocked: add at least one action-required comment first.
                        </div>
                    @endif
                    @if($hasPendingFacultyReturnRequest)
                        <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            Return action locked: faculty already requested a return. Use <span class="font-semibold">Approve Request</span> in the card above.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <button type="button"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 px-3 py-2 rounded-full bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark transition">
        <span aria-hidden="true">&uarr;</span>
    </button>

    <script>
        function reviewerCommentCenter(initialItems, initialOpenCount, initialRevisionItems, reviewerRole, applicationId) {
            return {
                panelOpen: false,
                revisionPanelOpen: false,
                showDetailedRevisionLog: false,
                activeTab: 'open',
                tabs: [
                    { key: 'open', label: 'Action required' },
                    { key: 'addressed', label: 'Addressed' },
                    { key: 'resolved', label: 'Resolved' },
                    { key: 'notes', label: 'Notes' },
                ],
                items: Array.isArray(initialItems) ? initialItems : [],
                reviewerRole: String(reviewerRole || '').toLowerCase(),
                openCount: Number(initialOpenCount || 0),
                revisionItems: Array.isArray(initialRevisionItems) ? initialRevisionItems : [],
                revisionCount: 0,
                activeRevisionTab: 'all',
                commentGroupsOpen: {},
                revisionGroupsOpen: {},
                showPreviousReviewerResolved: true,
                revisionTabs: [
                    { key: 'all', label: 'All' },
                    { key: 'updated', label: 'Updated' },
                    { key: 'added', label: 'Added' },
                    { key: 'removed', label: 'Removed' },
                ],
                panelStateStorageKey: `reviewer_side_panels:${Number(applicationId || 0)}:${String(reviewerRole || '').toLowerCase() || 'reviewer'}`,
                init() {
                    this.loadPanelUiState();
                    this.openCount = this.unresolvedCount();
                    this.revisionCount = this.revisionItems.length;
                    this.setShowPreviousReviewerResolved(this.showPreviousReviewerResolved);
                    this.$watch('panelOpen', () => this.savePanelUiState());
                    this.$watch('revisionPanelOpen', () => this.savePanelUiState());
                    this.$watch('activeTab', () => this.savePanelUiState());
                    this.$watch('activeRevisionTab', () => this.savePanelUiState());
                    this.$watch('commentGroupsOpen', () => this.savePanelUiState());
                    this.$watch('revisionGroupsOpen', () => this.savePanelUiState());
                },
                loadPanelUiState() {
                    try {
                        const raw = window.localStorage.getItem(this.panelStateStorageKey);
                        if (!raw) return;
                        const saved = JSON.parse(raw);
                        if (!saved || typeof saved !== 'object') return;

                        if (typeof saved.panelOpen === 'boolean') this.panelOpen = saved.panelOpen;
                        if (typeof saved.revisionPanelOpen === 'boolean') this.revisionPanelOpen = saved.revisionPanelOpen;
                        if (typeof saved.activeTab === 'string') this.activeTab = saved.activeTab;
                        if (typeof saved.activeRevisionTab === 'string') this.activeRevisionTab = saved.activeRevisionTab;
                        if (saved.commentGroupsOpen && typeof saved.commentGroupsOpen === 'object') this.commentGroupsOpen = saved.commentGroupsOpen;
                        if (saved.revisionGroupsOpen && typeof saved.revisionGroupsOpen === 'object') this.revisionGroupsOpen = saved.revisionGroupsOpen;

                        if (this.panelOpen && this.revisionPanelOpen) {
                            this.revisionPanelOpen = false;
                        }
                    } catch (error) {}
                },
                savePanelUiState() {
                    try {
                        window.localStorage.setItem(this.panelStateStorageKey, JSON.stringify({
                            panelOpen: !!this.panelOpen,
                            revisionPanelOpen: !!this.revisionPanelOpen,
                            activeTab: String(this.activeTab || 'open'),
                            activeRevisionTab: String(this.activeRevisionTab || 'all'),
                            commentGroupsOpen: this.commentGroupsOpen || {},
                            revisionGroupsOpen: this.revisionGroupsOpen || {},
                        }));
                    } catch (error) {}
                },
                toggleCommentHistory() {
                    this.panelOpen = !this.panelOpen;
                    if (this.panelOpen) this.revisionPanelOpen = false;
                },
                toggleRevisionLog() {
                    this.revisionPanelOpen = !this.revisionPanelOpen;
                    if (this.revisionPanelOpen) this.panelOpen = false;
                },
                openRevisionFromCommentPanel() {
                    this.panelOpen = false;
                    this.revisionPanelOpen = true;
                },
                openCommentFromRevisionPanel() {
                    this.revisionPanelOpen = false;
                    this.panelOpen = true;
                },
                setShowPreviousReviewerResolved(enabled) {
                    this.showPreviousReviewerResolved = !!enabled;
                    window.dispatchEvent(new CustomEvent('reviewer-toggle-previous-resolved', {
                        detail: {
                            enabled: this.showPreviousReviewerResolved,
                            reviewerRole: this.reviewerRole,
                        },
                    }));
                },
                isPreviousReviewerResolvedItem(item) {
                    const type = String(item?.action_type || 'requires_action');
                    const status = String(item?.status || 'open');
                    const authorRole = String(item?.author_role || '').toLowerCase();
                    const reviewerRole = String(this.reviewerRole || '').toLowerCase();
                    if (type === 'info' || status !== 'resolved') return false;
                    if (!reviewerRole) return false;
                    return authorRole !== '' && authorRole !== reviewerRole;
                },
                scopedItems() {
                    const items = this.items || [];
                    if (!items.length) return [];

                    const currentReview = items.filter((item) => String(item?.return_label || '').trim().toLowerCase() === 'current review');
                    if (currentReview.length > 0) return currentReview;

                    const numbered = items.filter((item) => {
                        const label = String(item?.return_label || '').trim();
                        return /(first|second|third|fourth|fifth|\d+(st|nd|rd|th)\s+return)/i.test(label);
                    });

                    if (numbered.length > 0) {
                        let latestLabel = '';
                        let latestScore = -Infinity;
                        numbered.forEach((item) => {
                            const label = String(item?.return_label || '').trim();
                            const score = this.snapshotSortIndex(label);
                            if (score > latestScore) {
                                latestScore = score;
                                latestLabel = label;
                            }
                        });
                        return items.filter((item) => String(item?.return_label || '').trim() === latestLabel);
                    }

                    return items;
                },
                matchesFilter(type, status, authorRole = '') {
                    const t = String(type || 'requires_action');
                    const s = String(status || 'open');
                    if (this.activeTab === 'notes') return t === 'info';
                    if (this.activeTab === 'addressed') return t !== 'info' && s === 'addressed';
                    if (this.activeTab === 'resolved') {
                        if (t === 'info' || s !== 'resolved') return false;
                        if (!this.showPreviousReviewerResolved && this.isPreviousReviewerResolvedItem({ action_type: t, status: s, author_role: authorRole })) {
                            return false;
                        }
                        return true;
                    }
                    return t !== 'info' && s === 'open';
                },
                datasetForMode(mode) {
                    const key = String(mode || 'open');
                    if (key === 'open') return this.scopedItems();
                    return this.items || [];
                },
                countFor(mode, list = null) {
                    const items = Array.isArray(list) ? list : this.datasetForMode(mode);
                    return items.filter((item) => {
                        const t = String(item?.action_type || 'requires_action');
                        const s = String(item?.status || 'open');
                        if (mode === 'notes') return t === 'info';
                        if (mode === 'addressed') return t !== 'info' && s === 'addressed';
                        if (mode === 'resolved') {
                            if (t === 'info' || s !== 'resolved') return false;
                            if (!this.showPreviousReviewerResolved && this.isPreviousReviewerResolvedItem(item)) {
                                return false;
                            }
                            return true;
                        }
                        return t !== 'info' && s === 'open';
                    }).length;
                },
                openRequiredCount() {
                    return (this.items || []).filter((item) => {
                        const t = String(item?.action_type || 'requires_action');
                        const s = String(item?.status || 'open');
                        return t !== 'info' && s === 'open';
                    }).length;
                },
                addressedCount() {
                    return (this.items || []).filter((item) => {
                        const t = String(item?.action_type || 'requires_action');
                        const s = String(item?.status || 'open');
                        return t !== 'info' && s === 'addressed';
                    }).length;
                },
                notesCount() {
                    return (this.items || []).filter((item) => String(item?.action_type || 'requires_action') === 'info').length;
                },
                requiredTotalCount() {
                    return (this.items || []).filter((item) => String(item?.action_type || 'requires_action') !== 'info').length;
                },
                unresolvedCount() {
                    return (this.items || []).filter((item) => {
                        const t = String(item?.action_type || 'requires_action');
                        const s = String(item?.status || 'open');
                        return t !== 'info' && s !== 'resolved';
                    }).length;
                },
                resolvedCount() {
                    return (this.items || []).filter((item) => {
                        const t = String(item?.action_type || 'requires_action');
                        const s = String(item?.status || 'open');
                        return t !== 'info' && s === 'resolved';
                    }).length;
                },
                resolutionProgress() {
                    const total = this.requiredTotalCount();
                    if (total < 1) return 100;
                    return Math.round((this.resolvedCount() / total) * 100);
                },
                hasResolutionProgress() {
                    return (this.addressedCount() + this.resolvedCount()) > 0;
                },
                trackerItems() {
                    const role = String(this.reviewerRole || '').toLowerCase();
                    return (this.items || []).filter((item) => {
                        const type = String(item?.action_type || 'requires_action');
                        const visibility = String(item?.visibility || 'faculty_visible');
                        if (type === 'info' || visibility !== 'faculty_visible') return false;
                        if (!role) return true;
                        return String(item?.author_role || '').toLowerCase() === role;
                    });
                },
                trackerOpenRequiredCount() {
                    return this.trackerItems().filter((item) => String(item?.status || 'open') === 'open').length;
                },
                trackerAddressedCount() {
                    return this.trackerItems().filter((item) => String(item?.status || 'open') === 'addressed').length;
                },
                trackerResolvedCount() {
                    return this.trackerItems().filter((item) => String(item?.status || 'open') === 'resolved').length;
                },
                trackerRequiredTotalCount() {
                    return this.trackerItems().length;
                },
                trackerResolutionProgress() {
                    const total = this.trackerRequiredTotalCount();
                    if (total < 1) return 0;
                    return Math.round((this.trackerResolvedCount() / total) * 100);
                },
                trackerHasResolutionProgress() {
                    return this.trackerRequiredTotalCount() > 0
                        && (this.trackerAddressedCount() + this.trackerResolvedCount()) > 0;
                },
                visibleItemCount() {
                    const items = this.datasetForMode(this.activeTab);
                    return (items || []).filter((item) => {
                        const t = String(item?.action_type || 'requires_action');
                        const s = String(item?.status || 'open');
                        if (this.activeTab === 'notes') return t === 'info';
                        if (this.activeTab === 'addressed') return t !== 'info' && s === 'addressed';
                        if (this.activeTab === 'resolved') return t !== 'info' && s === 'resolved';
                        return t !== 'info' && s === 'open';
                    }).length;
                },
                hasVisibleInGroup(items) {
                    return (items || []).some((item) => this.matchesFilter(item?.action_type, item?.status, item?.author_role));
                },
                snapshotHasVisible(snapshot) {
                    if (!snapshot || !Array.isArray(snapshot.items)) return false;
                    return this.hasVisibleInGroup(snapshot.items);
                },
                groupedItems() {
                    const groups = [];
                    const map = new Map();
                    (this.items || []).forEach((item) => {
                        const section = String(item.section_code || '-');
                        if (!map.has(section)) {
                            const group = {
                                section,
                                sectionLabel: section === '-' ? 'General' : `Section ${section}`,
                                items: [],
                            };
                            map.set(section, group);
                            groups.push(group);
                        }
                        const group = map.get(section);
                        group.items.push(item);
                    });
                    groups.forEach((group) => {
                        group.items.sort((a, b) => this.compareCriterionOrder(a, b));
                    });
                    const sorted = groups.sort((a, b) => {
                        const aNum = Number(a.section);
                        const bNum = Number(b.section);
                        if (Number.isNaN(aNum) || Number.isNaN(bNum)) return a.section.localeCompare(b.section);
                        return aNum - bNum;
                    });
                    sorted.forEach((group) => {
                        const key = String(group.section);
                        if (!(key in this.commentGroupsOpen)) {
                            this.commentGroupsOpen[key] = false;
                        }
                    });
                    return sorted;
                },
                snapshotSortIndex(label) {
                    const text = String(label || '').trim();
                    if (text.toLowerCase() === 'current review') return 10000;
                    if (text.toLowerCase() === 'before first return') return -1;
                    const match = text.match(/(\d+)(?:st|nd|rd|th)\s+return/i);
                    if (match) return Number(match[1] || 0);
                    if (/^first return$/i.test(text)) return 1;
                    if (/^second return$/i.test(text)) return 2;
                    if (/^third return$/i.test(text)) return 3;
                    if (/^fourth return$/i.test(text)) return 4;
                    if (/^fifth return$/i.test(text)) return 5;
                    return 0;
                },
                groupedSnapshotItems() {
                    const batches = [];
                    const map = new Map();
                    (this.items || []).forEach((item) => {
                        const label = String(item.return_label || 'Current Review');
                        const key = label.toLowerCase().replace(/\s+/g, '_');
                        if (!map.has(key)) {
                            const batch = { key, label, items: [] };
                            map.set(key, batch);
                            batches.push(batch);
                        }
                        map.get(key).items.push(item);
                    });

                    batches.sort((a, b) => this.snapshotSortIndex(b.label) - this.snapshotSortIndex(a.label));

                    return batches.map((batch) => ({
                        ...batch,
                        reviewer: String(batch.items?.[0]?.return_reviewer || ''),
                        dateLabel: String(batch.items?.[0]?.return_date_label || ''),
                         openCount: (batch.items || []).filter((item) => {
                            const t = String(item?.action_type || 'requires_action');
                            const s = String(item?.status || 'open');
                            return t !== 'info' && s === 'open';
                        }).length,
                        sections: this.sectionGroups(batch.items),
                    }));
                },
                stageSortIndex(role) {
                    const normalized = String(role || '').toLowerCase();
                    if (normalized === 'dean') return 10;
                    if (normalized === 'hr') return 20;
                    if (normalized === 'vpaa') return 30;
                    if (normalized === 'president') return 40;
                    return 90;
                },
                stageResolvedLabel(role) {
                    const normalized = String(role || '').toLowerCase();
                    if (normalized === 'dean') return 'Dean Stage';
                    if (normalized === 'hr') return 'HR Stage';
                    if (normalized === 'vpaa') return 'VPAA Stage';
                    if (normalized === 'president') return 'President Stage';
                    return 'Reviewer Stage';
                },
                groupedResolvedStageItems() {
                    const stageMap = new Map();
                    (this.items || []).forEach((item) => {
                        const type = String(item?.action_type || 'requires_action');
                        const status = String(item?.status || 'open');
                        if (type === 'info' || status !== 'resolved') {
                            return;
                        }
                        if (!this.showPreviousReviewerResolved && this.isPreviousReviewerResolvedItem(item)) {
                            return;
                        }

                        const role = String(item?.author_role || 'reviewer').toLowerCase();
                        const key = `stage_${role || 'reviewer'}`;
                        if (!stageMap.has(key)) {
                            stageMap.set(key, {
                                key,
                                role,
                                label: this.stageResolvedLabel(role),
                                items: [],
                            });
                        }
                        stageMap.get(key).items.push(item);
                    });

                    return Array.from(stageMap.values())
                        .sort((a, b) => this.stageSortIndex(a.role) - this.stageSortIndex(b.role))
                        .map((stage) => ({
                            ...stage,
                            count: (stage.items || []).length,
                            sections: this.sectionGroups(stage.items),
                        }));
                },
                groupedCurrentSections() {
                    return this.sectionGroups(this.scopedItems());
                },
                commentGroupKey(snapshotKey, section) {
                    return `${String(snapshotKey)}::${String(section)}`;
                },
                isCommentGroupOpen(snapshotKey, section) {
                    const key = this.commentGroupKey(snapshotKey, section);
                    return !!this.commentGroupsOpen[key];
                },
                toggleCommentGroup(snapshotKey, section) {
                    const key = this.commentGroupKey(snapshotKey, section);
                    this.commentGroupsOpen[key] = !this.isCommentGroupOpen(snapshotKey, section);
                    this.savePanelUiState();
                },
                criterionOrderParts(item) {
                    const raw = String(item?.criterion_key || '').toUpperCase().trim();
                    const normalized = raw.replace(/[^A-Z0-9]/g, '');
                    const match = normalized.match(/^([A-Z]+)(\d+)?(.*)$/);
                    if (!match) return { prefix: 'ZZZ', number: 9999, tail: normalized };
                    return {
                        prefix: String(match[1] || ''),
                        number: Number(match[2] || 0),
                        tail: String(match[3] || ''),
                    };
                },
                compareCriterionOrder(a, b) {
                    const left = this.criterionOrderParts(a);
                    const right = this.criterionOrderParts(b);
                    if (left.prefix !== right.prefix) return left.prefix.localeCompare(right.prefix);
                    if (left.number !== right.number) return left.number - right.number;
                    if (left.tail !== right.tail) return left.tail.localeCompare(right.tail);
                    const leftDate = String(a?.created_at || '');
                    const rightDate = String(b?.created_at || '');
                    return leftDate.localeCompare(rightDate);
                },
                statusLabel(item) {
                    const type = item?.action_type || 'requires_action';
                    const status = item?.status || 'open';
                    if (type === 'info') return 'Info';
                    if (status === 'resolved') return 'Resolved by reviewer';
                    if (status === 'addressed') return 'Addressed by faculty';
                    return 'Action required';
                },
                statusClass(item) {
                    const type = item?.action_type || 'requires_action';
                    const status = item?.status || 'open';
                    if (type === 'info') return 'border-slate-200 bg-slate-50 text-slate-700';
                    if (status === 'resolved') return 'border-green-200 bg-green-50 text-green-700';
                    if (status === 'addressed') return 'border-blue-200 bg-blue-50 text-blue-700';
                    return 'border-amber-200 bg-amber-50 text-amber-700';
                },
                jumpToEntry(entryId) {
                    const id = Number(entryId || 0);
                    if (!id) return;
                    if (typeof window.reviewerSelectEntry === 'function') {
                        window.reviewerSelectEntry(id, { scroll: true });
                    } else {
                        const target = document.getElementById(`entry-comments-${id}`);
                        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                },
                openOpenCommentsPanel() {
                    this.activeTab = this.countFor('open') > 0 ? 'open' : 'addressed';
                    this.revisionPanelOpen = false;
                    this.panelOpen = true;
                },
                sectionGroups(items) {
                    const groups = [];
                    const map = new Map();
                    (items || []).forEach((item) => {
                        const section = String(item.section_code || '-');
                        if (!map.has(section)) {
                            const group = {
                                section,
                                sectionLabel: section === '-' ? 'General' : `Section ${section}`,
                                items: [],
                            };
                            map.set(section, group);
                            groups.push(group);
                        }
                        map.get(section).items.push(item);
                    });
                    groups.forEach((group) => {
                        group.items.sort((a, b) => this.compareCriterionOrder(a, b));
                    });
                    return groups.sort((a, b) => {
                        const aNum = Number(a.section);
                        const bNum = Number(b.section);
                        if (Number.isNaN(aNum) || Number.isNaN(bNum)) return a.section.localeCompare(b.section);
                        return aNum - bNum;
                    });
                },
                groupedRevisionBatches() {
                    const batches = [];
                    const map = new Map();
                    this.filteredRevisionItems().forEach((item) => {
                        const key = String(item.batch_key || 'initial');
                        if (!map.has(key)) {
                            const batch = {
                                key,
                                label: String(item.batch_label || 'Revisions'),
                                dateLabel: String(item.batch_date_label || ''),
                                sortAt: String(item.batch_sort_at || item.created_at || ''),
                                items: [],
                            };
                            map.set(key, batch);
                            batches.push(batch);
                        }
                        map.get(key).items.push(item);
                    });

                    batches.sort((a, b) => {
                        const aTime = Date.parse(a.sortAt || '');
                        const bTime = Date.parse(b.sortAt || '');
                        const aValid = Number.isFinite(aTime);
                        const bValid = Number.isFinite(bTime);
                        if (aValid && bValid) return bTime - aTime;
                        if (aValid) return -1;
                        if (bValid) return 1;
                        return String(a.label || '').localeCompare(String(b.label || ''));
                    });

                    return batches.map((batch) => ({
                        ...batch,
                        sections: this.sectionGroups(batch.items),
                    }));
                },
                revisionGroupKey(batchKey, section) {
                    return `${String(batchKey)}::${String(section)}`;
                },
                isRevisionGroupOpen(batchKey, section) {
                    const key = this.revisionGroupKey(batchKey, section);
                    return !!this.revisionGroupsOpen[key];
                },
                toggleRevisionGroup(batchKey, section) {
                    const key = this.revisionGroupKey(batchKey, section);
                    this.revisionGroupsOpen[key] = !this.isRevisionGroupOpen(batchKey, section);
                    this.savePanelUiState();
                },
                filteredRevisionItems() {
                    const list = this.revisionItems || [];
                    if (this.activeRevisionTab === 'all') return list;
                    if (this.activeRevisionTab === 'added') {
                        return list.filter((item) => (item?.change_type || 'update') === 'create');
                    }
                    if (this.activeRevisionTab === 'removed') {
                        return list.filter((item) => (item?.change_type || 'update') === 'remove');
                    }
                    // "Updated" tab includes update-like changes except add/remove.
                    return list.filter((item) => {
                        const type = String(item?.change_type || 'update');
                        return type !== 'create' && type !== 'remove';
                    });
                },
                revisionTypeLabel(item) {
                    const type = String(item?.change_type || 'update');
                    if (type === 'create') return 'Added';
                    if (type === 'remove') return 'Removed';
                    if (type === 'restore') return 'Restored';
                    if (type === 'section_total') return 'Section Total';
                    return 'Updated';
                },
                revisionTypeClass(item) {
                    const type = String(item?.change_type || 'update');
                    if (type === 'create') return 'border-green-200 bg-green-50 text-green-700';
                    if (type === 'remove') return 'border-red-200 bg-red-50 text-red-700';
                    if (type === 'restore') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                    if (type === 'section_total') return 'border-gray-200 bg-gray-50 text-gray-700';
                    return 'border-blue-200 bg-blue-50 text-blue-700';
                },
                jumpToRevision(item) {
                    if (!item) return;
                    const entryId = Number(item.entry_id || 0);
                    if (entryId > 0) {
                        this.jumpToEntry(entryId);
                        return;
                    }
                },
            };
        }

        (() => {
            if (window.__reviewerEntryCommentUiBound) return;
            window.__reviewerEntryCommentUiBound = true;

            const selectedRowClasses = ['bg-blue-50', 'ring-1', 'ring-blue-100'];

            const getRoot = () => document.getElementById('reviewer-content');

            const clearEntrySelection = (root) => {
                if (!root) return;

                root.querySelectorAll('[data-review-entry-row]').forEach((row) => {
                    row.classList.remove(...selectedRowClasses);
                });

            };

            const selectEntryById = (entryId, options = {}) => {
                const root = getRoot();
                if (!root) return false;
                const id = String(entryId ?? '').trim();
                if (!id) return false;
                const row = root.querySelector(`[data-review-entry-row="${id}"]`);
                if (!row) return false;

                clearEntrySelection(root);
                row.classList.add(...selectedRowClasses);

                if (options.scroll !== false) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                const commentsBlock = root.querySelector(`#entry-comments-${id}`);
                if (commentsBlock) {
                    commentsBlock.classList.add('ring-1', 'ring-blue-200');
                    setTimeout(() => commentsBlock.classList.remove('ring-1', 'ring-blue-200'), 900);
                }

                return true;
            };

            window.reviewerSelectEntry = (entryId, options = {}) => selectEntryById(entryId, options);

            document.addEventListener('click', (event) => {
                const root = getRoot();
                if (!root) return;

                const row = event.target.closest('[data-review-entry-row]');
                if (row && root.contains(row)) {
                    const rowId = row.getAttribute('data-review-entry-row');
                    selectEntryById(rowId, { scroll: false });
                    return;
                }

                if (!event.target.closest('[data-entry-comments-block]') && !event.target.closest('#reviewer-comments-panel') && !event.target.closest('#reviewer-revision-panel')) {
                    clearEntrySelection(root);
                }
            }, true);
        })();
    </script>

    @include('reclassification.partials.async-actions')
</x-app-layout>
