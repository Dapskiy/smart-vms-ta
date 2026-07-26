<?php

namespace App\Livewire\Kiosk;

use App\Models\Appointment;
use Carbon\Carbon;
use Livewire\Component;

class TokenCheckin extends Component
{
    public $token = '';
    public $errorMessage = '';

    protected $listeners = ['openTokenCheckin' => 'resetModal'];

    public function resetModal()
    {
        $this->token = '';
        $this->errorMessage = '';
        $this->dispatch('token-modal-opened');
    }

    public function submitToken()
    {
        $this->errorMessage = '';
        
        $token = trim($this->token);
        if (empty($token)) {
            $this->errorMessage = 'Token tidak boleh kosong.';
            return;
        }

        // Cari appointment berdasarkan token
        $appointment = Appointment::with(['visitor', 'pic.department', 'room'])
            ->where('token', $token)
            ->first();

        if (!$appointment) {
            $this->errorMessage = 'Token tidak valid atau tidak ditemukan.';
            return;
        }

        // Validasi Status
        if ($appointment->status === 'completed' || $appointment->status === 'checkout' || $appointment->status === 'inactive') {
            $this->errorMessage = 'Janji temu ini sudah selesai atau kedaluwarsa.';
            return;
        }

        if ($appointment->status === 'cancelled') {
            $this->errorMessage = 'Janji temu ini telah dibatalkan.';
            return;
        }

        // Jika walkin belum di-ACC
        if ($appointment->status === 'pending' && $appointment->type === 'walk-in') {
            $this->errorMessage = 'Janji temu (Walk-In) Anda masih menunggu persetujuan PIC.';
            return;
        }

        // Validasi Tanggal dan Jam (hanya untuk tipe appointment)
        if ($appointment->type === 'appointment') {
            $now = Carbon::now();
            $visitDate = Carbon::parse($appointment->visit_date);
            
            if ($now->toDateString() !== $visitDate->toDateString()) {
                $this->errorMessage = "Check-in hanya dapat dilakukan pada tanggal {$visitDate->translatedFormat('d F Y')}.";
                return;
            }

            // Validasi Jam (misalnya minimal 1 jam sebelumnya)
            $visitTimeStr = $appointment->should_book_room ? $appointment->room_start_time : $appointment->visit_time;
            if ($visitTimeStr) {
                $visitDateTime = $visitDate->setTimeFromTimeString($visitTimeStr);
                $checkInStart = $visitDateTime->copy()->subHour(); // 1 jam sebelumnya

                if ($now->isBefore($checkInStart)) {
                    $this->errorMessage = "Terlalu cepat. Check-in dimulai pukul {$checkInStart->format('H:i')}.";
                    return;
                }
            }
        }

        // Jika sudah aktif dan sudah ada checkin_time, berarti sudah check-in
        if ($appointment->status === 'active' && $appointment->checkin_time) {
            $this->errorMessage = 'Anda sudah melakukan check-in sebelumnya.';
            return;
        }

        // Semua valid, lakukan Check-In
        $appointment->update([
            'status' => 'active',
            'checkin_time' => now()->format('H:i'),
        ]);

        // Beritahu frontend untuk menutup modal dan memunculkan success popup
        $this->dispatch('token-checkin-success', appt: [
            'visitor_name' => $appointment->visitor->name,
            'company'      => $appointment->visitor->company ?? '-',
            'phone'        => $appointment->visitor->phone ?? '-',
            'pic_name'     => $appointment->pic->name ?? '-',
            'department'   => $appointment->pic->department->name ?? '-',
            'room_name'    => $appointment->room->name ?? '-',
            'visit_date'   => Carbon::parse($appointment->visit_date)->translatedFormat('d F Y'),
            'visit_time'   => $appointment->visit_time ? \Carbon\Carbon::parse($appointment->visit_time)->format('H:i') : '-',
            'checkin_time' => $appointment->checkin_time,
            'purpose'      => $appointment->purpose ?? '-',
        ]);
    }

    public function render()
    {
        return view('livewire.kiosk.token-checkin');
    }
}
