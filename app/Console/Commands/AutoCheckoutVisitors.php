<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCheckoutVisitors extends Command
{
    /**
     * Nama command artisan: php artisan visitor:auto-checkout
     */
    protected $signature = 'visitor:auto-checkout {--dry-run : Tampilkan data tanpa benar-benar mengubah database}';

    protected $description = 'Auto-checkout semua visitor yang masih aktif (status=active) di akhir hari kerja';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('[AUTO-CHECKOUT] Memulai proses auto-checkout...');
        $this->info('[AUTO-CHECKOUT] Mode: ' . ($isDryRun ? 'DRY-RUN (tidak ada perubahan)' : 'LIVE'));

        // Cari semua appointment yang masih aktif (visitor belum check-out)
        $activeAppointments = Appointment::where('status', 'active')
            ->whereDate('visit_date', '<=', today())
            ->with('visitor')
            ->get();

        if ($activeAppointments->isEmpty()) {
            $this->info('[AUTO-CHECKOUT] Tidak ada visitor aktif. Tidak ada yang perlu di-checkout.');
            Log::info('[AUTO-CHECKOUT] Tidak ada visitor aktif hari ini.');
            return self::SUCCESS;
        }

        $this->info("[AUTO-CHECKOUT] Ditemukan {$activeAppointments->count()} visitor aktif:");

        $checkoutTime = '23:59';
        $count        = 0;

        foreach ($activeAppointments as $appointment) {
            $visitorName = $appointment->visitor?->name ?? "ID#{$appointment->visitor_id}";
            $this->line("  → [{$appointment->id}] {$visitorName}");

            if (!$isDryRun) {
                $appointment->update([
                    'status'          => 'completed',
                    'checkout_time'   => $checkoutTime,
                    'checkout_method' => 'system',
                ]);
                $count++;
            }
        }

        if (!$isDryRun) {
            $msg = "[AUTO-CHECKOUT] Berhasil auto-checkout {$count} visitor pada jam {$checkoutTime}.";
            $this->info($msg);
            Log::info($msg, ['appointment_ids' => $activeAppointments->pluck('id')->toArray()]);
        } else {
            $this->warn('[AUTO-CHECKOUT] Dry-run selesai. Tidak ada perubahan disimpan.');
        }

        return self::SUCCESS;
    }
}
