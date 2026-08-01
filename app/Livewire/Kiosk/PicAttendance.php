<?php

namespace App\Livewire\Kiosk;

use App\Models\Pic;
use App\Models\PicAttendance as PicAttendanceLog;
use Livewire\Component;
use Livewire\Attributes\On;
class PicAttendance extends Component
{
    public string $lang = 'id';
    public $message = 'Memulai kamera...';
    public $messageType = 'info'; // info, success, error
    
    // Variables for holding temporary matched state to debounce the same person
    public $lastMatchedPicId = null;
    public $lastMatchedTime = null;

    #[On('setLang')]
    public function setLanguage($lang)
    {
        $this->lang = $lang;
    }

    #[On('process-pic-face')]
    public function processFace($descriptor, $location = 'SA')
    {
        if (!\App\Helpers\KioskHelper::isKioskLocal()) {
            $this->message = "Fitur absensi hanya dapat digunakan di jaringan lokal kantor.";
            $this->messageType = 'error';
            $this->dispatch('attendance-error', message: $this->message);
            return;
        }

        $pics = Pic::whereNotNull('face_features')->get();
        $bestMatch = null;
        $bestDistance = 1.0;
        $threshold = 0.55; // Relaxed threshold for robustness (lighting, angle, etc.)

        foreach ($pics as $pic) {
            $stored = $pic->face_features ?? [];
            if (!is_array($stored)) continue;
            if (isset($stored[0]) && !is_array($stored[0])) $stored = [$stored];

            foreach ($stored as $storedDescriptor) {
                $dist = $this->euclideanDistance($storedDescriptor, $descriptor);
                if ($dist < $bestDistance) {
                    $bestDistance = $dist;
                    $bestMatch = $pic;
                }
            }
        }

        if ($bestMatch && $bestDistance <= $threshold) {
            // Prevent spamming
            if ($this->lastMatchedPicId === $bestMatch->id && $this->lastMatchedTime && now()->diffInSeconds($this->lastMatchedTime) < 10) {
                return;
            }

            $this->lastMatchedPicId = $bestMatch->id;
            $this->lastMatchedTime = now();

            // Check latest attendance log today
            $latest = PicAttendanceLog::where('pic_id', $bestMatch->id)
                ->whereDate('checked_at', today())
                ->latest('checked_at')
                ->first();

            $isCheckingIn = ($latest === null || $latest->type === 'checkout');

            // Set attendance status to present/absent and record building location
            $bestMatch->is_available = $isCheckingIn;
            $bestMatch->current_location = $isCheckingIn ? $location : null;
            $bestMatch->save();

            // Log attendance record
            PicAttendanceLog::create([
                'pic_id' => $bestMatch->id,
                'type' => $isCheckingIn ? 'checkin' : 'checkout',
                'method' => 'kiosk',
                'location' => $location,
                'checked_at' => now(),
            ]);

            $statusText = $isCheckingIn ? 'Masuk' : 'Keluar';
            $locText = $isCheckingIn ? " di Gedung {$location}" : "";
            $this->message = "{$bestMatch->name} berhasil Absen {$statusText}{$locText}!";
            $this->messageType = 'success';
            
            // Dispatch event to show success visually
            $this->dispatch('attendance-success', message: $this->message, type: $isCheckingIn ? 'checkin' : 'checkout');
        } else {
            $this->message = "Wajah tidak dikenali dalam sistem PIC.";
            $this->messageType = 'error';
            $this->dispatch('attendance-error', message: $this->message);
        }
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $val) {
            $diff = $val - ($b[$i] ?? 0);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    public function render()
    {
        return <<<'HTML'
        <div style="display:none;"></div>
        HTML;
    }
}
