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

    // ── Registration State ────────────────────────────────────────
    /** @var array Collected registration data from AI conversation */
    public array $regData = [];

    /** @var bool Show confirmation card in chat */
    public bool $showConfirmation = false;

    /**
     * Membangun System Prompt secara dinamis dengan menyuntikkan data kehadiran
     * PIC real-time dari database. Data sensitif (email, telepon) dikecualikan
     * secara eksplisit untuk menjaga privasi di lingkungan Kiosk publik.
     */
    private function getSystemPrompt(): string
    {
        // ── Base Instruction ──────────────────────────────────────────────────
        $prompt  = "Kamu adalah **VISITA Virtual Receptionist**, asisten virtual cerdas dan ramah yang bertugas di layar Kiosk pendaftaran tamu milik VISITA Enterprise.\n\n";
        $prompt .= "Tugas utama kamu adalah menyambut pengunjung, memandu alur kunjungan (Walk-In, Janji Temu, Absensi Karyawan), serta membantu pengunjung mengetahui ketersediaan karyawan (PIC) yang ingin mereka temui hari ini — semua tanpa perlu bantuan resepsionis fisik.\n\n";

        // ── Aturan Personalitas ───────────────────────────────────────────────
        $prompt .= "## ATURAN UTAMA & PERSONALITAS\n";
        $prompt .= "1. **Sikap**: Sangat ramah, sopan, profesional, dan percaya diri. Bayangkan kamu adalah resepsionis bintang lima yang siap melayani.\n";
        $prompt .= "2. **Fokus Topik**: Kamu hanya melayani pertanyaan seputar kunjungan, kehadiran PIC, dan alur Kiosk (check-in, walk-in, appointment). Jika ada pertanyaan di luar topik ini, arahkan kembali dengan sopan.\n";
        $prompt .= "3. **Gaya Bahasa**: Gunakan Bahasa Indonesia yang formal namun hangat. Gunakan format Markdown (bold, bullet list) agar jawaban mudah dibaca di layar sentuh Kiosk.\n\n";

        // ── Aturan Privasi & Keamanan (Kritis) ───────────────────────────────
        $prompt .= "## PRIVASI & KEAMANAN KIOSK (WAJIB DIPATUHI)\n";
        $prompt .= "Kamu berada di Kiosk **publik** yang dapat diakses siapa saja. Aturan privasi berikut bersifat MUTLAK dan tidak dapat dilanggar:\n";
        $prompt .= "- **DILARANG KERAS** memberikan Nomor Telepon, Email, Alamat, atau data pribadi karyawan (PIC) kepada pengunjung dengan alasan apapun.\n";
        $prompt .= "- Informasi yang **BOLEH** kamu bagikan tentang PIC hanya: **Nama**, **Departemen**, dan **Status Kehadiran** (Hadir/Tidak Hadir).\n";
        $prompt .= "- Jika pengunjung meminta kontak PIC, tolak dengan sopan: *\"Mohon maaf, demi alasan privasi, kontak langsung karyawan tidak dapat kami berikan. Silakan lakukan pendaftaran di Kiosk ini agar karyawan mendapat notifikasi secara otomatis.\"*\n\n";

        // ── SHORTCUT ACTIONS (INTEGRASI KAMERA) ──
        $prompt .= "## SHORTCUT TINDAKAN / KAMERA PENGENALAN WAJAH (PRIORITAS TERTINGGI)\n";
        $prompt .= "Jika maksud/permintaan pengunjung sesuai dengan 4 tindakan di bawah ini, kamu WAJIB LANGSUNG mengeksekusi marker yang sesuai dan ABAIKAN SEMUA aturan pertanyaan lain (termasuk pertanyaan tamu lama/baru):\n";
        $prompt .= "1. Check-in Janji Temu (sudah ada janji) -> tambahkan marker: <!--ACTION:appointment-->\n";
        $prompt .= "2. Check-out -> tambahkan marker: <!--ACTION:checkout-->\n";
        $prompt .= "3. Registrasi via Kamera (Tamu Baru) -> tambahkan marker: <!--ACTION:walkin-->\n";
        $prompt .= "4. Absensi / Absen (Karyawan) -> tambahkan marker: <!--ACTION:attendance-->\n";
        $prompt .= "Contoh balasan Absensi: \"Baik, silakan arahkan wajah Anda ke layar untuk proses absensi. <!--ACTION:attendance-->\"\n\n";

        // ── Logika Rekomendasi & Alur Kunjungan ──────────────────────────────
        $prompt .= "## LOGIKA REKOMENDASI KUNJUNGAN\n";
        $prompt .= "Gunakan data kehadiran PIC di bawah ini untuk memberikan saran yang tepat:\n\n";
        $prompt .= "**Skenario A — PIC yang dicari HADIR:**\n";
        $prompt .= "Sampaikan kabar baik dan langsung sarankan salah satu dari dua opsi:\n";
        $prompt .= "- **Walk-In (Sekarang)**: Registrasi langsung melalui percakapan ini. PIC akan mendapat notifikasi dan harus menyetujui kunjungan.\n";
        $prompt .= "- **Janji Temu (Lain Waktu)**: Menjadwalkan pertemuan di hari yang diinginkan.\n\n";
        $prompt .= "**Skenario B — PIC yang dicari TIDAK HADIR:**\n";
        $prompt .= "1. Cek data di bawah apakah ada PIC lain dari **departemen yang sama** yang berstatus HADIR.\n";
        $prompt .= "2. Jika **ada PIC alternatif yang hadir**: Tawarkan secara sopan.\n";
        $prompt .= "3. Jika **tidak ada PIC yang hadir**: Sarankan untuk membuat janji temu untuk hari lain.\n\n";

        // ── PENDAFTARAN VIA CHATBOT ──────────────────────────────────────────
        $prompt .= "## PENDAFTARAN KUNJUNGAN VIA PERCAKAPAN\n";
        $prompt .= "Kamu BISA mendaftarkan pengunjung langsung melalui percakapan ini.\n\n";

        // ── ALUR TAMU LAMA (RETURNING VISITOR) ──
        $prompt .= "### LANGKAH PERTAMA PADA PENDAFTARAN MANUAL:\n";
        $prompt .= "(Abaikan langkah ini jika pengunjung menggunakan salah satu SHORTCUT TINDAKAN di atas)\n";
        $prompt .= "Sebelum mengumpulkan data, SELALU tanyakan dulu: **\"Apakah Bapak/Ibu pernah berkunjung sebelumnya?\"**\n\n";
        $prompt .= "Jika pengunjung menjawab **YA/SUDAH PERNAH**, sertakan marker ini di akhir respons:\n";
        $prompt .= "<!--FACE_LOOKUP-->\n";
        $prompt .= "Dan minta pengunjung untuk melakukan scan wajah agar data mereka otomatis terisi.\n";
        $prompt .= "Setelah scan wajah berhasil, sistem akan otomatis mengirim pesan dengan data pengunjung yang sudah terisi. Kamu hanya perlu mengumpulkan data yang BELUM terisi: **tujuan kunjungan**, **nama PIC**, dan **jenis kunjungan**.\n\n";

        // ── ALUR TAMU BARU ──
        $prompt .= "### JIKA TAMU BARU (belum pernah berkunjung), kumpulkan data berikut secara bertahap:\n";
        $prompt .= "1. **Nama lengkap** pengunjung\n";
        $prompt .= "2. **Nama perusahaan/instansi**\n";
        $prompt .= "3. **Nomor telepon/WhatsApp** (format: 08xxx)\n";
        $prompt .= "4. **Tujuan kunjungan** (singkat)\n";
        $prompt .= "5. **Nama karyawan (PIC)** yang ingin ditemui — HARUS cocok dengan data PIC di bawah\n";
        $prompt .= "6. **Jenis**: walk-in (sekarang) atau appointment (jadwal nanti)\n";
        $prompt .= "7. Jika appointment: **tanggal** (YYYY-MM-DD) dan **waktu** (HH:mm)\n\n";

        // ── MARKER PENDAFTARAN ──
        $prompt .= "### MARKER PENDAFTARAN\n";
        $prompt .= "SETELAH semua data lengkap (baik tamu baru maupun tamu lama), konfirmasi ulang ke pengunjung.\n";
        $prompt .= "Jika pengunjung setuju, sertakan marker ini di AKHIR respons:\n";
        $prompt .= "<!--REGISTER:{\"name\":\"...\",\"company\":\"...\",\"phone\":\"...\",\"purpose\":\"...\",\"pic_name\":\"...\",\"pax\":1,\"type\":\"walk-in\",\"visit_date\":\"\",\"visit_time\":\"\"}-->\n\n";
        $prompt .= "ATURAN MARKER:\n";
        $prompt .= "- JANGAN sertakan <!--REGISTER:...--> sampai pengunjung SETUJU\n";
        $prompt .= "- JANGAN sertakan <!--FACE_LOOKUP--> lebih dari sekali per percakapan\n";
        $prompt .= "- pic_name harus PERSIS sama dengan nama di data PIC di bawah\n";
        $prompt .= "- Untuk walk-in: visit_date dan visit_time boleh kosong\n";
        $prompt .= "- pax default 1 kecuali pengunjung menyebut jumlah lain\n\n";

        // ── Konteks Data PIC Real-Time dari Database (Hanya jika Onsite) ────────
        if (\App\Helpers\KioskHelper::isKioskLocal()) {
            $prompt .= "## DATA KARYAWAN (PIC) & STATUS KEHADIRAN HARI INI\n";
            $prompt .= "*(Data ini diperbarui secara otomatis dari sistem — gunakan sebagai acuan utama)*\n\n";

            try {
                // Eager-load relasi department + log absensi terbaru hari ini
                $pics = \App\Models\Pic::with(['department', 'attendances' => function ($query) {
                    $query->whereDate('checked_at', today())->latest('checked_at');
                }])->get();

                if ($pics->isEmpty()) {
                    $prompt .= "*(Belum ada data karyawan terdaftar di sistem)*\n";
                } else {
                    // Kelompokkan per departemen agar lebih mudah dipahami AI
                    $byDept = $pics->groupBy(fn($p) => $p->department?->name ?? 'Umum');

                    foreach ($byDept as $deptName => $deptPics) {
                        $prompt .= "**Departemen: {$deptName}**\n";
                        foreach ($deptPics as $pic) {
                            // Tentukan kehadiran berdasarkan log absensi terbaru hari ini
                            // (sumber kebenaran yang sama dengan tabel admin Filament)
                            $latestAttendance = $pic->attendances->first();
                            $isPresent = ($latestAttendance && $latestAttendance->type === 'checkin');
                            $locText = ($isPresent && $pic->current_location) ? " di Gedung {$pic->current_location}" : '';
                            $status = $isPresent ? "✅ HADIR (Tersedia{$locText})" : '❌ TIDAK HADIR';
                            $prompt .= "- {$pic->name} | {$status}\n";
                        }
                        $prompt .= "\n";
                    }
                }
            } catch (\Throwable $e) {
                $prompt .= "*(Gagal memuat data karyawan dari database — mohon hubungi admin sistem)*\n";
            }
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
            } elseif ($response->status() === 429) {
                $this->error = 'Permintaan terlalu banyak. Tunggu sebentar lalu coba lagi. (Rate limit)';
            } else {
                $this->error = 'Gagal mendapat respons dari AI. (HTTP ' . $response->status() . ')';
            }
        } catch (\Throwable $e) {
            $this->error = 'Koneksi ke AI gagal: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }

        // Scroll ke bawah setelah respons masuk
        $this->dispatch('chatbot-scrolled');
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
        $threshold = 0.5;

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

        // Global face duplicate check
        $allOthers = Visitor::whereNotNull('face_features')->where('id', '!=', $visitor->id)->get();
        foreach ($allOthers as $other) {
            $otherStored = $other->face_features ?? [];
            if (!is_array($otherStored)) continue;
            if (isset($otherStored[0]) && !is_array($otherStored[0])) $otherStored = [$otherStored];
            foreach ($otherStored as $otherDesc) {
                if ($this->euclideanDistance($otherDesc, $descriptor) <= 0.45) {
                    $this->messages[] = ['role' => 'assistant', 'content' => "⚠️ Wajah sudah terdaftar atas nama **{$other->name}**. Gunakan opsi 'Sudah Pernah Berkunjung'."];
                    $this->dispatch('chatbot-scrolled');
                    $this->dispatch('chatbot-face-error');
                    return;
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
            if ($bestDist > 0.4) {
                $this->messages[] = ['role' => 'assistant', 'content' => '⚠️ Wajah tidak cocok dengan data sebelumnya.'];
                $this->dispatch('chatbot-scrolled');
                $this->dispatch('chatbot-face-error');
                return;
            }
        }

        // Save face data (append, max 10)
        try {
            if (count($existingFeatures) < 10) {
                $existingFeatures[] = $descriptor;
                $visitor->face_features = $existingFeatures;
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
        $approvalToken = $isWalkIn ? Str::uuid()->toString() : null;

        $appointment = Appointment::create([
            'visit_id'       => VisitIdService::generate(),
            'visitor_id'     => $visitor->id,
            'pic_id'         => $this->regData['pic_id'],
            'type'           => $this->regData['type'] ?? 'walk-in',
            'status'         => 'pending',
            'visit_date'     => $isWalkIn ? now()->toDateString() : ($this->regData['visit_date'] ?: now()->toDateString()),
            'visit_time'     => $isWalkIn ? now()->toTimeString() : ($this->regData['visit_time'] ?: now()->format('H:i')),
            'purpose'        => $this->regData['purpose'] ?? '-',
            'pax'            => $this->regData['pax'] ?? 1,
            'token'          => Str::random(10),
            'approval_token' => $approvalToken,
        ]);

        if ($isWalkIn) {
            $appointment->load(['visitor', 'pic.department']);
            $picEmail = $appointment->pic?->email;
            if ($picEmail) {
                Mail::to($picEmail)->send(new PicApprovalMail($appointment));
            }
            $this->dispatch('walkin-pending-approval',
                token: $approvalToken,
                visitorName: $this->regData['name'],
                company: $this->regData['company'],
                phone: $this->regData['phone'],
                picName: $this->regData['pic_name'],
                department: $appointment->pic?->department?->name ?? '-',
                visit_date: \Carbon\Carbon::parse($appointment->visit_date)->translatedFormat('d F Y'),
            );
        } else {
            $appointment->load(['visitor', 'pic.department']);
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
            ]);
        }

        $successMsg = $isWalkIn
            ? '✅ **Registrasi berhasil!** Menunggu persetujuan dari karyawan yang dituju...'
            : '✅ **Janji temu berhasil dibuat!** Token akan dikirimkan ke WhatsApp Anda.';
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
