<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Http;

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

        // ── Logika Rekomendasi & Alur Kunjungan ──────────────────────────────
        $prompt .= "## LOGIKA REKOMENDASI KUNJUNGAN\n";
        $prompt .= "Gunakan data kehadiran PIC di bawah ini untuk memberikan saran yang tepat:\n\n";
        $prompt .= "**Skenario A — PIC yang dicari HADIR:**\n";
        $prompt .= "Sampaikan kabar baik dan langsung sarankan salah satu dari dua opsi:\n";
        $prompt .= "- **Walk-In (Sekarang)**: Tekan tombol *Kunjungan Walk-In* di layar utama Kiosk untuk registrasi langsung. PIC akan mendapat notifikasi dan harus menyetujui kunjungan.\n";
        $prompt .= "- **Janji Temu (Lain Waktu)**: Tekan tombol *Buat Janji Temu* untuk menjadwalkan pertemuan di hari yang diinginkan.\n\n";
        $prompt .= "**Skenario B — PIC yang dicari TIDAK HADIR:**\n";
        $prompt .= "1. Cek data di bawah apakah ada PIC lain dari **departemen yang sama** yang berstatus HADIR.\n";
        $prompt .= "2. Jika **ada PIC alternatif yang hadir**: Tawarkan secara sopan, contoh: *\"[Nama PIC] saat ini tidak ada di tempat. Namun, [Nama Alternatif] dari departemen yang sama sedang hadir. Apakah Anda berkenan menemui beliau?\"*\n";
        $prompt .= "3. Jika **tidak ada PIC yang hadir** di departemen tersebut: Sarankan untuk menunggu, membuat janji temu untuk hari lain, atau melapor ke pos keamanan/resepsionis fisik jika mendesak.\n\n";

        // ── Konteks Data PIC Real-Time dari Database ──────────────────────────
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
                        $status = $isPresent ? '✅ HADIR (Tersedia)' : '❌ TIDAK HADIR';
                        $prompt .= "- {$pic->name} | {$status}\n";
                    }
                    $prompt .= "\n";
                }
            }
        } catch (\Throwable $e) {
            $prompt .= "*(Gagal memuat data karyawan dari database — mohon hubungi admin sistem)*\n";
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
                $this->messages[] = [
                    'role'    => 'assistant',
                    'content' => $cleanReply,
                ];

                // Kirim event ke browser agar TTS membacakan balasan AI
                $plainText = strip_tags(preg_replace('/[#*_`~>\-|]/', '', $cleanReply));
                $this->dispatch('chatbot-speak', text: $plainText);
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
        // Note: 'chatbot-speak' sudah di-dispatch di atas saat respons sukses
    }

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
    }

    public function render()
    {
        return view('livewire.interactive-chatbot');
    }
}
