<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteHelper
{
    /**
     * Send WhatsApp message via Fonnte API.
     *
     * @param string $target Target phone number (e.g. 08123456789 or 628123456789)
     * @param string $message The message text
     * @param int $delay Delay in seconds before sending
     * @return bool
     */
    public static function sendMessage(string $target, string $message, int $delay = 9): bool
    {
        $token = env('FONNTE_TOKEN');
        if (empty($token)) {
            Log::warning('Fonnte token is not set. WhatsApp message not sent.');
            return false;
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'delay' => (string) $delay,
                'countryCode' => '62', // Default to Indonesia
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === true) {
                    Log::info("Fonnte: Message queued successfully to {$target} with {$delay}s delay.");
                    return true;
                } else {
                    Log::error("Fonnte Error: " . json_encode($data));
                    return false;
                }
            } else {
                Log::error("Fonnte HTTP Error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Fonnte Exception: " . $e->getMessage());
            return false;
        }
    }
}
