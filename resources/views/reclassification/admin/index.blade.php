<x-app-layout>
    <x-slot name="header">
        <div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">All Reclassification Submissions</h2>
                <p class="text-sm text-gray-500">
                    Track active-period submissions across all statuses.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div id="admin-submissions-live"
             data-auto-refresh
             data-auto-refresh-url="{{ request()->fullUrl() }}"
             data-auto-refresh-interval="10000"
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

            @if(!$hasActivePeriod)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl shadow-card p-6">
                    <div class="text-sm font-semibold text-amber-900">No active period</div>
                    <div class="mt-1 text-sm text-amber-800">
                        Submissions are only shown for the active period. Past submissions are available in Reclassification History.
                    </div>
                </div>
            @endif

            @php
                $activity = $activity ?? 'active';
                $baseToggleQuery = request()->except(['page', 'activity']);
                $activeToggleQuery = array_merge($baseToggleQuery, ['activity' => 'active']);
                $rejectedToggleQuery = array_merge($baseToggleQuery, ['activity' => 'rejected']);
                $hasAdminFilters = $status !== 'all'
                    || ($departmentId !== null && $departmentId !== '')
                    || ($rankLevelId !== null && $rankLevelId !== '');
            @endphp

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs font-semibold text-gray-600">Submission State</div>
                </div>
                <div class="mt-3 inline-flex rounded-xl border border-gray-300 bg-gray-50 p-1">
                    <a href="{{ $indexRoute . '?' . http_build_query($activeToggleQuery) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activity === 'active' ? 'bg-bu text-white shadow-soft' : 'text-gray-700 hover:bg-gray-100' }}">
                        Active Submissions
                    </a>
                    <a href="{{ $indexRoute . '?' . http_build_query($rejectedToggleQuery) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activity === 'rejected' ? 'bg-red-600 text-white shadow-soft' : 'text-gray-700 hover:bg-gray-100' }}">
                        Rejected Submissions
                    </a>
                </div>
            </div>

            <form id="admin-submissions-filter-form"
                  method="GET" action="{{ $indexRoute }}"
                  data-auto-submit
                  data-auto-submit-delay="450"
                  data-auto-submit-ajax="true"
                  data-auto-submit-target="#admin-submissions-results"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 grid grid-cols-1 md:grid-cols-6 gap-4">
                <input type="hidden" name="activity" value="{{ $activity }}">
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-gray-600">Search</label>
                    <div class="mt-1 w-full md:w-[70%]">
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Search faculty, email, or ID"
                               class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-bu focus:ring-bu">
                    </div>
                </div>
                <div class="md:col-span-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end">
                        <div class="w-full md:w-56">
                            <label class="block text-xs font-semibold text-gray-600">Stage</label>
                            <select name="status"
                                    class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-bu focus:ring-bu">
                                <option value="all" @selected($status === 'all')>All</option>
                                <option value="submitted" @selected($status === 'submitted')>Submitted (In Queue)</option>
                                @foreach(['dean_review','hr_review','vpaa_review','vpaa_approved','president_review','returned_to_faculty','finalized','rejected_final'] as $st)
                                    <option value="{{ $st }}" @selected($status === $st)>
                                        {{ ucfirst(str_replace('_',' ', $st)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
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
                                    data-reset-admin-filters
                                    class="h-11 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 {{ $hasAdminFilters ? '' : 'hidden' }}">
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div id="admin-submissions-results" data-ux-panel data-ux-initial-panel aria-busy="false" class="space-y-4">
                <div data-ux-panel-skeleton class="hidden">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                        <x-ui.skeleton-table :rows="8" :cols="7" />
                    </div>
                </div>

                <div data-ux-panel-content class="space-y-4">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-visible">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            {{ $activity === 'rejected' ? 'Rejected Submissions' : 'Active Submissions' }}
                        </h3>
                    </div>

                    @if($applications->isEmpty())
                        <div class="p-6">
                            <x-ui.state-empty
                                :title="$hasActivePeriod ? 'No submissions match your filters.' : 'No active period submissions'"
                                :message="$hasActivePeriod ? 'Try adjusting filters or search keywords.' : 'Submissions are visible only while a period is active.'"
                            />
                        </div>
                    @else
                        <div class="overflow-x-auto md:overflow-visible">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-left">
                                    <tr>
                                        <th class="px-4 py-2">Faculty</th>
                                        <th class="px-4 py-2">Department</th>
                                        <th class="px-4 py-2">Rank</th>
                                        <th class="px-4 py-2">Cycle</th>
                                        <th class="px-4 py-2">Stage</th>
                                        <th class="px-4 py-2">Submitted</th>
                                        <th class="px-4 py-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                @foreach($applications as $app)
                                    @php
                                        $profile = $app->faculty?->facultyProfile;
                                        $rankLabel = $profile?->rankLevel?->title ?: ($profile?->teaching_rank ?? '—');
                                        $isHrUser = strtolower((string) auth()->user()->role) === 'hr';
                                        $canReview = $isHrUser && $app->status === 'hr_review';
                                        $canToggleReject = $isHrUser;
                                        $statusClass = $app->status === 'rejected_final'
                                            ? 'bg-red-50 text-red-700 border-red-200'
                                            : 'bg-blue-50 text-blue-700 border-blue-200';
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
                                            default => ucfirst(str_replace('_',' ', (string) $app->status)),
                                        };
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</div>
                                            <div class="text-xs text-gray-500">{{ $profile?->employee_no ?? '—' }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            {{ $app->faculty?->department?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $rankLabel }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $app->cycle_year ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border {{ $statusClass }}">
                                                {{ $stageLabel }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            {{ optional($app->submitted_at)->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            @if($canReview || $canToggleReject)
                                                <div class="inline-flex items-center gap-2">
                                                    @if($canReview)
                                                        <a href="{{ route('reclassification.review.show', $app) }}"
                                                           class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold text-bu hover:bg-blue-50">
                                                            Open
                                                        </a>
                                                    @endif

                                                    <div class="relative inline-block text-left"
                                                         x-data="{ open: false }"
                                                         @click.away="open = false"
                                                         @keydown.escape.window="open = false">
                                                        <button type="button"
                                                                @click="open = !open"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                                                                aria-label="More actions">
                                                            &#8942;
                                                        </button>

                                                        <div x-show="open"
                                                             x-cloak
                                                             x-transition
                                                             class="absolute right-0 top-full z-50 mt-2 w-48 rounded-xl border border-gray-300 bg-white shadow-xl">
                                                            <div class="absolute -top-2 right-3 h-3 w-3 rotate-45 border-l border-t border-gray-300 bg-white"></div>

                                                            <div class="p-1">
                                                                @if($canToggleReject)
                                                                    @if($app->status === 'rejected_final')
                                                                        <form method="POST"
                                                                              action="{{ route('reclassification.admin.submissions.toggle-reject', $app) }}"
                                                                              data-async-action
                                                                              data-async-refresh-target="#admin-submissions-results"
                                                                              data-loading-message="Setting submission active..."
                                                                              data-loading-text="Setting active..."
                                                                              data-success-message="Submission set to active."
                                                                              onsubmit="return confirm('Set this submission back to active?');">
                                                                            @csrf
                                                                            <button type="submit"
                                                                                    class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium text-green-700 hover:bg-green-50">
                                                                                Set Active
                                                                            </button>
                                                                        </form>
                                                                    @else
                                                                        <form method="POST"
                                                                              action="{{ route('reclassification.admin.submissions.toggle-reject', $app) }}"
                                                                              data-async-action
                                                                              data-async-refresh-target="#admin-submissions-results"
                                                                              data-loading-message="Rejecting submission..."
                                                                              data-loading-text="Rejecting..."
                                                                              data-success-message="Submission marked as rejected."
                                                                              onsubmit="return confirm('Reject this submission?');">
                                                                            @csrf
                                                                            <button type="submit"
                                                                                    class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50">
                                                                                Reject
                                                                            </button>
                                                                        </form>
                                                                    @endif

                                                                    <form method="POST"
                                                                          action="{{ route('reclassification.admin.submissions.destroy', $app) }}"
                                                                          data-async-action
                                                                          data-async-refresh-target="#admin-submissions-results"
                                                                          data-loading-message="Deleting submission..."
                                                                          data-loading-text="Deleting..."
                                                                          data-success-message="Submission deleted."
                                                                          onsubmit="return confirm('Delete this submission and all related records? This cannot be undone.');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                                class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium text-red-800 hover:bg-red-100">
                                                                            Delete
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div>
                    {{ $applications->links() }}
                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('admin-submissions-filter-form');
            if (!form) return;

            const resetButton = form.querySelector('[data-reset-admin-filters]');
            const status = form.querySelector('select[name="status"]');
            const department = form.querySelector('select[name="department_id"]');
            const rank = form.querySelector('select[name="rank_level_id"]');
            if (!resetButton || !status || !rank) return;

            const syncResetVisibility = () => {
                const hasFilters = status.value !== 'all'
                    || !!(department && department.value !== '')
                    || rank.value !== '';
                resetButton.classList.toggle('hidden', !hasFilters);
            };

            status.addEventListener('change', syncResetVisibility);
            if (department) department.addEventListener('change', syncResetVisibility);
            rank.addEventListener('change', syncResetVisibility);
            syncResetVisibility();
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-reset-admin-filters]');
            if (!trigger) return;

            const form = document.getElementById('admin-submissions-filter-form');
            if (!form) return;

            const status = form.querySelector('select[name="status"]');
            const department = form.querySelector('select[name="department_id"]');
            const rank = form.querySelector('select[name="rank_level_id"]');

            if (status) status.value = 'all';
            if (department) department.value = '';
            if (rank) rank.value = '';
            trigger.classList.add('hidden');

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    </script>

    @include('reclassification.partials.async-actions')
</x-app-layout>
