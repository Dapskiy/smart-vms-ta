<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Pic;
use App\Models\Visitor;
use Illuminate\Support\Carbon;

class AdminAIService
{
    // ══════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ══════════════════════════════════════════════════════════════

    /**
     * Bangun string konteks data real-time dari database
     * untuk diinjeksikan ke system prompt Gemini (konteks global).
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

    /**
     * Jembatan data query-aware: deteksi intent dari pertanyaan admin
     * lalu tarik data yang paling relevan dari database.
     *
     * Digunakan oleh AdminChatController sebagai konteks tambahan
     * di atas buildContext() yang sudah berjalan sebagai baseline.
     */
    public function getDataForAI(string $query): string
    {
        $parts = [];

        // ── Intent 1: Nama PIC spesifik disebut dalam query ───────────────
        $picNames = $this->extractPicNamesFromQuery($query);
        foreach ($picNames as $picName) {
            $parts[] = $this->getPicDetail($picName);
        }

        // ── Intent 2: Keyword umum tentang PIC / departemen ───────────────
        if (empty($picNames) && $this->matchesKeywords($query, ['pic', 'person in charge', 'departemen', 'department', 'penanggung jawab'])) {
            $parts[] = $this->getAllPicSummary();
        }

        // ── Intent 3: Tamu / Check-in / Appointment aktif ─────────────────
        if ($this->matchesKeywords($query, ['tamu', 'check-in', 'checkin', 'aktif', 'sedang masuk', 'sedang berkunjung', 'appointment', 'janji'])) {
            $parts[] = $this->getActiveAppointmentDetail();
        }

        // ── Intent 4: Data visitor / pengunjung terdaftar ─────────────────
        if ($this->matchesKeywords($query, ['visitor', 'pengunjung', 'terdaftar', 'blacklist', 'wajah', 'face'])) {
            $parts[] = $this->getVisitorSummary($query);
        }

        // ── Intent 5: Checkout / selesai hari ini ─────────────────────────
        if ($this->matchesKeywords($query, ['checkout', 'check-out', 'pulang', 'selesai', 'keluar'])) {
            $parts[] = $this->getCompletedTodaySummary();
        }

        // ── Fallback: tidak ada intent spesifik → kirim dashboard summary ─
        if (empty($parts)) {
            $parts[] = $this->getDashboardSummary();
        }

        return implode("\n\n", array_filter($parts));
    }

    // ══════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS — Intent Handlers
    // ══════════════════════════════════════════════════════════════

