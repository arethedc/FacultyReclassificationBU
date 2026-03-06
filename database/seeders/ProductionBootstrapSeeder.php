<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentsSeeder::class,
            RankLevelsSeeder::class,
        ]);

        $email = (string) env('DEFAULT_ADMIN_EMAIL', 'admin@test.com');
        $password = (string) env('DEFAULT_ADMIN_PASSWORD', 'admin123');

        if ($email !== '' && $password !== '') {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Admin',
                    'role' => 'hr',
                    'status' => 'active',
                    'password' => Hash::make($password),
                ]
            );

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
