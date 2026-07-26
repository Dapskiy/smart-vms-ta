<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Setting::count() === 0) {
            Setting::create([
                'company_name' => 'PT VISITA Enterprise',
            ]);
        }
    }
}
