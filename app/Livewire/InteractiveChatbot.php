<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Department;
use App\Models\Pic;
use App\Models\Visitor;
use App\Models\Appointment;
use App\Services\VisitIdService;
use App\Mail\PicApprovalMail;

class InteractiveChatbot extends Component
{
    /** @var string Pesan yang sedang diketik user */
    public string $inputMessage = '';

    /** @var array Riwayat chat: [['role' => 'user'|'assistant', 'content' => '...']] */
    public array $messages = [];

    /** @var bool Sedang menunggu respons bot */
    public bool $isLoading = false;

    /** @var string|null Pesan error jika API gagal */
    public ?string $error = null;

    /** @var string Bahasa Kiosk (id|en) */
    public string $lang = 'id';

    /** @var string Kiosk Location (SA|SB|GKT) */
    public string $kioskLocation = '';

    #[On('setLang')]
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    #[On('chatbot-receive-message')]
    public function receiveMessage($message, $location = null)
    {
        if ($location) {
            $this->kioskLocation = $location;
        }
        $this->inputMessage = $message;
        $this->sendMessage();
    }

    // ── Registration State ────────────────────────────────────────
    /** @var array Collected registration data from AI conversation */
    public array $regData = [];

    /** @var bool Show confirmation card in chat */
    public bool $showConfirmation = false;

    /** @var array PIC names for server-side response sanitization (not persisted to client) */
    private array $picNamesForSanitization = [];

