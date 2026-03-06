<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\FacultyHighestDegree;
use App\Models\RankLevel;
use App\Notifications\SetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    private function authorizeProfileAccess(User $viewer, User $subject): string
    {
        $viewerRole = strtolower((string) ($viewer->role ?? ''));
        abort_unless(in_array($viewerRole, ['hr', 'dean'], true), 403);

        if ($viewerRole === 'dean') {
            abort_unless($subject->role === 'faculty', 403);
            abort_unless(
                $viewer->department_id && $subject->department_id && (int) $viewer->department_id === (int) $subject->department_id,
                403
            );
        }

        return $viewerRole;
    }

    private function resolveEditContext(Request $request, string $viewerRole, User $subject): string
    {
        $context = strtolower(trim((string) $request->query('context', '')));
        if (in_array($context, ['faculty', 'users'], true)) {
            return $context;
        }

        if ($viewerRole === 'dean') {
            return 'faculty';
        }

        $referer = strtolower((string) url()->previous());
        if (str_contains($referer, '/faculty') || str_contains($referer, 'faculty-records')) {
            return 'faculty';
        }

        return 'users';
    }

    private function deriveOriginalAppointmentDateFromEmployeeNo(?string $employeeNo): ?string
    {
        if (!$employeeNo) {
            return null;
        }

        $employeeNo = trim($employeeNo);
        if (!preg_match('/^\d{4}-\d{3}$/', $employeeNo)) {
            return null;
        }

        $year = (int) substr($employeeNo, 0, 2);
        $month = (int) substr($employeeNo, 2, 2);
        if ($month < 1 || $month > 12) {
            return null;
        }

        return sprintf('%04d-%02d-01', 2000 + $year, $month);
    }

    private function findUserByEmailExcept(string $email, int $exceptUserId): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->whereKeyNot($exceptUserId)
            ->first();
    }

    private function resolveDuplicateCreateError(QueryException $exception): ?array
    {
        $message = mb_strtolower($exception->getMessage());

        $isEmailDuplicate = str_contains($message, 'users_email_unique')
            || str_contains($message, 'users.email')
            || str_contains($message, 'duplicate entry')
                && str_contains($message, 'for key')
                && str_contains($message, 'email');
        if ($isEmailDuplicate) {
            return [
                'field' => 'email',
                'message' => 'Email is already in use.',
            ];
        }

        $isEmployeeDuplicate = str_contains($message, 'faculty_profiles_employee_no_unique')
            || str_contains($message, 'faculty_profiles.employee_no')
            || str_contains($message, 'duplicate entry')
                && str_contains($message, 'for key')
                && str_contains($message, 'employee_no');
        if ($isEmployeeDuplicate) {
            return [
                'field' => 'employee_no',
                'message' => 'Employee number already exists.',
            ];
        }

        return null;
    }

    /* =====================================================
        USERS INDEX
    ===================================================== */
   public function index(Request $request)
{
    $q = trim((string) $request->get('q', ''));
    $status = $request->get('status', 'active'); // active | inactive | all
    $role = strtolower(trim((string) $request->get('role', ''))); // all | faculty | dean | hr | vpaa | president
    $allowedRoles = ['faculty', 'dean', 'hr', 'vpaa', 'president'];

    // ✅ guard allowed values
    if (!in_array($status, ['active', 'inactive', 'all'], true)) {
        $status = 'active';
    }
    if (!in_array($role, $allowedRoles, true)) {
        $role = '';
    }

    $usersQuery = User::query()->with(['department', 'facultyProfile']);

    // ✅ hide inactive by default
    if ($status !== 'all') {
        $usersQuery->where('status', $status);
    }
    if ($role !== '') {
        $usersQuery->where('role', $role);
    }

    if ($q !== '') {
        $like = $this->likeOperator();
        $usersQuery->where(function ($query) use ($q, $like) {
            $query->where('name', $like, "%{$q}%")
                  ->orWhere('email', $like, "%{$q}%")
                  ->orWhere('role', $like, "%{$q}%")
                  ->orWhereHas('facultyProfile', function ($fp) use ($q, $like) {
                      $fp->where('employee_no', $like, "%{$q}%");
                  });
        });
    }

    $users = $usersQuery
        ->latest()
        ->paginate(10)
        ->appends([
            'q' => $q,
            'status' => $status,
            'role' => $role,
        ]);

    return view('users.index', compact('users', 'q', 'status', 'role'));
}


    /* =====================================================
        CREATE USER / CREATE FACULTY
    ===================================================== */
    public function create()
    {
        $context = request('context'); // 'faculty' or null
        $viewer = request()->user();
        $isDean = $viewer && $viewer->role === 'dean';
        $defaultDepartmentId = $isDean ? $viewer->department_id : null;
        $forceRole = $isDean ? 'faculty' : null;
        $lockDepartment = $isDean;
        $actionRoute = $isDean ? route('dean.users.store') : route('users.store');
        $departments = $isDean && $defaultDepartmentId
            ? Department::where('id', $defaultDepartmentId)->get()
            : Department::orderBy('name')->get();

        return view('users.create', [
            'context' => $context,
            'departments' => $departments,
            'rankLevels' => Schema::hasTable('rank_levels')
                ? RankLevel::orderBy('order_no')->get()
                : collect(),
            'forceRole' => $forceRole,
            'lockDepartment' => $lockDepartment,
            'defaultDepartmentId' => $defaultDepartmentId,
            'actionRoute' => $actionRoute,
        ]);
    }

    /* =====================================================
        STORE USER
    ===================================================== */
    public function store(Request $request)
    {
        $isDean = $request->user()->role === 'dean';
        if ($isDean) {
            $departmentId = $request->user()->department_id;
            abort_unless($departmentId, 422);
            $request->merge([
                'role' => 'faculty',
                'department_id' => $departmentId,
            ]);
        }
        $request->merge([
            'email' => trim((string) $request->input('email')),
            'employee_no' => trim((string) $request->input('employee_no', '')),
        ]);
        $isManualPassword = $request->boolean('manual_password');

        $data = $request->validate([
            'role' => ['required', Rule::in(['faculty', 'dean', 'hr', 'vpaa', 'president'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'manual_password' => ['nullable', 'boolean'],

            'email' => 'required|email|unique:users,email',
            'password' => ['nullable', 'min:8', 'confirmed', Rule::requiredIf($isManualPassword)],

            // name parts
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:20',

            // department rules
            'department_id' => 'nullable|exists:departments,id|required_if:role,faculty,dean',

            // faculty-only
            'employee_no' => ['nullable', 'string', 'size:8', 'required_if:role,faculty', 'unique:faculty_profiles,employee_no', 'regex:/^\d{2}(0[1-9]|1[0-2])-\d{3}$/'],
            'employment_type' => 'nullable|in:full_time,part_time',
            'rank_level_id' => 'nullable|exists:rank_levels,id|required_if:role,faculty',
            'teaching_rank' => 'nullable|string|max:100',
            'original_appointment_date' => 'nullable|date',
            'highest_degree' => ['nullable', Rule::in(['bachelors', 'masters', 'doctorate'])],
        ], [
            'email.unique' => 'Email is already in use.',
            'employee_no.unique' => 'Employee number already exists.',
            'employee_no.regex' => 'Employee number must follow YYMM-XXX format.',
        ]);

        if (
            User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $data['email'])])
                ->exists()
        ) {
            return back()
                ->withErrors(['email' => 'Email is already in use.'])
                ->withInput();
        }

        if (($data['role'] ?? '') === 'faculty' && !empty($data['employee_no'])) {
            $duplicateEmployeeNo = FacultyProfile::query()
                ->where('employee_no', (string) $data['employee_no'])
                ->exists();
            if ($duplicateEmployeeNo) {
                return back()
                    ->withErrors(['employee_no' => 'Employee number already exists.'])
                    ->withInput();
            }
        }

        $fullName = trim(
            $data['first_name'] . ' ' .
            ($data['middle_name'] ? $data['middle_name'] . ' ' : '') .
            $data['last_name'] . ' ' .
            ($data['suffix'] ?? '')
        );
        $rawPassword = $isManualPassword
            ? (string) ($data['password'] ?? '')
            : Str::password(16);

        try {
            $user = DB::transaction(function () use ($data, $fullName, $rawPassword) {
                $createdUser = User::create([
                    'name' => $fullName,
                    'email' => $data['email'],
                    'password' => Hash::make($rawPassword),
                    'role' => $data['role'],
                    'status' => $data['status'] ?? 'active',
                    'department_id' => $data['department_id'] ?? null,
                ]);

                if ($createdUser->role === 'faculty') {
                    $rankTitle = null;
                    if (!empty($data['rank_level_id'])) {
                        $rankTitle = RankLevel::where('id', $data['rank_level_id'])->value('title');
                    }
                    $derivedOriginalAppointmentDate = $data['original_appointment_date'] ?? null;
                    if (empty($derivedOriginalAppointmentDate)) {
                        $derivedOriginalAppointmentDate = $this->deriveOriginalAppointmentDateFromEmployeeNo($data['employee_no'] ?? null);
                    }

                    FacultyProfile::create([
                        'user_id' => $createdUser->id,
                        'employee_no' => $data['employee_no'],
                        'employment_type' => $data['employment_type'] ?? 'full_time',
                        'rank_level_id' => $data['rank_level_id'] ?? null,
                        'teaching_rank' => $rankTitle ?? ($data['teaching_rank'] ?? 'Instructor'),
                        'original_appointment_date' => $derivedOriginalAppointmentDate,
                    ]);

                    if (!empty($data['highest_degree'])) {
                        FacultyHighestDegree::create([
                            'user_id' => $createdUser->id,
                            'highest_degree' => $data['highest_degree'],
                        ]);
                    }
                }

                return $createdUser;
            });
        } catch (QueryException $exception) {
            $duplicate = $this->resolveDuplicateCreateError($exception);
            if ($duplicate) {
                return back()
                    ->withErrors([$duplicate['field'] => $duplicate['message']])
                    ->withInput();
            }
            throw $exception;
        }

        $message = 'User created successfully.';
        if (!$isManualPassword) {
            $token = Password::broker()->createToken($user);
            try {
                $user->notify(new SetPasswordNotification($token));
                $message .= ' Invitation email sent with password setup link.';
            } catch (\Throwable $e) {
                $message .= ' Password setup email could not be sent. You may resend using Forgot Password.';
            }
        } elseif (method_exists($user, 'sendEmailVerificationNotification') && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $message .= ' Verification email sent.';
        }

        return redirect()
            ->route($isDean ? 'dean.faculty.index' : 'users.index')
            ->with('success', $message);
    }

    /* =====================================================
        EDIT USER
    ===================================================== */
    public function edit(Request $request, User $user)
    {
        $viewer = $request->user();
        $viewerRole = $this->authorizeProfileAccess($viewer, $user);
        $isDeanViewer = $viewerRole === 'dean';
        $editContext = $this->resolveEditContext($request, $viewerRole, $user);

        $departments = Department::orderBy('name')
            ->when(
                $isDeanViewer && $viewer->department_id,
                fn ($query) => $query->where('id', $viewer->department_id)
            )
            ->get();

        $user->load([
            'department',
            'facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
            'facultyHighestDegree',
        ]);

        if ($user->role === 'faculty' && !$user->facultyProfile) {
            $profile = FacultyProfile::create([
                'user_id' => $user->id,
                'employee_no' => 'TEMP-' . $user->id,
            ]);
            $user->setRelation('facultyProfile', $profile);
        }

        $rankLevels = Schema::hasTable('rank_levels')
            ? RankLevel::orderBy('order_no')->get()
            : collect();
        $highestDegree = $user->facultyHighestDegree;
        $nameParts = $this->splitName($user->name ?? '');
        $manageFacultyRoute = $viewerRole === 'dean'
            ? route('dean.faculty.index')
            : route('faculty.index');
        $manageUsersRoute = $viewerRole === 'dean'
            ? $manageFacultyRoute
            : route('users.index');
        $backRoute = $editContext === 'faculty' ? $manageFacultyRoute : $manageUsersRoute;
        $backLabel = $editContext === 'faculty' ? 'Back to Manage Faculties' : 'Back to Manage Users';

        return view('users.edit', compact(
            'user',
            'departments',
            'backRoute',
            'backLabel',
            'editContext',
            'nameParts',
            'rankLevels',
            'highestDegree',
        ));
    }

    /* =====================================================
        UPDATE USER
    ===================================================== */
    public function update(Request $request, User $user)
    {
        $viewer = $request->user();
        $viewerRole = $this->authorizeProfileAccess($viewer, $user);
        $editContext = $this->resolveEditContext($request, $viewerRole, $user);

        if ($request->boolean('email_change_only')) {
            $data = $request->validate([
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);

            $nextEmail = trim((string) $data['email']);
            $emailChanged = strcasecmp($nextEmail, (string) $user->email) !== 0;
            if (!$emailChanged) {
                return redirect()
                    ->route('users.edit', ['user' => $user, 'context' => $editContext])
                    ->with('success', 'Email is unchanged.');
            }

            $user->forceFill([
                'email' => $nextEmail,
            ])->save();

            return redirect()
                ->route('users.edit', ['user' => $user, 'context' => $editContext])
                ->with('success', 'Email updated successfully.');
        }

        $needsDepartment = in_array($user->role, ['faculty', 'dean']);
        $isFacultyUser = $user->role === 'faculty';
        $hasRankLevels = Schema::hasTable('rank_levels');

        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        if ($needsDepartment) {
            $rules['department_id'] = 'required|exists:departments,id';
        } else {
            $rules['department_id'] = 'nullable';
        }

        if ($isFacultyUser) {
            $profile = FacultyProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['employee_no' => 'TEMP-' . $user->id]
            );

            $rules['employee_no'] = [
                'required',
                'string',
                'size:8',
                'regex:/^\d{2}(0[1-9]|1[0-2])-\d{3}$/',
                Rule::unique('faculty_profiles', 'employee_no')->ignore($profile->id),
            ];
            $rules['employment_type'] = ['required', Rule::in(['full_time', 'part_time'])];
            $rules['original_appointment_date'] = 'nullable|date';
            $rules['highest_degree'] = ['required', Rule::in(['bachelors', 'masters', 'doctorate'])];

            if ($hasRankLevels) {
                $rules['rank_level_id'] = ['required', 'exists:rank_levels,id'];
            } else {
                $rules['teaching_rank'] = 'required|string|max:100';
                $rules['rank_step'] = ['nullable', Rule::in(['A', 'B', 'C'])];
            }
        }

        $data = $request->validate($rules, [
            'employee_no.regex' => 'Employee number must follow YYMM-XXX format.',
            'employee_no.unique' => 'Employee number already exists.',
        ]);

        if ($viewerRole === 'dean') {
            $data['department_id'] = $viewer->department_id;
        }

        if ($isFacultyUser) {
            $duplicateEmployeeNo = FacultyProfile::query()
                ->where('employee_no', (string) ($data['employee_no'] ?? ''))
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($duplicateEmployeeNo) {
                return back()
                    ->withErrors(['employee_no' => 'Employee number already exists.'])
                    ->withInput();
            }
        }

        $fullName = trim(
            $data['first_name'] . ' ' .
            ($data['middle_name'] ? $data['middle_name'] . ' ' : '') .
            $data['last_name'] . ' ' .
            ($data['suffix'] ?? '')
        );

        $user->update([
            'name' => $fullName,
            'status' => $data['status'],
            'department_id' => $needsDepartment ? $data['department_id'] : null,
        ]);

        if ($isFacultyUser) {
            $profile = $user->facultyProfile ?? FacultyProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['employee_no' => 'TEMP-' . $user->id]
            );

            $rankTitle = $profile->teaching_rank;
            if ($hasRankLevels) {
                $rankTitle = RankLevel::whereKey($data['rank_level_id'])->value('title') ?? $rankTitle;
            }

            $profile->update([
                'employee_no' => $data['employee_no'],
                'employment_type' => $data['employment_type'],
                'rank_level_id' => $hasRankLevels ? $data['rank_level_id'] : $profile->rank_level_id,
                'teaching_rank' => $hasRankLevels ? $rankTitle : $data['teaching_rank'],
                'rank_step' => $hasRankLevels ? null : ($data['rank_step'] ?? null),
                'original_appointment_date' => $data['original_appointment_date']
                    ?? $this->deriveOriginalAppointmentDateFromEmployeeNo($data['employee_no'] ?? null),
            ]);

            FacultyHighestDegree::updateOrCreate(
                ['user_id' => $user->id],
                ['highest_degree' => $data['highest_degree']]
            );
        }

        $message = 'Profile updated successfully.';

        return redirect()
            ->route('users.edit', ['user' => $user, 'context' => $editContext])
            ->with('success', $message);
    }

    public function emailAvailability(Request $request, User $user)
    {
        $viewer = $request->user();
        $this->authorizeProfileAccess($viewer, $user);

        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $email = trim((string) $data['email']);
        if (strcasecmp($email, (string) $user->email) === 0) {
            return response()->json([
                'available' => false,
                'message' => 'New email must be different from current email.',
            ]);
        }

        $owner = $this->findUserByEmailExcept($email, (int) $user->id);
        if (!$owner) {
            return response()->json([
                'available' => true,
                'message' => 'Email is available.',
            ]);
        }

        return response()->json([
            'available' => false,
            'message' => 'Email is already in use.',
        ]);
    }

    public function createEmailAvailability(Request $request)
    {
        $viewerRole = strtolower((string) ($request->user()?->role ?? ''));
        abort_unless(in_array($viewerRole, ['hr', 'dean'], true), 403);

        $data = $request->validate([
            'email' => 'required|email',
        ], [
            'email.email' => 'Enter a valid email address.',
        ]);

        $email = trim((string) $data['email']);
        $exists = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'Email is already in use.',
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Email is available.',
        ]);
    }

    public function createEmployeeNoAvailability(Request $request)
    {
        $viewerRole = strtolower((string) ($request->user()?->role ?? ''));
        abort_unless(in_array($viewerRole, ['hr', 'dean'], true), 403);

        $data = $request->validate([
            'employee_no' => ['required', 'regex:/^\d{2}(0[1-9]|1[0-2])-\d{3}$/'],
        ], [
            'employee_no.regex' => 'Employee number must follow YYMM-XXX format.',
        ]);

        $employeeNo = trim((string) $data['employee_no']);
        $exists = FacultyProfile::query()
            ->where('employee_no', $employeeNo)
            ->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'Employee number already exists.',
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Employee number is available.',
        ]);
    }

    public function employeeNoAvailability(Request $request, User $user)
    {
        $viewer = $request->user();
        $this->authorizeProfileAccess($viewer, $user);

        if ($user->role !== 'faculty') {
            return response()->json([
                'available' => false,
                'message' => 'Employee number check is for faculty profiles only.',
            ], 422);
        }

        $data = $request->validate([
            'employee_no' => ['required', 'regex:/^\d{2}(0[1-9]|1[0-2])-\d{3}$/'],
        ], [
            'employee_no.regex' => 'Employee number must follow YYMM-XXX format.',
        ]);

        $employeeNo = trim((string) $data['employee_no']);
        $user->loadMissing('facultyProfile');
        $currentProfileId = (int) ($user->facultyProfile?->id ?? 0);

        $exists = FacultyProfile::query()
            ->where('employee_no', $employeeNo)
            ->when($currentProfileId > 0, fn ($query) => $query->whereKeyNot($currentProfileId))
            ->exists();

        if (!$exists) {
            return response()->json([
                'available' => true,
                'message' => 'Employee number is available.',
            ]);
        }

        return response()->json([
            'available' => false,
            'message' => 'Employee number already exists.',
        ]);
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name));
        if (!$parts || count($parts) === 1) {
            return [
                'first_name' => $name,
                'middle_name' => '',
                'last_name' => '',
                'suffix' => '',
            ];
        }

        $suffixes = ['jr', 'sr', 'ii', 'iii', 'iv', 'v'];
        $suffix = '';
        $last = strtolower(end($parts));
        if (in_array($last, $suffixes, true)) {
            $suffix = array_pop($parts);
        }

        $first = array_shift($parts) ?? '';
        $last = array_pop($parts) ?? '';
        $middle = trim(implode(' ', $parts));

        return [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => $suffix,
        ];
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'hr', 403);
        $expectsJson = $request->expectsJson() || $request->ajax();

        if ((int) $request->user()->id === (int) $user->id) {
            if ($expectsJson) {
                return response()->json([
                    'message' => 'You cannot delete your own account.',
                ], 422);
            }

            return redirect()
                ->route('users.index')
                ->withErrors([
                    'user' => 'You cannot delete your own account.',
                ]);
        }

        $deletedName = (string) ($user->name ?? $user->email ?? 'User');
        $user->delete();

        if ($expectsJson) {
            return response()->json([
                'message' => "User deleted: {$deletedName}.",
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', "User deleted: {$deletedName}.");
    }
}
