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
        $globalContext  = $this->aiService->buildContext();
        $specificData   = $this->aiService->getDataForAI($userMessage);

        $systemPrompt  = "Kamu adalah VISITA AI Assistant, asisten cerdas untuk panel admin sistem manajemen kunjungan tamu VISITA Enterprise. "
            . "Kamu memiliki akses ke data real-time sistem yang disediakan di bawah ini.\n\n"
            . "Jawab pertanyaan admin secara akurat, ringkas, dan profesional menggunakan Bahasa Indonesia. "
            . "Jika data tidak tersedia, katakan dengan jujur. "
            . "Admin yang bertanya saat ini adalah: {$adminName}.\n\n"
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
