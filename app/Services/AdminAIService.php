<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Carbon;

class AdminAIService
{
    /**
     * Bangun string konteks data real-time dari database
     * untuk diinjeksikan ke system prompt Gemini.
     */
    public function buildContext(): string
    {
        $today = Carbon::today();

        // ── 1. Tamu aktif (status = 'active') ─────────────────────────────
        $activeAppointments = Appointment::with(['visitor', 'pic', 'room'])
            ->where('status', 'active')
            ->whereDate('visit_date', $today)
            ->get();

        $activeCount = $activeAppointments->count();

        $activeList = $activeAppointments->map(function ($apt) {
            $name    = $apt->visitor?->name    ?? 'N/A';
            $purpose = $apt->purpose           ?? '-';
            $pic     = $apt->pic?->name        ?? '-';
            $room    = $apt->room?->name       ?? '-';
            $checkin = $apt->checkin_time      ?? '-';
            return "  • {$name} | Tujuan: {$purpose} | PIC: {$pic} | Ruang: {$room} | Check-in: {$checkin}";
        })->implode("\n");

        // ── 2. Statistik hari ini ──────────────────────────────────────────
        $todayTotal     = Appointment::whereDate('visit_date', $today)->count();
        $todayPending   = Appointment::whereDate('visit_date', $today)->where('status', 'pending')->count();
        $todayCompleted = Appointment::whereDate('visit_date', $today)->where('status', 'completed')->count();
        $todayWalkin    = Appointment::whereDate('visit_date', $today)->where('type', 'walk-in')->count();

        // ── 3. Tamu yang sudah check-out hari ini ─────────────────────────
        $completedList = Appointment::with(['visitor', 'pic'])
            ->where('status', 'completed')
            ->whereDate('visit_date', $today)
            ->get()
            ->map(fn($apt) => "  • {$apt->visitor?->name} | PIC: {$apt->pic?->name} | Keluar: {$apt->checkout_time}")
            ->implode("\n");

        // ── 4. Format konteks ──────────────────────────────────────────────
        $now = Carbon::now()->format('d F Y, H:i');

        $context = <<<CONTEXT
[KONTEKS SISTEM VISITA — {$now}]

STATISTIK HARI INI:
- Total janji temu  : {$todayTotal}
- Sedang aktif      : {$activeCount}
- Menunggu (pending): {$todayPending}
- Selesai checkout  : {$todayCompleted}
- Walk-in           : {$todayWalkin}

TAMU SEDANG CHECK-IN ({$activeCount} orang):
{$activeList}

TAMU SUDAH CHECKOUT HARI INI:
{$completedList}
CONTEXT;

        return trim($context);
    }
}
