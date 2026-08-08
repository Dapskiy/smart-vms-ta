<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FacePhotoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Buat permission ViewVisitorFacePhoto
        $permission = Permission::firstOrCreate([
            'name' => 'ViewVisitorFacePhoto',
            'guard_name' => 'web',
        ]);

        // Assign ke super_admin dan admin
        foreach (['super_admin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && !$role->hasPermissionTo('ViewVisitorFacePhoto')) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
