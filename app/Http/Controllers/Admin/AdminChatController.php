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

        // Bangun system prompt + konteks data real-time
        $dataContext   = $this->aiService->buildContext();
        $systemPrompt  = "Kamu adalah VISITA AI Assistant, asisten cerdas untuk panel admin sistem manajemen kunjungan tamu VISITA Enterprise. "
            . "Kamu memiliki akses ke data real-time sistem yang disediakan di bawah ini.\n\n"
            . "Jawab pertanyaan admin secara akurat, ringkas, dan profesional menggunakan Bahasa Indonesia. "
            . "Jika data tidak tersedia, katakan dengan jujur. "
            . "Admin yang bertanya saat ini adalah: {$adminName}.\n\n"
            . $dataContext;

        try {
            $model  = config('services.gemini.model', 'gemini-2.0-flash');
            $apiKey = config('services.gemini.key');

            if (empty($apiKey)) {
                return response()->json(['error' => 'Gemini API Key belum dikonfigurasi.'], 500);
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(30)
                ->withoutVerifying() // bypass SSL cert di dev (Windows)
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $userMessage]]],
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.4, // Lebih deterministik untuk data faktual
                        'maxOutputTokens' => 600,
                    ],
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text', '...');
                Log::info("[ADMIN-AI] Admin={$adminName} | Q={$userMessage}");
                return response()->json(['reply' => trim($reply)]);
            }

            if ($response->status() === 429) {
                return response()->json(['error' => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.'], 429);
            }

            Log::warning('[ADMIN-AI] Gemini error: HTTP ' . $response->status() . ' — ' . $response->body());
            return response()->json(['error' => 'Gagal mendapat respons dari AI. (HTTP ' . $response->status() . ')'], 502);

        } catch (\Throwable $e) {
            Log::error('[ADMIN-AI] Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Koneksi ke AI gagal: ' . $e->getMessage()], 500);
        }
    }
}
