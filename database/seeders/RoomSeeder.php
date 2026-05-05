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
                'name'       => 'Ruang Konferensi',
                'capacity'   => 20,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Ruang Meeting A',
                'capacity'   => 10,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Ruang Meeting B',
                'capacity'   => 10,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Ruang Direktur',
                'capacity'   => 6,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Aula',
                'capacity'   => 50,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Hanya insert jika tabel masih kosong
        if (DB::table('rooms')->count() === 0) {
            DB::table('rooms')->insert($rooms);
        }
    }
}
