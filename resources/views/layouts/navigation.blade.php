<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php
        $dashboardRoute = match(Auth::user()->role) {
            'faculty'   => route('faculty.dashboard'),
            'dean'      => route('dean.dashboard'),
            'hr'        => route('hr.dashboard'),
            'vpaa'      => route('vpaa.dashboard'),
            'president' => route('president.dashboard'),
            default     => route('dashboard'),
        };
    @endphp

    {{-- Top accent bar (BU theme) --}}
    <div class="h-1 bg-bu"></div>

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- LEFT: Logo + Links --}}
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $dashboardRoute }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                        <span class="hidden md:inline text-sm font-semibold text-gray-700 tracking-wide">
                            Faculty Reclassification
                        </span>
                    </a>
                </div>

                <!-- Desktop Links -->
                <div class="hidden sm:flex sm:items-center sm:gap-2">
                    <x-nav-link :href="$dashboardRoute" :active="request()->url() === $dashboardRoute">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    {{-- HR-only shortcuts --}}
                    @if(Auth::user()->role === 'hr')
                        <div class="hidden lg:flex items-center gap-2 ml-4 pl-4 border-l border-gray-200">
                            <div class="relative"
                                 x-data="{ open: false }"
                                 @mouseenter="open = true"
                                 @mouseleave="open = false">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl
                                               text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50
                                               border border-transparent transition">
                                    Reclassification
                                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition
                                     class="absolute left-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg z-50">
                                    <div class="py-2">
                                        <a href="{{ route('reclassification.review.queue') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.queue') ? 'font-semibold text-gray-900' : '' }}">
                                            HR Queue
                                        </a>
                                        <a href="{{ route('reclassification.admin.submissions') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.admin.submissions') ? 'font-semibold text-gray-900' : '' }}">
                                            All Submissions
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.admin.approved') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.admin.approved') ? 'font-semibold text-gray-900' : '' }}">
                                            Approved Reclassification
                                        </a>
                                        <a href="{{ route('reclassification.history') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.history') ? 'font-semibold text-gray-900' : '' }}">
                                            Reclassification History
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.periods') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.periods') ? 'font-semibold text-gray-900' : '' }}">
                                            Manage Periods
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.faculty-records') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records') ? 'font-semibold text-gray-900' : '' }}">
                                            Faculty Records
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="relative"
                                 x-data="{ open: false }"
                                 @mouseenter="open = true"
                                 @mouseleave="open = false">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl
                                               text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50
                                               border border-transparent transition">
                                    User Management
                                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition
                                     class="absolute left-0 mt-2 w-52 rounded-xl border border-gray-200 bg-white shadow-lg z-50">
                                    <div class="py-2">
                                        <a href="{{ route('users.index') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('users.index') ? 'font-semibold text-gray-900' : '' }}">
                                            Manage Users
                                        </a>
                                        <a href="{{ route('users.create') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('users.create') ? 'font-semibold text-gray-900' : '' }}">
                                            Create User
                                        </a>
                                        @if(Route::has('faculty.index'))
                                            <a href="{{ route('faculty.index') }}"
                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('faculty.index') || request()->routeIs('faculty.records') ? 'font-semibold text-gray-900' : '' }}">
                                                Manage Faculties
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(Auth::user()->role === 'dean')
                        <div class="hidden lg:flex items-center gap-2 ml-4 pl-4 border-l border-gray-200">
                            <div class="relative"
                                 x-data="{ open: false }"
                                 @mouseenter="open = true"
                                 @mouseleave="open = false">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl
                                               text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50
                                               border border-transparent transition">
                                    Reclassification
                                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition
                                     class="absolute left-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg z-50">
                                    <div class="py-2">
                                        <a href="{{ route('reclassification.dean.review') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.dean.review') ? 'font-semibold text-gray-900' : '' }}">
                                            Dean Queue
                                        </a>
                                        <a href="{{ route('dean.submissions') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('dean.submissions') ? 'font-semibold text-gray-900' : '' }}">
                                            Department Submissions
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('dean.approved') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('dean.approved') ? 'font-semibold text-gray-900' : '' }}">
                                            Approved List
                                        </a>
                                        <a href="{{ route('reclassification.history') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.history') ? 'font-semibold text-gray-900' : '' }}">
                                            Reclassification History
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.faculty-records') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records') ? 'font-semibold text-gray-900' : '' }}">
                                            Faculty Records
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="relative"
                                 x-data="{ open: false }"
                                 @mouseenter="open = true"
                                 @mouseleave="open = false">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl
                                               text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50
                                               border border-transparent transition">
                                    Faculty Management
                                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition
                                     class="absolute left-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg z-50">
                                    <div class="py-2">
                                        <a href="{{ route('dean.faculty.index') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('dean.faculty.index') || request()->routeIs('faculty.records') ? 'font-semibold text-gray-900' : '' }}">
                                            Manage Faculties
                                        </a>
                                        <a href="{{ route('dean.users.create') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('dean.users.create') ? 'font-semibold text-gray-900' : '' }}">
                                            Create Faculty
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(Auth::user()->role === 'vpaa')
                        <div class="hidden lg:flex items-center gap-2 ml-4 pl-4 border-l border-gray-200">
                            <div class="relative"
                                 x-data="{ open: false }"
                                 @mouseenter="open = true"
                                 @mouseleave="open = false">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl
                                               text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50
                                               border border-transparent transition">
                                    Reclassification
                                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition
                                     class="absolute left-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg z-50">
                                    <div class="py-2">
                                        <a href="{{ route('reclassification.review.queue') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.queue') ? 'font-semibold text-gray-900' : '' }}">
                                            VPAA Queue
                                        </a>
                                        <a href="{{ route('reclassification.review.submissions') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.submissions') ? 'font-semibold text-gray-900' : '' }}">
                                            All Submissions
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.review.approved') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.approved') ? 'font-semibold text-gray-900' : '' }}">
                                            VPAA Endorsement List
                                        </a>
                                        <a href="{{ route('reclassification.review.finalized') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.finalized') ? 'font-semibold text-gray-900' : '' }}">
                                            Approved Reclassification
                                        </a>
                                        <a href="{{ route('reclassification.history') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.history') ? 'font-semibold text-gray-900' : '' }}">
                                            Reclassification History
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.faculty-records') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records') ? 'font-semibold text-gray-900' : '' }}">
                                            Faculty Records
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(Auth::user()->role === 'president')
                        <div class="hidden lg:flex items-center gap-2 ml-4 pl-4 border-l border-gray-200">
                            <div class="relative"
                                 x-data="{ open: false }"
                                 @mouseenter="open = true"
                                 @mouseleave="open = false">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl
                                               text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50
                                               border border-transparent transition">
                                    Reclassification
                                    <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition
                                     class="absolute left-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg z-50">
                                    <div class="py-2">
                                        <a href="{{ route('reclassification.review.submissions') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.submissions') ? 'font-semibold text-gray-900' : '' }}">
                                            All Submissions
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.review.approved') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.approved') ? 'font-semibold text-gray-900' : '' }}">
                                            President Approval List
                                        </a>
                                        <a href="{{ route('reclassification.review.finalized') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.review.finalized') ? 'font-semibold text-gray-900' : '' }}">
                                            Approved Reclassification
                                        </a>
                                        <a href="{{ route('reclassification.history') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.history') ? 'font-semibold text-gray-900' : '' }}">
                                            Reclassification History
                                        </a>
                                        <div class="my-1 border-t border-gray-200"></div>
                                        <a href="{{ route('reclassification.faculty-records') }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records') ? 'font-semibold text-gray-900' : '' }}">
                                            Faculty Records
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT: User Dropdown + Mobile Hamburger --}}
            <div class="flex items-center gap-3">

                <!-- Settings Dropdown (Desktop) -->
                <div class="hidden sm:flex sm:items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl
                                       border border-gray-200 bg-white
                                       text-sm font-medium text-gray-700
                                       hover:bg-gray-50 hover:text-gray-900
                                       focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-2
                                       transition">
                                {{-- Initial badge --}}
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-bu-light text-bu font-bold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>

                                <span class="hidden md:inline max-w-[180px] truncate">
                                    {{ Auth::user()->name }}
                                </span>

                                <svg class="fill-current h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <div class="text-sm font-semibold text-gray-800 truncate">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
                                <div class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                    Role: {{ ucfirst(str_replace('_',' ', Auth::user()->role)) }}
                                </div>
                            </div>

                            {{-- HR quick links inside dropdown (optional but useful) --}}
                            @if(Auth::user()->role === 'hr')
                                <div class="py-2 border-b border-gray-100">
                                    <x-dropdown-link :href="route('users.index')">
                                        {{ __('Users') }}
                                    </x-dropdown-link>

                                    <x-dropdown-link :href="route('users.create')">
                                        {{ __('Create User') }}
                                    </x-dropdown-link>

                                    @if(Route::has('faculty.index'))
                                        <x-dropdown-link :href="route('faculty.index')">
                                            {{ __('Manage Faculties') }}
                                        </x-dropdown-link>
                                    @endif
                                    <x-dropdown-link :href="route('reclassification.faculty-records')">
                                        {{ __('Faculty Records') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.admin.approved')">
                                        {{ __('Approved Reclassification') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.history')">
                                        {{ __('Reclassification History') }}
                                    </x-dropdown-link>
                                </div>
                            @elseif(Auth::user()->role === 'dean')
                                <div class="py-2 border-b border-gray-100">
                                    <x-dropdown-link :href="route('reclassification.dean.review')">
                                        {{ __('Dean Queue') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('dean.approved')">
                                        {{ __('Approved List') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.history')">
                                        {{ __('Reclassification History') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('dean.faculty.index')">
                                        {{ __('Manage Faculties') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.faculty-records')">
                                        {{ __('Faculty Records') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('dean.users.create')">
                                        {{ __('Create Faculty') }}
                                    </x-dropdown-link>
                                </div>
                            @elseif(Auth::user()->role === 'vpaa')
                                <div class="py-2 border-b border-gray-100">
                                    <x-dropdown-link :href="route('reclassification.review.queue')">
                                        {{ __('VPAA Queue') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.review.submissions')">
                                        {{ __('All Submissions') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.review.approved')">
                                        {{ __('VPAA Endorsement List') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.review.finalized')">
                                        {{ __('Approved Reclassification') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.history')">
                                        {{ __('Reclassification History') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('faculty.index')">
                                        {{ __('Manage Faculties') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.faculty-records')">
                                        {{ __('Faculty Records') }}
                                    </x-dropdown-link>
                                </div>
                            @elseif(Auth::user()->role === 'president')
                                <div class="py-2 border-b border-gray-100">
                                    <x-dropdown-link :href="route('reclassification.review.submissions')">
                                        {{ __('All Submissions') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.review.approved')">
                                        {{ __('President Approval List') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.review.finalized')">
                                        {{ __('Approved Reclassification') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.history')">
                                        {{ __('Reclassification History') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('faculty.index')">
                                        {{ __('Manage Faculties') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('reclassification.faculty-records')">
                                        {{ __('Faculty Records') }}
                                    </x-dropdown-link>
                                </div>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link
                                    :href="route('logout')"
                                    onclick="event.preventDefault(); if (confirm('Are you sure you want to log out?')) { this.closest('form').submit(); }">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Hamburger (Mobile) -->
                <div class="sm:hidden">
                    <button @click="open = ! open"
                            class="inline-flex items-center justify-center p-2 rounded-xl
                                   text-gray-500 hover:text-gray-700 hover:bg-gray-100
                                   focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-2
                                   transition">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }"
                                  class="inline-flex"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }"
                                  class="hidden"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="$dashboardRoute" :active="request()->url() === $dashboardRoute">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            {{-- HR tools on mobile --}}
            @if(Auth::user()->role === 'hr')
                <div class="px-4 pt-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    HR Tools
                </div>

                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')">
                    {{ __('Users') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('users.create')" :active="request()->routeIs('users.create')">
                    {{ __('Create User') }}
                </x-responsive-nav-link>

                @if(Route::has('faculty.index'))
                    <x-responsive-nav-link :href="route('faculty.index')" :active="request()->routeIs('faculty.index') || request()->routeIs('faculty.records')">
                        {{ __('Manage Faculties') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('reclassification.faculty-records')" :active="request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records')">
                    {{ __('Faculty Records') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.admin.approved')" :active="request()->routeIs('reclassification.admin.approved')">
                    {{ __('Approved Reclassification') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.history')" :active="request()->routeIs('reclassification.history')">
                    {{ __('Reclassification History') }}
                </x-responsive-nav-link>
            @elseif(Auth::user()->role === 'vpaa')
                <div class="px-4 pt-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    Reclassification
                </div>
                <x-responsive-nav-link :href="route('reclassification.review.queue')" :active="request()->routeIs('reclassification.review.queue')">
                    {{ __('VPAA Queue') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.review.submissions')" :active="request()->routeIs('reclassification.review.submissions')">
                    {{ __('All Submissions') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.review.approved')" :active="request()->routeIs('reclassification.review.approved')">
                    {{ __('VPAA Endorsement List') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.review.finalized')" :active="request()->routeIs('reclassification.review.finalized')">
                    {{ __('Approved Reclassification') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.history')" :active="request()->routeIs('reclassification.history')">
                    {{ __('Reclassification History') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.faculty-records')" :active="request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records')">
                    {{ __('Faculty Records') }}
                </x-responsive-nav-link>
            @elseif(Auth::user()->role === 'president')
                <div class="px-4 pt-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    Reclassification
                </div>
                <x-responsive-nav-link :href="route('reclassification.review.submissions')" :active="request()->routeIs('reclassification.review.submissions')">
                    {{ __('All Submissions') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.review.approved')" :active="request()->routeIs('reclassification.review.approved')">
                    {{ __('President Approval List') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.review.finalized')" :active="request()->routeIs('reclassification.review.finalized')">
                    {{ __('Approved Reclassification') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.history')" :active="request()->routeIs('reclassification.history')">
                    {{ __('Reclassification History') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.faculty-records')" :active="request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records')">
                    {{ __('Faculty Records') }}
                </x-responsive-nav-link>
            @elseif(Auth::user()->role === 'dean')
                <div class="px-4 pt-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    Reclassification
                </div>
                <x-responsive-nav-link :href="route('reclassification.dean.review')" :active="request()->routeIs('reclassification.dean.review')">
                    {{ __('Dean Queue') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dean.submissions')" :active="request()->routeIs('dean.submissions')">
                    {{ __('Department Submissions') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dean.approved')" :active="request()->routeIs('dean.approved')">
                    {{ __('Approved List') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.history')" :active="request()->routeIs('reclassification.history')">
                    {{ __('Reclassification History') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reclassification.faculty-records')" :active="request()->routeIs('reclassification.faculty-records') || request()->routeIs('faculty.records')">
                    {{ __('Faculty Records') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                <div class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                    Role: {{ ucfirst(str_replace('_',' ', Auth::user()->role)) }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="event.preventDefault(); if (confirm('Are you sure you want to log out?')) { this.closest('form').submit(); }">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
