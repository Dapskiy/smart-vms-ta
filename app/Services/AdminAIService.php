<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Pic;
use App\Models\PicAttendance;
use App\Models\User;
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

    // ══════════════════════════════════════════════════════════════
    //  RBAC CONTEXT & RECOMMENDATIONS
    // ══════════════════════════════════════════════════════════════

    /**
     * Bangun konteks RBAC (role & permissions) untuk user yang sedang login.
     * Diinjeksikan ke system prompt agar AI tahu batasan akses user.
     */
    public function buildRbacContext(User $user): string
    {
        $roles = $user->getRoleNames()->implode(', ') ?: 'Tidak ada role';
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        $context = "## KONTEKS RBAC PENGGUNA\n";
        $context .= "- Nama: {$user->name}\n";
        $context .= "- Role: {$roles}\n";
        $context .= "- Jumlah Permissions: " . count($permissions) . "\n\n";

        // Kategorikan permissions untuk AI
        $permGroups = [
            'view' => [],
            'create' => [],
            'update' => [],
            'delete' => [],
            'action' => [],
            'page' => [],
            'widget' => [],
        ];

        foreach ($permissions as $perm) {
            if (str_starts_with($perm, 'view_')) $permGroups['view'][] = $perm;
            elseif (str_starts_with($perm, 'create_')) $permGroups['create'][] = $perm;
            elseif (str_starts_with($perm, 'update_')) $permGroups['update'][] = $perm;
            elseif (str_starts_with($perm, 'delete_')) $permGroups['delete'][] = $perm;
            elseif (str_starts_with($perm, 'action:')) $permGroups['action'][] = $perm;
            elseif (str_starts_with($perm, 'page_')) $permGroups['page'][] = $perm;
            elseif (str_starts_with($perm, 'widget_')) $permGroups['widget'][] = $perm;
        }

        if (!empty($permGroups['view'])) {
            $context .= "**Boleh Melihat**: " . implode(', ', array_map(fn($p) => str_replace('view_', '', $p), $permGroups['view'])) . "\n";
        }
        if (!empty($permGroups['create'])) {
            $context .= "**Boleh Membuat**: " . implode(', ', array_map(fn($p) => str_replace('create_', '', $p), $permGroups['create'])) . "\n";
        }
        if (!empty($permGroups['update'])) {
            $context .= "**Boleh Mengubah**: " . implode(', ', array_map(fn($p) => str_replace('update_', '', $p), $permGroups['update'])) . "\n";
        }
        if (!empty($permGroups['delete'])) {
            $context .= "**Boleh Menghapus**: " . implode(', ', array_map(fn($p) => str_replace('delete_', '', $p), $permGroups['delete'])) . "\n";
        }

        $context .= "\n**ATURAN RBAC KETAT**:\n";
        $context .= "1. JANGAN memberikan data atau melakukan aksi di luar scope permission pengguna di atas.\n";
        $context .= "2. Jika pengguna meminta data/aksi yang tidak sesuai permission-nya, tolak dengan sopan dan jelaskan bahwa mereka tidak memiliki izin.\n";
        $context .= "3. Untuk PIC/Staff biasa, hanya tampilkan data yang berkaitan dengan diri mereka sendiri (tamu mereka, appointment mereka).\n";

        return $context;
    }

    /**
     * Bangun daftar aksi yang DIIZINKAN untuk user berdasarkan RBAC.
     * Hanya aksi yang termasuk di sini yang boleh dieksekusi AI.
     */
    public function getPermittedActions(User $user): string
    {
        $actions = [];

        // Cek permission granular untuk setiap aksi
        if ($user->can('update_appointment') || $user->can('action:Appointment')) {
            $actions[] = "- **Approve Appointment**: Menyetujui janji temu pending → `<!--EXEC_ACTION:{\"action\":\"approve_appointment\",\"appointment_id\":ID}-->`";
            $actions[] = "- **Reject Appointment**: Menolak janji temu pending → `<!--EXEC_ACTION:{\"action\":\"reject_appointment\",\"appointment_id\":ID}-->`";
            $actions[] = "- **Checkout Tamu**: Checkout tamu aktif → `<!--EXEC_ACTION:{\"action\":\"checkout_appointment\",\"appointment_id\":ID}-->`";
        }

        if ($user->can('update_visitor') || $user->can('action:Visitor')) {
            $actions[] = "- **Blacklist Visitor**: Mem-blacklist pengunjung → `<!--EXEC_ACTION:{\"action\":\"blacklist_visitor\",\"visitor_id\":ID,\"reason\":\"ALASAN\"}-->`";
        }

        // PIC bisa update status sendiri jika punya akun terkait PIC
        $currentPic = Pic::where('user_id', $user->id)->first();
        if ($currentPic) {
            $actions[] = "- **Update Ketersediaan Sendiri**: Ubah status available/sibuk → `<!--EXEC_ACTION:{\"action\":\"update_availability\",\"is_available\":true/false}-->`";
            $actions[] = "- **Update Lokasi Sendiri**: Ubah lokasi saat ini → `<!--EXEC_ACTION:{\"action\":\"update_location\",\"location\":\"LOKASI\"}-->`";
        }

        if (empty($actions)) {
            return "## AKSI YANG DIIZINKAN\nPengguna ini **TIDAK MEMILIKI** izin untuk mengeksekusi tindakan apapun melalui chatbot. Jika diminta, tolak dengan sopan.\n";
        }

        $actionList = implode("\n", $actions);
        return "## AKSI YANG DIIZINKAN UNTUK PENGGUNA INI\n"
            . "Sertakan marker JSON di akhir jawaban Anda HANYA jika admin secara eksplisit meminta aksi berikut:\n"
            . $actionList . "\n\n"
            . "**ATURAN**: JANGAN eksekusi aksi yang TIDAK ADA dalam daftar di atas. Jika diminta aksi lain, tolak dengan sopan.\n";
    }

    /**
     * Generate to-do list / rekomendasi berdasarkan role & data real-time.
     * Dipanggil saat panel chat dibuka untuk memberikan context awal.
     */
    public function buildRecommendations(User $user): array
    {
        $recommendations = [];
        $today = Carbon::today();
        $roles = $user->getRoleNames()->toArray();
        $currentPic = Pic::where('user_id', $user->id)->first();

        $isAdmin = in_array('super_admin', $roles) || in_array('admin', $roles) || $user->can('view_appointment');
        $isPic = $currentPic !== null;

        // ── Rekomendasi untuk Admin / Super Admin ──
        if ($isAdmin) {
            // Pending appointments
            $pendingCount = Appointment::where('status', 'pending')->count();
            if ($pendingCount > 0) {
                $recommendations[] = [
                    'icon' => '⏳',
                    'type' => 'warning',
                    'text' => "{$pendingCount} janji temu menunggu persetujuan",
                    'action' => 'Lihat daftar appointment pending',
                ];
            }

            // Active guests yang lama tidak checkout (> 4 jam)
            $longStay = Appointment::where('status', 'active')
                ->whereDate('visit_date', $today)
                ->whereNotNull('checkin_time')
                ->get()
                ->filter(function ($apt) {
                    $checkin = Carbon::parse($apt->visit_date . ' ' . $apt->checkin_time);
                    return $checkin->diffInHours(now()) >= 4;
                })->count();
            if ($longStay > 0) {
                $recommendations[] = [
                    'icon' => '🕐',
                    'type' => 'info',
                    'text' => "{$longStay} tamu sudah > 4 jam belum checkout",
                    'action' => 'Siapa tamu yang belum checkout?',
                ];
            }

            // Active guests count today
            $activeCount = Appointment::where('status', 'active')
                ->whereDate('visit_date', $today)->count();
            if ($activeCount > 0) {
                $recommendations[] = [
                    'icon' => '👥',
                    'type' => 'info',
                    'text' => "{$activeCount} tamu sedang berada di gedung",
                    'action' => 'Siapa saja yang sedang check-in?',
                ];
            }

            // PIC yang belum set lokasi hari ini
            $picsNoLocation = Pic::whereNull('current_location')
                ->orWhere('current_location', '')
                ->count();
            if ($picsNoLocation > 0) {
                $recommendations[] = [
                    'icon' => '📍',
                    'type' => 'suggestion',
                    'text' => "{$picsNoLocation} PIC belum update lokasi hari ini",
                    'action' => 'Daftar PIC yang belum set lokasi',
                ];
            }
        }

        // ── Rekomendasi untuk PIC / Staff ──
        if ($isPic) {
            // Appointment pending milik PIC ini
            $myPending = Appointment::where('pic_id', $currentPic->id)
                ->where('status', 'pending')->count();
            if ($myPending > 0) {
                $recommendations[] = [
                    'icon' => '📋',
                    'type' => 'warning',
                    'text' => "{$myPending} janji temu untuk Anda menunggu approval",
                    'action' => 'Tampilkan appointment pending saya',
                ];
            }

            // Tamu aktif milik PIC ini
            $myActive = Appointment::where('pic_id', $currentPic->id)
                ->where('status', 'active')
                ->whereDate('visit_date', $today)->count();
            if ($myActive > 0) {
                $recommendations[] = [
                    'icon' => '🧑‍💼',
                    'type' => 'info',
                    'text' => "{$myActive} tamu sedang menemui Anda",
                    'action' => 'Siapa tamu saya hari ini?',
                ];
            }

            // Reminder set availability
            if (!$currentPic->is_available) {
                $recommendations[] = [
                    'icon' => '🔴',
                    'type' => 'suggestion',
                    'text' => 'Status Anda: Tidak Tersedia. Apakah ingin mengubahnya?',
                    'action' => 'Ubah status saya menjadi tersedia',
                ];
            }

            // Reminder set lokasi
            if (empty($currentPic->current_location)) {
                $recommendations[] = [
                    'icon' => '📍',
                    'type' => 'suggestion',
                    'text' => 'Lokasi Anda belum diperbarui hari ini',
                    'action' => 'Update lokasi saya',
                ];
            }

            // Absensi hari ini
            $todayAttendance = PicAttendance::where('pic_id', $currentPic->id)
                ->whereDate('checked_at', $today)->exists();
            if (!$todayAttendance) {
                $recommendations[] = [
                    'icon' => '✅',
                    'type' => 'warning',
                    'text' => 'Anda belum melakukan absensi hari ini',
                    'action' => 'Bagaimana cara absensi?',
                ];
            }
        }

        // ── Fallback jika tidak ada rekomendasi ──
        if (empty($recommendations)) {
            $todayTotal = Appointment::whereDate('visit_date', $today)->count();
            $recommendations[] = [
                'icon' => '✨',
                'type' => 'info',
                'text' => "Semua beres! {$todayTotal} kunjungan terjadwal hari ini",
                'action' => 'Statistik hari ini',
            ];
        }

        return $recommendations;
    }

    /**
     * Jembatan data query-aware: deteksi intent dari pertanyaan admin
     * lalu tarik data yang paling relevan dari database.
     *
     * Digunakan oleh AdminChatController sebagai konteks tambahan
     * di atas buildContext() yang sudah berjalan sebagai baseline.
     */
    public function getDataForAI(string $query, ?\App\Models\Pic $currentPic = null, ?User $user = null): string
    {
        $parts = [];
        $queryLower = strtolower($query);

        // Determine RBAC scope: is this an admin or a limited PIC?
        $isAdmin = false;
        if ($user) {
            $roles = $user->getRoleNames()->toArray();
            $isAdmin = in_array('super_admin', $roles) || in_array('admin', $roles) || $user->can('view_appointment');
        }

        // Jika ada PIC login, tambahkan infonya sebagai prioritas
        if ($currentPic) {
            $parts[] = "KONTEKS PENGGUNA YANG BERTANYA (KAMU):\n"
                . "- Nama PIC: {$currentPic->name}\n"
                . "- ID PIC: {$currentPic->id}\n"
                . "- Status Ketersediaan Saat Ini: " . ($currentPic->is_available ? 'Tersedia' : 'Tidak Tersedia') . "\n"
                . "- Lokasi Saat Ini: " . ($currentPic->current_location ?? 'Belum diset') . "\n";
        }

        // ── Intent 1: Nama PIC spesifik disebut dalam query ───────────────
        $picNames = $this->extractPicNamesFromQuery($query);
        foreach ($picNames as $picName) {
            $parts[] = $this->getPicDetail($picName);
        }

        // ── Intent 2: Keyword umum tentang PIC / departemen ───────────────
        if (empty($picNames) && $this->matchesKeywords($queryLower, ['pic', 'person in charge', 'departemen', 'department', 'penanggung jawab'])) {
            if ($isAdmin) {
                $parts[] = $this->getAllPicSummary();
            } else {
                $parts[] = "Anda hanya bisa melihat data PIC yang berkaitan dengan akun Anda.";
            }
        }

        // ── Intent "Tamu saya" (PIC spesifik) ─────────────────────────────
        if ($currentPic && $this->matchesKeywords($queryLower, ['tamu saya', 'pengunjung saya', 'tamu ku', 'visitor saya', 'appointment saya'])) {
            $parts[] = $this->getMyAppointments($currentPic);
        }

        // ── Intent Aksi 1: Approve / Reject / Setujui / Tolak / Batal ─────────────────
        if ($this->matchesKeywords($queryLower, ['approve', 'setuju', 'tolak', 'reject', 'batal', 'cancel'])) {
            if ($isAdmin || $currentPic) {
                $parts[] = $this->getPendingAndActiveAppointmentsForAction($currentPic, $isAdmin);
            } else {
                $parts[] = "Anda tidak memiliki izin untuk melihat data approval.";
            }
        }

        // ── Intent Aksi 2: Blacklist / Blokir / Banned ───────────────────────────────
        if ($this->matchesKeywords($queryLower, ['blacklist', 'blokir', 'banned'])) {
            if ($isAdmin || ($user && $user->can('update_visitor'))) {
                $parts[] = $this->getVisitorsForBlacklistAction($query);
            } else {
                $parts[] = "Anda tidak memiliki izin untuk mengakses data blacklist.";
            }
        }

        // ── Intent Analitik 1: PIC Populer / Terpopuler / Paling sering dikunjungi ─────
        if ($this->matchesKeywords($queryLower, ['pic paling', 'sering dikunjungi', 'pic terpopuler', 'pic terfavorit'])) {
            if ($isAdmin) {
                $parts[] = $this->getTopPicsAnalytics();
            } else {
                $parts[] = "Anda tidak memiliki izin untuk melihat analitik PIC.";
            }
        }

        // ── Intent Analitik 2: Akurasi / Biometrik / Face Log / Scan Wajah ─────────────
        if ($this->matchesKeywords($queryLower, ['akurasi', 'accuracy', 'scan wajah', 'log verifikasi', 'biometrik', 'face log'])) {
            if ($isAdmin) {
                $parts[] = $this->getFaceVerificationAnalytics();
            } else {
                $parts[] = "Anda tidak memiliki izin untuk melihat data biometrik.";
            }
        }

        // ── Intent 3: Tamu / Check-in / Appointment aktif ─────────────────
        if ($this->matchesKeywords($queryLower, ['tamu', 'check-in', 'checkin', 'aktif', 'sedang masuk', 'sedang berkunjung', 'appointment', 'janji'])) {
            if ($isAdmin) {
                $parts[] = $this->getActiveAppointmentDetail();
            } elseif ($currentPic) {
                $parts[] = $this->getMyAppointments($currentPic);
            }
        }

        // ── Intent 4: Data visitor / pengunjung terdaftar ─────────────────
        if ($this->matchesKeywords($queryLower, ['visitor', 'pengunjung', 'terdaftar', 'blacklist', 'wajah', 'face'])) {
            if ($isAdmin || ($user && $user->can('view_visitor'))) {
                $parts[] = $this->getVisitorSummary($query);
            } else {
                $parts[] = "Anda tidak memiliki izin untuk melihat data visitor.";
            }
        }

        // ── Intent 5: Checkout / selesai hari ini ─────────────────────────
        if ($this->matchesKeywords($queryLower, ['checkout', 'check-out', 'pulang', 'selesai', 'keluar'])) {
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

    /** Dapatkan daftar pending dan active appointments untuk aksi approve/reject */
    private function getPendingAndActiveAppointmentsForAction(?Pic $currentPic = null, bool $isAdmin = true): string
    {
        $query = Appointment::with(['visitor', 'pic'])
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('created_at', 'desc')
            ->take(15);

        // Non-admin PIC hanya bisa lihat appointment miliknya
        if (!$isAdmin && $currentPic) {
            $query->where('pic_id', $currentPic->id);
        }

        $apts = $query->get();

        if ($apts->isEmpty()) {
            return "DATA JANJI TEMU UNTUK AKSI:\n- Tidak ada janji temu pending atau aktif saat ini.";
        }

        $lines = $apts->map(function ($a) {
            $statusStr = $a->status === 'pending' ? 'PENDING' : 'AKTIF';
            return "  • [ID: {$a->id}] Tamu: {$a->visitor?->name} (Visitor ID: {$a->visitor_id}) | PIC: {$a->pic?->name} | Status: {$statusStr} | Keperluan: {$a->purpose}";
        })->implode("\n");

        return "DATA JANJI TEMU UNTUK AKSI (PILIH ID YANG SESUAI):\n{$lines}";
    }

    /** Detail appointment milik PIC sendiri */
    private function getMyAppointments(Pic $pic): string
    {
        $today = Carbon::today();
        $apts = Appointment::with(['visitor'])
            ->where('pic_id', $pic->id)
            ->whereDate('visit_date', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($apts->isEmpty()) {
            return "APPOINTMENT ANDA HARI INI:\n- Tidak ada tamu yang dijadwalkan menemui Anda hari ini.";
        }

        $lines = $apts->map(function ($a) {
            $statusMap = ['pending' => 'PENDING', 'active' => 'AKTIF', 'completed' => 'SELESAI', 'rejected' => 'DITOLAK'];
            $statusStr = $statusMap[$a->status] ?? strtoupper($a->status);
            return "  • [ID: {$a->id}] Tamu: {$a->visitor?->name} | Status: {$statusStr} | Keperluan: {$a->purpose} | Waktu: {$a->visit_time}";
        })->implode("\n");

        $count = $apts->count();
        return "APPOINTMENT ANDA HARI INI ({$count}):\n{$lines}";
    }

    /** Cari visitor yang cocok untuk proses blacklist */
    private function getVisitorsForBlacklistAction(string $query): string
    {
        $visitors = Visitor::where('is_blacklisted', false)
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        if ($visitors->isEmpty()) {
            return "DATA VISITOR UNTUK BLACKLIST:\n- Tidak ada visitor terdaftar yang aktif.";
        }

        $lines = $visitors->map(fn($v) => "  • [ID: {$v->id}] Nama: {$v->name} | Instansi: " . ($v->company ?? '-'))->implode("\n");

        return "DAFTAR VISITOR AKTIF (PILIH ID UNTUK BLACKLIST):\n{$lines}";
    }

    /** Dapatkan analitik PIC yang paling sering dikunjungi */
    private function getTopPicsAnalytics(): string
    {
        $topPics = Appointment::select('pic_id', \DB::raw('count(*) as total'))
            ->groupBy('pic_id')
            ->orderBy('total', 'desc')
            ->with('pic')
            ->take(5)
            ->get();

        if ($topPics->isEmpty()) {
            return "ANALITIK PIC TERPOPULER:\n- Belum ada data kunjungan untuk menghitung PIC terpopuler.";
        }

        $lines = $topPics->map(function ($item, $index) {
            $rank = $index + 1;
            $name = $item->pic?->name ?? 'N/A';
            $dept = $item->pic?->department?->name ?? '-';
            return "  {$rank}. {$name} (Dept: {$dept}) - {$item->total} kunjungan";
        })->implode("\n");

        return "ANALITIK PIC PALING SERING DIKUNJUNGI:\n{$lines}";
    }

    /** Dapatkan analitik akurasi pemindaian wajah */
    private function getFaceVerificationAnalytics(): string
    {
        $totalScans = \App\Models\FaceVerificationLog::whereIn('type', ['checkin', 'checkout', 'qr-verify', 'walkin-verify'])->count();
        $successScans = \App\Models\FaceVerificationLog::whereIn('type', ['checkin', 'checkout', 'qr-verify', 'walkin-verify'])->where('is_success', true)->count();
        $successRate = $totalScans > 0 ? round(($successScans / $totalScans) * 100, 1) : 100.0;
        
        $avgDistance = \App\Models\FaceVerificationLog::whereIn('type', ['checkin', 'checkout', 'qr-verify', 'walkin-verify'])
            ->where('is_success', true)
            ->avg('euclidean_distance');
        $avgDistanceStr = $avgDistance !== null ? number_format($avgDistance, 4) : '0.0000';

        $failedLogs = \App\Models\FaceVerificationLog::where('is_success', false)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $failedList = $failedLogs->isEmpty()
            ? '  (tidak ada pemindaian gagal terbaru)'
            : $failedLogs->map(fn($log) => "  • Tamu: {$log->visitor_name} | Jarak: " . ($log->euclidean_distance ?? 'N/A') . " | Waktu: " . $log->created_at->format('d/m H:i'))->implode("\n");

        return "ANALITIK AKURASI SCAN WAJAH HARI INI:\n"
             . "- Persentase Sukses: {$successRate}%\n"
             . "- Rata-rata Euclidean Distance (Match): {$avgDistanceStr}\n"
             . "- Total Pemindaian: {$totalScans} (Sukses: {$successScans}, Gagal: " . ($totalScans - $successScans) . ")\n\n"
             . "PEMINDAIAN GAGAL TERBARU:\n{$failedList}";
    }
}
