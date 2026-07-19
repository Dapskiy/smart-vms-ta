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

        $appointment->update([
            'status'        => 'active',
            'checkin_time'  => now()->format('H:i:s'),
            'approved_at'   => now(),
        ]);

        return view('appointments.approval-response', [
            'status'      => 'approved',
            'title'       => 'Kunjungan Disetujui ✅',
            'message'     => 'Tamu telah diizinkan masuk. Layar Kiosk akan otomatis menampilkan konfirmasi.',
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

        $appointment->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
        ]);

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
