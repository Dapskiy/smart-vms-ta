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
     * System prompt — ubah sesuai konteks aplikasi
     */
    private string $systemPrompt = 'Kamu adalah asisten virtual VISITA Enterprise, sistem manajemen kunjungan tamu. '
        . 'Bantu user menjawab pertanyaan seputar cara membuat appointment, proses check-in, dan fitur sistem. '
        . 'Gunakan Bahasa Indonesia yang sopan dan profesional. '
        . 'Jika ditanya di luar topik sistem kunjungan, arahkan kembali dengan ramah.';

    /**
     * Kirim pesan user ke Gemini dan simpan balasannya
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
            $model  = config('services.gemini.model', 'gemini-2.0-flash');
            $apiKey = config('services.gemini.key');
            $url    = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            // Bangun history dalam format Gemini (role: user / model)
            $contents = [];
            foreach ($this->messages as $msg) {
                $contents[] = [
                    'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]],
                ];
            }

            $response = Http::timeout(30)
                ->withoutVerifying()  // bypass SSL cert error di Windows (cURL error 60)
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => $this->systemPrompt]],
                    ],
                    'contents'         => $contents,
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 500,
                    ],
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text', '...');
                $this->messages[] = [
                    'role'    => 'assistant',
                    'content' => trim($reply),
                ];
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

    /**
     * Kirim saat user tekan Enter (tanpa Shift)
     */
    public function submitOnEnter(): void
    {
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
