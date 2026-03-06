<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">
                Faculty Dashboard
            </h2>
            <p class="text-sm text-gray-500">
                Manage your reclassification applications.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-blue-50 border border-blue-200 rounded-2xl shadow-card p-6">
                    <div class="text-sm font-semibold text-blue-800">
                        {{ session('success') }}
                    </div>
                </div>
            @endif
            @if ($errors->has('draft_delete'))
                <div class="bg-red-50 border border-red-200 rounded-2xl shadow-card p-6">
                    <div class="text-sm font-semibold text-red-800">
                        {{ $errors->first('draft_delete') }}
                    </div>
                </div>
            @endif

            @php
                $latestFinalized = $applications->firstWhere('status', 'finalized');
            @endphp
            @if(!empty($promotionNotification) || $latestFinalized)
                @php
                    $payload = $promotionNotification?->data ?? [];
                    $fromRank = $payload['from_rank'] ?? ($latestFinalized->current_rank_label_at_approval ?? null);
                    $toRank = $payload['to_rank'] ?? ($latestFinalized->approved_rank_label ?? null);
                    $cycle = $payload['cycle_year'] ?? ($latestFinalized->cycle_year ?? null);
                    $congratsKeySeed = $payload['application_id'] ?? ($latestFinalized->id ?? 'latest');
                    $congratsDismissKey = 'faculty_congrats_dismissed_' . $congratsKeySeed;
                @endphp
                <div
                    x-data="{ hidden: localStorage.getItem(@js($congratsDismissKey)) === '1' }"
                    x-show="!hidden"
                    x-cloak
                    class="bg-green-50 border border-green-200 rounded-2xl shadow-card p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="text-sm font-semibold text-green-800">
                            {{ $payload['title'] ?? 'Congratulations! Your reclassification has been approved.' }}
                        </div>
                        <button type="button"
                                @click="hidden = true; localStorage.setItem(@js($congratsDismissKey), '1')"
                                class="inline-flex items-center justify-center h-7 w-7 rounded-md border border-green-300 text-green-700 hover:bg-green-100"
                                aria-label="Dismiss notification">
                            ×
                        </button>
                    </div>
                    <div class="mt-1 text-sm text-green-700">
                        {{ $payload['message'] ?? 'Your promotion has been finalized.' }}
                        @if($fromRank && $toRank)
                            <span class="font-semibold"> {{ $fromRank }} to {{ $toRank }}.</span>
                        @endif
                        @if($cycle)
                            <span> (Cycle {{ $cycle }})</span>
                        @endif
                    </div>
                </div>
            @endif

            @php
                $bannerPeriod = $activePeriod ?? null;
                $isOpen = !empty($openPeriod);
                $startAt = $bannerPeriod?->start_at;
                $endAt = $bannerPeriod?->end_at;
                $periodRange = null;
                if ($startAt || $endAt) {
                    $periodRange = (optional($startAt)->format('M d, Y') ?? 'TBD') . ' to ' . (optional($endAt)->format('M d, Y') ?? 'TBD');
                }

                $bannerClass = 'bg-gray-50 border-gray-200 text-gray-800';
                $bannerTitle = 'No active reclassification submission';
                $bannerMessage = 'There is currently no ongoing reclassification period. Please wait for HR announcement.';

                if ($bannerPeriod && $isOpen) {
                    $bannerClass = 'bg-green-50 border-green-200 text-green-900';
                    $bannerTitle = 'Reclassification is open';
                    if ($endAt) {
                        $daysLeft = now()->startOfDay()->diffInDays($endAt->copy()->startOfDay(), false);
                        if ($daysLeft > 1) {
                            $bannerMessage = $daysLeft . ' days left to submit.';
                        } elseif ($daysLeft === 1) {
                            $bannerMessage = '1 day left to submit.';
                        } elseif ($daysLeft === 0) {
                            $bannerMessage = 'Last day to submit.';
                        } else {
                            $bannerClass = 'bg-amber-50 border-amber-200 text-amber-900';
                            $bannerMessage = 'Submission deadline has passed. Please contact HR.';
                        }
                    } else {
                        $bannerMessage = 'Submission period is open. No closing date set yet.';
                    }
                } elseif ($bannerPeriod) {
                    $bannerClass = 'bg-amber-50 border-amber-200 text-amber-900';
                    $bannerTitle = 'Reclassification is closed';
                    if ($startAt && now()->lt($startAt)) {
                        $daysToOpen = now()->startOfDay()->diffInDays($startAt->copy()->startOfDay(), false);
                        if ($daysToOpen > 1) {
                            $bannerMessage = 'Opens in ' . $daysToOpen . ' days.';
                        } elseif ($daysToOpen === 1) {
                            $bannerMessage = 'Opens tomorrow.';
                        } else {
                            $bannerMessage = 'Opens today.';
                        }
                    } else {
                        $bannerMessage = 'No ongoing reclassification submission.';
                    }
                }
            @endphp
            <div id="faculty-reclassification-banner"
                 data-auto-refresh
                 data-auto-refresh-url="{{ request()->fullUrl() }}"
                 data-auto-refresh-interval="8000">
                <div class="rounded-2xl border p-5 {{ $bannerClass }}">
                    <div class="flex flex-col gap-1">
                        <div class="text-sm font-semibold">{{ $bannerTitle }}</div>
                        <div class="text-sm">{{ $bannerMessage }}</div>
                        @if($bannerPeriod)
                            <div class="text-xs opacity-80 mt-1">
                                Period: {{ $bannerPeriod->name ?? ('CY ' . ($bannerPeriod->cycle_year ?? 'N/A')) }}
                                @if($periodRange)
                                    - {{ $periodRange }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @php
                $highestDegree = strtolower((string) ($user->facultyHighestDegree?->highest_degree ?? ''));
                $facultyYearsInBu = $user->facultyProfile?->original_appointment_date
                    ? $user->facultyProfile->original_appointment_date->diffInYears(now())
                    : 0;
                $hasMastersForSubmit = in_array($highestDegree, ['masters', 'doctorate'], true);
                $hasYearsForSubmit = $facultyYearsInBu >= 3;
                $missingSubmitRequirements = [];
                if (!$hasMastersForSubmit) {
                    $missingSubmitRequirements[] = "Master's degree";
                }
                if (!$hasYearsForSubmit) {
                    $missingSubmitRequirements[] = 'At least 3 years of BU service';
                }
            @endphp
            @if(!empty($missingSubmitRequirements))
                <div class="bg-amber-50 border border-amber-200 rounded-2xl shadow-card p-5">
                    <div class="text-sm font-semibold text-amber-900">
                        Final submit is currently locked.
                    </div>
                    <div class="mt-1 text-sm text-amber-800">
                        Missing requirement{{ count($missingSubmitRequirements) > 1 ? 's' : '' }}:
                        {{ implode(' and ', $missingSubmitRequirements) }}.
                        You can still work and save your draft.
                    </div>
                </div>
            @endif

            @php
                $returnedSubmission = $applications->firstWhere('status', 'returned_to_faculty');
            @endphp
            @if($returnedSubmission)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl shadow-card p-5">
                    <div class="text-sm font-semibold text-amber-900">
                        Your reclassification was returned for revision.
                    </div>
                    <div class="mt-1 text-sm text-amber-800">
                        Open your current submission and address reviewer's comments. Only flagged/commented entries should be updated before resubmission.
                    </div>
                </div>
            @endif
            @if(!empty($currentCycleRejectedApplication))
                <div class="bg-red-50 border border-red-200 rounded-2xl shadow-card p-5">
                    <div class="text-sm font-semibold text-red-900">
                        Your application was final rejected for the current reclassification period.
                    </div>
                    <div class="mt-1 text-sm text-red-800">
                        You cannot create another application in this period. Please wait for the next reclassification cycle.
                    </div>
                </div>
            @endif
            @if(!empty($currentCycleFinalizedApplication))
                <div class="bg-blue-50 border border-blue-200 rounded-2xl shadow-card p-5">
                    <div class="text-sm font-semibold text-blue-900">
                        Reclassification for this period is already completed.
                    </div>
                    <div class="mt-1 text-sm text-blue-800">
                        Your submission has been approved by President. Please wait for the next reclassification period.
                    </div>
                </div>
            @endif

            {{-- PROFILE SUMMARY --}}
            @php
                $profile = $user->facultyProfile;
                $departmentName = $user->department?->name ?? 'Not set';
                $employeeNo = $profile?->employee_no ?? 'Not set';
                $employmentType = $profile?->employment_type
                    ? ucwords(str_replace('_', ' ', $profile->employment_type))
                    : 'Not set';
                $rank = $profile?->teaching_rank ?? '';
                $rankStep = $profile?->rank_step ?? '';
                $currentRank = trim($rank . ($rankStep !== '' ? ' - ' . $rankStep : ''));
                $currentRank = $currentRank !== '' ? $currentRank : 'Not set';
            @endphp

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Faculty Profile
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Employee No.</span>
                        <div class="font-medium text-gray-800">
                            {{ $employeeNo }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Department</span>
                        <div class="font-medium text-gray-800">
                            {{ $departmentName }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Current Rank</span>
                        <div class="font-medium text-gray-800">
                            {{ $currentRank }}
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500">Employment Type</span>
                        <div class="font-medium text-gray-800">
                            {{ $employmentType }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ACTION NAV --}}
            @php
                $activeDraftStatuses = [
                    'draft',
                    'returned_to_faculty',
                    'dean_review',
                    'hr_review',
                    'vpaa_review',
                    'vpaa_approved',
                    'president_review',
                ];
                $activePeriodId = (int) ($activePeriod?->id ?? 0);
                $activeCycleYear = trim((string) ($activePeriod?->cycle_year ?? ''));

                $currentSubmission = $applications->first(function ($app) use ($activeDraftStatuses, $activePeriodId, $activeCycleYear) {
                    if (!in_array($app->status, $activeDraftStatuses, true)) {
                        return false;
                    }

                    $appPeriodId = (int) ($app->period_id ?? 0);
                    $appCycleYear = trim((string) ($app->cycle_year ?? ''));

                    if ($activePeriodId > 0) {
                        if ($appPeriodId === $activePeriodId) {
                            return true;
                        }

                        return $appPeriodId === 0 && $activeCycleYear !== '' && $appCycleYear === $activeCycleYear;
                    }

                    return $appPeriodId === 0 && $appCycleYear === '';
                });
                if (!$currentSubmission && $activePeriodId > 0) {
                    // Fallback: unassigned draft created while no period was active.
                    // It will be auto-assigned as soon as faculty opens the form.
                    $currentSubmission = $applications->first(function ($app) use ($activeDraftStatuses) {
                        return in_array($app->status, $activeDraftStatuses, true)
                            && (int) ($app->period_id ?? 0) === 0
                            && trim((string) ($app->cycle_year ?? '')) === '';
                    });
                }

                $hasCurrentSubmissionForStartLock = !empty($currentSubmission);
                $lockStartForRejectedCurrentCycle = !empty($currentCycleRejectedApplication);
                $lockStartForFinalizedCurrentCycle = !empty($currentCycleFinalizedApplication);
                $disableStartButton = $hasCurrentSubmissionForStartLock || $lockStartForRejectedCurrentCycle || $lockStartForFinalizedCurrentCycle;
            @endphp

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    @if(!$disableStartButton)
                        <a href="{{ route('reclassification.show') }}"
                           class="px-5 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                            Start Reclassification
                        </a>
                    @else
                        <button type="button"
                                disabled
                                class="px-5 py-2.5 rounded-xl bg-gray-200 text-gray-500 text-sm font-semibold cursor-not-allowed">
                            Start Reclassification
                        </button>
                    @endif

                    <a href="{{ route('profile.edit') }}"
                       class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        My Profile
                    </a>
                </div>
                @if(!empty($currentCycleFinalizedApplication))
                    <div class="mt-3 text-xs text-gray-500">
                        Start is locked because this period is already completed.
                    </div>
                @endif
            </div>

            @php
                $returnStageLabel = function (?string $from): string {
                    return match (strtolower(trim((string) $from))) {
                        'dean' => 'Dean',
                        'hr' => 'HR',
                        'vpaa' => 'VPAA',
                        'president' => 'President',
                        default => 'Reviewer',
                    };
                };
                $statusMap = [
                    'draft' => ['Draft', 'bg-gray-100 text-gray-700'],
                    'returned_to_faculty' => ['Returned', 'bg-amber-50 text-amber-700'],
                    'dean_review' => ['Dean', 'bg-blue-50 text-blue-700'],
                    'hr_review' => ['HR', 'bg-blue-50 text-blue-700'],
                    'vpaa_review' => ['VPAA', 'bg-blue-50 text-blue-700'],
                    'vpaa_approved' => ['VPAA Approved', 'bg-indigo-50 text-indigo-700'],
                    'president_review' => ['President', 'bg-blue-50 text-blue-700'],
                    'finalized' => ['Finalized', 'bg-green-50 text-green-700'],
                    'rejected_final' => ['Rejected', 'bg-red-50 text-red-700'],
                ];
                $historyApplications = $applications->reject(function ($app) use ($currentSubmission) {
                    return $currentSubmission && (int) $app->id === (int) $currentSubmission->id;
                });
            @endphp

            {{-- CURRENT SUBMISSION --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Current Submission
                    </h3>
                </div>

                <div class="p-6">
                    @if($currentSubmission)
                        @php
                            $statusInfo = $statusMap[$currentSubmission->status] ?? [ucfirst(str_replace('_', ' ', $currentSubmission->status)), 'bg-gray-100 text-gray-700'];
                            if (($currentSubmission->status ?? '') === 'returned_to_faculty') {
                                $statusInfo[0] = 'Returned by ' . $returnStageLabel($currentSubmission->returned_from ?? null);
                            }
                            $term = trim((string) ($currentSubmission->cycle_year ?? ''));
                            $isUnassignedDraft = ((int) ($currentSubmission->period_id ?? 0) === 0) && trim((string) ($currentSubmission->cycle_year ?? '')) === '';
                            if ($term === '') {
                                $term = trim((string) ($activePeriod?->name ?? ''));
                            }
                            if ($term === '') {
                                $term = trim((string) ($activePeriod?->cycle_year ?? ''));
                            }
                            if ($term === '') {
                                $term = 'Not set';
                            }
                            $isEditable = in_array($currentSubmission->status, ['draft', 'returned_to_faculty'], true);
                            $canRequestReturn = in_array($currentSubmission->status, ['dean_review', 'hr_review', 'vpaa_review', 'vpaa_approved'], true);
                            $hasPendingReturnRequest = !is_null($currentSubmission->faculty_return_requested_at ?? null);
                        @endphp
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="space-y-2">
                                <div class="text-sm text-gray-500">
                                    Term: <span class="font-medium text-gray-800">{{ $term }}</span>
                                </div>
                                <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $statusInfo[1] }}">
                                    {{ $statusInfo[0] }}
                                </span>
                                <div class="text-xs text-gray-500">
                                    Last updated {{ optional($currentSubmission->updated_at)->format('M d, Y h:i A') }}
                                </div>
                                @if($isUnassignedDraft && $activePeriod)
                                    <div class="text-xs text-amber-700">
                                        Draft is from no-period state and will be assigned to the active period when you open it.
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if($isEditable)
                                    <a href="{{ route('reclassification.show') }}"
                                       class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                                        Continue Draft
                                    </a>
                                @else
                                    <a href="{{ route('reclassification.submitted-summary.show', $currentSubmission) }}"
                                       class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                                        View Submission
                                    </a>
                                    @if($canRequestReturn)
                                        <form method="POST" action="{{ route('reclassification.request-return', $currentSubmission) }}">
                                            @csrf
                                            <button type="submit"
                                                    @disabled($hasPendingReturnRequest)
                                                    class="px-4 py-2 rounded-xl border text-sm font-semibold {{ $hasPendingReturnRequest ? 'border-amber-200 bg-amber-50 text-amber-700 cursor-not-allowed' : 'border-amber-300 text-amber-700 hover:bg-amber-50' }}">
                                                {{ $hasPendingReturnRequest ? 'Return Requested' : 'Request Return' }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if($hasPendingReturnRequest)
                            <div class="mt-3 text-xs text-amber-700">
                                Return request sent on {{ optional($currentSubmission->faculty_return_requested_at)->format('M d, Y h:i A') }}.
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-gray-500">
                            No active draft or in-review submission.
                        </div>
                    @endif
                </div>
            </div>

            {{-- APPLICATION HISTORY --}}
            <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Reclassification History
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left">Term</th>
                                <th class="px-6 py-3 text-left">Current Rank</th>
                                <th class="px-6 py-3 text-left">Stage</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @forelse($historyApplications as $app)
                                @php
                                    $term = $app->cycle_year ?: 'Not set';
                                    $statusInfo = $statusMap[$app->status] ?? [ucfirst(str_replace('_', ' ', $app->status)), 'bg-gray-100 text-gray-700'];
                                    if (($app->status ?? '') === 'returned_to_faculty') {
                                        $statusInfo[0] = 'Returned by ' . $returnStageLabel($app->returned_from ?? null);
                                    }
                                    $historicalCurrentRank = trim((string) ($app->current_rank_label_at_approval ?? ''));
                                    if ($historicalCurrentRank === '') {
                                        $historicalCurrentRank = $currentRank;
                                    }
                                    $historicalApprovedRank = trim((string) ($app->approved_rank_label ?? ''));
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $term }}</td>
                                    <td class="px-6 py-4">
                                        <div>{{ $historicalCurrentRank }}</div>
                                        @if($historicalApprovedRank !== '' && $historicalApprovedRank !== $historicalCurrentRank)
                                            <div class="text-xs text-green-700">Promoted to {{ $historicalApprovedRank }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusInfo[1] }}">
                                            {{ $statusInfo[0] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            @if($app->status === 'draft')
                                                <a href="{{ route('reclassification.drafts.summary', $app) }}"
                                                   class="text-bu hover:underline font-medium">
                                                    View
                                                </a>
                                                <form method="POST"
                                                      action="{{ route('reclassification.drafts.destroy', $app) }}"
                                                      onsubmit="return confirm('Delete this old draft? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-red-600 hover:underline font-medium">
                                                        Delete
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('reclassification.submitted-summary.show', $app) }}"
                                                   class="text-bu hover:underline font-medium">
                                                    View
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-6 text-center text-gray-500" colspan="4">
                                        No reclassification applications yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
