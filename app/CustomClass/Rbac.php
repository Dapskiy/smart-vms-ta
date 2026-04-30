<?php

namespace App\CustomClass;

use Illuminate\Support\Facades\Route;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Support\Str;

class Rbac
{
    public static function generateRoleModule()
    {
        $routes = Route::getRoutes();
        $permissionsFound = [];

        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();
            foreach ($middlewares as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware, 'can:')) {
                    $permissionName = str_replace('can:', '', $middleware);
                    $permissionsFound[] = $permissionName;
                }
            }
        }

        // Ambil semua permission yang ada di DB dan normalisasi namanya
        $allDbPermissions = Permission::all();
        foreach ($allDbPermissions as $p) {
            $normalized = strtolower(str_replace(':', ' ', $p->name));
            $normalized = preg_replace('/\s+/', ' ', trim($normalized));
            if ($p->name !== $normalized) {
                $p->update(['name' => $normalized]);
            }
        }

        // Ambil lagi list nama setelah dinormalisasi
        $permissionsFound = array_merge($permissionsFound, Permission::whereNull('menu_id')->pluck('name')->toArray());
        $permissionsFound = array_unique($permissionsFound);

        foreach ($permissionsFound as $permissionName) {
            // Normalisasi nama permission: lowercase dan ganti : dengan spasi
            $normalizedName = strtolower(str_replace(':', ' ', $permissionName));
            $normalizedName = preg_replace('/\s+/', ' ', trim($normalizedName));

            // Split untuk mencari nama entity/menu
            $parts = explode(' ', $normalizedName, 2);
            
            if (count($parts) < 2) {
                $entityName = $normalizedName;
            } else {
                $entityName = $parts[1];
            }

            // Create or get Menu
            $menuName = ucwords(str_replace(['-', '_'], ' ', $entityName));
            $menu = Menu::firstOrCreate(
                ['name' => $menuName],
                [
                    'slug' => Str::slug($entityName),
                    'is_active' => true,
                    'sort_order' => 0
                ]
            );

            // Create or update Permission dengan nama yang sudah dinormalisasi
            Permission::updateOrCreate(
                ['name' => $normalizedName],
                [
                    'guard_name' => 'web',
                    'menu_id' => $menu->id,
                    'is_active' => true,
                    'description' => 'Generated from route middleware'
                ]
            );
        }

        return count($permissionsFound);
    }
}
