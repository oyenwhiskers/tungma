<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('Admin123!'),
                'role' => 'super_admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'superadmin@tungma.com'],
            [
                'name' => 'TungMa Superadmin',
                'username' => 'tungma_admin',
                'password' => Hash::make('stephen1118'),
                'role' => 'super_admin',
            ]
        );
    }
}
