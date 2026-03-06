<x-app-layout>
    <x-slot name="header">
        <div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Department Submissions</h2>
                <p class="text-sm text-gray-500">
                    Active-period submissions in your department.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div id="dean-submissions-live"
             data-auto-refresh
             data-auto-refresh-url="{{ request()->fullUrl() }}"
             data-auto-refresh-interval="10000"
             class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(!$hasActivePeriod)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl shadow-card p-6">
                    <div class="text-sm font-semibold text-amber-900">No active period</div>
                    <div class="mt-1 text-sm text-amber-800">
                        Submissions are only shown for the active period. Past submissions are available in Reclassification History.
                    </div>
                </div>
            @endif

            @php
                $hasDeanFilters = $status !== 'all'
                    || ($rankLevelId !== null && $rankLevelId !== '');
            @endphp

            <form id="dean-submissions-filter-form"
                  method="GET" action="{{ route('dean.submissions') }}"
                  data-auto-submit
                  data-auto-submit-delay="450"
                  data-auto-submit-ajax="true"
                  data-auto-submit-target="#dean-submissions-results"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                <div class="w-full md:w-[70%]">
                    <label class="block text-xs font-semibold text-gray-600">Search</label>
                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Faculty name, email, or employee no."
                           class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
                </div>
                <div class="flex flex-col gap-4 md:flex-row md:items-end">
                    <div class="w-full md:w-56">
                        <label class="block text-xs font-semibold text-gray-600">Stage</label>
                        <select name="status"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
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
                        <label class="block text-xs font-semibold text-gray-600">Rank</label>
                        <select name="rank_level_id"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">
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
                                data-reset-dean-filters
                                class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 {{ $hasDeanFilters ? '' : 'hidden' }}">
                            Reset Filters
                        </button>
                    </div>
                </div>
            </form>

            <div id="dean-submissions-results" data-ux-panel data-ux-initial-panel aria-busy="false" class="space-y-4">
                <div data-ux-panel-skeleton class="hidden">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                        <x-ui.skeleton-table :rows="8" :cols="6" />
                    </div>
                </div>
                <div data-ux-panel-content class="space-y-4">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Submissions</h3>
                    </div>

                    @if($applications->isEmpty())
                        <div class="p-6">
                            <x-ui.state-empty
                                :title="$hasActivePeriod ? 'No submissions match your filters.' : 'No active period submissions'"
                                :message="$hasActivePeriod ? 'Try adjusting filters or search keywords.' : 'Submissions are visible only while a period is active.'"
                            />
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Faculty</th>
                                        <th class="px-6 py-3 text-left">Rank</th>
                                        <th class="px-6 py-3 text-left">Cycle</th>
                                        <th class="px-6 py-3 text-left">Stage</th>
                                        <th class="px-6 py-3 text-left">Submitted</th>
                                        <th class="px-6 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($applications as $app)
                                        @php
                                            $profile = $app->faculty?->facultyProfile;
                                            $rankLabel = $profile?->rankLevel?->title ?: ($profile?->teaching_rank ?? '--');
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
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</div>
                                                <div class="text-xs text-gray-500">{{ $profile?->employee_no ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">{{ $rankLabel }}</td>
                                            <td class="px-6 py-4 text-gray-600">{{ $app->cycle_year ?? '--' }}</td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border bg-blue-50 text-blue-700 border-blue-200">
                                                    {{ $stageLabel }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                {{ optional($app->submitted_at)->format('M d, Y') ?? '--' }}
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                @if($app->status === 'dean_review')
                                                    <a href="{{ route('reclassification.review.show', $app) }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                        Review
                                                    </a>
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
            const form = document.getElementById('dean-submissions-filter-form');
            if (!form) return;

            const resetButton = form.querySelector('[data-reset-dean-filters]');
            const status = form.querySelector('select[name="status"]');
            const rank = form.querySelector('select[name="rank_level_id"]');
            if (!resetButton || !status || !rank) return;

            const syncResetVisibility = () => {
                const hasFilters = status.value !== 'all' || rank.value !== '';
                resetButton.classList.toggle('hidden', !hasFilters);
            };

            status.addEventListener('change', syncResetVisibility);
            rank.addEventListener('change', syncResetVisibility);
            syncResetVisibility();
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-reset-dean-filters]');
            if (!trigger) return;

            const form = trigger.closest('form');
            if (!form) return;

            const status = form.querySelector('select[name="status"]');
            const rank = form.querySelector('select[name="rank_level_id"]');

            if (status) status.value = 'all';
            if (rank) rank.value = '';
            trigger.classList.add('hidden');

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    </script>
</x-app-layout>