    /** Cek apakah query mengandung salah satu keyword (case-insensitive) */
    private function matchesKeywords(string $query, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (stripos($query, $kw) !== false) return true;
        }
        return false;
    }

    /**
     * Ekstrak nama PIC potensial dari query:
     * Ambil semua nama PIC dari DB, cek apakah sebagian namanya muncul di query.
     */
    private function extractPicNamesFromQuery(string $query): array
    {
        $allPics = Pic::pluck('name');
        $matched = [];
        foreach ($allPics as $name) {
            // Cek kata pertama nama saja (misal "Wahyu" dari "Wahyu Septika")
            $firstName = explode(' ', $name)[0];
            if (strlen($firstName) >= 3 && stripos($query, $firstName) !== false) {
                $matched[] = $name;
            }
        }
        return array_unique($matched);
    }

    /** Detail lengkap satu PIC + appointment aktif yang ditanganinya */
    private function getPicDetail(string $picName): string
    {
        $pic = Pic::with('department')
            ->where('name', 'like', "%{$picName}%")
            ->first();

        if (!$pic) {
            return "PIC dengan nama \"{$picName}\" tidak ditemukan dalam sistem.";
        }

        $activeApts = Appointment::with('visitor')
            ->where('pic_id', $pic->id)
            ->where('status', 'active')
            ->whereDate('visit_date', Carbon::today())
            ->get();

        $aptList = $activeApts->isEmpty()
            ? '  (tidak ada tamu aktif saat ini)'
            : $activeApts->map(fn($a) => "  • {$a->visitor?->name} | Tujuan: {$a->purpose} | Checkin: {$a->checkin_time}")->implode("\n");

        $dept       = $pic->department?->name ?? '-';
        $available  = $pic->is_available ? 'Tersedia' : 'Tidak tersedia';
        $phone      = $pic->phone ?? '-';
        $email      = $pic->email ?? '-';
        $aptCount   = $activeApts->count();
        $picName    = $pic->name;

        return <<<PICINFO
Informasi PIC: {$picName}
- Departemen   : {$dept}
- Telepon      : {$phone}
- Email        : {$email}
- Status       : {$available}
- Tamu aktif hari ini ({$aptCount} orang):
{$aptList}
PICINFO;
    }

    /** Ringkasan semua PIC dan jumlah tamu aktif per PIC */
    private function getAllPicSummary(): string
    {
        $pics = Pic::with(['appointments' => fn($q) =>
            $q->where('status', 'active')->whereDate('visit_date', Carbon::today())
        ])->get();

        if ($pics->isEmpty()) return 'Belum ada data PIC dalam sistem.';

        $lines = $pics->map(function ($p) {
            $name   = $p->name;
            $dept   = $p->department?->name ?? '-';
            $count  = $p->appointments->count();
            return "  • {$name} | Dept: {$dept} | Tamu aktif: {$count}";
        })->implode("\n");

        $total = $pics->count();
        return "DAFTAR SEMUA PIC ({$total} orang):\n{$lines}";
    }

    /** Detail tamu yang sedang check-in hari ini */
    private function getActiveAppointmentDetail(): string
    {
        $apts = Appointment::with(['visitor', 'pic', 'room'])
            ->where('status', 'active')
            ->whereDate('visit_date', Carbon::today())
            ->orderBy('checkin_time')
            ->get();

        if ($apts->isEmpty()) {
            return 'Tidak ada tamu yang sedang check-in saat ini.';
        }

        $lines = $apts->map(function ($a) {
            $vName   = $a->visitor?->name    ?? '-';
            $picName = $a->pic?->name         ?? '-';
            $room    = $a->room?->name        ?? '-';
            $purpose = $a->purpose            ?? '-';
            $checkin = $a->checkin_time       ?? '-';
            return "  • {$vName} | PIC: {$picName} | Ruang: {$room} | Tujuan: {$purpose} | Masuk: {$checkin}";
        })->implode("\n");

        $count = $apts->count();
        return "TAMU SEDANG CHECK-IN ({$count} orang):\n{$lines}";
    }

    /** Ringkasan visitor terdaftar (termasuk filter blacklist jika disebutkan) */
    private function getVisitorSummary(string $query): string
    {
        $isBlacklistQuery = $this->matchesKeywords($query, ['blacklist', 'diblokir', 'banned']);

        if ($isBlacklistQuery) {
            $visitors = Visitor::where('is_blacklisted', true)->get();
            if ($visitors->isEmpty()) return 'Tidak ada visitor yang masuk daftar blacklist.';
            $lines = $visitors->map(function ($v) {
                $name    = $v->name;
                $company = $v->company ?? '-';
                $reason  = $v->blacklist_reason ?? '-';
                return "  • {$name} | {$company} | Alasan: {$reason}";
            })->implode("\n");
            $count = $visitors->count();
            return "VISITOR BLACKLISTED ({$count} orang):\n{$lines}";
        }

        $total      = Visitor::count();
        $withFace   = Visitor::whereNotNull('face_features')->count();
        $blacklisted = Visitor::where('is_blacklisted', true)->count();

        return <<<VISITORINFO
RINGKASAN VISITOR TERDAFTAR:
- Total visitor     : {$total}
- Sudah daftar wajah: {$withFace}
- Diblacklist       : {$blacklisted}
VISITORINFO;
    }

    /** Tamu yang sudah checkout hari ini */
    private function getCompletedTodaySummary(): string
    {
        $apts = Appointment::with(['visitor', 'pic'])
            ->where('status', 'completed')
            ->whereDate('visit_date', Carbon::today())
            ->orderBy('checkout_time')
            ->get();

        if ($apts->isEmpty()) return 'Belum ada tamu yang checkout hari ini.';

        $lines = $apts->map(function ($a) {
            $vName   = $a->visitor?->name   ?? '-';
            $picName = $a->pic?->name        ?? '-';
            $out     = $a->checkout_time    ?? '-';
            $method  = $a->checkout_method  ?? '-';
            return "  • {$vName} | PIC: {$picName} | Keluar: {$out} | Metode: {$method}";
        })->implode("\n");

        $count = $apts->count();
        return "TAMU SUDAH CHECKOUT HARI INI ({$count} orang):\n{$lines}";
    }

    /** Fallback: dashboard summary singkat */
    private function getDashboardSummary(): string
    {
        $today     = Carbon::today();
        $now       = Carbon::now()->format('d/m/Y H:i');
        $active    = Appointment::where('status', 'active')->whereDate('visit_date', $today)->count();
        $total     = Appointment::whereDate('visit_date', $today)->count();
        $pending   = Appointment::where('status', 'pending')->whereDate('visit_date', $today)->count();
        $completed = Appointment::where('status', 'completed')->whereDate('visit_date', $today)->count();

        return <<<DATA
RINGKASAN DASHBOARD HARI INI ({$now}):
- Total janji temu  : {$total}
- Sedang aktif      : {$active}
- Menunggu (pending): {$pending}
- Sudah checkout    : {$completed}
DATA;
    }
}
