<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentApprovalController extends Controller
{
    /**
     * PIC menyetujui kunjungan walk-in via link email.
     */
    public function approve(string $token)
    {
        $appointment = Appointment::with(['visitor', 'pic'])
            ->where('approval_token', $token)
            ->first();

        if (!$appointment) {
            return view('appointments.approval-response', [
                'status'  => 'error',
                'title'   => 'Token Tidak Valid',
                'message' => 'Link persetujuan tidak valid atau sudah kadaluarsa.',
            ]);
        }

        if ($appointment->status !== 'pending') {
            $statusText = match ($appointment->status) {
                'active'    => 'sedang berkunjung',
                'rejected'  => 'sudah ditolak',
                'cancelled' => 'sudah dibatalkan',
                'completed' => 'sudah selesai',
                default     => 'sudah diproses',
            };

            return view('appointments.approval-response', [
                'status'      => 'already_processed',
                'title'       => 'Sudah Diproses',
                'message'     => "Kunjungan ini {$statusText} sebelumnya.",
                'appointment' => $appointment,
            ]);
        }
        
        // Expiry Link: Berlaku hingga pukul 23:59 pada hari kunjungan (visit_date).
        // Jika sudah berganti hari melewati visit_date, link hangus.
        $visitDate = \Carbon\Carbon::parse($appointment->visit_date);
        if (now()->startOfDay()->greaterThan($visitDate->startOfDay())) {
            return view('appointments.approval-response', [
                'status'  => 'error',
                'title'   => 'Link Kadaluarsa',
                'message' => 'Tautan persetujuan ini sudah tidak berlaku karena tanggal kunjungan telah lewat.',
            ]);
        }

        // Cek apakah sudah diapprove (untuk menghindari tombol ditekan berkali-kali namun pesan salah)
        if ($appointment->approved_at !== null) {
            return view('appointments.approval-response', [
                'status'      => 'already_processed',
                'title'       => 'Sudah Disetujui',
                'message'     => "Janji temu ini sudah Anda setujui sebelumnya.",
                'appointment' => $appointment,
            ]);
        }


        $appointment->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        if (!empty($appointment->visitor->phone)) {
            $msg = "Halo *{$appointment->visitor->name}*,\n\n";
            $msg .= "Kunjungan Anda telah *DISETUJUI* ✅ dengan detail berikut:\n";
            $msg .= "🏢 Menemui: {$appointment->pic->name}\n";
            $msg .= "📅 Tanggal: " . \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y') . "\n";
            $msg .= "⏰ Waktu: " . \Carbon\Carbon::parse($appointment->visit_time)->format('H:i') . " WIB\n";
            $msg .= "📝 Keperluan: {$appointment->purpose}\n\n";
            $msg .= "Silakan gunakan layar Kiosk (Menu Check-in) di Lobby saat Anda tiba.\n\n";
            $msg .= "Salam hangat,\nResepsionis VISITA";
            \App\Helpers\FonnteHelper::sendMessage($appointment->visitor->phone, $msg, 9);
        }

        return view('appointments.approval-response', [
            'status'      => 'approved',
            'title'       => 'Kunjungan Disetujui ✅',
            'message'     => 'Janji temu telah disetujui. Tamu dapat melakukan check-in melalui Kiosk saat tiba di lokasi.',
            'appointment' => $appointment,
        ]);
    }

    /**
     * PIC menolak kunjungan walk-in via link email.
     */
    public function reject(string $token)
    {
        $appointment = Appointment::with(['visitor', 'pic'])
            ->where('approval_token', $token)
            ->first();

        if (!$appointment) {
            return view('appointments.approval-response', [
                'status'  => 'error',
                'title'   => 'Token Tidak Valid',
                'message' => 'Link persetujuan tidak valid atau sudah kadaluarsa.',
            ]);
        }

        if ($appointment->status !== 'pending') {
            $statusText = match ($appointment->status) {
                'active'    => 'sudah disetujui',
                'rejected'  => 'sudah ditolak',
                'cancelled' => 'sudah dibatalkan',
                'completed' => 'sudah selesai',
                default     => 'sudah diproses',
            };

            return view('appointments.approval-response', [
                'status'      => 'already_processed',
                'title'       => 'Sudah Diproses',
                'message'     => "Kunjungan ini {$statusText} sebelumnya.",
                'appointment' => $appointment,
            ]);
        }
        
        // Expiry Link: Berlaku hingga pukul 23:59 pada hari kunjungan (visit_date).
        // Jika sudah berganti hari melewati visit_date, link hangus.
        $visitDate = \Carbon\Carbon::parse($appointment->visit_date);
        if (now()->startOfDay()->greaterThan($visitDate->startOfDay())) {
            return view('appointments.approval-response', [
                'status'  => 'error',
                'title'   => 'Link Kadaluarsa',
                'message' => 'Tautan persetujuan ini sudah tidak berlaku karena tanggal kunjungan telah lewat.',
            ]);
        }

        $appointment->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
        ]);

        if (!empty($appointment->visitor->phone)) {
            $msg = "Halo *{$appointment->visitor->name}*,\n\n";
            $msg .= "Mohon maaf, permintaan kunjungan Anda terpaksa *DITOLAK* ❌ dengan detail:\n";
            $msg .= "🏢 Menemui: {$appointment->pic->name}\n";
            $msg .= "📅 Tanggal: " . \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y') . "\n";
            $msg .= "⏰ Waktu: " . \Carbon\Carbon::parse($appointment->visit_time)->format('H:i') . " WIB\n";
            $msg .= "📝 Keperluan: {$appointment->purpose}\n\n";
            $msg .= "Alasan: PIC saat ini sedang tidak dapat ditemui. Mohon berkenan untuk menghubungi PIC Anda secara langsung guna mengatur ulang jadwal pertemuan (reschedule) di waktu yang lebih tepat.\n\n";
            $msg .= "Salam hangat,\nResepsionis VISITA";
            \App\Helpers\FonnteHelper::sendMessage($appointment->visitor->phone, $msg, 9);
        }

        return view('appointments.approval-response', [
            'status'      => 'rejected',
            'title'       => 'Kunjungan Ditolak',
            'message'     => 'Permintaan kunjungan telah ditolak. Tamu akan diberitahu melalui layar Kiosk.',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Endpoint polling JSON untuk Kiosk — cek status approval secara real-time.
     */
    public function status(string $token)
    {
        $appointment = Appointment::where('approval_token', $token)
            ->select(['id', 'status', 'approved_at', 'rejected_at', 'checkin_time'])
            ->first();

        if (!$appointment) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json([
            'status'        => $appointment->status,
            'check_in_time' => $appointment->checkin_time,
            'approved_at'   => $appointment->approved_at?->format('H:i'),
            'rejected_at'   => $appointment->rejected_at?->format('H:i'),
        ]);
    }
}