    /**
     * Membangun System Prompt secara dinamis dengan menyuntikkan data kehadiran
     * PIC real-time dari database. Data sensitif (email, telepon) dikecualikan
     * secara eksplisit untuk menjaga privasi di lingkungan Kiosk publik.
     */
    private function getSystemPrompt(): string
    {
        // ── Base Instruction ──────────────────────────────────────────────────
        $todayDate = now()->locale('id')->translatedFormat('l, d F Y');
        $currentTime = now()->format('H:i');
        $hour = now()->setTimezone('Asia/Jakarta')->format('H');
        
        if ($this->lang === 'en') {
            $timeGreeting = 'Good Morning';
            if ($hour >= 11 && $hour < 15) {
                $timeGreeting = 'Good Afternoon';
            } elseif ($hour >= 15 && $hour < 18) {
                $timeGreeting = 'Good Evening';
            } elseif ($hour >= 18) {
                $timeGreeting = 'Good Night';
            }
        } else {
            $timeGreeting = 'Selamat Pagi';
            if ($hour >= 11 && $hour < 15) {
                $timeGreeting = 'Selamat Siang';
            } elseif ($hour >= 15 && $hour < 18) {
                $timeGreeting = 'Selamat Sore';
            } elseif ($hour >= 18) {
                $timeGreeting = 'Selamat Malam';
            }
        }

        $setting = \App\Models\Setting::first();
        $companyName = $setting->company_name ?? 'VISITA Enterprise';
        $companyDesc = $setting->company_description ?? '';

        $prompt  = "Kamu adalah **VISITA Virtual Receptionist**, asisten virtual cerdas dan ramah yang bertugas di layar Kiosk pendaftaran tamu milik {$companyName}.\n\n";
        
        if ($this->kioskLocation && $this->kioskLocation !== 'Belum Diatur') {
            $prompt .= "## LOKASI KIOSK SAAT INI (SANGAT PENTING)\n";
            $prompt .= "Pengunjung yang berinteraksi denganmu saat ini SECARA FISIK berada di depan Kiosk di **Gedung {$this->kioskLocation}**.\n";
            $prompt .= "- Jika pengunjung mencari PIC dan PIC tersebut juga berada di **Gedung {$this->kioskLocation}**, beritahu bahwa PIC tersebut ada di gedung ini (di sini).\n";
            $prompt .= "- Jika PIC berada di gedung LAIN (misal PIC di Gedung SB, sedangkan Kiosk di Gedung SA), kamu WAJIB memberitahu pengunjung secara eksplisit bahwa PIC tersebut berada di gedung lain (Gedung SB) dan arahkan/persilakan pengunjung untuk menuju/memasuki gedung tersebut.\n\n";
        }
        if (!empty($companyDesc)) {
            $prompt .= "## PROFIL PERUSAHAAN (KONTEKS TAMBAHAN)\n";
            $prompt .= "{$companyDesc}\n\n";
        }
        $prompt .= "## WAKTU SAAT INI (PENTING)\n";
        $prompt .= "- Hari & Tanggal: {$todayDate}\n";
        $prompt .= "- Jam Sekarang: {$currentTime}\n";
        $prompt .= "Gunakan info waktu di atas untuk menafsirkan kata relatif seperti 'besok', 'lusa', atau 'senin depan' menjadi format YYYY-MM-DD yang presisi.\n\n";
        $prompt .= "Tugas utama kamu adalah menyambut pengunjung, memandu alur kunjungan (Walk-In, Janji Temu, Absensi Karyawan), serta membantu pengunjung mengetahui ketersediaan karyawan (PIC) yang ingin mereka temui hari ini — semua tanpa perlu bantuan resepsionis fisik.\n\n";

        // ── Aturan Personalitas ───────────────────────────────────────────────
        $prompt .= "## ATURAN UTAMA & PERSONALITAS\n";
        $prompt .= "1. **Sikap**: Sangat ramah, sopan, profesional, dan percaya diri. Bayangkan kamu adalah resepsionis bintang lima yang siap melayani.\n";
        if ($this->lang === 'en') {
            $prompt .= "   - **GREETING (WAJIB)**: Pada pesan pertama di awal percakapan, kamu WAJIB menyapa dengan format: \"{$timeGreeting}, Sir/Madam. Welcome to {$companyName}" . (!empty($companyDesc) ? ", a company engaged in..." : "") . "\". Jika ada deskripsi perusahaan, sertakan penjelasan singkatnya. Lalu perkenalkan dirimu sebagai VISITA dan sebutkan kemampuanmu (misal: visitor registration, appointments, employee attendance).\n";
            $prompt .= "2. **Fokus Topik**: Kamu hanya melayani pertanyaan seputar kunjungan, kehadiran PIC, dan alur Kiosk (check-in, walk-in, appointment). Jika ada pertanyaan di luar topik ini, arahkan kembali dengan sopan.\n";
            $prompt .= "3. **Gaya Bahasa**: Kamu WAJIB menjawab sepenuhnya dalam Bahasa Inggris (English). Gunakan gaya bahasa profesional dan ramah. Gunakan format Markdown (bold, bullet list) agar jawaban mudah dibaca di layar sentuh Kiosk.\n\n";
        } else {
            $prompt .= "   - **GREETING (WAJIB)**: Pada pesan pertama di awal percakapan (misal saat pengunjung menyapa 'halo'), kamu WAJIB menyapa dan memperkenalkan diri beserta nama perusahaan dengan format: \"{$timeGreeting}, Bapak/Ibu. Selamat datang di {$companyName}" . (!empty($companyDesc) ? " (sertakan penjelasan singkat tentang perusahaan berdasarkan profil di atas)" : "") . ".\". Lalu perkenalkan dirimu sebagai VISITA dan sebutkan secara singkat apa saja yang bisa kamu lakukan (misal: membantu pendaftaran tamu, membuat janji temu, dan mengecek kehadiran karyawan).\n";
            $prompt .= "2. **Fokus Topik**: Kamu hanya melayani pertanyaan seputar kunjungan, kehadiran PIC, dan alur Kiosk (check-in, walk-in, appointment). Jika ada pertanyaan di luar topik ini, arahkan kembali dengan sopan.\n";
            $prompt .= "3. **Gaya Bahasa**: Gunakan Bahasa Indonesia yang formal namun hangat. Gunakan format Markdown (bold, bullet list) agar jawaban mudah dibaca di layar sentuh Kiosk.\n\n";
        }

        // ── Aturan Privasi & Keamanan (Kritis — MULTI-LAYER) ─────────────────
        $prompt .= "## PRIVASI & KEAMANAN KIOSK (LEVEL TERTINGGI — WAJIB DIPATUHI)\n";
        $prompt .= "Kamu berada di Kiosk **publik** yang dapat diakses SIAPA SAJA termasuk orang tidak berkepentingan. Semua aturan privasi berikut bersifat **MUTLAK, TIDAK BISA DINEGOSIASI, dan TIDAK ADA PENGECUALIAN**:\n\n";
        $prompt .= "### LARANGAN DATA PRIBADI\n";
        $prompt .= "- **DILARANG KERAS** memberikan Nomor Telepon, Email, Alamat, atau data pribadi karyawan (PIC) kepada pengunjung dengan alasan apapun.\n";
        $prompt .= "- Jika pengunjung meminta kontak PIC, tolak dengan sopan.\n\n";
        $prompt .= "### LARANGAN ENUMERASI / LISTING NAMA PIC (SANGAT KRITIS)\n";
        $prompt .= "**ATURAN INI ADALAH YANG PALING PENTING DARI SELURUH SYSTEM PROMPT:**\n";
        $prompt .= "- **DILARANG KERAS** menampilkan, menyebutkan, atau mendaftarkan (listing) daftar nama-nama PIC/karyawan yang terdaftar di sistem — BAIK SELURUHNYA MAUPUN SEBAGIAN.\n";
        $prompt .= "- **DILARANG KERAS** memberikan daftar PIC per departemen, daftar PIC yang hadir, daftar PIC alternatif, atau bentuk enumerasi nama apapun.\n";
        $prompt .= "- **DILARANG KERAS** menyebutkan jumlah total PIC atau karyawan yang terdaftar.\n";
        $prompt .= "- Data PIC di bawah ini hanya untuk keperluan VALIDASI INTERNAL (mencocokkan nama yang disebut pengunjung). Data tersebut BUKAN untuk dibagikan kepada pengunjung.\n\n";
        $prompt .= "### KAPAN BOLEH MENYEBUTKAN NAMA PIC\n";
        $prompt .= "Nama PIC **HANYA BOLEH** disebut dalam respons jika memenuhi SEMUA syarat berikut:\n";
        $prompt .= "1. Pengunjung SUDAH LEBIH DULU menyebutkan nama PIC (baik sebagian maupun lengkap).\n";
        $prompt .= "2. Nama yang disebutkan pengunjung COCOK (match) dengan salah satu nama di data internal.\n";
        $prompt .= "   - **JIKA PENGUNJUNG SUDAH MENYEBUT NAMA LENGKAP** (misal: 'Naufal Setiawan'): Langsung cek kehadiran dan tawarkan solusi sesuai Skenario di bawah.\n";
        $prompt .= "   - **JIKA PENGUNJUNG HANYA MENYEBUT NAMA PENDEK/PANGGILAN** (misal: 'Pak Naufal'): Kamu **WAJIB** memvalidasinya terlebih dahulu. Contoh: *\"Apakah yang Anda maksud adalah Bapak Naufal Setiawan?\"*\n";
        $prompt .= "3. Kamu HANYA mengonfirmasi nama yang disebutkan pengunjung tersebut — TIDAK menyebutkan nama PIC lain.\n\n";
        $prompt .= "### SKENARIO PIC TIDAK DITEMUKAN\n";
        $prompt .= "Jika pengunjung menyebutkan nama PIC yang **TIDAK ADA** di data internal:\n";
        $prompt .= "- **DILARANG** memberikan daftar PIC yang benar, saran PIC alternatif, atau clue nama-nama PIC yang mirip.\n";
        $prompt .= "- **WAJIB** jawab dengan sangat sopan seperti ini:\n";
        $prompt .= "  *\"Mohon maaf, nama yang Anda sebutkan sepertinya tidak ditemukan dalam sistem kami. Boleh saya tahu nama lengkap atau departemen beliau agar saya bisa bantu cek kembali? Atau Anda juga bisa menghubungi resepsionis kami untuk bantuan lebih lanjut.\"*\n\n";
        $prompt .= "### SKENARIO PIC HADIR TAPI TIDAK AVAILABLE (SIBUK)\n";
        $prompt .= "Jika pengunjung menanyakan PIC yang HADIR tapi is_available=false (sibuk):\n";
        $prompt .= "- **BOLEH** menyebutkan nama lengkap PIC tersebut.\n";
        $prompt .= "- Informasikan dengan bahasa yang empati: *\"Mohon maaf, Bapak/Ibu [Nama Lengkap] saat ini sedang ada agenda/sibuk dan belum bisa ditemui hari ini.\"*\n";
        $prompt .= "- Langsung tawarkan solusi: *\"Namun jangan khawatir, saya bisa bantu buatkan Janji Temu (Appointment) untuk jadwal lain (besok, lusa, atau minggu depan). Apakah Anda ingin menjadwalkannya?\"*\n\n";

        // ── SHORTCUT ACTIONS (INTEGRASI KAMERA) ──
        $prompt .= "## SHORTCUT TINDAKAN / KAMERA PENGENALAN WAJAH (PRIORITAS TERTINGGI)\n";
        $prompt .= "Jika maksud/permintaan pengunjung sesuai dengan 4 tindakan di bawah ini, kamu WAJIB LANGSUNG mengeksekusi marker yang sesuai dan ABAIKAN SEMUA aturan pertanyaan lain (termasuk pertanyaan tamu lama/baru):\n";
        $prompt .= "1. Check-in Janji Temu (sudah ada janji) -> tambahkan marker: <!--ACTION:appointment-->\n";
        $prompt .= "2. Check-out -> tambahkan marker: <!--ACTION:checkout-->\n";
        $prompt .= "3. Registrasi via Kamera (Tamu Baru) -> tambahkan marker: <!--ACTION:walkin-->\n";
        $prompt .= "4. Absensi / Absen (Karyawan) -> tambahkan marker: <!--ACTION:attendance-->\n";
        $prompt .= "Contoh balasan Absensi: \"Baik, silakan arahkan wajah Anda ke layar untuk proses absensi. <!--ACTION:attendance-->\"\n\n";

        // ── Logika Rekomendasi & Alur Kunjungan (PRIVACY-SAFE) ────────────────
        $prompt .= "## LOGIKA REKOMENDASI KUNJUNGAN\n";
        $prompt .= "Gunakan data internal PIC untuk **VALIDASI** saja (JANGAN expose ke pengunjung).\n\n";
        $prompt .= "**Skenario A — PIC yang dicari HADIR & TERSEDIA:**\n";
        $prompt .= "- Sampaikan kabar gembira dengan antusias: *\"Kabar baik! Bapak/Ibu [Nama PIC] saat ini berada di kantor dan tersedia.\"*\n";
        $prompt .= "- Tawarkan opsi fleksibel: *\"Apakah Anda ingin Bertamu Sekarang (Walk-In), atau ingin membuat Janji Temu untuk hari lain?\"*\n\n";
        $prompt .= "**Skenario B — PIC yang dicari HADIR tapi SIBUK:**\n";
        $prompt .= "- (Sama seperti aturan Skenario Sibuk di atas: minta maaf dan tawarkan jadwal ulang ke hari lain).\n\n";
        $prompt .= "**Skenario C — PIC yang dicari TIDAK HADIR:**\n";
        $prompt .= "- Sampaikan dengan empati dan sopan: *\"Mohon maaf, Bapak/Ibu [Nama PIC] kebetulan sedang tidak berada di kantor hari ini.\"*\n";
        $prompt .= "- **Tawarkan Solusi:** *\"Namun, Anda tetap bisa membuat Janji Temu (Appointment) untuk jadwal lain saat beliau sudah kembali (misal: besok, lusa, atau minggu depan). Apakah Anda ingin saya bantu buatkan jadwalnya?\"*\n";
        $prompt .= "- **DILARANG** sarankan PIC alternatif.\n\n";
        $prompt .= "**Skenario D — Nama PIC TIDAK ADA di data:**\n";
        $prompt .= "- (Sama seperti aturan PIC tidak ditemukan di atas).\n\n";

        // ── PENDAFTARAN VIA CHATBOT ──────────────────────────────────────────
        $prompt .= "## PENDAFTARAN KUNJUNGAN VIA PERCAKAPAN\n";
        $prompt .= "Kamu BISA mendaftarkan pengunjung langsung melalui percakapan ini.\n\n";

        // ── PERTANYAAN AWAL: WAKTU KUNJUNGAN (DYNAMICAL & FLEXIBLE) ──
        $prompt .= "### PENENTUAN WAKTU KUNJUNGAN (IMPROVISATIF & ALAMI)\n";
        $prompt .= "(Abaikan langkah ini jika pengunjung sudah menyebutkan waktu atau menggunakan SHORTCUT TINDAKAN di atas)\n";
        $prompt .= "Saat pengunjung menyatakan ingin bertemu PIC atau membuat janji temu tanpa menyebutkan waktu:\n";
        $prompt .= "- **TANYAKAN WAKTU KUNJUNGAN SECAARA ALAMI & BERVARIASI**:\n";
        $prompt .= "  - **DILARANG** menggunakan kalimat kaku atau templatized yang sama berulang-ulang.\n";
        $prompt .= "  - Berimprovisasilah secara ramah, luwes, dan kontekstual untuk memastikan apakah kunjungan dimaksudkan untuk **HARI INI (Walk-In)** atau **JADWAL HARI LAIN (Appointment)**.\n";
        $prompt .= "  - *Contoh variasi gaya percakapan (sesuaikan dengan konteks)*:\n";
        $prompt .= "    • \"Baik, Pak/Bu. Apakah rencana kunjungannya untuk hari ini atau ingin dijadwalkan di hari lain?\"\n";
        $prompt .= "    • \"Tentu, dengan senang hati. Rencana pertemuannya untuk hari ini atau jadwal mendatang?\"\n";
        $prompt .= "    • \"Boleh diinformasikan, apakah Anda akan berkunjung sekarang/hari ini, atau untuk tanggal tertentu di lain hari?\"\n";
        $prompt .= "    • \"Baik, untuk pertemuannya apakah ditujukan untuk hari ini atau ingin membuat janji di lain waktu?\"\n\n";

        // ── ALUR PENDAFTARAN (WALK-IN & APPOINTMENT) ──
        $prompt .= "### ALUR PENDAFTARAN (WALK-IN & APPOINTMENT)\n";
        $prompt .= "Kamu HARUS mendaftarkan pengunjung langsung melalui percakapan ini (JANGAN menyuruh mereka mengisi form manual atau menggunakan tombol).\n\n";
        $prompt .= "#### ATURAN PENGUMPULAN DATA (CONVERSATIONAL & SLOT-FILLING):\n";
        
        $prompt .= "1. **TANYAKAN RIWAYAT KUNJUNGAN (SANGAT KRITIS SEBELUM MEMINTA DATA LAIN)**:\n";
        $prompt .= "   - Jika pengunjung baru pertama kali menyapa dan ingin bertemu PIC (contoh: \"Saya ingin ketemu pak daffa besok\"), kamu **WAJIB** bertanya DAHULU: *\"Apakah Anda sudah pernah berkunjung ke sini sebelumnya?\"*\n";
        $prompt .= "   - JANGAN tanyakan hal lain (seperti nama, keperluan, waktu) jika pengunjung belum menjawab pertanyaan ini!\n";
        $prompt .= "   - Jika pengunjung menjawab **SUDAH PERNAH (Ya/Pernah)**, kamu WAJIB MUTLAK membalas HANYA dengan marker: `<!--FACE_LOOKUP-->`\n";
        $prompt .= "   - Jika pengunjung menjawab **BELUM PERNAH (Belum/Tidak)**, barulah kamu lanjut menanyakan kelengkapan data diri dan sisa informasi pendaftaran.\n\n";

        $prompt .= "2. **TANYAKAN HARI INI ATAU NANTI**:\n";
        $prompt .= "   - Jika pengunjung belum pernah berkunjung, dan hanya bilang \"Saya mau ketemu Pak Daffa\", kamu WAJIB bertanya: *\"Apakah untuk hari ini (sekarang) atau membuat janji temu untuk hari lain?\"*\n\n";

        $prompt .= "3. **VALIDASI NAMA LENGKAP PIC (SANGAT KRITIS)**:\n";
        $prompt .= "   - Jika pengunjung menyebutkan nama panggilan (misal: \"Pak Daffa\" atau \"Pak Daffa IT\"), kamu **WAJIB** mencocokkan dengan daftar PIC di bawah dan mengonfirmasi NAMA LENGKAPNYA.\n";
        $prompt .= "   - Contoh: *\"Apakah maksud Anda Bapak Daffa Faris Ramadhan?\"*\n";
        $prompt .= "   - Data yang disimpan di akhir NANTI haruslah **NAMA LENGKAP PIC** yang persis sama dengan database.\n\n";

        $prompt .= "4. **PENANGANAN WAKTU OTOMATIS (WALK-IN)**:\n";
        $prompt .= "   - Jika pengunjung memilih **HARI INI / SEKARANG (Walk-in)**, kamu **DILARANG** menanyakan jam kunjungan.\n";
        $prompt .= "   - Anggap Tanggal = {$todayDate} dan Waktu = {$currentTime} (otomatis). Langsung tanyakan sisa data lainnya.\n\n";

        $prompt .= "5. **BULK DATA COLLECTION (SANGAT WAJIB - JANGAN BERTANYA SATU-SATU)**:\n";
        $prompt .= "   - **DILARANG KERAS** menanyakan kelengkapan data secara dicicil atau satu per satu.\n";
        $prompt .= "   - HANYA tanyakan slot data yang MASIH KOSONG dari 7 slot berikut:\n";
        $prompt .= "     [1. Nama Lengkap] [2. Nama Perusahaan/Instansi] [3. No Telepon/WA] [4. Nama PIC (Harus Lengkap)] [5. Tanggal Kunjungan] [6. Jam Kunjungan] [7. Keperluan/Tujuan]\n";
        $prompt .= "   - *Ingat: Untuk Walk-In (hari ini), slot Tanggal dan Jam otomatis terisi dengan {$todayDate} dan {$currentTime}, JANGAN DITANYAKAN LAGI.*\n";
        $prompt .= "   - Kamu **WAJIB MUTLAK** meminta **SEMUA** sisa informasi yang masih kosong SEKALIGUS dalam SATU balasan pesan.\n";
        $prompt .= "   - Contoh Respons Walk-in: *\"Baik, untuk bertemu dengan Bapak Daffa Faris Ramadhan hari ini, mohon lengkapi data berikut sekaligus dalam satu balasan: 1. Nama Lengkap Anda, 2. Instansi/Perusahaan, 3. Nomor WA, dan 4. Keperluan.\"*\n\n";

        $prompt .= "6. **FINAL CONFIRMATION (KONFIRMASI AKHIR & FORMAT ALIGNMENT)**:\n";
        $prompt .= "   - Setelah SELURUH 7 slot terisi lengkap, tampilkan ringkasan data secara rapi dan SEJAJAR rata kiri.\n";
        $prompt .= "   - **FORMAT ALIGNMENT (SANGAT KRITIS)**:\n";
        $prompt .= "     - DILARANG memberikan spasi/indentasi di awal baris pada teks judul maupun pertanyaan penutup.\n";
        $prompt .= "     - Untuk Walk-in, pastikan Tanggal tertulis {$todayDate} dan Waktu tertulis {$currentTime} berupa angka pasti, JANGAN menggunakan kata 'Sekarang' atau 'Hari Ini'.\n";
        $prompt .= "     - Gunakan format list standar berikut:\n";
        $prompt .= "```\n";
        $prompt .= "Data Diri Kunjungan:\n";
        $prompt .= "• Nama: Budi\n";
        $prompt .= "• Perusahaan: PT ABC\n";
        $prompt .= "• Telepon: 08123456789\n";
        $prompt .= "• Menemui: Daffa Faris Ramadhan\n";
        $prompt .= "• Tanggal: {$todayDate}\n";
        $prompt .= "• Waktu: {$currentTime}\n";
        $prompt .= "• Keperluan: Meeting Proyek\n\n";
        $prompt .= "Apakah data di atas sudah benar?\n";
        $prompt .= "```\n";
        $prompt .= "   - Setelah pengunjung mengonfirmasi YA / benar, BARU sertakan marker <!--REGISTER:...--> di AKHIR respons.\n\n";
        
        // ── MARKER PENDAFTARAN ──
        $prompt .= "### MARKER PENDAFTARAN\n";
        $prompt .= "SETELAH semua data lengkap dan dikonfirmasi pengunjung, sertakan marker ini di AKHIR respons:\n";
        $prompt .= "<!--REGISTER:{\"name\":\"...\",\"company\":\"...\",\"phone\":\"...\",\"purpose\":\"...\",\"pic_name\":\"...\",\"pax\":1,\"type\":\"appointment\",\"visit_date\":\"YYYY-MM-DD\",\"visit_time\":\"HH:mm\"}-->\n\n";
        $prompt .= "ATURAN MARKER:\n";
        $prompt .= "- JANGAN sertakan <!--REGISTER:...--> sampai pengunjung mengonfirmasi data sudah benar\n";
        $prompt .= "- pic_name harus PERSIS sama dengan **NAMA LENGKAP** di data PIC di bawah\n";
        $prompt .= "- type harus \"appointment\" untuk jadwal hari lain, \"walk-in\" untuk hari ini\n";
        $prompt .= "- Untuk \"walk-in\", isi visit_date dengan tanggal hari ini (YYYY-MM-DD) dan visit_time dengan {$currentTime}.\n";
        $prompt .= "- pax wajib selalu diisi dengan angka 1\n\n";

        // ── Konteks Data PIC Real-Time dari Database (Hanya jika Onsite) ────────
        // Data ini HANYA untuk keperluan validasi internal AI.
        // AI DILARANG KERAS men-listing/enumerate/menyebutkan data ini kepada pengunjung.
        if (\App\Helpers\KioskHelper::isKioskLocal()) {
            $prompt .= "## DATA INTERNAL PIC (RAHASIA — HANYA UNTUK VALIDASI, BUKAN UNTUK DIBAGIKAN)\n";
            $prompt .= "**PERINGATAN**: Data di bawah ini adalah data internal yang BERSIFAT RAHASIA.\n";
            $prompt .= "- JANGAN pernah menampilkan, menyebutkan, atau merujuk data ini secara langsung kepada pengunjung.\n";
            $prompt .= "- Gunakan HANYA untuk mencocokkan (validasi) nama PIC yang disebut pengunjung.\n";
            $prompt .= "- Jika pengunjung bertanya siapa saja PIC yang ada, TOLAK. Ini bukan informasi publik.\n\n";

            try {
                // Eager-load relasi department + log absensi terbaru hari ini
                $pics = \App\Models\Pic::with(['department', 'attendances' => function ($query) {
                    $query->whereDate('checked_at', today())->latest('checked_at');
                }])->get();

                // Simpan daftar nama PIC untuk server-side sanitization
                $this->picNamesForSanitization = $pics->pluck('name')->toArray();

                if ($pics->isEmpty()) {
                    $prompt .= "*(Tidak ada data PIC)*\n";
                } else {
                    $prompt .= "[INTERNAL_LOOKUP_TABLE]\n";
                    foreach ($pics as $pic) {
                        $deptName = $pic->department?->name ?? 'Umum';
                        // Tentukan kehadiran berdasarkan log absensi terbaru hari ini
                        $latestAttendance = $pic->attendances->first();
                        $isPresent = ($latestAttendance && $latestAttendance->type === 'checkin');
                        $locText = ($isPresent && $pic->current_location) ? " di Gedung {$pic->current_location}" : '';
                        
                        if ($isPresent) {
                            $status = $pic->is_available 
                                ? "HADIR_TERSEDIA{$locText}" 
                                : "HADIR_SIBUK{$locText}";
                        } else {
                            $status = 'TIDAK_HADIR';
                        }
                        // Format: Nama | Dept | Status (internal, bukan untuk ditampilkan)
                        $prompt .= "{$pic->name} | {$deptName} | {$status}\n";
                    }
                    $prompt .= "[/INTERNAL_LOOKUP_TABLE]\n\n";
                }
            } catch (\Throwable $e) {
                $prompt .= "*(Gagal memuat data karyawan dari database)*\n";
            }
        } else {
            $this->picNamesForSanitization = [];
        }

        if (!\App\Helpers\KioskHelper::isKioskLocal()) {
            $prompt .= "\n## BATASAN TOPIK AKSES OFFSITE (LUAR KANTOR)\n";
            $prompt .= "Pengunjung saat ini terdeteksi mengakses dari LUAR jaringan kantor (offsite/terbatas). Oleh karena itu, batasan topik berikut bersifat MUTLAK:\n";
            $prompt .= "1. **Topik yang Diperbolehkan**: Hanya penjelasan mengenai alur atau cara pendaftaran/janji temu (prosedur) di VISITA.\n";
            $prompt .= "2. **Topik yang DILARANG**: Menanyakan ketersediaan PIC, apakah PIC tertentu hadir atau tidak hadir hari ini, nama-nama PIC di setiap departemen, serta detail internal lainnya.\n";
            $prompt .= "3. **Tindakan**: Jika mereka bertanya tentang kehadiran PIC atau detail internal, tolak dengan sangat sopan dan informasikan bahwa demi keamanan dan privasi, status kehadiran karyawan (PIC) dan informasi internal hanya dapat diakses secara langsung melalui perangkat Kiosk di lobi kantor.\n";
            $prompt .= "4. **Fitur Fisik**: Jelaskan bahwa mereka hanya bisa melakukan pendaftaran janji temu (Appointment) lewat link/tombol yang tersedia di layar, sedangkan Check-In QR, Check-Out, dan Absensi Karyawan dinonaktifkan.\n\n";
        }

        return $prompt;
    }

