<x-app-layout>
    <x-slot name="header">
        <div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">{{ $title }}</h2>
                <p class="text-sm text-gray-500">{{ $subtitle }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form id="history-filter-form"
                  method="GET"
                  action="{{ $indexRoute }}"
                  data-auto-submit
                  data-auto-submit-delay="450"
                  data-auto-submit-ajax="true"
                  data-auto-submit-target="#history-results"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 items-end">
                    <div class="w-full lg:w-[70%]">
                        <label class="block text-xs font-semibold text-gray-600">Search Period</label>
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Search by period name or cycle"
                               class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-bu focus:ring-bu">
                    </div>
                </div>

            </form>

            <div id="history-results" data-ux-panel data-ux-initial-panel aria-busy="false" class="space-y-4">
                <div data-ux-panel-skeleton class="hidden">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @for($i = 0; $i < 3; $i++)
                                <div class="ux-skeleton-metric">
                                    <div class="ux-skeleton-title"></div>
                                    <div class="ux-skeleton-value"></div>
                                </div>
                            @endfor
                        </div>
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                            <div class="mb-4 space-y-2">
                                <div class="ux-skeleton h-6 w-40"></div>
                                <div class="ux-skeleton h-4 w-56"></div>
                            </div>
                            <x-ui.skeleton-table :rows="8" :cols="7" />
                        </div>
                    </div>
                </div>

                <div data-ux-panel-content class="space-y-4">
                    @php
                        $totalPeriods = (int) $periods->count();
                        $totalApproved = (int) $periods->sum('approved_count');
                        $endedCount = (int) $periods->count();
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                            <div class="text-xs text-gray-500">Total Periods</div>
                            <div class="text-2xl font-semibold text-gray-800">{{ $totalPeriods }}</div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                            <div class="text-xs text-gray-500">Total Approved Promotions</div>
                            <div class="text-2xl font-semibold text-gray-800">{{ $totalApproved }}</div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-5">
                            <div class="text-xs text-gray-500">Ended Periods</div>
                            <div class="text-2xl font-semibold text-gray-800">{{ $endedCount }}</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-x-auto">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Period History</h3>
                            <p class="text-sm text-gray-500">Only ended periods are listed here.</p>
                        </div>

                        @if($periods->isEmpty())
                            <div class="p-6">
                                <x-ui.state-empty title="No periods found" message="Try adjusting your filters or create a period first." />
                            </div>
                        @else
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Name</th>
                                        <th class="px-6 py-3 text-left">Cycle</th>
                                        <th class="px-6 py-3 text-left">Stage</th>
                                        <th class="px-6 py-3 text-left">Submissions</th>
                                        <th class="px-6 py-3 text-left">Approved</th>
                                        <th class="px-6 py-3 text-left">Created</th>
                                        <th class="px-6 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($periods as $period)
                                        @php
                                            $periodStatus = (string) ($period->status ?? ($period->is_open ? 'active' : 'ended'));
                                            $periodStatusClass = $periodStatus === 'active'
                                                ? 'bg-green-50 text-green-700 border-green-200'
                                                : ($periodStatus === 'draft'
                                                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                    : 'bg-gray-50 text-gray-600 border-gray-200');
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-800">{{ $period->name }}</td>
                                            <td class="px-6 py-4 text-gray-700">{{ $period->cycle_year ?? '-' }}</td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border {{ $periodStatusClass }}">
                                                    {{ ucfirst($periodStatus) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-gray-700">{{ (int) ($period->submission_count ?? 0) }}</td>
                                            <td class="px-6 py-4 text-gray-700 font-semibold">{{ (int) ($period->approved_count ?? 0) }}</td>
                                            <td class="px-6 py-4 text-gray-700">{{ optional($period->created_at)->format('M d, Y') ?? '-' }}</td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="{{ route('reclassification.history.period', ['period' => $period]) }}"
                                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                    View List
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
