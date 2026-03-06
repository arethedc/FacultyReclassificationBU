<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">Faculty Records</h2>
            <p class="text-sm text-gray-500">Previous reclassification submissions and outcomes.</p>
        </div>
    </x-slot>

    @php
        $rankLabel = $user->facultyProfile?->rankLevel?->title
            ?: ($user->facultyProfile?->teaching_rank ?? '-');
    @endphp

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             data-ux-panel
             data-ux-initial-panel
             aria-busy="false">
            <div data-ux-panel-skeleton class="hidden space-y-6" aria-hidden="true">
                <div class="ux-skeleton-card space-y-3">
                    <div class="ux-skeleton h-6 w-56"></div>
                    <div class="ux-skeleton h-4 w-48"></div>
                    <div class="flex flex-wrap gap-2">
                        <div class="ux-skeleton h-6 w-32 rounded-full"></div>
                        <div class="ux-skeleton h-6 w-24 rounded-full"></div>
                        <div class="ux-skeleton h-6 w-36 rounded-full"></div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                    <x-ui.skeleton-table :rows="8" :cols="6" />
                </div>
            </div>

            <div data-ux-panel-content class="space-y-6">
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-gray-100 border border-gray-200">
                                {{ $user->department?->name ?? 'No Department' }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-gray-100 border border-gray-200">
                                {{ $rankLabel }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-gray-100 border border-gray-200">
                                Employee No: {{ $user->facultyProfile?->employee_no ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ $indexRoute }}"
                       class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        Back to Faculty Records
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3 text-left">Period</th>
                            <th class="px-6 py-3 text-left">Cycle</th>
                            <th class="px-6 py-3 text-left">Stage</th>
                            <th class="px-6 py-3 text-left">Submitted</th>
                            <th class="px-6 py-3 text-left">Finalized</th>
                            <th class="px-6 py-3 text-left">Approved Rank</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($applications as $application)
                            @php
                                $stageLabel = match((string) ($application->status ?? '')) {
                                    'dean_review' => 'Dean',
                                    'hr_review' => 'HR',
                                    'vpaa_review' => 'VPAA',
                                    'vpaa_approved' => 'VPAA Approved',
                                    'president_review' => 'President',
                                    'returned_to_faculty' => 'Returned',
                                    'finalized' => 'Finalized',
                                    'rejected_final' => 'Rejected',
                                    default => ucfirst(str_replace('_', ' ', (string) ($application->status ?? '-'))),
                                };
                                $periodLabel = trim((string) ($application->period?->name ?? ''));
                                if ($periodLabel === '') {
                                    $periodLabel = 'Not set';
                                }
                            @endphp
                            <tr>
                                <td class="px-6 py-4 text-gray-700">{{ $periodLabel }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $application->cycle_year ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ $stageLabel }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ optional($application->submitted_at)->format('M d, Y g:i A') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ optional($application->finalized_at)->format('M d, Y g:i A') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ $application->approved_rank_label ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-500">
                                    No previous reclassification submissions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $applications->links() }}
            </div>
            </div>
        </div>
    </div>
</x-app-layout>
