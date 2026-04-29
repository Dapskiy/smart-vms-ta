<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Visitor;
use App\Models\Appointment;
use Carbon\Carbon;

Appointment::truncate();
Visitor::truncate();

$v1 = Visitor::create(['name' => 'Arya Santoso']); 
Appointment::create(['visitor_id' => $v1->id, 'purpose' => 'Wawancara HR', 'expected_arrival_time' => Carbon::today()->setTime(8, 30), 'status' => 'checked_in', 'visit_date' => Carbon::today()]); 

$v2 = Visitor::create(['name' => 'Dewi Kurniawati']); 
Appointment::create(['visitor_id' => $v2->id, 'purpose' => 'Rapat Vendor', 'expected_arrival_time' => Carbon::today()->setTime(9, 0), 'status' => 'pending', 'visit_date' => Carbon::today()]); 

$v3 = Visitor::create(['name' => 'Budi Rahardjo']); 
Appointment::create(['visitor_id' => $v3->id, 'purpose' => 'Presentasi Produk', 'expected_arrival_time' => Carbon::today()->setTime(9, 15), 'status' => 'approved', 'visit_date' => Carbon::today()]); 

$v4 = Visitor::create(['name' => 'Siti Lestari']); 
Appointment::create(['visitor_id' => $v4->id, 'purpose' => 'Kunjungan Klien', 'expected_arrival_time' => Carbon::today()->setTime(10, 0), 'status' => 'checked_in', 'visit_date' => Carbon::today()]);

echo "Done\n";
