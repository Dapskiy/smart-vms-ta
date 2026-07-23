<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk mengklasifikasikan permission ke dalam menu/modul.
 *
 * Membuat record Menu jika belum ada, lalu meng-assign setiap Permission
 * ke menu yang sesuai berdasarkan suffix nama permission (setelah ":").
 */
class MenuPermissionClassifierSeeder extends Seeder
{
    public function run(): void
    {
        // ── Definisi kategori menu beserta ikon & urutan ──────────────
        $categories = [
            'Appointment' => [
                'slug'       => 'appointment',
                'icon'       => 'heroicon-o-calendar-days',
                'sort_order' => 1,
            ],
            'Visitor' => [
                'slug'       => 'visitor',
                'icon'       => 'heroicon-o-user-group',
                'sort_order' => 2,
            ],
            'Department' => [
                'slug'       => 'department',
                'icon'       => 'heroicon-o-building-office-2',
                'sort_order' => 3,
            ],
            'Pic' => [
                'slug'       => 'pic',
                'icon'       => 'heroicon-o-identification',
                'sort_order' => 4,
            ],
            'Room' => [
                'slug'       => 'room',
                'icon'       => 'heroicon-o-map-pin',
                'sort_order' => 5,
            ],
            'Dashboard' => [
                'slug'       => 'dashboard',
                'icon'       => 'heroicon-o-chart-bar',
                'sort_order' => 6,
                // Mapping khusus untuk permission widget dashboard
                'extra_permissions' => [
                    'View:GuestStatsOverview',
                    'View:VisitTrendChart',
                    'View:VisitPurposeChart',
                    'View:LatestGuestsTable',
                    'View:AdminAiChatWidget',
                ],
            ],
            'User' => [
                'slug'       => 'user',
                'icon'       => 'heroicon-o-users',
                'sort_order' => 7,
            ],
            'Role' => [
                'slug'       => 'role',
                'icon'       => 'heroicon-o-shield-check',
                'sort_order' => 8,
            ],
            'Permission' => [
                'slug'       => 'permission',
                'icon'       => 'heroicon-o-key',
                'sort_order' => 9,
            ],
            'Menu' => [
                'slug'       => 'menu',
                'icon'       => 'heroicon-o-bars-3',
                'sort_order' => 10,
            ],
            'FaceVerificationLog' => [
                'slug'       => 'face-verification-log',
                'icon'       => 'heroicon-o-document-text',
                'sort_order' => 11,
            ],
            'PicAttendance' => [
                'slug'       => 'pic-attendance',
                'icon'       => 'heroicon-o-clipboard-document-check',
                'sort_order' => 12,
            ],
        ];

        // ── Buat / update record Menu ────────────────────────────────
        $menuMap = []; // name => menu_id
        foreach ($categories as $name => $meta) {
            $menu = Menu::updateOrCreate(
                ['slug' => $meta['slug']],
                [
                    'name'       => $name,
                    'icon'       => $meta['icon'],
                    'is_active'  => true,
                    'sort_order' => $meta['sort_order'],
                ]
            );
            $menuMap[$name] = $menu->id;

            // Deskripsi untuk logging
            $this->command?->info("  Menu: {$name} (id={$menu->id})");
        }

        // ── Assign permission ke menu berdasarkan suffix ─────────────
        $permissions = Permission::all();
        $updated     = 0;

        foreach ($permissions as $perm) {
            // Format: "Action:Resource" — ambil bagian setelah ":"
            $parts    = explode(':', $perm->name, 2);
            $resource = $parts[1] ?? null;

            if ($resource && isset($menuMap[$resource])) {
                $perm->menu_id = $menuMap[$resource];
                $perm->save();
                $updated++;
                continue;
            }

            // Cek apakah masuk ke extra_permissions (misal widget Dashboard)
            foreach ($categories as $catName => $meta) {
                if (isset($meta['extra_permissions']) && in_array($perm->name, $meta['extra_permissions'])) {
                    $perm->menu_id = $menuMap[$catName];
                    $perm->save();
                    $updated++;
                    break;
                }
            }
        }

        $this->command?->info("✅ {$updated} permissions berhasil diklasifikasikan ke menu/modul.");
    }
}
