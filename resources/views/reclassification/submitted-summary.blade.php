<x-app-layout>
    @php
        $summaryMode = $summaryMode ?? 'submitted';
        $isDraftHistoryMode = $summaryMode === 'draft_history';
        $canRequestReturn = !$isDraftHistoryMode && in_array((string) ($application->status ?? ''), [
            'dean_review',
            'hr_review',
            'vpaa_review',
            'vpaa_approved',
        ], true);
        $hasPendingReturnRequest = !is_null($application->faculty_return_requested_at ?? null);
    @endphp
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ $isDraftHistoryMode ? 'Draft Reclassification Paper' : 'Submitted Reclassification Paper' }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $isDraftHistoryMode ? 'Read-only summary of your historical draft.' : 'Read-only summary of your submitted form.' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($canRequestReturn)
                    <form method="POST" action="{{ route('reclassification.request-return', $application) }}">
                        @csrf
                        <button type="submit"
                                @disabled($hasPendingReturnRequest)
                                class="px-4 py-2 rounded-xl border text-sm font-semibold {{ $hasPendingReturnRequest ? 'border-amber-200 bg-amber-50 text-amber-700 cursor-not-allowed' : 'border-amber-300 text-amber-700 hover:bg-amber-50' }}">
                            {{ $hasPendingReturnRequest ? 'Return Requested' : 'Request Return' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $sections = $application->sections->sortBy('section_code');
        $sectionsByCode = $sections->keyBy('section_code');
        $sectionTotals = [
            '1' => (float) optional($sectionsByCode->get('1'))->points_total,
            '2' => (float) optional($sectionsByCode->get('2'))->points_total,
            '3' => (float) optional($sectionsByCode->get('3'))->points_total,
            '4' => (float) optional($sectionsByCode->get('4'))->points_total,
            '5' => (float) optional($sectionsByCode->get('5'))->points_total,
        ];
        $currentRank = $currentRankLabel ?? 'Instructor';
        $trackKey = match (strtolower(trim($currentRank))) {
            'full professor', 'full' => 'full',
            'associate professor', 'associate' => 'associate',
            'assistant professor', 'assistant' => 'assistant',
            default => 'instructor',
        };
        $returnedFrom = strtolower(trim((string) ($application->returned_from ?? '')));
        $returnedFromLabel = match($returnedFrom) {
            'dean' => 'Dean',
            'hr' => 'HR',
            'vpaa' => 'VPAA',
            'president' => 'President',
            default => 'Reviewer',
        };
        $statusLabel = match($application->status) {
            'draft' => 'Draft',
            'returned_to_faculty' => "Returned by {$returnedFromLabel}",
            'dean_review' => 'Dean',
            'hr_review' => 'HR',
            'vpaa_review' => 'VPAA',
            'vpaa_approved' => 'VPAA Approved',
            'president_review' => 'President',
            'finalized' => 'Finalized',
            'rejected_final' => 'Rejected',
            default => ucfirst(str_replace('_',' ', $application->status)),
        };
        $approvedRankLabel = trim((string) ($application->approved_rank_label ?? ''));
        $criterionLabels = [
            '1' => [
                'a1' => "A1. Bachelor's Degree (Latin honors)",
                'a2' => "A2. Additional Bachelor's Degree",
                'a3' => "A3. Master's Degree",
                'a4' => "A4. Master's Degree Units",
                'a5' => "A5. Additional Master's Degree",
                'a6' => 'A6. Doctoral Units',
                'a7' => "A7. Doctor's Degree",
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

        $summaryTotalPoints = array_sum($sectionTotals);
        $summaryEqPercent = $summaryTotalPoints / 4;
        $summaryRankLabels = [
            'full' => 'Full Professor',
            'associate' => 'Associate Professor',
            'assistant' => 'Assistant Professor',
            'instructor' => 'Instructor',
        ];
        $summaryRanges = [
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
        $summaryPointsTrack = null;
        $summaryPointsLetter = null;
        foreach (['full', 'associate', 'assistant', 'instructor'] as $rank) {
            foreach ($summaryRanges[$rank] as $band) {
                if ($summaryEqPercent >= $band['min'] && $summaryEqPercent <= $band['max']) {
                    $summaryPointsTrack = $rank;
                    $summaryPointsLetter = $band['letter'];
                    break 2;
                }
            }
        }
        $summaryPointsRankLabel = $summaryPointsTrack
            ? ($summaryRankLabels[$summaryPointsTrack] . ' - ' . $summaryPointsLetter)
            : '-';

        $summaryHasMasters = (bool) ($eligibility['hasMasters'] ?? false);
        $summaryHasDoctorate = (bool) ($eligibility['hasDoctorate'] ?? false);
        $summaryHasResearchEquivalent = (bool) ($eligibility['hasResearchEquivalent'] ?? false);
        $summaryHasAcceptedResearchOutput = (bool) ($eligibility['hasAcceptedResearchOutput'] ?? false);

        $summaryAllowedRankLabel = 'Not eligible';
        if ($summaryHasMasters && $summaryHasResearchEquivalent) {
            $summaryOrder = ['instructor' => 1, 'assistant' => 2, 'associate' => 3, 'full' => 4];
            $summaryDesired = $summaryPointsTrack ?: $trackKey;
            $summaryMaxAllowed = ($summaryHasDoctorate && $summaryHasAcceptedResearchOutput) ? 'full' : 'associate';
            if (($summaryOrder[$summaryDesired] ?? 0) > ($summaryOrder[$summaryMaxAllowed] ?? 0)) {
                $summaryDesired = $summaryMaxAllowed;
            }
            $summaryOneStepOrder = ($summaryOrder[$trackKey] ?? 1) + 1;
            $summaryOneStep = array_search($summaryOneStepOrder, $summaryOrder, true) ?: $trackKey;
            if (($summaryOrder[$summaryDesired] ?? 0) > ($summaryOrder[$summaryOneStep] ?? 0)) {
                $summaryDesired = $summaryOneStep;
            }
            $summaryAllowedLetter = $summaryPointsLetter;
            if ($summaryPointsTrack && $summaryPointsTrack !== $summaryDesired) {
                // If capped down from a higher points rank, use highest letter in the allowed rank.
                $summaryAllowedLetter = 'A';
            }
            $summaryAllowedRankLabel = ($summaryRankLabels[$summaryDesired] ?? 'Not eligible')
                . ($summaryAllowedLetter ? (' - ' . $summaryAllowedLetter) : '');
        }
    @endphp

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Current Stage</div>
                    <div class="text-lg font-semibold text-gray-800">{{ $statusLabel }}</div>
                    @if($hasPendingReturnRequest)
                        <div class="mt-1 text-xs text-amber-700">
                            Return request sent on {{ optional($application->faculty_return_requested_at)->format('M d, Y h:i A') }}.
                        </div>
                    @endif
                </div>
                <div class="text-sm text-gray-500">
                    {{ $isDraftHistoryMode ? 'Saved at:' : 'Submitted at:' }}
                    <span class="font-medium text-gray-700">
                        {{ optional($isDraftHistoryMode ? $application->updated_at : $application->submitted_at)->format('M d, Y') ?? 'Not set' }}
                    </span>
                </div>
            </div>

            {{-- MY INFORMATION --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6"
                 x-data="submittedSummary({
                    s1: {{ $sectionTotals['1'] }},
                    s2: {{ $sectionTotals['2'] }},
                    s3: {{ $sectionTotals['3'] }},
                    s4: {{ $sectionTotals['4'] }},
                    s5: {{ $sectionTotals['5'] }},
                    track: '{{ $trackKey }}',
                    trackLabel: @js($currentRank),
                    hasMasters: {{ ($eligibility['hasMasters'] ?? false) ? 'true' : 'false' }},
                    hasDoctorate: {{ ($eligibility['hasDoctorate'] ?? false) ? 'true' : 'false' }},
                    hasResearchEquivalent: {{ ($eligibility['hasResearchEquivalent'] ?? false) ? 'true' : 'false' }},
                    hasAcceptedResearchOutput: {{ ($eligibility['hasAcceptedResearchOutput'] ?? false) ? 'true' : 'false' }},
                 })">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">My Information</h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs text-gray-500">Name</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $application->faculty?->name ?? 'Faculty' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Date of Original Appointment</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $appointmentDate ?? '"' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Total Years of Service (BU)</div>
                            <div class="text-sm font-semibold text-gray-800">
                                {{ $yearsService !== null ? (int) $yearsService . ' years' : '"' }}
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
                            <div class="text-sm font-semibold text-gray-800">{{ $currentRankLabel ?? 'Instructor' }}</div>
                        </div>
                        @if($approvedRankLabel !== '')
                            <div>
                                <div class="text-xs text-gray-500">Approved Rank</div>
                                <div class="text-sm font-semibold text-green-700">{{ $approvedRankLabel }}</div>
                            </div>
                        @endif
                        <div class="space-y-2 text-sm text-gray-700">
                            <div>
                                <div class="text-xs text-gray-500">Rank Based on Points</div>
                                <div class="font-semibold text-gray-800">{{ $summaryPointsRankLabel }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Allowed Rank (Rules Applied)</div>
                                <div class="font-semibold text-gray-800">{{ $summaryAllowedRankLabel }}</div>
                            </div>
                            <div class="text-xs text-gray-500">
                                Total points: <span class="font-semibold text-gray-800">{{ number_format((float) $summaryTotalPoints, 2) }}</span>
                                <span class="mx-2 text-gray-300">•</span>
                                Equivalent %: <span class="font-semibold text-gray-800">{{ number_format((float) $summaryEqPercent, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs text-gray-700 space-y-2">
                        <div class="font-semibold text-gray-800">Reminder</div>
                        <div>This summary is read-only.</div>
                        <div>If revisions are requested, you will be notified.</div>
                        <div class="text-gray-500">Section II points are not included yet.</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Ranks and Equivalent Percentages</h3>
                    <p class="text-sm text-gray-500">Reference table used to determine A/B/C rank letter.</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Track</th>
                                    <th class="px-4 py-3 text-left">A</th>
                                    <th class="px-4 py-3 text-left">B</th>
                                    <th class="px-4 py-3 text-left">C</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr>
                                    <td class="px-4 py-3 font-medium">Full Professor</td>
                                    <td class="px-4 py-3">95.87 - 100.00</td>
                                    <td class="px-4 py-3">91.50 - 95.86</td>
                                    <td class="px-4 py-3">87.53 - 91.49</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium">Associate Professor</td>
                                    <td class="px-4 py-3">83.34 - 87.52</td>
                                    <td class="px-4 py-3">79.19 - 83.33</td>
                                    <td class="px-4 py-3">75.02 - 79.18</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium">Assistant Professor</td>
                                    <td class="px-4 py-3">70.85 - 75.01</td>
                                    <td class="px-4 py-3">66.68 - 70.84</td>
                                    <td class="px-4 py-3">62.51 - 66.67</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-medium">Instructor</td>
                                    <td class="px-4 py-3">58.34 - 62.50</td>
                                    <td class="px-4 py-3">54.14 - 58.33</td>
                                    <td class="px-4 py-3">50.00 - 54.16</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @forelse($sections as $section)
                @if($section->section_code === '2')
                    @continue
                @endif
                @php
                    $entries = $section->entries->groupBy('criterion_key');
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

                    <div class="p-6 space-y-6">
                        @if($section->entries->isEmpty())
                            <p class="text-sm text-gray-500">No entries submitted for this section.</p>
                        @else
                            @foreach($entries as $criterionKey => $rows)
                                @php
                                    $label = $criterionLabels[$section->section_code][$criterionKey]
                                        ?? ($rows->first()?->title ?? strtoupper($criterionKey));
                                    $rowsPoints = $rows->sum('points');
                                @endphp
                                @if($section->section_code === '4' && $criterionKey === 'b' && $rowsPoints <= 0)
                                    @continue
                                @endif
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
                                                        $title = $entry->title ?: ($data['text'] ?? $data['title'] ?? 'Entry');
                                                        $evidences = $entry->evidences ?? collect();
                                                    @endphp
                                                    <tr>
                                                        <td class="px-4 py-2 font-medium text-gray-800">{{ $title }}</td>
                                                        <td class="px-4 py-2 text-gray-600">
                                                            <div class="space-y-1">
                                                                @foreach($data as $key => $value)
                                                                    @if($key === 'evidence')
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
                                                        <td class="px-4 py-2 text-right font-semibold text-gray-800">
                                                            {{ number_format((float) $entry->points, 2) }}
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

                @if($section->section_code === '1' && !empty($section2Review))
                    @php
                        $ratings = $section2Review['ratings'] ?? [];
                        $points = $section2Review['points'] ?? [];
                        $rDe = $ratings['dean'] ?? [];
                        $rCh = $ratings['chair'] ?? [];
                        $rSt = $ratings['student'] ?? [];
                    @endphp
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Section II " Instructional Competence</h3>
                            <p class="text-sm text-gray-500">Ratings from Dean, Chair, and Students (read-only).</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <div class="rounded-xl border p-4">
                                    <div class="text-sm font-semibold text-gray-800">Dean Ratings</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        <div>Item 1: {{ $rDe['i1'] ?? '"' }}</div>
                                        <div>Item 2: {{ $rDe['i2'] ?? '"' }}</div>
                                        <div>Item 3: {{ $rDe['i3'] ?? '"' }}</div>
                                        <div>Item 4: {{ $rDe['i4'] ?? '"' }}</div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['dean'] ?? 0), 2) }}</div>
                                </div>

                                <div class="rounded-xl border p-4">
                                    <div class="text-sm font-semibold text-gray-800">Chair Ratings</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        <div>Item 1: {{ $rCh['i1'] ?? '"' }}</div>
                                        <div>Item 2: {{ $rCh['i2'] ?? '"' }}</div>
                                        <div>Item 3: {{ $rCh['i3'] ?? '"' }}</div>
                                        <div>Item 4: {{ $rCh['i4'] ?? '"' }}</div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">Points: {{ number_format((float) ($points['chair'] ?? 0), 2) }}</div>
                                </div>

                                <div class="rounded-xl border p-4">
                                    <div class="text-sm font-semibold text-gray-800">Student Ratings</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        <div>Item 1: {{ $rSt['i1'] ?? '"' }}</div>
                                        <div>Item 2: {{ $rSt['i2'] ?? '"' }}</div>
                                        <div>Item 3: {{ $rSt['i3'] ?? '"' }}</div>
                                        <div>Item 4: {{ $rSt['i4'] ?? '"' }}</div>
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
                    </div>
                @endif
            @empty
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <p class="text-sm text-gray-500">No sections found for this application.</p>
                </div>
            @endforelse

        </div>
    </div>

    <script>
        function submittedSummary(init) {
            return {
                showRanks: false,
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

                totalPoints() {
                    return Number(this.s1 + this.s2 + this.s3 + this.s4 + this.s5);
                },

                eqPercent() {
                    return this.totalPoints() / 4;
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
                    if (!this.hasMasters || !this.hasResearchEquivalent) return '';
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
                    const maxAllowed = (this.hasDoctorate && this.hasAcceptedResearchOutput) ? 'full' : 'associate';
                    if (order[desired] > order[maxAllowed]) desired = maxAllowed;

                    const oneStep = nextRank(this.track);
                    if (order[desired] > order[oneStep]) desired = oneStep;

                    return labels[desired] || '';
                },
            };
        }
    </script>
</x-app-layout>