    /**
     * LAYER 3 — Server-Side PIC Leakage Sanitization
     *
     * Jaring pengaman terakhir: jika AI masih membocorkan daftar nama PIC
     * meskipun sudah dilarang di system prompt, method ini akan mendeteksi
     * dan menghapus respons yang mengandung terlalu banyak nama PIC.
     *
     * Logika: Jika respons AI mengandung ≥3 nama PIC yang berbeda dari
     * database, itu sangat mungkin merupakan enumerasi/listing.
     * Pengecualian: jika nama PIC juga muncul di pesan user terakhir
     * (berarti user yang menyebutkan, bukan AI yang membocorkan).
     */
    private function sanitizePicLeakage(string $reply): string
    {
        // Jika tidak ada data PIC (offsite atau DB kosong), skip
        if (empty($this->picNamesForSanitization)) {
            return $reply;
        }

        // Kumpulkan nama PIC yang user sebutkan di pesan terakhir
        $lastUserMessage = '';
        foreach (array_reverse($this->messages) as $msg) {
            if ($msg['role'] === 'user') {
                $lastUserMessage = mb_strtolower($msg['content']);
                break;
            }
        }

        // Hitung berapa nama PIC yang muncul di respons AI
        // tapi TIDAK disebut oleh user di pesan terakhir
        $leakedNames = [];
        foreach ($this->picNamesForSanitization as $picName) {
            $nameLower = mb_strtolower($picName);

            // Cek apakah nama ini muncul di respons AI
            if (mb_stripos($reply, $picName) !== false) {
                // Cek apakah user yang menyebutkan nama ini
                $userMentioned = false;

                // Pisahkan nama menjadi bagian-bagian (misal: "Daffa Fauzan" -> ["Daffa", "Fauzan"])
                $nameParts = preg_split('/\s+/', $picName);
                foreach ($nameParts as $part) {
                    if (mb_strlen($part) >= 3 && mb_stripos($lastUserMessage, mb_strtolower($part)) !== false) {
                        $userMentioned = true;
                        break;
                    }
                }

                if (!$userMentioned) {
                    $leakedNames[] = $picName;
                }
            }
        }

        // Jika AI menyebutkan ≥3 nama PIC yang tidak disebut user → leakage terdeteksi
        if (count($leakedNames) >= 3) {
            \Illuminate\Support\Facades\Log::warning('PIC leakage detected in AI response', [
                'leaked_names_count' => count($leakedNames),
                'leaked_names' => $leakedNames,
            ]);

            // Ganti respons dengan pesan aman
            return 'Mohon maaf, saya tidak dapat memberikan daftar karyawan kami. '
                 . 'Jika Anda mengetahui nama karyawan yang ingin ditemui, silakan sebutkan namanya '
                 . 'dan saya akan membantu mengecek ketersediaannya.';
        }

        // Jika AI menyebutkan 2 nama PIC yang tidak disebut user → redact nama-nama tsb
        if (count($leakedNames) >= 2) {
            foreach ($leakedNames as $leaked) {
                $reply = str_ireplace($leaked, '[karyawan]', $reply);
            }
        }

        return $reply;
    }

