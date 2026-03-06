<x-app-layout>
    {{-- =========================================================
        EDIT FACULTY PROFILE (HR-controlled)
        Polished UX: View mode by default + Edit toggle
    ========================================================== --}}

    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-gray-800">Faculty Profile Details</h2>
            <p class="text-sm text-gray-500">
                HR-controlled faculty information used in reclassification validations.
            </p>
        </div>
    </x-slot>

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Alerts --}}
            @if(session('success'))
                <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700">
                    <p class="font-semibold mb-2">Please fix the following:</p>
                    <ul class="list-disc ml-5 text-sm space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- =====================================================
                FORM with Edit Mode Toggle (Alpine)
            ====================================================== --}}
            <form method="POST"
                  action="{{ route('faculty-profiles.update', $user) }}"
                  class="space-y-6"
                  x-data="{
                    editMode: {{ $errors->any() ? 'true' : 'false' }},
                    original: {},
                    init() {
                      this.original = {
                        employee_no: $refs.employee_no?.value ?? '',
                        employment_type: $refs.employment_type?.value ?? '',
                        rank_level_id: $refs.rank_level_id?.value ?? '',
                        teaching_rank: $refs.teaching_rank?.value ?? '',
                        highest_degree: $refs.highest_degree?.value ?? '',
                        original_appointment_date: $refs.original_appointment_date?.value ?? ''
                      };
                    },
                    enableEdit() { this.editMode = true; },
                    discard() {
                      if ($refs.employee_no) $refs.employee_no.value = this.original.employee_no;
                      if ($refs.employment_type) $refs.employment_type.value = this.original.employment_type;
                      if ($refs.rank_level_id) $refs.rank_level_id.value = this.original.rank_level_id;
                      if ($refs.teaching_rank) $refs.teaching_rank.value = this.original.teaching_rank;
                      if ($refs.highest_degree) $refs.highest_degree.value = this.original.highest_degree;
                      if ($refs.original_appointment_date) $refs.original_appointment_date.value = this.original.original_appointment_date;
                      this.editMode = false;
                    }
                  }">
                @csrf
                @method('PUT')

                {{-- =====================================================
                    Top Action Bar (Back / Edit / Discard / Save)
                ====================================================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ $back ?? route('faculty.index') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                ← Back
                            </a>

                            <div class="text-sm text-gray-500">
                                Role:
                                <span class="font-semibold text-gray-800">
                                    {{ ucfirst(str_replace('_',' ', $user->role)) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            {{-- View Mode --}}
                            <button type="button"
                                    x-show="!editMode"
                                    @click="enableEdit()"
                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl
                                           border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Edit Faculty Details
                            </button>

                            {{-- Edit Mode --}}
                            <button type="button"
                                    x-show="editMode"
                                    @click="discard()"
                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl
                                           border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Discard Changes
                            </button>

                            <button type="submit"
                                    x-show="editMode"
                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl
                                           bg-bu text-white hover:bg-bu-dark shadow-soft transition">
                                Save Changes
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pb-4">
                        <div x-show="!editMode" class="text-xs text-gray-500">
                            Fields are locked for safety. Click <span class="font-semibold">Edit Faculty Details</span> to unlock.
                        </div>
                        <div x-show="editMode" class="text-xs text-gray-500">
                            Edit mode enabled. Save to apply changes or Discard to cancel.
                        </div>
                    </div>
                </div>

                {{-- =====================================================
                    Faculty Summary (Reference)
                ====================================================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Faculty Summary</h3>
                            <p class="text-sm text-gray-500">
                                Reference only (account + department are edited in User Information).
                            </p>
                        </div>

                        <a href="{{ route('users.edit', ['user' => $user, 'context' => 'faculty']) }}"
                           class="shrink-0 px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                            Edit User Information
                        </a>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-gray-500">Full Name</p>
                            <p class="font-semibold text-gray-800 mt-1">{{ $user->name }}</p>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-gray-500">Email</p>
                            <p class="font-semibold text-gray-800 mt-1">{{ $user->email }}</p>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-gray-500">Department</p>
                            <p class="font-semibold text-gray-800 mt-1">{{ $user->department?->name ?? '—' }}</p>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-gray-500">Current Rank (HR)</p>
                            <p class="font-semibold text-gray-800 mt-1">
                                {{ $profile->rankLevel?->title ?: ($profile->teaching_rank ?? '-') }}
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <div class="p-4 rounded-xl border border-yellow-200 bg-yellow-50 text-yellow-900 text-sm">
                            <div class="font-semibold mb-1">HR Note</div>
                            <div>
                                This page controls official faculty details (employee no, rank, appointment date).
                                These values appear on reclassification forms and may affect validations.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- =====================================================
                    Faculty Profile Details (Editable Section)
                ====================================================== --}}
                <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Faculty Profile Details</h3>
                        <p class="text-sm text-gray-500">
                            Saved to <span class="font-medium">faculty_profiles</span> table.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Employee Number --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Employee Number</label>
                            <input x-ref="employee_no"
                                   :disabled="!editMode"
                                   type="text"
                                   name="employee_no"
                                   value="{{ old('employee_no', $profile->employee_no) }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu
                                          disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed"
                                   placeholder="e.g. BU-2021-0045"
                                   required>
                            @error('employee_no')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-2">
                                Must be unique. Used for searching and official identification.
                            </p>
                        </div>

                        {{-- Employment Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employment Type</label>
                            <select x-ref="employment_type"
                                    :disabled="!editMode"
                                    name="employment_type"
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                           focus:border-bu focus:ring-bu
                                           disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed"
                                    required>
                                <option value="full_time" @selected(old('employment_type', $profile->employment_type) === 'full_time')>
                                    Full-time
                                </option>
                                <option value="part_time" @selected(old('employment_type', $profile->employment_type) === 'part_time')>
                                    Part-time
                                </option>
                            </select>
                            @error('employment_type')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Academic Rank Level --}}
                        <div>
                            @if($rankLevels->isNotEmpty())
                                <label class="block text-sm font-medium text-gray-700">Academic Rank Level</label>
                                <select x-ref="rank_level_id"
                                        :disabled="!editMode"
                                        name="rank_level_id"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                               focus:border-bu focus:ring-bu
                                               disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed"
                                        required>
                                    <option value="">Select Rank Level</option>
                                    @foreach($rankLevels as $level)
                                        <option value="{{ $level->id }}" @selected((string) old('rank_level_id', $profile->rank_level_id) === (string) $level->id)>
                                            {{ $level->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('rank_level_id')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            @else
                                <label class="block text-sm font-medium text-gray-700">Teaching Rank</label>
                                <input x-ref="teaching_rank"
                                       :disabled="!editMode"
                                       type="text"
                                       name="teaching_rank"
                                       value="{{ old('teaching_rank', $profile->teaching_rank) }}"
                                       class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                              focus:border-bu focus:ring-bu
                                              disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed"
                                       required>
                                @error('teaching_rank')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        {{-- Highest Degree --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Highest Degree Earned</label>
                            <select x-ref="highest_degree"
                                    :disabled="!editMode"
                                    name="highest_degree"
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                           focus:border-bu focus:ring-bu
                                           disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed">
                                <option value="">—</option>
                                <option value="bachelors" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'bachelors')>Bachelor’s</option>
                                <option value="masters" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'masters')>Master’s</option>
                                <option value="doctorate" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'doctorate')>Doctorate</option>
                            </select>
                            @error('highest_degree')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Original Appointment Date --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Original Appointment</label>
                            <input x-ref="original_appointment_date"
                                   :disabled="!editMode"
                                   type="date"
                                   name="original_appointment_date"
                                   value="{{ old('original_appointment_date', optional($profile->original_appointment_date)->format('Y-m-d')) }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white
                                          focus:border-bu focus:ring-bu
                                          disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed">
                            @error('original_appointment_date')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-2">
                                Optional. Can be used later to compute years of service.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Bottom actions (optional for long pages) --}}
                <div class="flex justify-end gap-2">
                    <button type="button"
                            x-show="editMode"
                            @click="discard()"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                        Discard Changes
                    </button>
                    <button type="submit"
                            x-show="editMode"
                            class="px-5 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft transition">
                        Save Changes
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>
