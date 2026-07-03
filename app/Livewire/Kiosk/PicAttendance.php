<?php

namespace App\Livewire\Kiosk;

use App\Models\Pic;
use Livewire\Component;
use Livewire\Attributes\On;
class PicAttendance extends Component
{
    public $message = 'Memulai kamera...';
    public $messageType = 'info'; // info, success, error
    
    // Variables for holding temporary matched state to debounce the same person
    public $lastMatchedPicId = null;
    public $lastMatchedTime = null;

    #[On('process-pic-face')]
    public function processFace($descriptor)
    {
        $pics = Pic::whereNotNull('face_features')->get();
        $bestMatch = null;
        $bestDistance = 1.0;
        $threshold = 0.45; // Strict threshold for attendance

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
            // Prevent spamming the toggle if they just stood in front of the camera
            if ($this->lastMatchedPicId === $bestMatch->id && $this->lastMatchedTime && now()->diffInSeconds($this->lastMatchedTime) < 10) {
                return; // Ignore if scanned within last 10 seconds
            }

            $this->lastMatchedPicId = $bestMatch->id;
            $this->lastMatchedTime = now();

            // Toggle attendance status
            $isCheckIn = !$bestMatch->is_available;
            $bestMatch->is_available = $isCheckIn;
            $bestMatch->save();

            $statusText = $isCheckIn ? 'berhasil Check-in' : 'berhasil Check-out';
            $this->message = "{$bestMatch->name} {$statusText}!";
            $this->messageType = 'success';
            
            // Dispatch event to show success visually
            $this->dispatch('attendance-success', message: $this->message, type: $isCheckIn ? 'checkin' : 'checkout');
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
