<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-800">Manage Users</h2>
                <p class="text-sm text-gray-500">Manage system user records.</p>
            </div>
            <a href="{{ route('users.create') }}"
               class="h-11 px-6 rounded-xl bg-bu text-white text-sm font-semibold hover:bg-bu-dark shadow-soft transition inline-flex items-center justify-center whitespace-nowrap self-start sm:self-auto">
                + Create User
            </a>
        </div>
    </x-slot>

    @php
        $q = request('q');
        $status = request('status', 'active');
        $role = $role ?? request('role', '');
        $hasUserFilters = $status !== 'active' || $role !== '';
        $showDeanDepartmentColumn = $role === 'dean';
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

            <form id="users-filter-form"
                  method="GET"
                  action="{{ route('users.index') }}"
                  data-auto-submit
                  data-auto-submit-delay="450"
                  data-auto-submit-ajax="true"
                  data-auto-submit-target="#users-results"
                  class="bg-white rounded-2xl shadow-card border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 items-end">
                    <div class="w-full lg:w-[70%]">
                        <label class="block text-xs font-semibold text-gray-600">Search</label>
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Search name, email, role, or employee no."
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
                    <div class="w-full md:w-56">
                        <label class="block text-xs font-semibold text-gray-600">Role</label>
                        <select name="role"
                                class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-bu focus:ring-bu">
                            <option value="" @selected($role === '')>All Roles</option>
                            <option value="faculty" @selected($role === 'faculty')>Faculty</option>
                            <option value="dean" @selected($role === 'dean')>Dean</option>
                            <option value="hr" @selected($role === 'hr')>HR</option>
                            <option value="vpaa" @selected($role === 'vpaa')>VPAA</option>
                            <option value="president" @selected($role === 'president')>President</option>
                        </select>
                    </div>
                    <div class="flex items-end justify-end md:ml-auto">
                        <button type="button"
                                data-reset-users-filters
                                class="h-11 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 {{ $hasUserFilters ? '' : 'hidden' }}">
                            Reset Filters
                        </button>
                    </div>
                </div>
            </form>

            <div id="users-results" data-ux-panel data-ux-initial-panel aria-busy="false" class="space-y-4">
                <div data-ux-panel-skeleton class="hidden">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 p-4">
                        <x-ui.skeleton-table :rows="8" :cols="$showDeanDepartmentColumn ? 5 : 4" />
                    </div>
                </div>

                <div data-ux-panel-content class="space-y-4">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-visible">
                        <div class="overflow-x-auto md:overflow-visible">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Name</th>
                                        <th class="px-6 py-3 text-left">Email</th>
                                        @if($showDeanDepartmentColumn)
                                            <th class="px-6 py-3 text-left">Department</th>
                                        @endif
                                        <th class="px-6 py-3 text-left">Role</th>
                                        <th class="px-6 py-3 text-right">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y">
                                    @forelse($users as $user)
                                        @php
                                            $roleKey = strtolower((string) $user->role);
                                            $roleLabel = ucfirst(str_replace('_', ' ', (string) $user->role));
                                            $roleBadgeClass = match($roleKey) {
                                                'faculty' => 'bg-sky-50 text-sky-700 border-sky-200',
                                                'dean' => 'bg-violet-50 text-violet-700 border-violet-200',
                                                'hr' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'vpaa' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'president' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                default => 'bg-gray-50 text-gray-700 border-gray-200',
                                            };
                                            $metaLabel = $user->facultyProfile?->employee_no
                                                ? 'Employee No. ' . $user->facultyProfile->employee_no
                                                : 'User ID #' . $user->id;
                                        @endphp
                                        <tr class="transition-colors hover:bg-gray-50/80">
                                            <td class="px-6 py-4 align-top">
                                                <div class="flex items-start gap-2">
                                                    <a href="{{ route('users.edit', ['user' => $user, 'context' => 'users']) }}"
                                                       class="font-semibold text-gray-900 hover:text-bu hover:underline underline-offset-2">
                                                        {{ $user->name }}
                                                    </a>
                                                    @if($status === 'all')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                                                            {{ ucfirst($user->status) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">{{ $metaLabel }}</div>
                                            </td>

                                            <td class="px-6 py-4 align-top text-gray-700">{{ $user->email }}</td>
                                            @if($showDeanDepartmentColumn)
                                                <td class="px-6 py-4 align-top text-gray-700">{{ $user->department?->name ?? '-' }}</td>
                                            @endif
                                            <td class="px-6 py-4 align-top">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $roleBadgeClass }}">
                                                    {{ $roleLabel }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 text-right align-top">
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
                                                            <a href="{{ route('users.edit', ['user' => $user, 'context' => 'users']) }}"
                                                               class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                                Edit Profile
                                                            </a>

                                                            <div class="my-1 border-t border-gray-200"></div>

                                                            <form method="POST"
                                                                  action="{{ route('users.destroy', $user) }}"
                                                                  data-user-delete-form
                                                                  data-no-progress="true"
                                                                  data-confirm="Delete this user? This cannot be undone.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50">
                                                                    Delete User
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $showDeanDepartmentColumn ? 5 : 4 }}" class="px-6 py-6 text-center text-gray-500">
                                                <x-ui.state-empty title="No users found" message="Try adjusting your search or filters." />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('users-filter-form');
            if (!form) return;

            const resetButton = form.querySelector('[data-reset-users-filters]');
            const status = form.querySelector('select[name="status"]');
            const role = form.querySelector('select[name="role"]');
            if (!resetButton || !status || !role) return;

            const syncResetVisibility = () => {
                const hasFilters = status.value !== 'active' || role.value !== '';
                resetButton.classList.toggle('hidden', !hasFilters);
            };

            status.addEventListener('change', syncResetVisibility);
            role.addEventListener('change', syncResetVisibility);
            syncResetVisibility();
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-reset-users-filters]');
            if (!trigger) return;

            const form = document.getElementById('users-filter-form');
            if (!form) return;

            const status = form.querySelector('select[name="status"]');
            const role = form.querySelector('select[name="role"]');
            if (status) status.value = 'active';
            if (role) role.value = '';
            trigger.classList.add('hidden');

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });

        document.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (!form.matches('[data-user-delete-form]')) return;

            event.preventDefault();

            if (form.dataset.asyncBusy === '1') return;

            const confirmMessage = form.dataset.confirm || 'Delete this user? This cannot be undone.';
            if (!window.confirm(confirmMessage)) return;

            const submitButton = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : form.querySelector('button[type="submit"]');
            if (!submitButton) return;

            const panelSelector = '#users-results';
            const refreshResultsPanel = async () => {
                const response = await fetch(window.location.href, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-UX-Background': '1',
                    },
                });

                if (!response.ok) {
                    throw new Error('Could not refresh the users list.');
                }

                const html = await response.text();
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const incoming = parsed.querySelector(panelSelector);
                const current = document.querySelector(panelSelector);

                if (!incoming || !current) {
                    throw new Error('Could not refresh the users list.');
                }

                current.replaceWith(incoming);
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(incoming);
                }
                window.BuUx?.bindSubmitFeedback?.(document);
                window.BuUx?.bindActionLoading?.(document);
            };

            form.dataset.asyncBusy = '1';
            window.BuUx?.setActionButtonLoading?.(submitButton, 'Deleting...');
            window.BuUx?.panel?.setRefreshing(panelSelector, true, 'Refreshing users list...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });

                let payload = {};
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = {};
                }

                if (!response.ok) {
                    throw new Error(payload.message || 'Could not delete user.');
                }

                await refreshResultsPanel();
                window.BuUx?.toast?.(payload.message || 'User deleted.', 'success');
            } catch (error) {
                window.BuUx?.toast?.(error?.message || 'Could not delete user.', 'error');
            } finally {
                form.dataset.asyncBusy = '0';
                window.BuUx?.panel?.setRefreshing(panelSelector, false);
                window.BuUx?.resetActionButton?.(submitButton);
            }
        });
    </script>
</x-app-layout>
