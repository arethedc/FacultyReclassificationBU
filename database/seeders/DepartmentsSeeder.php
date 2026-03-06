<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'CITE',
            'CBAA',
            'CEHD',
            'CEDE',
            'CLAGE',
            'CNAHS',
        ];

        foreach ($departments as $departmentName) {
            Department::updateOrCreate(
                ['name' => $departmentName],
                ['name' => $departmentName]
            );
        }
    }
}

