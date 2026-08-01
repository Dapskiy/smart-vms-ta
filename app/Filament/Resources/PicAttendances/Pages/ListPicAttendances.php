<?php

namespace App\Filament\Resources\PicAttendances\Pages;

use App\Filament\Resources\PicAttendances\PicAttendanceResource;
use App\Models\Pic;
use App\Models\PicAttendance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListPicAttendances extends ListRecords
{
    protected static string $resource = PicAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scan_attendance')
                ->label('Absen Wajah')
                ->icon('heroicon-o-camera')
                ->color('success')
                ->modalHeading('Absensi Wajah PIC')
                ->modalContent(fn () => view('filament.admin.pic-attendance-scan'))
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
    }

    #[On('process-attendance-face')]
    public function processAttendanceFace(array $descriptor)
    {
        $currentUser = auth()->user();
        $targetPic = null;

        // 1. Determine target PIC and verify face features
        if ($currentUser && $currentUser->pic) {
            $targetPic = $currentUser->pic;
            if (empty($targetPic->face_features)) {
                $this->dispatch('attendance-scan-error', message: 'Wajah Anda belum terdaftar. Hubungi Admin.');
                return;
            }

            $stored = $targetPic->face_features;
            if (isset($stored[0]) && !is_array($stored[0])) {
                $stored = [$stored];
            }

            $matched = false;
            foreach ($stored as $storedDescriptor) {
                if ($this->euclideanDistance($storedDescriptor, $descriptor) <= 0.55) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $this->dispatch('attendance-scan-error', message: 'Wajah tidak cocok dengan akun Anda.');
                return;
            }
        } else {
            // Admin/Superadmin: match against all registered PICs
            $pics = Pic::whereNotNull('face_features')->get();
            $bestMatch = null;
            $bestDistance = 1.0;
            $threshold = 0.55;

            foreach ($pics as $pic) {
                $stored = $pic->face_features;
                if (!is_array($stored)) continue;
                if (isset($stored[0]) && !is_array($stored[0])) {
                    $stored = [$stored];
                }

                foreach ($stored as $storedDescriptor) {
                    $dist = $this->euclideanDistance($storedDescriptor, $descriptor);
                    if ($dist < $bestDistance) {
                        $bestDistance = $dist;
                        $bestMatch = $pic;
                    }
                }
            }

            if ($bestMatch && $bestDistance <= $threshold) {
                $targetPic = $bestMatch;
            } else {
                $this->dispatch('attendance-scan-error', message: 'Wajah tidak dikenali.');
                return;
            }
        }

        // 2. Check latest attendance log today
        $latest = PicAttendance::where('pic_id', $targetPic->id)
            ->whereDate('checked_at', today())
            ->latest('checked_at')
            ->first();

        $isCheckingIn = ($latest === null || $latest->type === 'checkout');

        // 3. Log checkin/checkout
        PicAttendance::create([
            'pic_id' => $targetPic->id,
            'type' => $isCheckingIn ? 'checkin' : 'checkout',
            'method' => 'admin',
            'checked_at' => now(),
        ]);

        $targetPic->is_available = $isCheckingIn;
        $targetPic->save();

        $statusText = $isCheckingIn ? 'Masuk' : 'Keluar';
        $this->dispatch('attendance-scan-success', message: "{$targetPic->name} berhasil Absen {$statusText}!");
        
        // Refresh table records
        $this->dispatch('refresh-table');
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
}

