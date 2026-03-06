<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-800">Person Profile</h2>
                <p class="text-sm text-gray-500">
                    Unified profile for account and faculty data management.
                </p>
            </div>
            <a href="{{ $backRoute ?? route('users.index') }}"
               class="h-11 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 inline-flex items-center justify-center whitespace-nowrap self-start sm:self-auto">
                {{ $backLabel ?? 'Back to Manage Users' }}
            </a>
        </div>
    </x-slot>

    @php
        $showDepartment = in_array($user->role, ['faculty', 'dean'], true);
        $showFacultyDetails = $user->role === 'faculty';
        $profile = $user->facultyProfile;
    @endphp

    <div class="py-12 bg-bu-muted min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <form method="POST"
                  action="{{ route('users.update', ['user' => $user, 'context' => ($editContext ?? 'users')]) }}"
                  class="space-y-6"
                  x-ref="profile_form"
                  @submit="if (isProfileSaveLocked()) { $event.preventDefault(); }"
                  x-data="{
                      editMode: {{ ($errors->any() && !$errors->has('email')) ? 'true' : 'false' }},
                      emailEditMode: {{ $errors->has('email') ? 'true' : 'false' }},
                      emailAvailabilityUrl: @js(route('users.email-availability', $user)),
                      employeeNoAvailabilityUrl: @js(route('users.employee-no-availability', $user)),
                      emailCheckState: 'idle',
                      emailCheckMessage: '',
                      emailCheckTimer: null,
                      employeeCheckState: 'idle',
                      employeeCheckMessage: '',
                      employeeCheckTimer: null,
                      hasEmployeeNoServerError: @js($errors->has('employee_no')),
                      original: {},
                      parseAppointmentDateFromEmployeeNo(value) {
                          const raw = String(value || '').trim();
                          if (!/^\d{2}(0[1-9]|1[0-2])-\d{3}$/.test(raw)) return null;

                          const yearPart = Number(raw.slice(0, 2));
                          const monthPart = Number(raw.slice(2, 4));
                          if (!Number.isInteger(monthPart) || monthPart < 1 || monthPart > 12) return null;

                          const fullYear = 2000 + yearPart;
                          return `${String(fullYear).padStart(4, '0')}-${String(monthPart).padStart(2, '0')}-01`;
                      },
                      formatEmployeeNoInput() {
                          if (!this.$refs.employee_no) return '';
                          const digits = String(this.$refs.employee_no.value || '').replace(/\D/g, '').slice(0, 7);
                          const formatted = digits.length > 4
                              ? `${digits.slice(0, 4)}-${digits.slice(4)}`
                              : digits;
                          this.$refs.employee_no.value = formatted;
                          return formatted;
                      },
                      autoSetOriginalAppointmentDateFromEmployeeNo() {
                          if (!this.$refs.employee_no || !this.$refs.original_appointment_date) return;
                          const derivedDate = this.parseAppointmentDateFromEmployeeNo(this.$refs.employee_no.value);
                          this.$refs.original_appointment_date.value = derivedDate || '';
                      },
                      init() {
                          const refs = this.$refs;
                          this.original = {
                              first_name: refs.first_name?.value ?? '',
                              middle_name: refs.middle_name?.value ?? '',
                              last_name: refs.last_name?.value ?? '',
                              suffix: refs.suffix?.value ?? '',
                              email: @js($user->email),
                              department_id: refs.department_id?.value ?? '',
                              status: refs.status?.value ?? '',
                              employee_no: refs.employee_no?.value ?? '',
                              employment_type: refs.employment_type?.value ?? '',
                              rank_level_id: refs.rank_level_id?.value ?? '',
                              teaching_rank: refs.teaching_rank?.value ?? '',
                              highest_degree: refs.highest_degree?.value ?? '',
                              original_appointment_date: refs.original_appointment_date?.value ?? '',
                          };

                          this.formatEmployeeNoInput();
                          this.autoSetOriginalAppointmentDateFromEmployeeNo();
                          if (this.editMode && refs.employee_no && !this.hasEmployeeNoServerError) {
                              this.checkEmployeeNoAvailability(true);
                          }
                      },
                      enableEdit() {
                          this.editMode = true;
                          this.emailEditMode = false;
                          if ($refs.email_change_only) $refs.email_change_only.value = '0';
                          this.emailCheckState = 'idle';
                          this.emailCheckMessage = '';
                          this.$nextTick(() => {
                              this.autoSetOriginalAppointmentDateFromEmployeeNo();
                              this.hasEmployeeNoServerError = false;
                              this.checkEmployeeNoAvailability(true);
                          });
                      },
                      startEmailEdit() {
                          if (this.editMode) return;
                          this.emailEditMode = true;
                          if ($refs.email_change_only) $refs.email_change_only.value = '0';
                          this.emailCheckState = 'idle';
                          this.emailCheckMessage = '';
                          this.$nextTick(() => $refs.email?.focus());
                      },
                      async checkEmailAvailability(immediate = false) {
                          if (!this.emailEditMode || !$refs.email) return this.emailCheckState;
                          const nextEmail = String($refs.email.value || '').trim();
                          const currentEmail = String(this.original.email || '').trim();

                          if (this.emailCheckTimer) {
                              clearTimeout(this.emailCheckTimer);
                              this.emailCheckTimer = null;
                          }

                          if (!nextEmail) {
                              this.emailCheckState = 'idle';
                              this.emailCheckMessage = '';
                              return this.emailCheckState;
                          }

                          if (nextEmail.toLowerCase() === currentEmail.toLowerCase()) {
                              this.emailCheckState = 'invalid';
                              this.emailCheckMessage = 'New email must be different from current email.';
                              return this.emailCheckState;
                          }

                          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                          if (!emailPattern.test(nextEmail)) {
                              this.emailCheckState = 'invalid';
                              this.emailCheckMessage = 'Enter a valid email address.';
                              return this.emailCheckState;
                          }

                          const runCheck = async () => {
                              this.emailCheckState = 'checking';
                              this.emailCheckMessage = 'Checking email availability...';
                              try {
                                  const url = `${this.emailAvailabilityUrl}?email=${encodeURIComponent(nextEmail)}`;
                                  const response = await fetch(url, {
                                      method: 'GET',
                                      credentials: 'same-origin',
                                      headers: {
                                          'X-Requested-With': 'XMLHttpRequest',
                                      },
                                  });
                                  if (!response.ok) throw new Error('Email check failed');
                                  const payload = await response.json();
                                  if (String($refs.email.value || '').trim().toLowerCase() !== nextEmail.toLowerCase()) {
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
                              this.emailCheckTimer = setTimeout(() => {
                                  runCheck();
                              }, 350);
                          }

                          return this.emailCheckState;
                      },
                      async saveEmailChange() {
                          await this.checkEmailAvailability(true);
                          if (this.emailCheckState !== 'valid') return;
                          if ($refs.email_change_only) $refs.email_change_only.value = '1';
                          if (typeof $refs.profile_form?.requestSubmit === 'function') {
                              $refs.profile_form.requestSubmit();
                          } else if ($refs.profile_form) {
                              $refs.profile_form.submit();
                          }
                      },
                      async checkEmployeeNoAvailability(immediate = false) {
                          if (!this.$refs.employee_no) return this.employeeCheckState;
                          this.hasEmployeeNoServerError = false;

                          const employeeNo = String(this.$refs.employee_no.value || '').trim();
                          const currentEmployeeNo = String(this.original.employee_no || '').trim();
                          const pattern = /^\d{2}(0[1-9]|1[0-2])-\d{3}$/;

                          if (this.employeeCheckTimer) {
                              clearTimeout(this.employeeCheckTimer);
                              this.employeeCheckTimer = null;
                          }

                          if (!this.editMode) {
                              this.employeeCheckState = 'idle';
                              this.employeeCheckMessage = '';
                              return this.employeeCheckState;
                          }

                          if (!employeeNo) {
                              this.employeeCheckState = 'invalid';
                              this.employeeCheckMessage = 'Employee number is required.';
                              return this.employeeCheckState;
                          }

                          if (employeeNo === currentEmployeeNo) {
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
                                  const url = `${this.employeeNoAvailabilityUrl}?employee_no=${encodeURIComponent(employeeNo)}`;
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
                              this.employeeCheckTimer = setTimeout(() => {
                                  runCheck();
                              }, 350);
                          }

                          return this.employeeCheckState;
                      },
                      isProfileSaveLocked() {
                          if (!this.editMode) return false;
                          if (!this.$refs.employee_no) return false;
                          if (this.hasEmployeeNoServerError) return true;
                          return ['invalid', 'unavailable', 'checking', 'error'].includes(this.employeeCheckState);
                      },
                      cancelEmailEdit() {
                          if ($refs.email) $refs.email.value = '';
                          if ($refs.email_change_only) $refs.email_change_only.value = '0';
                          this.emailCheckState = 'idle';
                          this.emailCheckMessage = '';
                          this.emailEditMode = false;
                      },
                      discard() {
                          if ($refs.first_name) $refs.first_name.value = this.original.first_name;
                          if ($refs.middle_name) $refs.middle_name.value = this.original.middle_name;
                          if ($refs.last_name) $refs.last_name.value = this.original.last_name;
                          if ($refs.suffix) $refs.suffix.value = this.original.suffix;
                          if ($refs.email) $refs.email.value = this.original.email;
                          if ($refs.department_id) $refs.department_id.value = this.original.department_id;
                          if ($refs.status) $refs.status.value = this.original.status;
                          if ($refs.employee_no) $refs.employee_no.value = this.original.employee_no;
                          if ($refs.employment_type) $refs.employment_type.value = this.original.employment_type;
                          if ($refs.rank_level_id) $refs.rank_level_id.value = this.original.rank_level_id;
                          if ($refs.teaching_rank) $refs.teaching_rank.value = this.original.teaching_rank;
                          if ($refs.highest_degree) $refs.highest_degree.value = this.original.highest_degree;
                          if ($refs.original_appointment_date) $refs.original_appointment_date.value = this.original.original_appointment_date;
                          this.emailEditMode = false;
                          this.emailCheckState = 'idle';
                          this.emailCheckMessage = '';
                          this.employeeCheckState = 'idle';
                          this.employeeCheckMessage = '';
                          this.hasEmployeeNoServerError = false;
                          if ($refs.email_change_only) $refs.email_change_only.value = '0';
                          this.editMode = false;
                      }
                  }">
                @csrf
                @method('PUT')
                <input type="hidden" name="email_change_only" value="0" x-ref="email_change_only">

                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="text-sm text-gray-500">
                                Current role:
                                <span class="font-semibold text-gray-800">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    x-show="!editMode"
                                    @click="enableEdit()"
                                    class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Edit Profile
                            </button>
                            <button type="button"
                                    x-show="editMode"
                                    @click="discard()"
                                    class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    x-show="editMode"
                                    :disabled="isProfileSaveLocked()"
                                    :class="isProfileSaveLocked() ? 'opacity-60 cursor-not-allowed' : ''"
                                    class="px-5 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft transition">
                                Save
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pb-4 text-xs text-gray-500">
                        <span x-show="!editMode">Fields are locked. Click <span class="font-semibold">Edit Profile</span> to unlock.</span>
                        <span x-show="editMode">Edit mode enabled. Save to apply changes or Cancel to discard.</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Account Information</h3>
                            <p class="text-sm text-gray-500">Email and login identity.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    x-show="!emailEditMode"
                                    @click="startEmailEdit()"
                                    :disabled="editMode"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition disabled:opacity-60 disabled:cursor-not-allowed">
                                Change Email
                            </button>
                            <button type="button"
                                    x-show="emailEditMode"
                                    @click="cancelEmailEdit()"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                                Cancel
                            </button>
                            <button type="button"
                                    x-show="emailEditMode"
                                    @click="saveEmailChange()"
                                    :disabled="emailCheckState !== 'valid'"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-bu text-white hover:bg-bu-dark transition disabled:opacity-60 disabled:cursor-not-allowed">
                                Save Email
                            </button>
                        </div>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Current Email</label>
                            <input type="email"
                                   value="{{ $user->email }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-100 text-gray-600"
                                   readonly
                                   disabled>
                        </div>

                        <div class="md:col-span-2" x-show="emailEditMode" x-cloak>
                            <label class="block text-sm font-medium text-gray-700">New Email</label>
                            <input x-ref="email"
                                   :disabled="!emailEditMode"
                                   :required="emailEditMode"
                                   type="email"
                                   name="email"
                                   value="{{ old('email', '') }}"
                                   @input="checkEmailAvailability()"
                                   @blur="checkEmailAvailability(true)"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu">

                            <p class="mt-2 text-xs"
                               x-show="emailCheckMessage !== ''"
                               x-bind:class="{
                                   'text-gray-500': emailCheckState === 'checking',
                                   'text-green-600': emailCheckState === 'valid',
                                   'text-red-600': ['invalid', 'unavailable', 'error'].includes(emailCheckState)
                               }"
                               x-text="emailCheckMessage"></p>

                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                        </div>

                        <div class="md:col-span-2" x-show="!emailEditMode">
                            <p class="text-xs text-gray-500">
                                Email is locked. Click <span class="font-semibold">Change Email</span> to set a new email.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">User Information</h3>
                        <p class="text-sm text-gray-500">Stored in the users table.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input x-ref="first_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="first_name"
                                   value="{{ old('first_name', $nameParts['first_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input x-ref="last_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="last_name"
                                   value="{{ old('last_name', $nameParts['last_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                            <input x-ref="middle_name"
                                   :disabled="!editMode"
                                   type="text"
                                   name="middle_name"
                                   value="{{ old('middle_name', $nameParts['middle_name'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Suffix</label>
                            <input x-ref="suffix"
                                   :disabled="!editMode"
                                   type="text"
                                   name="suffix"
                                   value="{{ old('suffix', $nameParts['suffix'] ?? '') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                        </div>

                        @if($showDepartment)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <select x-ref="department_id"
                                        :disabled="!editMode"
                                        name="department_id"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                        required>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected((string) old('department_id', $user->department_id) === (string) $dept->id)>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <input type="text"
                                   value="{{ ucfirst(str_replace('_', ' ', $user->role)) }}"
                                   class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-100 text-gray-600"
                                   disabled>
                            <p class="mt-2 text-xs text-gray-500">Role changes are managed during account provisioning.</p>
                        </div>
                    </div>
                </div>

                @if($showFacultyDetails)
                    <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Faculty Profile Details</h3>
                            <p class="text-sm text-gray-500">Saved to faculty_profiles and faculty_highest_degrees.</p>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Employee Number</label>
                                <input x-ref="employee_no"
                                       :disabled="!editMode"
                                       type="text"
                                       name="employee_no"
                                       inputmode="numeric"
                                       maxlength="8"
                                       pattern="\d{2}(0[1-9]|1[0-2])-\d{3}"
                                       @input="formatEmployeeNoInput(); autoSetOriginalAppointmentDateFromEmployeeNo(); checkEmployeeNoAvailability()"
                                       @change="autoSetOriginalAppointmentDateFromEmployeeNo(); checkEmployeeNoAvailability(true)"
                                       @blur="autoSetOriginalAppointmentDateFromEmployeeNo(); checkEmployeeNoAvailability(true)"
                                       value="{{ old('employee_no', $profile?->employee_no) }}"
                                       class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                       required>
                                @error('employee_no')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs mt-1"
                                   x-show="employeeCheckMessage !== '' && !hasEmployeeNoServerError"
                                   x-bind:class="{
                                       'text-gray-500': employeeCheckState === 'checking',
                                       'text-green-600': employeeCheckState === 'valid',
                                       'text-red-600': ['invalid', 'unavailable', 'error'].includes(employeeCheckState)
                                   }"
                                   x-text="employeeCheckMessage"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employment Type</label>
                                <select x-ref="employment_type"
                                        :disabled="!editMode"
                                        name="employment_type"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                        required>
                                    <option value="full_time" @selected(old('employment_type', $profile?->employment_type) === 'full_time')>Full-time</option>
                                    <option value="part_time" @selected(old('employment_type', $profile?->employment_type) === 'part_time')>Part-time</option>
                                </select>
                                @error('employment_type')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                @if($rankLevels->isNotEmpty())
                                    <label class="block text-sm font-medium text-gray-700">Academic Rank Level</label>
                                    <select x-ref="rank_level_id"
                                            :disabled="!editMode"
                                            name="rank_level_id"
                                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                            required>
                                        <option value="">Select Rank Level</option>
                                        @foreach($rankLevels as $level)
                                            <option value="{{ $level->id }}" @selected((string) old('rank_level_id', $profile?->rank_level_id) === (string) $level->id)>
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
                                           value="{{ old('teaching_rank', $profile?->teaching_rank) }}"
                                           class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                           required>
                                    @error('teaching_rank')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Highest Degree Earned</label>
                                <select x-ref="highest_degree"
                                        :disabled="!editMode"
                                        name="highest_degree"
                                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                        required>
                                    <option value="" disabled @selected(old('highest_degree', $highestDegree?->highest_degree) === null || old('highest_degree', $highestDegree?->highest_degree) === '')>
                                        Select highest degree earned
                                    </option>
                                    <option value="bachelors" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'bachelors')>Bachelor's</option>
                                    <option value="masters" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'masters')>Master's</option>
                                    <option value="doctorate" @selected(old('highest_degree', $highestDegree?->highest_degree) === 'doctorate')>Doctorate</option>
                                </select>
                                @error('highest_degree')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date of Original Appointment</label>
                                <input x-ref="original_appointment_date"
                                       :disabled="!editMode"
                                       type="date"
                                       name="original_appointment_date"
                                       readonly
                                       aria-readonly="true"
                                       value="{{ old('original_appointment_date', optional($profile?->original_appointment_date)->format('Y-m-d')) }}"
                                       class="mt-1 w-full rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600">
                                @error('original_appointment_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-2">
                                    Auto-filled from Employee Number using <span class="font-medium">YYMM</span> as <span class="font-medium">20YY-MM-01</span>.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-2xl shadow-card border border-gray-200">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Account Status</h3>
                        <p class="text-sm text-gray-500">Controls system access for this account.</p>
                    </div>

                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select x-ref="status"
                                :disabled="!editMode"
                                name="status"
                                class="mt-1 w-full md:w-1/2 rounded-xl border border-gray-300 bg-white focus:border-bu focus:ring-bu disabled:bg-gray-100 disabled:text-gray-600"
                                required>
                            <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
