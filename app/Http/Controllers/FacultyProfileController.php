<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FacultyProfile;
use App\Models\FacultyHighestDegree;
use App\Models\RankLevel;
use App\Models\ReclassificationApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class FacultyProfileController extends Controller
{
    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function recordsIndex(Request $request)
    {
        $viewer = $request->user();
        $viewerRole = strtolower((string) $viewer->role);
        abort_unless(in_array($viewerRole, ['hr', 'dean', 'vpaa', 'president'], true), 403);

        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');

        $query = User::query()
            ->where('role', 'faculty')
            ->with(['department', 'facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : '')]);

        if ($viewerRole === 'dean') {
            $departmentId = $viewer->department_id;
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if (!empty($rankLevelId)) {
            $query->whereHas('facultyProfile', function ($profile) use ($rankLevelId) {
                $profile->where('rank_level_id', $rankLevelId);
            });
        }

        if ($q !== '') {
            $like = $this->likeOperator();
            $query->where(function ($sub) use ($q, $like) {
                $sub->where('name', $like, "%{$q}%")
                    ->orWhere('email', $like, "%{$q}%")
                    ->orWhereHas('facultyProfile', function ($fp) use ($q, $like) {
                        $fp->where('employee_no', $like, "%{$q}%");
                    });
            });
        }

        $faculty = $query
            ->latest()
            ->paginate(12)
            ->appends([
                'q' => $q,
                'department_id' => $departmentId,
                'rank_level_id' => $rankLevelId,
            ]);

        $departments = \App\Models\Department::orderBy('name')->get();
        if ($viewerRole === 'dean' && $viewer->department_id) {
            $departments = $departments->where('id', $viewer->department_id)->values();
            $departmentId = $viewer->department_id;
        }

        $rankLevels = Schema::hasTable('rank_levels')
            ? RankLevel::orderBy('order_no')->get()
            : collect();
        $showDepartmentFilter = $viewerRole !== 'dean';
        $indexRoute = route('reclassification.faculty-records');

        return view('faculty_profiles.records-index', compact(
            'faculty',
            'q',
            'departments',
            'rankLevels',
            'departmentId',
            'rankLevelId',
            'showDepartmentFilter',
            'indexRoute'
        ));
    }

    public function index(Request $request)
    {
        $viewer = $request->user();
        $viewerRole = strtolower((string) $viewer->role);
        abort_unless(in_array($viewerRole, ['hr', 'dean', 'vpaa', 'president'], true), 403);

        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'active'); // active | inactive | all
        $departmentId = $request->get('department_id');
        $rankLevelId = $request->get('rank_level_id');

        // ✅ guard allowed values (prevents invalid filters)
        if (!in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }

        $query = User::query()
            ->where('role', 'faculty')
            ->with(['department', 'facultyProfile' . (\Illuminate\Support\Facades\Schema::hasTable('rank_levels') ? '.rankLevel' : '')]);

        // ✅ hide inactive by default
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($viewerRole === 'dean') {
            $departmentId = $viewer->department_id;
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if (!empty($rankLevelId)) {
            $query->whereHas('facultyProfile', function ($profile) use ($rankLevelId) {
                $profile->where('rank_level_id', $rankLevelId);
            });
        }

        // ✅ search (name/email/employee no)
        if ($q !== '') {
            $like = $this->likeOperator();
            $query->where(function ($sub) use ($q, $like) {
                $sub->where('name', $like, "%{$q}%")
                    ->orWhere('email', $like, "%{$q}%")
                    ->orWhereHas('facultyProfile', function ($fp) use ($q, $like) {
                        $fp->where('employee_no', $like, "%{$q}%");
                    });
            });
        }

        $faculty = $query
            ->latest()
            ->paginate(10)
            ->appends([
                'q' => $q,
                'status' => $status,
                'department_id' => $departmentId,
                'rank_level_id' => $rankLevelId,
            ]);

        $departments = \App\Models\Department::orderBy('name')->get();
        if ($viewerRole === 'dean' && $viewer->department_id) {
            $departments = $departments->where('id', $viewer->department_id)->values();
            $departmentId = $viewer->department_id;
        }
        $rankLevels = \Illuminate\Support\Facades\Schema::hasTable('rank_levels')
            ? \App\Models\RankLevel::orderBy('order_no')->get()
            : collect();
        $showDepartmentFilter = $viewerRole !== 'dean';
        $canManageFaculty = in_array($viewerRole, ['hr', 'dean'], true);
        $createFacultyRoute = match ($viewerRole) {
            'hr' => route('users.create', ['context' => 'faculty']),
            'dean' => route('dean.users.create'),
            default => null,
        };
        $indexRoute = $viewerRole === 'dean'
            ? route('dean.faculty.index')
            : route('faculty.index');

        return view('faculty_profiles.index', compact(
            'faculty',
            'q',
            'status',
            'departments',
            'rankLevels',
            'departmentId',
            'rankLevelId',
            'viewerRole',
            'showDepartmentFilter',
            'canManageFaculty',
            'createFacultyRoute',
            'indexRoute',
        ));
    }

    public function records(Request $request, User $user)
    {
        $viewer = $request->user();
        $viewerRole = strtolower((string) ($viewer->role ?? ''));
        abort_unless(in_array($viewerRole, ['hr', 'dean', 'vpaa', 'president'], true), 403);
        abort_unless($user->role === 'faculty', 404);

        if ($viewerRole === 'dean') {
            abort_unless(
                $viewer->department_id && $user->department_id && (int) $viewer->department_id === (int) $user->department_id,
                403
            );
        }

        $user->load([
            'department',
            'facultyProfile' . (Schema::hasTable('rank_levels') ? '.rankLevel' : ''),
        ]);

        $applications = ReclassificationApplication::query()
            ->where('faculty_user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->with(['period', 'approvedBy'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        $indexRoute = route('reclassification.faculty-records');

        return view('faculty_profiles.records', compact(
            'user',
            'applications',
            'viewerRole',
            'indexRoute',
        ));
    }

    public function edit(User $user)
    {
        $viewer = request()->user();
        $viewerRole = strtolower((string) ($viewer->role ?? ''));
        abort_unless(in_array($viewerRole, ['hr', 'dean'], true), 403);
        if ($viewerRole === 'dean') {
            abort_unless(
                $viewer->department_id && $user->department_id && (int) $viewer->department_id === (int) $user->department_id,
                403
            );
        }

        abort_unless($user->role === 'faculty', 404);

        $user->load(['facultyProfile.rankLevel', 'department', 'facultyHighestDegree']);

        // ✅ Ensure profile exists
        $profile = $user->facultyProfile ?? FacultyProfile::create([
            'user_id' => $user->id,
            'employee_no' => 'TEMP-' . $user->id,
        ]);

        $highestDegree = $user->facultyHighestDegree;
        $rankLevels = Schema::hasTable('rank_levels')
            ? RankLevel::orderBy('order_no')->get()
            : collect();

        return view('faculty_profiles.edit', compact('user', 'profile', 'highestDegree', 'rankLevels'));
    }

    public function update(Request $request, User $user)
    {
        $viewer = $request->user();
        $viewerRole = strtolower((string) ($viewer->role ?? ''));
        abort_unless(in_array($viewerRole, ['hr', 'dean'], true), 403);
        if ($viewerRole === 'dean') {
            abort_unless(
                $viewer->department_id && $user->department_id && (int) $viewer->department_id === (int) $user->department_id,
                403
            );
        }

        abort_unless($user->role === 'faculty', 404);

        $profile = FacultyProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['employee_no' => 'TEMP-' . $user->id]
        );

        $hasRankLevels = Schema::hasTable('rank_levels');

        $rules = [
            'employee_no' => 'required|string|max:50|unique:faculty_profiles,employee_no,' . $profile->id,
            'employment_type' => ['required', Rule::in(['full_time', 'part_time'])],
            'original_appointment_date' => 'nullable|date',
            'highest_degree' => ['nullable', Rule::in(['bachelors', 'masters', 'doctorate'])],
        ];

        if ($hasRankLevels) {
            $rules['rank_level_id'] = ['required', 'exists:rank_levels,id'];
        } else {
            $rules['teaching_rank'] = 'required|string|max:100';
            $rules['rank_step'] = ['nullable', Rule::in(['A', 'B', 'C'])];
        }

        $data = $request->validate($rules);

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
            'original_appointment_date' => $data['original_appointment_date'] ?? null,
        ]);

        if (!empty($data['highest_degree'])) {
            FacultyHighestDegree::updateOrCreate(
                ['user_id' => $user->id],
                ['highest_degree' => $data['highest_degree']]
            );
        }

        return redirect()
            ->route('users.edit', ['user' => $user, 'context' => 'faculty'])
            ->with('success', 'Faculty profile updated successfully.');
    }
}
