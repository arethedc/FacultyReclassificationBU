<x-app-layout>
    @php
        $role = auth()->user()->role ?? 'dean';
        $roleLabel = match($role) {
            'dean' => 'Dean',
            'hr' => 'HR',
            'vpaa' => 'VPAA',
            'president' => 'President',
            default => 'Reviewer',
        };
        $isHr = strtolower((string) $role) === 'hr';
        $queueTab = $queueTab ?? 'all';
        $allCount = (int) ($allCount ?? (isset($applications) ? $applications->count() : 0));
        $returnRequestCount = (int) ($returnRequestCount ?? 0);
        $tabRouteName = strtolower((string) $role) === 'dean'
            ? 'reclassification.dean.review'
            : 'reclassification.review.queue';
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">{{ $roleLabel }} Queue</h2>
            <p class="text-sm text-gray-500">Applications awaiting {{ strtolower($roleLabel) }} stage action.</p>
        </div>
    </x-slot>

    <div class="py-10 bg-bu-muted min-h-screen">
        <div id="review-queue-live"
             data-auto-refresh
             data-ux-panel
             data-ux-initial-panel
             aria-busy="false"
             data-auto-refresh-url="{{ request()->fullUrl() }}"
             data-auto-refresh-interval="8000"
             class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div data-ux-panel-skeleton class="hidden space-y-6" aria-hidden="true">
                <div class="ux-skeleton-card space-y-4">
                    <div class="ux-skeleton h-3 w-36"></div>
                    <div class="inline-flex rounded-xl border border-gray-200 p-1 gap-1">
                        <div class="ux-skeleton h-9 w-28"></div>
                        <div class="ux-skeleton h-9 w-36"></div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                    <div class="mb-4">
                        <div class="ux-skeleton h-6 w-48"></div>
                    </div>
                    <x-ui.skeleton-table :rows="8" :cols="5" />
                </div>
            </div>

            <div data-ux-panel-content class="space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if(empty($activePeriod))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No active period. Review queue is available only for the active cycle.
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs font-semibold text-gray-600">Submission State</div>
                </div>
                <div class="mt-3 inline-flex rounded-xl border border-gray-300 bg-gray-50 p-1">
                    <a href="{{ route($tabRouteName, ['tab' => 'all']) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $queueTab === 'all' ? 'bg-bu text-white shadow-soft' : 'text-gray-700 hover:bg-gray-100' }}">
                        All ({{ $allCount }})
                    </a>
                    <a href="{{ route($tabRouteName, ['tab' => 'requests']) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $queueTab === 'requests' ? 'bg-red-600 text-white shadow-soft' : 'text-gray-700 hover:bg-gray-100' }}">
                        Return Requests ({{ $returnRequestCount }})
                    </a>
                </div>
            </div>

            @if($returnRequestCount > 0)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $returnRequestCount === 1
                        ? '1 application has a faculty return request.'
                        : "{$returnRequestCount} applications have faculty return requests." }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ $queueTab === 'requests' ? 'Applications with Return Request' : 'Pending Applications' }}
                    </h3>
                </div>

                @if($applications->isEmpty())
                    <div class="p-6 text-sm text-gray-500">
                        {{ $queueTab === 'requests'
                            ? 'No return requests in this queue.'
                            : 'No applications in ' . strtolower($roleLabel) . ' queue.' }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-6 py-3 text-left">Faculty</th>
                                    <th class="px-6 py-3 text-left">Submitted</th>
                                    <th class="px-6 py-3 text-left">Cycle</th>
                                    <th class="px-6 py-3 text-left">Stage</th>
                                    <th class="px-6 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($applications as $app)
                                    @php
                                        $profile = $app->faculty?->facultyProfile;
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
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-800">{{ $app->faculty?->name ?? 'Faculty' }}</div>
                                            <div class="text-xs text-gray-500">{{ $profile?->employee_no ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            {{ optional($app->submitted_at)->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">{{ $app->cycle_year }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border bg-blue-50 text-blue-700 border-blue-200">
                                                {{ $stageLabel }}
                                            </span>
                                            @if(!is_null($app->faculty_return_requested_at ?? null))
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border bg-amber-50 text-amber-700 border-amber-200">
                                                        Return Requested
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-3">
                                                <a href="{{ route('reclassification.review.show', $app) }}"
                                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">Review</a>
                                                @if($isHr)
                                                    <form method="POST"
                                                          action="{{ route('reclassification.admin.submissions.destroy', $app) }}"
                                                          onsubmit="return confirm('Delete this reclassification and all related records? This cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="text-red-700 hover:underline font-semibold">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            </div>
        </div>
    </div>
</x-app-layout>

