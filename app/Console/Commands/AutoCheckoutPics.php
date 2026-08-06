<?php

namespace App\Console\Commands;

use App\Models\Pic;
use App\Models\PicAttendanceLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCheckoutPics extends Command
{
    /**
     * Nama command artisan: php artisan pic:auto-checkout
     */
    protected $signature = 'pic:auto-checkout {--dry-run : Tampilkan data tanpa benar-benar mengubah database}';

    protected $description = 'Auto-checkout semua PIC yang masih berstatus tersedia (is_available=true) di akhir hari kerja';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('[AUTO-CHECKOUT PIC] Memulai proses auto-checkout...');
        $this->info('[AUTO-CHECKOUT PIC] Mode: ' . ($isDryRun ? 'DRY-RUN (tidak ada perubahan)' : 'LIVE'));

        // Cari semua PIC yang masih tersedia
        $activePics = Pic::where('is_available', true)->get();

        if ($activePics->isEmpty()) {
            $this->info('[AUTO-CHECKOUT PIC] Tidak ada PIC aktif. Tidak ada yang perlu di-checkout.');
            Log::info('[AUTO-CHECKOUT PIC] Tidak ada PIC aktif (tersedia) hari ini.');
            return self::SUCCESS;
        }

        $this->info("[AUTO-CHECKOUT PIC] Ditemukan {$activePics->count()} PIC aktif (belum checkout):");

        $count = 0;

        foreach ($activePics as $pic) {
            $this->line("  → [{$pic->id}] {$pic->name}");

            if (!$isDryRun) {
                // Update ketersediaan PIC menjadi false
                $pic->update([
                    'is_available' => false,
                    'current_location' => null,
                ]);

                // Buat log absen keluar
                PicAttendanceLog::create([
                    'pic_id' => $pic->id,
                    'type' => 'checkout',
                    'method' => 'system', // Ditandai sebagai checkout otomatis oleh sistem
                    'location' => 'System',
                    'checked_at' => now(),
                ]);

                $count++;
            }
        }

        if (!$isDryRun) {
            $msg = "[AUTO-CHECKOUT PIC] Berhasil auto-checkout {$count} PIC pada jam 23:59.";
            $this->info($msg);
            Log::info($msg, ['pic_ids' => $activePics->pluck('id')->toArray()]);
        } else {
            $this->warn('[AUTO-CHECKOUT PIC] Dry-run selesai. Tidak ada perubahan disimpan.');
        }

        return self::SUCCESS;
    }
}
