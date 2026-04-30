<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Permission;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'heroicon-o-home',
                'sort_order' => 1,
            ],
            [
                'name' => 'Tamu',
                'slug' => 'visitors',
                'icon' => 'heroicon-o-user-group',
                'sort_order' => 2,
            ],
            [
                'name' => 'Konfigurasi',
                'slug' => 'config',
                'icon' => 'heroicon-o-cog-6-tooth',
                'sort_order' => 3,
            ],
        ];

        foreach ($menus as $menuData) {
            Menu::firstOrCreate(['slug' => $menuData['slug']], $menuData);
        }

        // Link existing permissions to menus if possible
        $configMenu = Menu::where('slug', 'config')->first();
        if ($configMenu) {
            Permission::where('name', 'like', '%permission%')
                ->orWhere('name', 'like', '%role%')
                ->orWhere('name', 'like', '%user%')
                ->orWhere('name', 'like', '%menu%')
                ->update(['menu_id' => $configMenu->id]);
        }
    }
}
