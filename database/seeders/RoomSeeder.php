<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'name'        => 'Ruang Konferensi',
                'location'    => 'Lantai 1',
                'description' => 'Ruang konferensi utama (kapasitas 20 orang)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Ruang Meeting A',
                'location'    => 'Lantai 2',
                'description' => 'Ruang meeting kecil A (kapasitas 10 orang)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Ruang Meeting B',
                'location'    => 'Lantai 2',
                'description' => 'Ruang meeting kecil B (kapasitas 10 orang)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Ruang Direktur',
                'location'    => 'Lantai 3',
                'description' => 'Ruang khusus (kapasitas 6 orang)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Aula',
                'location'    => 'Lantai Dasar',
                'description' => 'Aula serbaguna (kapasitas 50 orang)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        // Hanya insert jika tabel masih kosong
        if (DB::table('rooms')->count() === 0) {
            DB::table('rooms')->insert($rooms);
        }
    }
}
