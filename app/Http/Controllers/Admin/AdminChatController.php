<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminChatController extends Controller
{
    public function __construct(
        private readonly AdminAIService $aiService
    ) {}

    /**
     * Terima pertanyaan dari admin dan kembalikan respons dari Gemini AI
     * yang sudah diperkaya dengan data real-time sistem.
     */
    public function ask(Request $request): JsonResponse
    {
        // Pastikan hanya user yang sudah login
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = trim($request->input('message'));
        $adminName   = auth()->user()->name ?? 'Admin';

        // Bangun system prompt dua lapis:
        // 1. buildContext()   → snapshot statistik real-time (selalu ada)
        // 2. getDataForAI()   → data spesifik sesuai intent query admin
        $currentPic = \App\Models\Pic::where('user_id', auth()->id())->first();
        $globalContext  = $this->aiService->buildContext();
        $specificData   = $this->aiService->getDataForAI($userMessage, $currentPic);

        $systemPrompt  = "Kamu adalah VISITA AI Assistant, asisten cerdas untuk panel admin sistem manajemen kunjungan tamu VISITA Enterprise. "
            . "Kamu memiliki akses ke data real-time sistem yang disediakan di bawah ini.\n\n"
            . "Jawab pertanyaan admin secara akurat, ringkas, dan profesional menggunakan Bahasa Indonesia. "
            . "Jika data tidak tersedia, katakan dengan jujur. "
            . "PENTING: Jika status ketersediaan PIC tertulis 'Tidak Tersedia' atau 'Tidak tersedia', artinya PIC tersebut sedang sibuk/istirahat/tidak di tempat dan TIDAK bisa ditemui oleh tamu saat ini. "
            . "Admin yang bertanya saat ini adalah: {$adminName}.\n\n"
            . "## SISTEM EKSEKUSI TINDAKAN (ACTION DISPATCHER)\n"
            . "Kamu memiliki kemampuan khusus untuk melakukan perubahan database secara otomatis jika admin meminta tindakan tersebut. "
            . "Sertakan marker JSON di akhir baris jawaban Anda jika dipicu oleh permintaan admin:\n"
            . "- Menyetujui janji temu pending: <!--EXEC_ACTION:{\"action\":\"approve_appointment\",\"appointment_id\":ID}-->\n"
            . "- Menolak janji temu pending: <!--EXEC_ACTION:{\"action\":\"reject_appointment\",\"appointment_id\":ID}-->\n"
            . "- Mem-blacklist visitor: <!--EXEC_ACTION:{\"action\":\"blacklist_visitor\",\"visitor_id\":ID,\"reason\":\"ALASAN\"}-->\n"
            . "- Mengubah status ketersediaan kamu sendiri: <!--EXEC_ACTION:{\"action\":\"update_availability\",\"is_available\":true/false}-->\n"
            . "- Mengubah lokasi saat ini kamu sendiri: <!--EXEC_ACTION:{\"action\":\"update_location\",\"location\":\"LOKASI\"}-->\n\n"
            . "Penting:\n"
            . "1. Selalu cari ID yang relevan dari data spesifik yang dilampirkan.\n"
            . "2. JANGAN membuat ID fiktif. Jika data ID tidak ditemukan atau nama tidak cocok, sampaikan dengan sopan.\n"
            . "3. Jangan mencantumkan marker jika admin hanya bertanya biasa tanpa ada maksud menyuruh melakukan aksi.\n\n"
            . $globalContext
            . "\n\n---\nDATA SPESIFIK UNTUK PERTANYAAN INI:\n"
            . $specificData;

        try {
            $model  = config('services.openai.model', 'gpt-4o-mini');
            $apiKey = config('services.openai.key');

            if (empty($apiKey)) {
                return response()->json(['error' => 'OpenAI API Key belum dikonfigurasi.'], 500);
            }

            $url = 'https://api.openai.com/v1/chat/completions';

            $response = Http::timeout(30)
                ->withoutVerifying() // bypass SSL cert di dev (Windows)
                ->withToken($apiKey)
                ->post($url, [
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userMessage],
                    ],
                    'temperature' => 0.4, // Lebih deterministik untuk data faktual
                    'max_tokens'  => 600,
                ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content', '...');
                Log::info("[ADMIN-AI] Admin={$adminName} | Q={$userMessage}");

                // ── Deteksi dan Eksekusi Perintah Aksi (Action Execution) ──
                if (preg_match('/<!--EXEC_ACTION:(.*?)-->/s', $reply, $matches)) {
                    $actionData = json_decode(trim($matches[1]), true);
                    $execResult = '';

                    if ($actionData && isset($actionData['action'])) {
                        try {
                            switch ($actionData['action']) {
                                case 'approve_appointment':
                                    $aptId = $actionData['appointment_id'] ?? null;
                                    if ($aptId) {
                                        $apt = \App\Models\Appointment::find($aptId);
                                        if ($apt) {
                                            $apt->update([
                                                'status'       => 'active',
                                                'checkin_time' => now()->format('H:i'),
                                                'approved_at'  => now(),
                                            ]);
                                            $execResult = "\n\n*(Sistem: Janji temu ID #{$aptId} atas nama " . ($apt->visitor?->name ?? 'N/A') . " berhasil disetujui)*";
                                        }
                                    }
                                    break;

                                case 'reject_appointment':
                                    $aptId = $actionData['appointment_id'] ?? null;
                                    if ($aptId) {
                                        $apt = \App\Models\Appointment::find($aptId);
                                        if ($apt) {
                                            $apt->update([
                                                'status'      => 'rejected',
                                                'rejected_at' => now(),
                                            ]);
                                            $execResult = "\n\n*(Sistem: Janji temu ID #{$aptId} atas nama " . ($apt->visitor?->name ?? 'N/A') . " berhasil ditolak)*";
                                        }
                                    }
                                    break;

                                case 'blacklist_visitor':
                                    $visitorId = $actionData['visitor_id'] ?? null;
                                    $reason = $actionData['reason'] ?? 'Melanggar aturan/kebijakan lobi via AI Chat';
                                    if ($visitorId) {
                                        $visitor = \App\Models\Visitor::find($visitorId);
                                        if ($visitor) {
                                            $visitor->update([
                                                'is_blacklisted' => true,
                                                'blacklist_reason' => $reason,
                                            ]);
                                            $execResult = "\n\n*(Sistem: Visitor ID #{$visitorId} ({$visitor->name}) telah dimasukkan ke daftar blacklist)*";
                                        }
                                    }
                                    break;

                                case 'update_availability':
                                    $currentPic = \App\Models\Pic::where('user_id', auth()->id())->first();
                                    if ($currentPic && isset($actionData['is_available'])) {
                                        $isAvailable = (bool) $actionData['is_available'];
                                        $currentPic->update(['is_available' => $isAvailable]);
                                        $statusStr = $isAvailable ? 'Tersedia' : 'Tidak Tersedia';
                                        $execResult = "\n\n*(Sistem: Status ketersediaan Anda berhasil diubah menjadi {$statusStr})*";
                                    }
                                    break;

                                case 'update_location':
                                    $currentPic = \App\Models\Pic::where('user_id', auth()->id())->first();
                                    $location = $actionData['location'] ?? null;
                                    if ($currentPic && $location) {
                                        $currentPic->update(['current_location' => $location]);
                                        $execResult = "\n\n*(Sistem: Lokasi Anda berhasil diperbarui ke \"{$location}\")*";
                                    }
                                    break;
                            }
                        } catch (\Throwable $e) {
                            Log::error("[ADMIN-AI-ACTION] Gagal mengeksekusi aksi: " . $e->getMessage());
                            $execResult = "\n\n*(Sistem: Gagal mengeksekusi tindakan otomatis: {$e->getMessage()})*";
                        }
                    }

                    // Bersihkan tag EXEC_ACTION agar tidak tampil mentah di bubble chat
                    $reply = preg_replace('/<!--EXEC_ACTION:.*?-->/s', '', $reply);
                    $reply .= $execResult;
                }

                return response()->json(['reply' => trim($reply)]);
            }

            if ($response->status() === 429) {
                return response()->json(['error' => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.'], 429);
            }

            Log::warning('[ADMIN-AI] OpenAI error: HTTP ' . $response->status() . ' — ' . $response->body());
            return response()->json(['error' => 'Gagal mendapat respons dari AI. (HTTP ' . $response->status() . ')'], 502);

        } catch (\Throwable $e) {
            Log::error('[ADMIN-AI] Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Koneksi ke AI gagal: ' . $e->getMessage()], 500);
        }
    }
}
