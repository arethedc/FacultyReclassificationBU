<x-app-layout>
    @php
        $isFacultyCreate = (($context ?? null) === 'faculty') || (($forceRole ?? null) === 'faculty');
        $isDeanViewer = strtolower((string) (auth()->user()->role ?? '')) === 'dean';
        $manageFacultyRoute = $isDeanViewer ? route('dean.faculty.index') : route('faculty.index');
        $backRoute = $isFacultyCreate ? $manageFacultyRoute : route('users.index');
        $pageTitle = $isFacultyCreate ? 'Create Faculty' : 'Create User';
        $pageSubtitle = $isFacultyCreate
            ? 'Create a new faculty account and faculty profile details.'
            : 'Create system users based on role and responsibility.';
        $backLabel = $isFacultyCreate ? 'Back to Manage Faculties' : 'Back to Manage Users';
        $submitLabel = $isFacultyCreate ? 'Create Faculty' : 'Create User';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-800">{{ $pageTitle }}</h2>
                <p class="text-sm text-gray-500">{{ $pageSubtitle }}</p>
            </div>
            @if($isFacultyCreate)
                <a href="{{ $backRoute }}"
                   class="h-11 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 inline-flex items-center justify-center whitespace-nowrap self-start sm:self-auto">
                    {{ $backLabel }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <form
                x-data="{
                    role: '{{ old('role', ($forceRole ?? null) ? $forceRole : (($context ?? null) === 'faculty' ? 'faculty' : '')) }}',
                    manualPassword: {{ old('manual_password') ? 'true' : 'false' }},
                    password: '',
                    passwordConfirmation: '',
                    validationTick: 0,
                    showManualPassword: false,
                    showManualPasswordConfirmation: false,
                    createEmailAvailabilityUrl: @js(route('users.create-email-availability')),
                    createEmployeeNoAvailabilityUrl: @js(route('users.create-employee-no-availability')),
                    emailCheckState: 'idle',
                    emailCheckMessage: '',
                    emailCheckTimer: null,
                    hasEmailServerError: @js($errors->has('email')),
                    employeeCheckState: 'idle',
                    employeeCheckMessage: '',
                    employeeCheckTimer: null,
                    hasEmployeeNoServerError: @js($errors->has('employee_no')),
                    formatEmployeeNo(value) {
                        const digits = String(value || '').replace(/\D/g, '').slice(0, 7);
                        return digits.length > 4
                            ? digits.slice(0, 4) + '-' + digits.slice(4)
                            : digits;
                    },
                    parseAppointmentDateFromEmployeeNo(value) {
                        const raw = String(value || '').trim();
                        if (!/^\d{2}(0[1-9]|1[0-2])-\d{3}$/.test(raw)) return '';
                        const yearPart = Number(raw.slice(0, 2));
                        const monthPart = Number(raw.slice(2, 4));
                        if (!Number.isInteger(monthPart) || monthPart < 1 || monthPart > 12) return '';
                        const fullYear = 2000 + yearPart;
                        return `${String(fullYear).padStart(4, '0')}-${String(monthPart).padStart(2, '0')}-01`;
                    },
                    autoSetOriginalAppointmentDateFromEmployeeNo() {
                        if (!this.$refs.employee_no || !this.$refs.original_appointment_date) return;
                        const normalized = this.formatEmployeeNo(this.$refs.employee_no.value);
                        this.$refs.employee_no.value = normalized;
                        this.$refs.original_appointment_date.value = this.parseAppointmentDateFromEmployeeNo(normalized);
                    },
                    init() {
                        this.$nextTick(() => {
                            this.autoSetOriginalAppointmentDateFromEmployeeNo();
                            if (this.$refs.email && String(this.$refs.email.value || '').trim() !== '' && !this.hasEmailServerError) {
                                this.checkCreateEmailAvailability(true);
                            }
                            if (
                                this.role === 'faculty' &&
                                this.$refs.employee_no &&
                                String(this.$refs.employee_no.value || '').trim() !== '' &&
                                !this.hasEmployeeNoServerError
                            ) {
                                this.checkCreateEmployeeNoAvailability(true);
                            }
                        });
                    },
                    hasPasswordValues() {
                        return this.password.length > 0 || this.passwordConfirmation.length > 0;
                    },
                    passwordsMatch() {
                        return this.password !== '' && this.password === this.passwordConfirmation;
                    },
                    passwordsMismatch() {
                        return this.passwordConfirmation !== '' && this.password !== this.passwordConfirmation;
                    },
                    bumpValidationTick() {
                        this.validationTick++;
                    },
                    hasInvalidNativeFields() {
                        if (!this.$refs.create_form || typeof this.$refs.create_form.checkValidity !== 'function') {
                            return false;
                        }

                        return !this.$refs.create_form.checkValidity();
                    },
                    async checkCreateEmailAvailability(immediate = false) {
                        if (!this.$refs.email) return this.emailCheckState;

                        const nextEmail = String(this.$refs.email.value || '').trim();
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                        this.hasEmailServerError = false;

                        if (this.emailCheckTimer) {
                            clearTimeout(this.emailCheckTimer);
                            this.emailCheckTimer = null;
                        }

                        if (!nextEmail) {
                            this.emailCheckState = 'idle';
                            this.emailCheckMessage = '';
                            return this.emailCheckState;
                        }

                        if (!emailPattern.test(nextEmail)) {
                            this.emailCheckState = 'invalid';
                            this.emailCheckMessage = 'Enter a valid email address.';
                            return this.emailCheckState;
                        }

                        const runCheck = async () => {
                            this.emailCheckState = 'checking';
                            this.emailCheckMessage = 'Checking email availability...';
                            try {
                                const url = `${this.createEmailAvailabilityUrl}?email=${encodeURIComponent(nextEmail)}`;
                                const response = await fetch(url, {
                                    method: 'GET',
                                    credentials: 'same-origin',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });

                                if (!response.ok) throw new Error('Email check failed');
                                const payload = await response.json();

                                if (String(this.$refs.email.value || '').trim().toLowerCase() !== nextEmail.toLowerCase()) {
                                    return;
                                }

                                if (payload.available) {
                                    this.emailCheckState = 'valid';
                                    this.emailCheckMessage = payload.message || 'Email is available.';
                                } else {
                                    this.emailCheckState = 'unavailable';
                                    this.emailCheckMessage = payload.message || 'Email is already in use.';
                                }
                            } catch (error) {
                                this.emailCheckState = 'error';
                                this.emailCheckMessage = 'Unable to verify email right now. Try again.';
                            }
                        };

                        if (immediate) {
                            await runCheck();
                        } else {
                            this.emailCheckState = 'checking';
                            this.emailCheckMessage = 'Checking email availability...';
                            this.emailCheckTimer = setTimeout(() => {
                                runCheck();
                            }, 350);
                        }

                        return this.emailCheckState;
                    },
                    async checkCreateEmployeeNoAvailability(immediate = false) {
                        if (!this.$refs.employee_no) return this.employeeCheckState;

                        if (this.role !== 'faculty') {
                            this.employeeCheckState = 'idle';
                            this.employeeCheckMessage = '';
                            return this.employeeCheckState;
                        }

                        const employeeNo = String(this.$refs.employee_no.value || '').trim();
                        const pattern = /^\d{2}(0[1-9]|1[0-2])-\d{3}$/;
                        this.hasEmployeeNoServerError = false;

                        if (this.employeeCheckTimer) {
                            clearTimeout(this.employeeCheckTimer);
                            this.employeeCheckTimer = null;
                        }

                        if (!employeeNo) {
                            this.employeeCheckState = 'idle';
                            this.employeeCheckMessage = '';
                            return this.employeeCheckState;
                        }

                        if (!pattern.test(employeeNo)) {
                            this.employeeCheckState = 'invalid';
                            this.employeeCheckMessage = 'Employee number must follow YYMM-XXX format.';
                            return this.employeeCheckState;
                        }

                        const runCheck = async () => {
                            this.employeeCheckState = 'checking';
                            this.employeeCheckMessage = 'Checking employee number...';
                            try {
                                const url = `${this.createEmployeeNoAvailabilityUrl}?employee_no=${encodeURIComponent(employeeNo)}`;
                                const response = await fetch(url, {
                                    method: 'GET',
                                    credentials: 'same-origin',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });

                                if (!response.ok) throw new Error('Employee number check failed');
                                const payload = await response.json();

                                if (String(this.$refs.employee_no.value || '').trim() !== employeeNo) {
                                    return;
                                }

                                if (payload.available) {
                                    this.employeeCheckState = 'valid';
                                    this.employeeCheckMessage = payload.message || 'Employee number is available.';
                                } else {
                                    this.employeeCheckState = 'unavailable';
                                    this.employeeCheckMessage = payload.message || 'Employee number already exists.';
                                }
                            } catch (error) {
                                this.employeeCheckState = 'error';
                                this.employeeCheckMessage = 'Unable to verify employee number right now. Try again.';
                            }
                        };

                        if (immediate) {
                            await runCheck();
                        } else {
                            this.employeeCheckState = 'checking';
                            this.employeeCheckMessage = 'Checking employee number...';
                            this.employeeCheckTimer = setTimeout(() => {
                                runCheck();
                            }, 350);
                        }

                        return this.employeeCheckState;
                    },
                    isCreateSubmitLocked() {
                        const _tick = this.validationTick;
                        if (this.hasInvalidNativeFields()) return true;
                        if (this.manualPassword && this.passwordsMismatch()) return true;
                        if (!this.$refs.email) return false;
                        if (this.hasEmailServerError) return true;

                        const nextEmail = String(this.$refs.email.value || '').trim();
                        if (!nextEmail) return false;

                        if (this.role === 'faculty') {
                            if (this.hasEmployeeNoServerError) return true;
                            if (this.$refs.employee_no) {
                                const employeeNo = String(this.$refs.employee_no.value || '').trim();
                                if (employeeNo !== '' && ['invalid', 'unavailable', 'checking', 'error'].includes(this.employeeCheckState)) {
                                    return true;
                                }
                            }
                        }

                        return ['invalid', 'unavailable', 'checking', 'error'].includes(this.emailCheckState);
                    },
                }"
                method="POST"
                action="{{ $actionRoute ?? route('users.store') }}"
                @submit="if (isCreateSubmitLocked()) { $event.preventDefault(); }"
                @input="bumpValidationTick()"
                @change="bumpValidationTick()"
                x-ref="create_form"
                class="space-y-8 pb-12"
            >
                @csrf

                {{-- ✅ TOP ERROR SUMMARY --}}
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-5">
                        <div class="font-semibold mb-2">Please fix the errors below.</div>
                        <ul class="list-disc ml-5 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- =========================
                    STEP 1: ROLE SELECTION
                ========================== --}}
                @if(($forceRole ?? null) === 'faculty')
                    <input type="hidden" name="role" value="faculty">
                @elseif(($context ?? null) !== 'faculty')
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">User Role</h3>
                            <p class="text-sm text-gray-500">Determines required information.</p>
                        </div>

                        <div class="p-6">
                            <label class="block text-sm font-medium text-gray-700">Role</label>

                            <select
                                x-model="role"
                                name="role"
                                required
                                class="mt-1 w-full md:w-1/2 rounded-xl border bg-white
                                {{ $errors->has('role') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select Role</option>
                                <option value="faculty" {{ old('role')==='faculty' ? 'selected' : '' }}>Faculty</option>
                                <option value="dean" {{ old('role')==='dean' ? 'selected' : '' }}>Dean</option>
                                <option value="hr" {{ old('role')==='hr' ? 'selected' : '' }}>HR</option>
                                <option value="vpaa" {{ old('role')==='vpaa' ? 'selected' : '' }}>VPAA</option>
                                <option value="president" {{ old('role')==='president' ? 'selected' : '' }}>President</option>
                            </select>

                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @else
                    {{-- FACULTY CONTEXT: enforce role faculty --}}
                    <input type="hidden" name="role" value="faculty">
                @endif

                {{-- =========================
                    STEP 2: ACCOUNT CREDENTIALS
                ========================== --}}
                <div x-show="role !== ''" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Account Credentials</h3>
                        <p class="text-sm text-gray-500">Used for system login.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    name="manual_password"
                                    value="1"
                                    x-model="manualPassword"
                                    class="rounded border-gray-300 text-bu focus:ring-bu"
                                >
                                Set password manually
                            </label>
                            <p class="mt-1 text-xs text-gray-500" x-show="!manualPassword">
                                If unchecked, the system generates a temporary password and sends a password setup email.
                            </p>
                            <p class="mt-1 text-xs text-gray-500" x-show="manualPassword">
                                If checked, enter password and confirmation below.
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input
                                type="email"
                                x-ref="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                @input="checkCreateEmailAvailability()"
                                @blur="checkCreateEmailAvailability(true)"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm"
                               x-show="emailCheckMessage !== '' && !hasEmailServerError"
                               x-bind:class="{
                                    'text-gray-500': emailCheckState === 'checking',
                                    'text-green-600': emailCheckState === 'valid',
                                    'text-red-600': ['invalid', 'unavailable', 'error'].includes(emailCheckState)
                                }"
                               x-text="emailCheckMessage"></p>
                        </div>

                        <div x-show="manualPassword" x-transition>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="relative mt-1">
                                <input
                                    x-bind:type="showManualPassword ? 'text' : 'password'"
                                    name="password"
                                    :required="manualPassword"
                                    minlength="8"
                                    x-model="password"
                                    class="w-full rounded-xl border bg-white pr-11
                                    {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                                >
                                <button
                                    type="button"
                                    @click="showManualPassword = !showManualPassword"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                                    :aria-label="showManualPassword ? 'Hide password' : 'Show password'"
                                >
                                    <svg x-show="!showManualPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg x-show="showManualPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="manualPassword" x-transition>
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <div class="relative mt-1">
                                <input
                                    x-bind:type="showManualPasswordConfirmation ? 'text' : 'password'"
                                    name="password_confirmation"
                                    :required="manualPassword"
                                    minlength="8"
                                    x-model="passwordConfirmation"
                                    class="w-full rounded-xl border bg-white pr-11
                                    {{ $errors->has('password_confirmation') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                                >
                                <button
                                    type="button"
                                    @click="showManualPasswordConfirmation = !showManualPasswordConfirmation"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-bu focus:ring-offset-1 rounded-md"
                                    :aria-label="showManualPasswordConfirmation ? 'Hide confirm password' : 'Show confirm password'"
                                >
                                    <svg x-show="!showManualPasswordConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg x-show="showManualPasswordConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.73 5.08A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639a10.477 10.477 0 0 1-1.61 3.04M6.228 6.228A10.451 10.451 0 0 0 2.037 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5a10.45 10.45 0 0 0 5.772-1.728M9.88 9.88a3 3 0 1 0 4.243 4.243" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="manualPassword && hasPasswordValues() && passwordsMatch()"
                               class="mt-1 text-sm text-green-600">
                                Passwords match.
                            </p>
                            <p x-show="manualPassword && passwordsMismatch()"
                               class="mt-1 text-sm text-red-600">
                                Passwords do not match.
                            </p>
                            @error('password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- =========================
                    STEP 3: PERSONAL DETAILS
                ========================== --}}
                <div x-show="role !== ''" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Personal & System Identity</h3>
                        <p class="text-sm text-gray-500">Required for all users.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input
                                type="text"
                                name="first_name"
                                value="{{ old('first_name') }}"
                                required
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('first_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input
                                type="text"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                required
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('last_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Middle Name (Optional)</label>
                            <input
                                type="text"
                                name="middle_name"
                                value="{{ old('middle_name') }}"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('middle_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('middle_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Suffix (Optional)</label>
                            <input
                                type="text"
                                name="suffix"
                                value="{{ old('suffix') }}"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('suffix') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                            @error('suffix')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ✅ Department required for faculty/dean (matches controller rule) --}}
                        <div x-show="role === 'faculty' || role === 'dean'" x-transition class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Department</label>

                            @if(!empty($lockDepartment) && !empty($defaultDepartmentId))
                                <div class="mt-1 w-full md:w-1/2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700">
                                    {{ optional($departments->first())->name ?? 'Department' }}
                                </div>
                                <input type="hidden" name="department_id" value="{{ $defaultDepartmentId }}">
                            @else
                                <select
                                    name="department_id"
                                    :required="role === 'faculty' || role === 'dean'"
                                    class="mt-1 w-full md:w-1/2 rounded-xl border bg-white
                                    {{ $errors->has('department_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                                >
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            @error('department_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <p class="mt-1 text-xs text-gray-500">
                                Required for Faculty and Dean.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- =========================
                    STEP 4: FACULTY ONLY
                ========================== --}}
                <div x-show="role === 'faculty'" x-transition
                     class="bg-white rounded-2xl shadow-card border border-gray-200">

                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Faculty Reclassification Information</h3>
                        <p class="text-sm text-gray-500">Required for faculty members.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Employee Number / Staff ID
                            </label>

                            <input
                                type="text"
                                x-ref="employee_no"
                                name="employee_no"
                                placeholder="1234-567"
                                value="{{ old('employee_no') }}"
                                maxlength="8"
                                inputmode="numeric"
                                pattern="\d{2}(0[1-9]|1[0-2])-\d{3}"
                                :required="role === 'faculty'"
                                @input="autoSetOriginalAppointmentDateFromEmployeeNo(); checkCreateEmployeeNoAvailability()"
                                @change="autoSetOriginalAppointmentDateFromEmployeeNo(); checkCreateEmployeeNoAvailability(true)"
                                @blur="autoSetOriginalAppointmentDateFromEmployeeNo(); checkCreateEmployeeNoAvailability(true)"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('employee_no') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >

                            @error('employee_no')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm"
                               x-show="employeeCheckMessage !== '' && !hasEmployeeNoServerError"
                               x-bind:class="{
                                    'text-gray-500': employeeCheckState === 'checking',
                                    'text-green-600': employeeCheckState === 'valid',
                                    'text-red-600': ['invalid', 'unavailable', 'error'].includes(employeeCheckState)
                               }"
                               x-text="employeeCheckMessage"></p>

                            <p class="mt-1 text-xs text-gray-500">
                                Required when Role = Faculty. Format: 4 digits, dash, 3 digits.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employment Type</label>

                            @php $emp = old('employment_type', 'full_time'); @endphp

                            <div class="mt-3 flex gap-8">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="employment_type" value="full_time"
                                           class="text-bu focus:ring-bu"
                                           {{ $emp === 'full_time' ? 'checked' : '' }}>
                                    Full-time
                                </label>

                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="employment_type" value="part_time"
                                           class="text-bu focus:ring-bu"
                                           {{ $emp === 'part_time' ? 'checked' : '' }}>
                                    Part-time
                                </label>
                            </div>

                            @error('employment_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Rank Level</label>

                            <select
                                name="rank_level_id"
                                :required="role === 'faculty'"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('rank_level_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select Rank Level</option>
                                @foreach($rankLevels as $level)
                                    <option value="{{ $level->id }}" {{ old('rank_level_id') == $level->id ? 'selected' : '' }}>
                                        {{ $level->title }}
                                    </option>
                                @endforeach
                            </select>

                            @error('rank_level_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Highest Degree Earned</label>
                            <select
                                name="highest_degree"
                                :required="role === 'faculty'"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('highest_degree') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >
                                <option value="">Select degree</option>
                                <option value="bachelors" {{ old('highest_degree') === 'bachelors' ? 'selected' : '' }}>Bachelor’s</option>
                                <option value="masters" {{ old('highest_degree') === 'masters' ? 'selected' : '' }}>Master’s</option>
                                <option value="doctorate" {{ old('highest_degree') === 'doctorate' ? 'selected' : '' }}>Doctorate</option>
                            </select>
                            @error('highest_degree')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Original Appointment</label>

                            <input
                                type="date"
                                x-ref="original_appointment_date"
                                name="original_appointment_date"
                                value="{{ old('original_appointment_date') }}"
                                readonly
                                aria-readonly="true"
                                class="mt-1 w-full rounded-xl border bg-white
                                {{ $errors->has('original_appointment_date') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-bu focus:ring-bu' }}"
                            >

                            @error('original_appointment_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Auto-filled from Employee Number using <span class="font-medium">YYMM</span> as <span class="font-medium">20YY-MM-01</span>.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- =========================
                    ACTION BUTTONS
                ========================== --}}
                <div class="pt-6 border-t flex justify-end gap-4">
                    <a href="{{ $backRoute }}"
                       class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </a>

                    <button type="submit"
                            :disabled="isCreateSubmitLocked()"
                            :class="isCreateSubmitLocked() ? 'opacity-60 cursor-not-allowed' : ''"
                            class="px-6 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft
                                   focus:ring-2 focus:ring-bu focus:ring-offset-2 transition">
                        {{ $submitLabel }}
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>
