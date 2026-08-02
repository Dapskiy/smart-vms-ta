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
     * yang sudah diperkaya dengan data real-time sistem + RBAC context.
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

        $user = auth()->user();
        $userMessage = trim($request->input('message'));
        $adminName   = $user->name ?? 'Admin';

        // Bangun system prompt tiga lapis:
        // 1. buildContext()       → snapshot statistik real-time (selalu ada)
        // 2. buildRbacContext()   → RBAC role & permissions (per-user)
        // 3. getPermittedActions()→ daftar aksi yang diizinkan
        // 4. getDataForAI()       → data spesifik sesuai intent query admin
        $currentPic = \App\Models\Pic::where('user_id', $user->id)->first();
        $globalContext    = $this->aiService->buildContext();
        $rbacContext      = $this->aiService->buildRbacContext($user);
        $permittedActions = $this->aiService->getPermittedActions($user);
        $specificData     = $this->aiService->getDataForAI($userMessage, $currentPic, $user);

        // Kumpulkan info role untuk greeting personalization
        $roles = $user->getRoleNames()->implode(', ') ?: 'User';

        $systemPrompt  = "Kamu adalah VISITA AI Assistant, asisten cerdas untuk panel admin sistem manajemen kunjungan tamu VISITA Enterprise. "
            . "Kamu memiliki akses ke data real-time sistem yang disediakan di bawah ini.\n\n"
            . "Jawab pertanyaan admin secara akurat, ringkas, dan profesional menggunakan Bahasa Indonesia. "
            . "Jika data tidak tersedia, katakan dengan jujur. "
            . "PENTING: Jika status ketersediaan PIC tertulis 'Tidak Tersedia' atau 'Tidak tersedia', artinya PIC tersebut sedang sibuk/istirahat/tidak di tempat dan TIDAK bisa ditemui oleh tamu saat ini. "
            . "Admin yang bertanya saat ini adalah: {$adminName} (Role: {$roles}).\n\n"
            . $rbacContext . "\n\n"
            . $permittedActions . "\n\n"
            . "Penting:\n"
            . "1. Selalu cari ID yang relevan dari data spesifik yang dilampirkan.\n"
            . "2. JANGAN membuat ID fiktif. Jika data ID tidak ditemukan atau nama tidak cocok, sampaikan dengan sopan.\n"
            . "3. Jangan mencantumkan marker jika admin hanya bertanya biasa tanpa ada maksud menyuruh melakukan aksi.\n"
            . "4. PATUHI batasan RBAC — jangan berikan data atau eksekusi aksi di luar scope permission user.\n\n"
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
                Log::info("[ADMIN-AI] Admin={$adminName} (Role={$roles}) | Q={$userMessage}");

                // ── Deteksi dan Eksekusi Perintah Aksi (Action Execution) ──
                if (preg_match('/<!--EXEC_ACTION:(.*?)-->/s', $reply, $matches)) {
                    $actionData = json_decode(trim($matches[1]), true);
                    $execResult = '';

                    if ($actionData && isset($actionData['action'])) {
                        // ── RBAC GUARD: Validasi permission sebelum eksekusi ──
                        $permissionCheck = $this->checkActionPermission($user, $actionData['action']);

                        if ($permissionCheck !== true) {
                            // User tidak punya izin — tolak aksi
                            $reply = preg_replace('/<!--EXEC_ACTION:.*?-->/s', '', $reply);
                            $reply .= "\n\n*(Sistem: ⚠️ {$permissionCheck})*";
                            return response()->json(['reply' => trim($reply)]);
                        }

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
                                            $execResult = "\n\n*(Sistem: ✅ Janji temu ID #{$aptId} atas nama " . ($apt->visitor?->name ?? 'N/A') . " berhasil disetujui)*";
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
                                            $execResult = "\n\n*(Sistem: ✅ Janji temu ID #{$aptId} atas nama " . ($apt->visitor?->name ?? 'N/A') . " berhasil ditolak)*";
                                        }
                                    }
                                    break;

                                case 'checkout_appointment':
                                    $aptId = $actionData['appointment_id'] ?? null;
                                    if ($aptId) {
                                        $apt = \App\Models\Appointment::find($aptId);
                                        if ($apt && $apt->status === 'active') {
                                            $apt->update([
                                                'status'          => 'completed',
                                                'checkout_time'   => now()->format('H:i:s'),
                                                'checkout_method' => 'ai-chat',
                                            ]);
                                            $execResult = "\n\n*(Sistem: ✅ Tamu " . ($apt->visitor?->name ?? 'N/A') . " berhasil di-checkout via AI Chat)*";
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
                                            $execResult = "\n\n*(Sistem: ✅ Visitor ID #{$visitorId} ({$visitor->name}) telah dimasukkan ke daftar blacklist)*";
                                        }
                                    }
                                    break;

                                case 'update_availability':
                                    $currentPic = \App\Models\Pic::where('user_id', $user->id)->first();
                                    if ($currentPic && isset($actionData['is_available'])) {
                                        $isAvailable = (bool) $actionData['is_available'];
                                        $currentPic->update(['is_available' => $isAvailable]);
                                        $statusStr = $isAvailable ? 'Tersedia' : 'Tidak Tersedia';
                                        $execResult = "\n\n*(Sistem: ✅ Status ketersediaan Anda berhasil diubah menjadi {$statusStr})*";
                                    }
                                    break;

                                case 'update_location':
                                    $currentPic = \App\Models\Pic::where('user_id', $user->id)->first();
                                    $location = $actionData['location'] ?? null;
                                    if ($currentPic && $location) {
                                        $currentPic->update(['current_location' => $location]);
                                        $execResult = "\n\n*(Sistem: ✅ Lokasi Anda berhasil diperbarui ke \"{$location}\")*";
                                    }
                                    break;
                            }
                        } catch (\Throwable $e) {
                            Log::error("[ADMIN-AI-ACTION] Gagal mengeksekusi aksi: " . $e->getMessage());
                            $execResult = "\n\n*(Sistem: ❌ Gagal mengeksekusi tindakan otomatis: {$e->getMessage()})*";
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

    /**
     * Endpoint untuk mendapatkan rekomendasi/to-do list berdasarkan role user.
     */
    public function recommendations(): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $user = auth()->user();
        $recommendations = $this->aiService->buildRecommendations($user);

        return response()->json(['recommendations' => $recommendations]);
    }

    /**
     * RBAC Guard: Cek apakah user memiliki permission untuk menjalankan aksi tertentu.
     * Return true jika diizinkan, atau string pesan error jika tidak.
     */
    private function checkActionPermission($user, string $action): bool|string
    {
        return match($action) {
            'approve_appointment', 'reject_appointment', 'checkout_appointment'
                => ($user->can('update_appointment') || $user->can('action:Appointment'))
                    ? true
                    : 'Anda tidak memiliki izin untuk mengelola janji temu. Hubungi administrator.',

            'blacklist_visitor'
                => ($user->can('update_visitor') || $user->can('action:Visitor'))
                    ? true
                    : 'Anda tidak memiliki izin untuk mem-blacklist visitor. Hubungi administrator.',

            'update_availability', 'update_location'
                => \App\Models\Pic::where('user_id', $user->id)->exists()
                    ? true
                    : 'Anda tidak terdaftar sebagai PIC. Hanya PIC yang bisa mengubah status ketersediaan/lokasi.',

            default => 'Aksi tidak dikenali.'
        };
    }
}
