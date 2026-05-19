<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\AutoCheckoutVisitors;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks — Smart VMS
|--------------------------------------------------------------------------
| Laravel 11+ mendaftarkan scheduled tasks di sini (bukan di Kernel.php).
| Pastikan cron sudah diset di server:
|   * * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
*/

// Auto-checkout semua visitor yang lupa check-out — berjalan setiap hari jam 23:59
Schedule::command(AutoCheckoutVisitors::class)
    ->dailyAt('23:59')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/auto-checkout.log'));