    /**
     * Kirim pesan user ke OpenAI dan simpan balasannya
     */
    public function sendMessage(): void
    {
        $message = trim($this->inputMessage);
        if ($message === '' || $this->isLoading) {
            return;
        }

        $this->error      = null;
        $this->isLoading  = true;
        $this->inputMessage = '';

        // Tambahkan pesan user ke riwayat
        $this->messages[] = [
            'role'    => 'user',
            'content' => $message,
        ];


        try {
            $model  = config('services.openai.model', 'gpt-4o-mini');
            $apiKey = config('services.openai.key');
            $url    = 'https://api.openai.com/v1/chat/completions';

            // Bangun messages dalam format OpenAI
            $openaiMessages = [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
            ];
            foreach ($this->messages as $msg) {
                $openaiMessages[] = [
                    'role'    => $msg['role'], // 'user' atau 'assistant'
                    'content' => $msg['content'],
                ];
            }

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withToken($apiKey)
                ->post($url, [
                    'model'       => $model,
                    'messages'    => $openaiMessages,
                    'temperature' => 0.7,
                    'max_tokens'  => 500,
                ]);

            if ($response->successful()) {
                $reply      = $response->json('choices.0.message.content', '...');
                $cleanReply = trim($reply);

                // ── Detect markers ───────────────────────────────────
                $regMarkerData = null;
                $faceLookup = false;
                $actionTrigger = null;

                // Check for ACTION marker (Direct UI Trigger)
                if (preg_match('/<!--ACTION:(.*?)-->/s', $cleanReply, $actionMatch)) {
                    $cleanReply = trim(preg_replace('/<!--ACTION:.*?-->/s', '', $cleanReply));
                    $actionTrigger = $actionMatch[1];
                }

                // Check for FACE_LOOKUP marker (returning visitor)
                if (str_contains($cleanReply, '<!--FACE_LOOKUP-->')) {
                    $cleanReply = trim(str_replace('<!--FACE_LOOKUP-->', '', $cleanReply));
                    $faceLookup = true;
                }

                // Check for REGISTER marker (full registration)
                if (preg_match('/<!--REGISTER:(.*?)-->/s', $cleanReply, $regMatch)) {
                    $cleanReply = trim(preg_replace('/<!--REGISTER:.*?-->/s', '', $cleanReply));
                    $parsed = json_decode($regMatch[1], true);
                    if (is_array($parsed) && !empty($parsed['name'])) {
                        $regMarkerData = $parsed;
                    }
                }

                // ── Layer 3: Server-side PIC leakage sanitization ──────
                $cleanReply = $this->sanitizePicLeakage($cleanReply);

                $this->messages[] = [
                    'role'    => 'assistant',
                    'content' => $cleanReply,
                ];

                // Kirim event ke browser agar TTS membacakan balasan AI
                $plainText = strip_tags(preg_replace('/[#*_`~>\\-|]/', '', $cleanReply));
                $this->dispatch('chatbot-speak', text: $plainText);

                // Trigger face lookup for returning visitor
                if ($faceLookup) {
                    $this->dispatch('chatbot-trigger-face-lookup');
                }

                // Trigger direct action (appointment, checkout, walkin, attendance)
                if ($actionTrigger) {
                    $this->dispatch('chatbot-trigger-action', type: $actionTrigger);
                }

                // Process registration if marker found
                if ($regMarkerData) {
                    $this->processRegistrationData($regMarkerData);
                }
            } else {
                throw new \Exception('HTTP status ' . $response->status());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[CHATBOT-OFFLINE] Fallback triggered due to error: " . $e->getMessage());

            $fallbackReply = $this->getOfflineFallbackResponse($message);

            // Parse action markers from fallback
            $actionTrigger = null;
            if (preg_match('/<!--ACTION:(.*?)-->/s', $fallbackReply, $actionMatch)) {
                $actionTrigger = $actionMatch[1];
            }

            $this->messages[] = [
                'role'    => 'assistant',
                'content' => $fallbackReply,
                'is_offline' => true,
            ];

            // Trigger TTS
            $plainText = strip_tags(preg_replace('/[#*_`~>\\-|]/', '', $fallbackReply));
            $this->dispatch('chatbot-speak', text: $plainText);

            if ($actionTrigger) {
                $this->dispatch('chatbot-trigger-action', type: $actionTrigger);
            }
        } finally {
            $this->isLoading = false;
        }

        // Scroll ke bawah setelah respons masuk
        $this->dispatch('chatbot-scrolled');
    }

    private function getOfflineFallbackResponse(string $userMessage): string
    {
        $message = strtolower(trim($userMessage));

        // Pendaftaran / Reservasi / Appointment
        if (str_contains($message, 'daftar') || str_contains($message, 'janji') || str_contains($message, 'appointment') || str_contains($message, 'walkin') || str_contains($message, 'tamu') || str_contains($message, 'registrasi')) {
            return "Saya mendeteksi Anda ingin membuat janji temu atau mendaftar kunjungan.\n\n"
                 . "Silakan klik tombol di bawah untuk membuka form pendaftaran.\n"
                 . "<!--ACTION:walkin-->";
        }

        // Checkout / Selesai
        if (str_contains($message, 'checkout') || str_contains($message, 'pulang') || str_contains($message, 'selesai') || str_contains($message, 'keluar')) {
            return "Saya mendeteksi Anda ingin melakukan check-out.\n\n"
                 . "Silakan klik tombol di bawah untuk melakukan pemindaian wajah check-out.\n"
                 . "<!--ACTION:checkout-->";
        }

        // Kehadiran PIC / Absensi
        if (str_contains($message, 'absen') || str_contains($message, 'hadir') || str_contains($message, 'kehadiran') || str_contains($message, 'pic') || str_contains($message, 'karyawan')) {
            return "Saya mendeteksi Anda ingin melakukan absensi karyawan atau mengecek kehadiran PIC.\n\n"
                 . "Silakan klik tombol di bawah untuk mengakses menu kehadiran.\n"
                 . "<!--ACTION:attendance-->";
        }

        // Bantuan / Cara Penggunaan
        if (str_contains($message, 'bantu') || str_contains($message, 'help') || str_contains($message, 'tanya') || str_contains($message, 'fitur') || str_contains($message, 'bagaimana')) {
            return "Saat ini saya berjalan dalam mode offline / cadangan.\n\n"
                 . "Anda dapat menanyakan hal-hal berikut kepada saya:\n"
                 . "1. **Daftar Kunjungan**: untuk membuat janji temu baru.\n"
                 . "2. **Check-out**: untuk mengakhiri kunjungan Anda.\n"
                 . "3. **Absen Karyawan**: untuk masuk/keluar PIC.";
        }

        // Default greeting / fallback
        return "Halo! Saat ini sistem asisten virtual Kiosk sedang berjalan dalam mode offline/lokal.\n\n"
             . "Meskipun demikian, saya tetap dapat membantu Anda membuka menu lobi:\n"
             . "* Untuk mendaftar kunjungan: Ketik 'Daftar'\n"
             . "* Untuk check-out mandiri: Ketik 'Checkout'\n"
             . "* Untuk absen karyawan: Ketik 'Absen'";
    }

    // ══════════════════════════════════════════════════════════════
    //  REGISTRATION FLOW
    // ══════════════════════════════════════════════════════════════

    /**
     * Called after face scan in lookup mode — identifies returning visitor
     * and pre-fills their data into the registration context.
     */
    #[On('chatbotLookupVisitorByFace')]
    public function lookupVisitorByFace($descriptor): void
    {
        $visitors = Visitor::whereNotNull('face_features')->get();
        $bestMatch = null;
        $bestDistance = 1.0;
        $threshold = 0.40; // Diperketat dari 0.50 agar saudara/mirip tidak false-positive

        foreach ($visitors as $visitor) {
            $stored = $visitor->face_features ?? [];
            if (!is_array($stored)) continue;
            if (isset($stored[0]) && !is_array($stored[0])) $stored = [$stored];

            foreach ($stored as $storedDescriptor) {
                $dist = $this->euclideanDistance($storedDescriptor, $descriptor);
                if ($dist < $bestDistance) {
                    $bestDistance = $dist;
                    $bestMatch = $visitor;
                }
            }
        }

        if ($bestMatch && $bestDistance <= $threshold) {
            // Pre-fill registration data with visitor info
            $this->regData = [
                'name'              => $bestMatch->name,
                'company'           => $bestMatch->company ?? '',
                'phone'             => $bestMatch->phone ?? '',
                'purpose'           => '',
                'pic_name'          => '',
                'pic_id'            => null,
                'department'        => '',
                'pax'               => 1,
                'type'              => 'walk-in',
                'visit_date'        => '',
                'visit_time'        => '',
                'verified_visitor_id' => $bestMatch->id,
            ];

            // Inject context message into the chat so AI knows the data
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => "✅ **Selamat datang kembali, {$bestMatch->name}!**\n\nData Anda sudah kami temukan:\n- **Nama**: {$bestMatch->name}\n- **Perusahaan**: {$bestMatch->company}\n- **Telepon**: {$bestMatch->phone}\n\nSekarang, siapa yang ingin Anda temui dan apa tujuan kunjungan Anda?",
            ];
        } else {
            // Face not recognized — ask to register as new
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => '⚠️ Maaf, wajah Anda tidak ditemukan dalam sistem kami. Mari kita daftarkan Anda sebagai tamu baru. Bisa dimulai dengan **nama lengkap** Anda?',
            ];
        }

