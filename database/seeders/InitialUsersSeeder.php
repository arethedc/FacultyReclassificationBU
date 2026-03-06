<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */
        $departments = [
            'CITE',
            'CBAA',
            'CEHD',
            'CEDE',
            'CLAGE',
            'CNAHS',
        ];

        $departmentMap = Department::query()
            ->whereIn('name', $departments)
            ->pluck('id', 'name')
            ->toArray();

        foreach ($departments as $deptName) {
            if (isset($departmentMap[$deptName])) {
                continue;
            }
            $dept = Department::firstOrCreate(['name' => $deptName]);
            $departmentMap[$deptName] = $dept->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        $users = [
            [
                'name' => 'Test Faculty',
                'email' => 'faculty@test.com',
                'role' => 'faculty',
                'department' => 'CITE',
                'password' => 'test1234',
            ],
            [
                'name' => 'Test Dean',
                'email' => 'dean@test.com',
                'role' => 'dean',
                'department' => 'CEDE',
                'password' => 'test1234',
            ],
            [
                'name' => 'Test HR',
                'email' => 'hr@test.com',
                'role' => 'hr',
                'department' => null,
                'password' => 'test1234',
            ],
            [
                'name' => 'Test VPAA',
                'email' => 'vpaa@test.com',
                'role' => 'vpaa',
                'department' => null,
                'password' => 'test1234',
            ],
            [
                'name' => 'Test President',
                'email' => 'president@test.com',
                'role' => 'president',
                'department' => null,
                'password' => 'test1234',
            ],
            [
                'name' => 'Admin Test',
                'email' => 'admin@test.com',
                'role' => 'hr',
                'department' => null,
                'password' => 'admin123',
            ],
        ];

        foreach ($users as $user) {
            $seededUser = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'status' => 'active',
                    'department_id' => $user['department']
                        ? $departmentMap[$user['department']]
                        : null,
                    'password' => Hash::make($user['password'] ?? 'test1234'),
                ]
            );

            $seededUser->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
