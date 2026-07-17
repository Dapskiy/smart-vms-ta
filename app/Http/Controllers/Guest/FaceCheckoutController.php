<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaceCheckoutController extends Controller
{
    /**
     * Cocokkan deskriptor wajah dengan visitor yang sedang aktif check-in hari ini.
     * Jika cocok, lakukan check-out mandiri (method = 'self').
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'descriptor' => 'required|array|min:128',
        ]);

        $incoming  = $request->input('descriptor');
        $visitors  = Visitor::whereNotNull('face_features')->get();
        $threshold = 0.5;

        $bestMatch    = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($visitors as $visitor) {
            $stored = $visitor->face_features ?? [];
            if (!is_array($stored)) continue;

            // Backwards compatibility for single descriptor array
            if (isset($stored[0]) && !is_array($stored[0])) {
                $stored = [$stored];
            }

            foreach ($stored as $descriptor) {
                if (!is_array($descriptor) || count($descriptor) !== count($incoming)) {
                    continue;
                }

                $distance = $this->euclideanDistance($incoming, $descriptor);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch    = $visitor;
                }
            }
        }

        if (!$bestMatch || $bestDistance > $threshold) {
            \App\Models\FaceVerificationLog::create([
                'visitor_id' => $bestMatch ? $bestMatch->id : null,
                'visitor_name' => $bestMatch ? $bestMatch->name : 'Unknown',
                'type' => 'checkout',
                'euclidean_distance' => $bestMatch ? $bestDistance : null,
                'threshold' => $threshold,
                'is_success' => false,
                'error_message' => 'Wajah tidak dikenali dalam sistem.',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Wajah tidak dikenali dalam sistem.',
            ], 404);
        }

        \App\Models\FaceVerificationLog::create([
            'visitor_id' => $bestMatch->id,
            'visitor_name' => $bestMatch->name,
            'type' => 'checkout',
            'euclidean_distance' => $bestDistance,
            'threshold' => $threshold,
            'is_success' => true,
            'ip_address' => $request->ip(),
        ]);

        // Cari appointment yang sedang aktif (status = 'active') untuk visitor ini
        $appointment = Appointment::where('visitor_id', $bestMatch->id)
            ->where('status', 'active')
            ->whereDate('visit_date', today())
            ->with(['visitor', 'pic', 'room'])
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => "Hai, {$bestMatch->name}! Tidak ada sesi aktif yang perlu di-checkout.",
            ], 404);
        }

        // Lakukan check-out mandiri
        $appointment->update([
            'status'          => 'completed',
            'checkout_time'   => now()->format('H:i'),
            'checkout_method' => 'self',
        ]);

        Log::info("[KIOSK CHECKOUT] Self-checkout: visitor={$bestMatch->name}, appointment_id={$appointment->id}, time={$appointment->checkout_time}");

        return response()->json([
            'success' => true,
            'message' => 'Check-out berhasil!',
            'data'    => [
                'visitor_name'  => $bestMatch->name,
                'pic_name'      => $appointment->pic?->name ?? '-',
                'room_name'     => $appointment->room?->name ?? '-',
                'checkin_time'  => $appointment->checkin_time ?? '-',
                'checkout_time' => $appointment->checkout_time,
                'visit_date'    => $appointment->visit_date?->translatedFormat('d F Y') ?? '-',
            ],
        ]);
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
}
