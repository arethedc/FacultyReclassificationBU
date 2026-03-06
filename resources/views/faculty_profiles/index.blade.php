<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-800">Manage Faculties</h2>
                <p class="text-sm text-gray-500">Faculty master records and reclassification history access.</p>
            </div>

            @if($canManageFaculty && $createFacultyRoute)
                <a href="{{ $createFacultyRoute }}"
                   class="h-11 px-6 rounded-xl bg-bu text-white text-sm font-semibold hover:bg-bu-dark shadow-soft transition inline-flex items-center justify-center whitespace-nowrap self-start sm:self-auto">
                    + Create Faculty
                </a>
            @endif
        </div>
    </x-slot>

    @php
        $q = request('q');
        $status = request('status', 'active');
        $departmentId = request('department_id');
        $rankLevelId = request('rank_level_id');
        $hasManageFacultyFilters = $status !== 'active'
            || ($departmentId !== null && $departmentId !== '')
            || ($rankLevelId !== null && $rankLevelId !== '');
    @endphp

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <form id="manage-faculty-filter-form"
                  method="GET"
                  action="{{ $indexRoute }}"
                  data-auto-submit
                  data-auto-submit-delay="450"
                  data-auto-submit-ajax="true"
                  data-auto-submit-target="#faculty-results"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 items-end">
                    <div class="w-full lg:w-[70%]">
                        <label class="block text-xs font-semibold text-gray-600">Search</label>
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Search name, email, or employee no."
                               class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-bu focus:ring-bu">
                    </div>
                </div>

                <div class="flex flex-col gap-4 md:flex-row md:items-end">
                    <div class="w-full md:w-56">
                        <label class="block text-xs font-semibold text-gray-600">Account State</label>
                        <select name="status"
                                class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-bu focus:ring-bu">
                            <option value="active" @selected($status === 'active')>Active</option>
                            <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                            <option value="all" @selected($status === 'all')>All</option>
                        </select>
                    </div>

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
                                data-reset-manage-faculty-filters
                                class="h-11 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 {{ $hasManageFacultyFilters ? '' : 'hidden' }}">
                            Reset Filters
                        </button>
                    </div>
                </div>
            </form>

            <div id="faculty-results" data-ux-panel data-ux-initial-panel aria-busy="false" class="space-y-4">
                <div data-ux-panel-skeleton class="hidden">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                        <x-ui.skeleton-table :rows="8" :cols="5" />
                    </div>
                </div>

                <div data-ux-panel-content class="space-y-4">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-visible">
                        <div class="overflow-x-auto md:overflow-visible">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Name</th>
                                        <th class="px-6 py-3 text-left">Department</th>
                                        <th class="px-6 py-3 text-left">Rank</th>
                                        <th class="px-6 py-3 text-left">Employment</th>
                                        <th class="px-6 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse($faculty as $f)
                                        @php
                                            $rankLabel = $f->facultyProfile?->rankLevel?->title ?: ($f->facultyProfile?->teaching_rank ?? '-');
                                        @endphp
                                        <tr class="transition-colors hover:bg-gray-50/80">
                                            <td class="px-6 py-4 align-top font-medium text-gray-800">
                                                <div class="flex items-center gap-2">
                                                    <span>{{ $f->name }}</span>
                                                    @if($status === 'all')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $f->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                                            {{ ucfirst((string) $f->status) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">Employee No. {{ $f->facultyProfile?->employee_no ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 align-top text-gray-700">{{ $f->department?->name ?? '-' }}</td>
                                            <td class="px-6 py-4 align-top text-gray-700">{{ $rankLabel }}</td>
                                            <td class="px-6 py-4 align-top text-gray-700">{{ ucfirst(str_replace('_', ' ', $f->facultyProfile?->employment_type ?? '-')) }}</td>
                                            <td class="px-6 py-4 align-top text-right">
                                                @if($canManageFaculty)
                                                    <a href="{{ route('users.edit', ['user' => $f, 'context' => 'faculty']) }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                        Edit Profile
                                                    </a>
                                                @else
                                                    <span class="text-xs text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                                                <x-ui.state-empty title="No faculty records found" message="Try adjusting your search or filters." />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        {{ $faculty->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('manage-faculty-filter-form');
            if (!form) return;

            const resetButton = form.querySelector('[data-reset-manage-faculty-filters]');
            const status = form.querySelector('select[name="status"]');
            const department = form.querySelector('select[name="department_id"]');
            const rank = form.querySelector('select[name="rank_level_id"]');
            if (!resetButton || !status || !rank) return;

            const syncResetVisibility = () => {
                const hasFilters = status.value !== 'active'
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
            const trigger = event.target.closest('[data-reset-manage-faculty-filters]');
            if (!trigger) return;

            const form = document.getElementById('manage-faculty-filter-form');
            if (!form) return;

            const status = form.querySelector('select[name="status"]');
            const department = form.querySelector('select[name="department_id"]');
            const rank = form.querySelector('select[name="rank_level_id"]');

            if (status) status.value = 'active';
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
</x-app-layout>
