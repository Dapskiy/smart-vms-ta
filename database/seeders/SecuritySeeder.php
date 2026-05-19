<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SecuritySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role Security
        $role = Role::firstOrCreate([
            'name' => 'Security',
            'guard_name' => 'web',
        ]);

        // 2. Daftar permission spesifik untuk Satpam (Appointments & Summary/Visitor)
        $permissions = [
            'ViewAny:Appointment',
            'View:Appointment',
            'Create:Appointment',
            'Update:Appointment',
            'ViewAny:Visitor',
            'View:Visitor',
        ];

        // 3. Pastikan permission tersebut ada di database, lalu kaitkan ke role Security
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $role->syncPermissions($permissions);

        // 4. Buat / ambil user Satpam
        $user = User::firstOrCreate(
            ['email' => 'security@gmail.com'],
            [
                'name' => 'Pak Satpam',
                'password' => Hash::make('security'),
            ]
        );

        // 5. Assign role Security ke user tersebut
        $user->syncRoles([$role]);
    }
}
