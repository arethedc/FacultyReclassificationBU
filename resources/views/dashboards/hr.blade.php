<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">HR Dashboard</h2>
            <p class="text-sm text-gray-500">Overview, submissions, and staff management.</p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div id="hr-dashboard-live"
             data-auto-refresh
             data-ux-panel
             data-ux-initial-panel
             aria-busy="false"
             data-auto-refresh-url="{{ request()->fullUrl() }}"
             data-auto-refresh-interval="15000"
             class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div data-ux-panel-skeleton class="hidden space-y-6" aria-hidden="true">
                <div class="ux-skeleton-card space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-3">
                            <div class="ux-skeleton h-3 w-40"></div>
                            <div class="ux-skeleton h-7 w-64"></div>
                            <div class="ux-skeleton h-3 w-56"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="ux-skeleton h-11 w-full"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="ux-skeleton h-3 w-52"></div>
                        <div class="flex flex-wrap gap-2">
                            <div class="ux-skeleton h-8 w-32"></div>
                            <div class="ux-skeleton h-8 w-40"></div>
                            <div class="ux-skeleton h-8 w-44"></div>
                            <div class="ux-skeleton h-8 w-32"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    @for($i = 0; $i < 4; $i++)
                        <div class="ux-skeleton-metric">
                            <div class="ux-skeleton-title"></div>
                            <div class="ux-skeleton-value"></div>
                        </div>
                    @endfor
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="ux-skeleton-card lg:col-span-2 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="ux-skeleton h-6 w-44"></div>
                            <div class="ux-skeleton h-4 w-16"></div>
                        </div>
                        <x-ui.skeleton-table :rows="6" :cols="4" />
                    </div>
                    <div class="flex flex-col gap-6">
                        <div class="ux-skeleton-card space-y-3">
                            <div class="ux-skeleton h-5 w-40"></div>
                            <div class="ux-skeleton h-3 w-full"></div>
                            <div class="ux-skeleton h-11 w-full"></div>
                        </div>
                        <div class="ux-skeleton-card space-y-3">
                            <div class="ux-skeleton h-5 w-32"></div>
                            <div class="ux-skeleton h-3 w-full"></div>
                            <div class="ux-skeleton h-11 w-full"></div>
                            <div class="ux-skeleton h-11 w-full"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div data-ux-panel-content class="space-y-6">

            @php
                $currentPeriod = $activePeriod ?? $openPeriod ?? null;
                $periodStateLabel = $openPeriod?->is_open
                    ? 'Open for submissions'
                    : ($currentPeriod ? 'Closed for submissions' : 'No active period');
                $periodStateClass = $openPeriod?->is_open
                    ? 'bg-green-50 text-green-700 border-green-200'
                    : ($currentPeriod ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-gray-100 text-gray-700 border-gray-200');
            @endphp
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <div class="text-sm text-gray-500">Current Submission Period</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <div class="text-lg font-semibold text-gray-800">
                                {{ $currentPeriod?->name ?? 'No active period' }}
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border {{ $periodStateClass }}">
                                {{ $periodStateLabel }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            @if($currentPeriod?->start_at || $currentPeriod?->end_at)
                                {{ optional($currentPeriod?->start_at)->format('M d, Y') ?? '-' }}
                                to {{ optional($currentPeriod?->end_at)->format('M d, Y') ?? '-' }}
                            @else
                                No configured date range
                            @endif
                            @if(!empty($currentPeriod?->cycle_year))
                                • Cycle {{ $currentPeriod->cycle_year }}
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        @if($hasActivePeriod)
                            <a href="{{ route('reclassification.review.queue') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                                Open HR Queue
                            </a>
                        @else
                            <a href="{{ route('reclassification.periods') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft">
                                Manage Periods
                            </a>
                        @endif
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Other Reclassification Actions</div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('reclassification.admin.submissions') }}"
                           class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50">
                            All Submissions
                        </a>
                        <a href="{{ route('reclassification.admin.approved') }}"
                           class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50">
                            Approved Reclassification
                        </a>
                        <a href="{{ route('reclassification.history') }}"
                           class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50">
                            Reclassification History
                        </a>
                        <a href="{{ route('reclassification.periods') }}"
                           class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-semibold hover:bg-gray-50">
                            Manage Periods
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Pending HR</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $statusCounts['hr_review'] ?? 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Returned to Faculty</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $statusCounts['returned_to_faculty'] ?? 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Finalized</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $statusCounts['finalized'] ?? 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                    <div class="text-xs text-gray-500">Faculty Accounts</div>
                    <div class="text-2xl font-semibold text-gray-800">
                        {{ $facultyCount ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Submissions</h3>
                        <a href="{{ route('reclassification.admin.submissions') }}"
                           class="text-sm font-semibold text-bu hover:underline">
                            View all
                        </a>
                    </div>

                    @if($recentApplications->isEmpty())
                        <div class="text-sm text-gray-500">
                            {{ !empty($hasActivePeriod) ? 'No submissions yet.' : 'No active period. No recent submissions.' }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-left text-gray-500 border-b">
                                    <tr>
                                        <th class="py-2">Faculty</th>
                                        <th class="py-2">Department</th>
                                        <th class="py-2">Stage</th>
                                        <th class="py-2">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($recentApplications as $app)
                                        @php
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
                                            <td class="py-2 font-medium text-gray-800">
                                                {{ $app->faculty?->name ?? 'Faculty' }}
                                            </td>
                                            <td class="py-2 text-gray-600">
                                                {{ $app->faculty?->department?->name ?? '—' }}
                                            </td>
                                            <td class="py-2 text-gray-600">
                                                {{ $stageLabel }}
                                            </td>
                                            <td class="py-2 text-gray-600">
                                                {{ optional($app->submitted_at)->format('M d, Y') ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-1 flex flex-col gap-6">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-800">Faculty Management</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            Maintain faculty records and profile accuracy.
                        </p>
                        <div class="mt-5 grid grid-cols-1 gap-3">
                            <a href="{{ route('faculty.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl
                                      bg-bu text-white text-sm font-semibold shadow-soft">
                                Manage Faculties
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-800">User Management</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            Create accounts, manage roles, and keep faculty records accurate.
                        </p>
                        <div class="mt-5 grid grid-cols-1 gap-3">
                            <a href="{{ route('users.create') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl
                                      bg-bu text-white text-sm font-semibold shadow-soft">
                                Create User
                            </a>
                            <a href="{{ route('users.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl
                                      border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                                Manage Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            </div>
        </div>
    </div>
</x-app-layout>
