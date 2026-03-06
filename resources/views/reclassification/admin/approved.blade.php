<x-app-layout>
    @php
        $title = $title ?? 'Approved Reclassifications';
        $subtitle = $subtitle ?? 'Applications finalized after final approval.';
        $indexRoute = $indexRoute ?? route('reclassification.admin.approved');
        $showDepartmentFilter = $showDepartmentFilter ?? true;
        $showCycleFilter = $showCycleFilter ?? true;
        $showVpaaActions = $showVpaaActions ?? false;
        $showPresidentActions = $showPresidentActions ?? false;
        $allowExportActions = $allowExportActions ?? true;
        $rankLevels = $rankLevels ?? collect();
        $cycleYear = $cycleYear ?? null;
        $rankLevelId = $rankLevelId ?? null;
        $batchReadyCount = (int) ($batchReadyCount ?? 0);
        $batchBlockingCount = (int) ($batchBlockingCount ?? 0);
        $activePeriod = $activePeriod ?? null;
        $enforceActivePeriod = $enforceActivePeriod ?? false;
        $hasActivePeriod = (bool) ($hasActivePeriod ?? $activePeriod);
        $exportPeriodId = $exportPeriodId ?? null;
        $applicationItems = method_exists($applications, 'getCollection')
            ? $applications->getCollection()
            : collect($applications ?? []);
        $hasFinalizedRows = $applicationItems->contains(function ($app) {
            return (string) ($app->status ?? '') === 'finalized';
        });
        $showBackToHistoryButton = request()->routeIs('reclassification.history.period');
        $exportQuery = array_filter(array_merge(request()->query(), [
            'q' => $q ?? '',
            'department_id' => $departmentId ?? null,
            'rank_level_id' => $rankLevelId ?? null,
            'period_id' => $exportPeriodId,
        ]), fn ($value) => !is_null($value) && $value !== '');
        $hasApprovedFilters = ($departmentId !== null && $departmentId !== '')
            || ($cycleYear !== null && $cycleYear !== '')
            || ($rankLevelId !== null && $rankLevelId !== '');
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">{{ $title }}</h2>
                <p class="text-sm text-gray-500">{{ $subtitle }}</p>
            </div>
            @if($showBackToHistoryButton)
                <a href="{{ route('reclassification.history') }}"
                   class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Back to Reclassification History
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($enforceActivePeriod && !$hasActivePeriod)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No active period. Approved list is only shown for the active period. Past approved records are in Reclassification History.
                </div>
            @endif

            <form id="approved-filter-form"
                  method="GET"
                  action="{{ $indexRoute }}"
                  data-auto-submit
                  data-auto-submit-delay="450"
                  data-auto-submit-ajax="true"
                  data-auto-submit-target="#approved-results"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 items-end">
                    <div class="w-full lg:w-[70%]">
                        <label class="block text-xs font-semibold text-gray-600">Search</label>
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Search faculty, email, or employee no."
                               class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-bu focus:ring-bu">
                    </div>
                </div>

                <div class="flex flex-col gap-4 md:flex-row md:items-end">
                    @if($showDepartmentFilter)
                        <div class="w-full md:w-56">
                            <label class="block text-xs font-semibold text-gray-600">Department</label>
                            <select name="department_id"
                                    class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-bu focus:ring-bu">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected((string) $departmentId === (string) $dept->id)>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($showCycleFilter)
                        <div class="w-full md:w-56">
                            <label class="block text-xs font-semibold text-gray-600">Cycle Year</label>
                            <select name="cycle_year"
                                    class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-bu focus:ring-bu">
                                <option value="">All Cycles</option>
                                @foreach($cycleYears as $year)
                                    <option value="{{ $year }}" @selected((string) $cycleYear === (string) $year)>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="w-full md:w-56">
                        <label class="block text-xs font-semibold text-gray-600">Rank</label>
                        <select name="rank_level_id"
                                class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-bu focus:ring-bu">
                            <option value="">All Ranks</option>
                            @foreach($rankLevels as $level)
                                <option value="{{ $level->id }}" @selected((string) $rankLevelId === (string) $level->id)>
                                    {{ $level->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end justify-end md:ml-auto">
                        <button type="button"
                                data-reset-approved-filters
                                class="h-11 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 {{ $hasApprovedFilters ? '' : 'hidden' }}">
                            Reset Filters
                        </button>
                    </div>
                </div>
            </form>

            <div id="approved-results" data-ux-panel data-ux-initial-panel aria-busy="false" class="space-y-4">
                <div data-ux-panel-skeleton class="hidden">
                    <div class="space-y-4">
                        @if($showVpaaActions || $showPresidentActions)
                            <div class="ux-skeleton-card space-y-3">
                                <div class="ux-skeleton h-4 w-64"></div>
                                <div class="ux-skeleton h-4 w-56"></div>
                                <div class="ux-skeleton h-10 w-60"></div>
                            </div>
                        @endif
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <div class="ux-skeleton h-6 w-40"></div>
                                <div class="flex gap-2">
                                    <div class="ux-skeleton h-10 w-28"></div>
                                    <div class="ux-skeleton h-10 w-28"></div>
                                </div>
                            </div>
                            <x-ui.skeleton-table :rows="8" :cols="$showVpaaActions ? 9 : 8" />
                        </div>
                    </div>
                </div>

                <div data-ux-panel-content class="space-y-4">
                    @if($showVpaaActions || $showPresidentActions)
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-3">
                            <div class="text-sm text-gray-700">
                                <span class="font-semibold">Active period:</span>
                                @if($activePeriod)
                                    {{ $activePeriod->name }} ({{ $activePeriod->cycle_year ?? 'No cycle' }})
                                @else
                                    No open period
                                @endif
                            </div>
                            <div class="text-sm text-gray-700">
                                <span class="font-semibold">Ready for batch action:</span>
                                {{ $batchReadyCount }}
                                <span class="mx-2 text-gray-300">-</span>
                                <span class="font-semibold">Blocking submissions:</span>
                                {{ $batchBlockingCount }}
                            </div>

                            @if($errors->has('approved_list'))
                                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                                    {{ $errors->first('approved_list') }}
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-3">
                                @if($showVpaaActions)
                                    <form method="POST" action="{{ route('reclassification.review.approved.forward') }}">
                                        @csrf
                                        <button type="submit"
                                                data-ux-action-loading="Forwarding list..."
                                                @disabled(!$activePeriod || $batchReadyCount === 0)
                                                class="px-4 py-2 rounded-xl bg-bu text-white shadow-soft disabled:opacity-60 disabled:cursor-not-allowed">
                                            Forward Approved List to President
                                        </button>
                                    </form>
                                @endif

                                @if($showPresidentActions)
                                    <form method="POST" action="{{ route('reclassification.review.approved.finalize') }}">
                                        @csrf
                                        <button type="submit"
                                                data-ux-action-loading="Finalizing cycle..."
                                                @disabled(!$activePeriod || $batchReadyCount === 0)
                                                class="px-4 py-2 rounded-xl bg-green-600 text-white shadow-soft disabled:opacity-60 disabled:cursor-not-allowed">
                                            Approve and Finalize Cycle List
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-gray-800">Approved List</h3>
                            @if($allowExportActions)
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($hasFinalizedRows)
                                        <button type="button"
                                                data-print-url="{{ route('reclassification.approved.print', $exportQuery) }}"
                                                data-ux-action-loading="Preparing print..."
                                                class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Print Format
                                        </button>
                                        <a href="{{ route('reclassification.approved.export.csv', $exportQuery) }}"
                                           data-ux-link-loading="Preparing CSV..."
                                           class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Export CSV
                                        </a>
                                    @else
                                        <button type="button"
                                                disabled
                                                title="Print is available after President approval."
                                                class="px-4 py-2 rounded-xl border border-gray-200 text-gray-400 cursor-not-allowed">
                                            Print Format
                                        </button>
                                        <button type="button"
                                                disabled
                                                title="Export is available after President approval."
                                                class="px-4 py-2 rounded-xl border border-gray-200 text-gray-400 cursor-not-allowed">
                                            Export CSV
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($applications->isEmpty())
                            <div class="p-6">
                                <x-ui.state-empty
                                    :title="($enforceActivePeriod && !$hasActivePeriod) ? 'No approved list for inactive period' : 'No approved records found'"
                                    :message="($enforceActivePeriod && !$hasActivePeriod) ? 'Activate a period to view the current approved list.' : 'Try adjusting your filters or search terms.'"
                                />
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-left">
                                        <tr>
                                            <th class="px-4 py-2">Faculty</th>
                                            <th class="px-4 py-2">Department</th>
                                            <th class="px-4 py-2">Cycle</th>
                                            <th class="px-4 py-2">Current Rank</th>
                                            <th class="px-4 py-2">Approved Rank</th>
                                            <th class="px-4 py-2">Stage</th>
                                            <th class="px-4 py-2">Approved By</th>
                                            <th class="px-4 py-2">Approved At</th>
                                            @if($showVpaaActions)
                                                <th class="px-4 py-2 text-right">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach($applications as $app)
                                            @php
                                                $displayCurrentRank = $app->current_rank_label_at_approval
                                                    ?? $app->current_rank_label_preview
                                                    ?? '-';
                                                $displayApprovedRank = $app->approved_rank_label
                                                    ?? $app->approved_rank_label_preview
                                                    ?? $displayCurrentRank;
                                                $stageLabel = match((string) $app->status) {
                                                    'dean_review' => 'Dean',
                                                    'hr_review' => 'HR',
                                                    'vpaa_review' => 'VPAA',
                                                    'vpaa_approved' => 'VPAA Approved',
                                                    'president_review' => 'President',
                                                    'returned_to_faculty' => 'Returned',
                                                    'finalized' => 'Finalized',
                                                    'rejected_final' => 'Rejected',
                                                    'draft' => 'Draft',
                                                    default => ucfirst(str_replace('_', ' ', (string) $app->status)),
                                                };
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-2">
                                                    <div class="font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</div>
                                                    <div class="text-xs text-gray-500">ID #{{ $app->faculty_user_id }}</div>
                                                </td>
                                                <td class="px-4 py-2 text-gray-600">{{ $app->faculty?->department?->name ?? '-' }}</td>
                                                <td class="px-4 py-2 text-gray-600">{{ $app->cycle_year ?? '-' }}</td>
                                                <td class="px-4 py-2 text-gray-700 font-medium">{{ $displayCurrentRank }}</td>
                                                <td class="px-4 py-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border bg-green-50 text-green-700 border-green-200">
                                                        {{ $displayApprovedRank }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-gray-600">{{ $stageLabel }}</td>
                                                <td class="px-4 py-2 text-gray-600">{{ $app->approvedBy?->name ?? '-' }}</td>
                                                <td class="px-4 py-2 text-gray-600">{{ optional($app->approved_at ?? $app->finalized_at)->format('M d, Y g:i A') ?? '-' }}</td>
                                                @if($showVpaaActions)
                                                    <td class="px-4 py-2 text-right">
                                                        @if((string) ($app->status ?? '') === 'vpaa_approved')
                                                            <form method="POST"
                                                                  action="{{ route('reclassification.return', $app) }}"
                                                                  onsubmit="return confirm('Return this submission to faculty?');"
                                                                  class="inline-block">
                                                                @csrf
                                                                <button type="submit"
                                                                        data-ux-action-loading="Returning..."
                                                                        class="px-3 py-1.5 rounded-lg border border-amber-300 text-amber-700 text-xs font-semibold hover:bg-amber-50">
                                                                    Return to Faculty
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    @if(method_exists($applications, 'links'))
                        <div>
                            {{ $applications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const resetTrigger = event.target.closest('[data-reset-approved-filters]');
            if (resetTrigger) {
                const form = document.getElementById('approved-filter-form');
                if (!form) return;

                const department = form.querySelector('select[name="department_id"]');
                const cycle = form.querySelector('select[name="cycle_year"]');
                const rank = form.querySelector('select[name="rank_level_id"]');

                if (department) department.value = '';
                if (cycle) cycle.value = '';
                if (rank) rank.value = '';
                resetTrigger.classList.add('hidden');

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
                return;
            }

            const printTrigger = event.target.closest('[data-print-url]');
            if (!printTrigger) {
                return;
            }

            event.preventDefault();
            const printUrl = printTrigger.getAttribute('data-print-url');
            if (!printUrl) {
                return;
            }

            const existing = document.getElementById('approved-print-frame');
            if (existing) {
                existing.remove();
            }

            const frame = document.createElement('iframe');
            frame.id = 'approved-print-frame';
            frame.style.position = 'fixed';
            frame.style.right = '0';
            frame.style.bottom = '0';
            frame.style.width = '0';
            frame.style.height = '0';
            frame.style.border = '0';
            frame.src = printUrl;
            frame.onload = function () {
                const target = frame.contentWindow;
                if (!target) {
                    return;
                }
                target.focus();
                target.print();
            };

            document.body.appendChild(frame);
        });

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('approved-filter-form');
            if (!form) return;

            const resetButton = form.querySelector('[data-reset-approved-filters]');
            const department = form.querySelector('select[name="department_id"]');
            const cycle = form.querySelector('select[name="cycle_year"]');
            const rank = form.querySelector('select[name="rank_level_id"]');
            if (!resetButton || !rank) return;

            const syncResetVisibility = () => {
                const hasFilters = !!(department && department.value !== '')
                    || !!(cycle && cycle.value !== '')
                    || rank.value !== '';
                resetButton.classList.toggle('hidden', !hasFilters);
            };

            if (department) department.addEventListener('change', syncResetVisibility);
            if (cycle) cycle.addEventListener('change', syncResetVisibility);
            rank.addEventListener('change', syncResetVisibility);
            syncResetVisibility();
        });
    </script>
</x-app-layout>
