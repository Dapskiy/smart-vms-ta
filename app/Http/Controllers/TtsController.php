<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class TtsController extends Controller
{
    /**
     * Generate or serve cached Edge TTS audio for given text.
     */
    public function generate(Request $request)
    {
        $text = $request->input('text', '');

        if (empty(trim($text))) {
            return response()->json(['error' => 'Text is required'], 400);
        }

        // Clean markdown and formatting
        $cleanText = $this->cleanMarkdown($text);

        if (empty($cleanText)) {
            return response()->json(['error' => 'No speakable text'], 400);
        }

        // Voice model: Indonesian Female (Gadis)
        $voice = 'id-ID-GadisNeural';

        // Maximum character limit to prevent abuse or timeout
        if (mb_strlen($cleanText) > 1500) {
            $cleanText = mb_substr($cleanText, 0, 1500);
        }

        // Cache path
        $hash = md5($voice . '|' . $cleanText);
        $storageDir = storage_path('app/public/tts');
        
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $outputPath = $storageDir . DIRECTORY_SEPARATOR . $hash . '.mp3';

        // Return cached audio if available
        if (file_exists($outputPath) && filesize($outputPath) > 0) {
            return response()->file($outputPath, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        // Temporary text file to avoid OS shell escaping issues with special characters
        $tempTxtFile = storage_path('app/public/tts/temp_' . $hash . '.txt');
        file_put_contents($tempTxtFile, $cleanText);

        $scriptPath = app_path('Scripts/generate_tts.py');

        try {
            $result = Process::env($_ENV + $_SERVER + getenv())->timeout(30)->run([
                'python',
                $scriptPath,
                $tempTxtFile,
                $voice,
                $outputPath,
            ]);

            @unlink($tempTxtFile);

            if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                return response()->file($outputPath, [
                    'Content-Type' => 'audio/mpeg',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }

            return response()->json([
                'error' => 'Failed to generate audio',
                'details' => $result->errorOutput()
            ], 500);

        } catch (\Throwable $e) {
            @unlink($tempTxtFile);
            return response()->json([
                'error' => 'TTS processing exception',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove markdown tokens, URLs, emojis, and formatting for smooth voice speech.
     */
    private function cleanMarkdown(string $text): string
    {
        // Remove URLs
        $text = preg_replace('/https?:\/\/\S+/i', '', $text);
        // Remove Markdown headers, bold, italic, code blocks, lists
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`[^`]*`/', '', $text);
        $text = preg_replace('/[*#_`~|\-><]/', ' ', $text);
        // Replace multiple newlines / spaces with single space or period
        $text = preg_replace('/\n+/', '. ', $text);
        $text = preg_replace('/\s{2,}/', ' ', $text);

        return trim($text);
    }
}
