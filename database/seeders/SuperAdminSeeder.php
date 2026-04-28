<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // pastikan role ada
        $role = Role::firstOrCreate([
            'name' => 'super_admin'
        ]);

        // buat user
        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin'),
            ]
        );

        // assign role
        $user->assignRole($role);
    }
}