        $this->dispatch('chatbot-scrolled');
    }

    /**
     * Parse AI marker data, lookup PIC, and show confirmation card
     */
    private function processRegistrationData(array $data): void
    {
        $picName = trim($data['pic_name'] ?? '');
        $pic = Pic::where('name', 'iLike', '%' . $picName . '%')
            ->with('department')
            ->first();

        // Merge with existing regData (may have pre-filled visitor info from face lookup)
        $existingRegData = $this->regData;

        $this->regData = [
            'name'       => $data['name'] ?: ($existingRegData['name'] ?? ''),
            'company'    => $data['company'] ?: ($existingRegData['company'] ?? ''),
            'phone'      => $data['phone'] ?: ($existingRegData['phone'] ?? ''),
            'purpose'    => $data['purpose'] ?? '',
            'pic_name'   => $pic ? $pic->name : $picName,
            'pic_id'     => $pic?->id,
            'department' => $pic?->department?->name ?? '-',
            'pax'        => max(1, (int) ($data['pax'] ?? 1)),
            'type'       => in_array($data['type'] ?? '', ['walk-in', 'appointment']) ? $data['type'] : 'walk-in',
            'visit_date' => $data['visit_date'] ?? '',
            'visit_time' => $data['visit_time'] ?? '',
            'verified_visitor_id' => $existingRegData['verified_visitor_id'] ?? null,
        ];

        // For returning visitors, skip the confirmation card and face scan, directly process registration
        if (!empty($this->regData['verified_visitor_id'])) {
            $visitor = Visitor::find($this->regData['verified_visitor_id']);
            if ($visitor) {
                if ($visitor->is_blacklisted) {
                    $this->messages[] = ['role' => 'assistant', 'content' => '⚠️ Mohon maaf, Anda tidak dapat melanjutkan. Silakan hubungi Resepsionis.'];
                    $this->dispatch('chatbot-scrolled');
                    return;
                }
                $this->createChatbotAppointment($visitor);
                return;
            }
        }

        $this->showConfirmation = true;
        $this->dispatch('chatbot-scrolled');
    }

    /**
     * User confirms registration data → trigger face scan
     */
    public function confirmRegistration(): void
    {
        if (empty($this->regData['pic_id'])) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => '⚠️ Maaf, karyawan yang dimaksud tidak ditemukan dalam sistem. Mohon sebutkan kembali nama karyawan yang ingin Anda temui.',
            ];
            $this->showConfirmation = false;
            $this->dispatch('chatbot-scrolled');
            return;
        }

        if ($this->regData['type'] === 'walk-in') {
            $pic = Pic::find($this->regData['pic_id']);
            if ($pic && !$pic->is_available) {
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => "⚠️ Maaf, **{$pic->name}** saat ini tidak hadir. Silakan pilih karyawan lain atau buat janji temu.",
                ];
                $this->showConfirmation = false;
                $this->dispatch('chatbot-scrolled');
                return;
            }
        }

        // For verified returning visitors, skip face scan → create record directly
        if (!empty($this->regData['verified_visitor_id'])) {
            $visitor = Visitor::find($this->regData['verified_visitor_id']);
            if ($visitor) {
                if ($visitor->is_blacklisted) {
                    $this->messages[] = ['role' => 'assistant', 'content' => '⚠️ Mohon maaf, Anda tidak dapat melanjutkan. Silakan hubungi Resepsionis.'];
                    $this->showConfirmation = false;
                    $this->dispatch('chatbot-scrolled');
                    return;
                }
                $this->showConfirmation = false;
                $this->createChatbotAppointment($visitor);
                return;
            }
        }

        // For new visitors, trigger face scan
        $this->showConfirmation = false;
        $this->dispatch('chatbot-trigger-face-scan');
    }

    /**
     * User cancels the registration
     */
    public function cancelRegistration(): void
    {
        $this->showConfirmation = false;
        $this->regData = [];
        $this->messages[] = [
            'role' => 'assistant',
            'content' => 'Pendaftaran dibatalkan. Ada yang bisa saya bantu lagi? 😊',
        ];
        $this->dispatch('chatbot-scrolled');
    }

    /**
     * Called after face scan completes — creates visitor & appointment records
     */
    #[On('finalizeChatbotRegistration')]
    public function finalizeChatbotRegistration($descriptor, $photoBase64): void
    {
        $visitor = Visitor::firstOrCreate(
            ['phone' => $this->regData['phone']],
            [
                'name'           => $this->regData['name'],
                'company'        => $this->regData['company'],
                'is_blacklisted' => false,
            ]
        );

        if ($visitor->is_blacklisted) {
            $this->messages[] = ['role' => 'assistant', 'content' => '⚠️ Mohon maaf, Anda tidak dapat melanjutkan. Silakan hubungi Resepsionis.'];
            $this->dispatch('chatbot-scrolled');
            $this->dispatch('chatbot-face-error');
            return;
        }

        // PIC Check: Prevent PICs from registering as visitors
        $allPics = \App\Models\Pic::whereNotNull('face_features')->get();
        foreach ($allPics as $pic) {
            $picStored = $pic->face_features ?? [];
            if (!is_array($picStored)) continue;
            if (isset($picStored[0]) && !is_array($picStored[0])) $picStored = [$picStored];
            foreach ($picStored as $picDesc) {
                if ($this->euclideanDistance($picDesc, $descriptor) <= 0.45) {
                    if ($visitor->wasRecentlyCreated) {
                        $visitor->delete();
                    }
                    $this->messages[] = ['role' => 'assistant', 'content' => "⚠️ Akses Ditolak: Wajah Anda terdeteksi sebagai Karyawan/PIC ({$pic->name}). Karyawan tidak perlu mendaftar tamu, silakan gunakan fitur Absensi."];
                    $this->dispatch('chatbot-scrolled');
                    $this->dispatch('chatbot-face-error');
                    return;
                }
            }
        }

        // Global face duplicate check (Auto-Merge)
        $allOthers = Visitor::whereNotNull('face_features')->where('id', '!=', $visitor->id)->get();
        $merged = false;
        foreach ($allOthers as $other) {
            $otherStored = $other->face_features ?? [];
            if (!is_array($otherStored)) continue;
            if (isset($otherStored[0]) && !is_array($otherStored[0])) $otherStored = [$otherStored];
            foreach ($otherStored as $otherDesc) {
                if ($this->euclideanDistance($otherDesc, $descriptor) <= 0.45) {
                    // Wajah ini sudah ada di visitor lain ($other)!
                    // Pengunjung mungkin mengganti nomor HP-nya saat ngobrol dengan AI.
                    // Alih-alih error, kita gabungkan (merge) ke profil lama.
                    if ($visitor->wasRecentlyCreated) {
                        $visitor->delete();
                    }
                    $visitor = $other;
                    $visitor->update([
                        'name'    => $this->regData['name'],
                        'company' => $this->regData['company'],
                        'phone'   => $this->regData['phone'],
                    ]);
                    $merged = true;
                    break 2; // Keluar dari kedua loop
                }
            }
        }

        // Self-face verify if existing
        $existingFeatures = is_array($visitor->face_features) ? $visitor->face_features : [];
        if (!empty($existingFeatures)) {
            if (isset($existingFeatures[0]) && !is_array($existingFeatures[0])) $existingFeatures = [$existingFeatures];
            $bestDist = 1.0;
            foreach ($existingFeatures as $sd) {
                $d = $this->euclideanDistance($sd, $descriptor);
                if ($d < $bestDist) $bestDist = $d;
            }
            if ($bestDist > 0.55) {
                $this->messages[] = ['role' => 'assistant', 'content' => '⚠️ Wajah tidak cocok dengan data sebelumnya.'];
                $this->dispatch('chatbot-scrolled');
                $this->dispatch('chatbot-face-error');
                return;
            }
        }

        // Save face data & photo (append, max 10)
        try {
            if (count($existingFeatures) < 10) {
                $existingFeatures[] = $descriptor;
                $visitor->face_features = $existingFeatures;
                
                // Fetch raw existing photos, decode, and append
                $rawPhoto = $visitor->getRawOriginal('face_photo');
                $existingPhotos = [];
                if ($rawPhoto) {
                    $jsonDecoded = json_decode($rawPhoto, true);
                    if (is_array($jsonDecoded)) {
                        $raw = isset($jsonDecoded['data']) ? $jsonDecoded['data'] : $rawPhoto;
                        try {
                            $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($raw);
                            $existingPhotos = json_decode($decrypted, true) ?? [];
                        } catch (\Throwable $e) {}
                    }
                }
                if (!is_array($existingPhotos)) $existingPhotos = [];
                $existingPhotos[] = $photoBase64;
                $visitor->face_photo = $existingPhotos;
            }
            $visitor->save();
        } catch (\Exception $e) { /* ignore */ }

        $this->createChatbotAppointment($visitor);
    }

    /**
     * Create the actual appointment/walk-in record and dispatch UI events
     */
    private function createChatbotAppointment(Visitor $visitor): void
    {
        $isWalkIn = ($this->regData['type'] ?? 'walk-in') === 'walk-in';

        // Logika disamakan dengan KioskWalkinForm:
        // Walk-in = otomatis ACC (active, checkin_time = now)
        // Appointment = pending (menunggu email PIC)
        $appointment = Appointment::create([
            'visit_id'       => VisitIdService::generate(),
            'visitor_id'     => $visitor->id,
            'pic_id'         => $this->regData['pic_id'],
            'type'           => $this->regData['type'] ?? 'walk-in',
            'status'         => $isWalkIn ? 'active' : 'pending',
            'visit_date'     => $isWalkIn ? now()->toDateString() : ($this->regData['visit_date'] ?: now()->toDateString()),
            'visit_time'     => $isWalkIn ? now()->toTimeString() : ($this->regData['visit_time'] ?: now()->format('H:i')),
            'checkin_time'   => $isWalkIn ? now()->format('H:i:s') : null,
            'purpose'        => $this->regData['purpose'] ?? '-',
            'pax'            => $this->regData['pax'] ?? 1,
            'token'          => Str::random(10),
            'approval_token' => $isWalkIn ? null : Str::uuid()->toString(),
        ]);

        if ($isWalkIn) {
            $appointment->load(['visitor', 'pic.department']);
            $picEmail = $appointment->pic?->email;
            if ($picEmail) {
                // Untuk walk-in, kirim notifikasi saja tanpa tombol approval
                Mail::to($picEmail)->send(new \App\Mail\PicWalkinNotificationMail($appointment));
            }
            $this->dispatch('walkin-success', appt: [
                'visitorName' => $this->regData['name'],
                'company'     => $this->regData['company'],
                'phone'       => $this->regData['phone'],
                'picName'     => $this->regData['pic_name'],
                'department'  => $appointment->pic?->department?->name ?? '-',
                'visit_date'  => \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y'),
                'visit_time'  => $appointment->visit_time,
                'purpose'     => $appointment->purpose,
                'type'        => $appointment->type,
            ]);
            $successMsg = '✅ **Registrasi berhasil!** Anda telah disetujui (Walk-In). Silakan masuk ke ruangan.';
        } else {
            $appointment->load(['visitor', 'pic.department']);
            $picEmail = $appointment->pic?->email;
            if ($picEmail) {
                // Untuk appointment, kirim email persetujuan (ada tombol approve/reject)
                Mail::to($picEmail)->send(new \App\Mail\PicApprovalMail($appointment));
            }
            $this->dispatch('appointment-success', appt: [
                'visitorName' => $this->regData['name'],
                'company'     => $this->regData['company'],
                'phone'       => $this->regData['phone'],
                'picName'     => $this->regData['pic_name'],
                'department'  => $appointment->pic?->department?->name ?? '-',
                'visit_date'  => \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y'),
                'visit_time'  => $appointment->visit_time,
                'purpose'     => $appointment->purpose,
                'type'        => $appointment->type,
                'token'       => $appointment->token,
            ]);
            
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($appointment->token);
            $successMsg = "✅ **Janji temu berhasil dibuat!** Menunggu persetujuan PIC.\n\nToken Anda: **{$appointment->token}**\n\nGunakan QR Code berikut untuk Check-in saat tiba di lokasi:\n\n![QR Code]({$qrUrl})";
        }

        $this->messages[] = ['role' => 'assistant', 'content' => $successMsg];

        $this->regData = [];
        $this->showConfirmation = false;
        $this->dispatch('chatbot-scrolled');
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

    // ══════════════════════════════════════════════════════════════
    //  EXISTING HELPERS
    // ══════════════════════════════════════════════════════════════

    /**
     * Kirim saat user tekan Enter (tanpa Shift)
     */
    public function submitOnEnter(): void
    {
        $this->sendMessage();
    }

    /**
     * Memilih salah satu chip saran dan mengirimkannya langsung ke AI
     */
    public function selectSuggestedChip(string $text): void
    {
        $this->inputMessage = $text;
        $this->sendMessage();
    }

    /**
     * Reset semua riwayat chat
     */
    public function clearHistory(): void
    {
        $this->messages    = [];
        $this->error       = null;
        $this->isLoading   = false;
        $this->inputMessage = '';
        $this->regData     = [];
        $this->showConfirmation = false;
    }

    public function render()
    {
        return view('livewire.interactive-chatbot');
    }
}
