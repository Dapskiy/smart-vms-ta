<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class FaceCheckinController extends Controller
{
    /**
     * Validate face descriptor against all visitors who have face_features
     * and have a pending appointment today.
     * Optionally receives face_photo (base64 data URI) → encrypted & stored.
     */
    public function checkin(Request $request)
    {
        $request->validate([
            'descriptor' => 'required|array|min:128',
            'face_photo' => 'nullable|string', // base64 data URI dari kamera
        ]);

        $incomingDescriptor = $request->input('descriptor');

        // Find all visitors with stored face features
        $visitors = Visitor::whereNotNull('face_features')->get();

        $bestMatch     = null;
        $bestDistance  = PHP_FLOAT_MAX;
        $threshold     = 0.5; // Euclidean distance threshold

        foreach ($visitors as $visitor) {
            $stored = json_decode($visitor->face_features, true);
            if (!is_array($stored)) continue;

            // Backwards compatibility for single descriptor array
            if (isset($stored[0]) && !is_array($stored[0])) {
                $stored = [$stored];
            }

            foreach ($stored as $descriptor) {
                if (count($descriptor) !== count($incomingDescriptor)) {
                    continue;
                }

                $distance = $this->euclideanDistance($incomingDescriptor, $descriptor);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch    = $visitor;
                }
            }
        }

        if (!$bestMatch || $bestDistance > $threshold) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah tidak dikenali dalam sistem.',
            ], 404);
        }

        // Simpan foto wajah terenkripsi jika dikirim
        if ($request->filled('face_photo')) {
            try {
                $existingPhotos = [];
                if (!empty($bestMatch->face_photo)) {
                    $decoded = json_decode($bestMatch->face_photo, true);
                    if (is_array($decoded)) {
                        $existingPhotos = $decoded;
                    } else {
                        $existingPhotos = [$bestMatch->face_photo];
                    }
                }
                $existingPhotos[] = Crypt::encryptString($request->input('face_photo'));
                if (count($existingPhotos) > 10) {
                    array_shift($existingPhotos);
                }

                $existingFeatures = [];
                if (!empty($bestMatch->face_features)) {
                    $decoded = json_decode($bestMatch->face_features, true);
                    if (is_array($decoded)) {
                        $existingFeatures = (isset($decoded[0]) && is_array($decoded[0])) ? $decoded : [$decoded];
                    }
                }
                $existingFeatures[] = $incomingDescriptor;
                if (count($existingFeatures) > 10) {
                    array_shift($existingFeatures);
                }

                $bestMatch->update([
                    'face_photo' => json_encode($existingPhotos),
                    'face_features' => json_encode($existingFeatures)
                ]);
                Log::info("[FACE-CHECKIN] Face photo and descriptor appended for visitor #{$bestMatch->id}");
            } catch (\Throwable $e) {
                Log::warning("[FACE-CHECKIN] Failed to save face photo: " . $e->getMessage());
            }
        }

        // Find a pending appointment for this visitor today
        $appointment = Appointment::where('visitor_id', $bestMatch->id)
            ->where('status', 'pending')
            ->whereDate('visit_date', today())
            ->with(['visitor', 'pic', 'room'])
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => "Selamat datang, {$bestMatch->name}! Tidak ada janji aktif hari ini.",
            ], 404);
        }

        // Perform check-in
        $appointment->update([
            'status'       => 'active',
            'checkin_time' => now()->format('H:i'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil!',
            'appointment' => [
                'visitor_name' => $bestMatch->name,
                'visitor_phone' => $bestMatch->phone ?? '-',
                'pic_name'     => $appointment->pic?->name ?? '-',
                'room_name'    => $appointment->room?->name ?? '-',
                'visit_date'   => $appointment->visit_date?->translatedFormat('d F Y') ?? '-',
                'visit_time'   => $appointment->visit_time ?? '-',
                'checkin_time' => $appointment->checkin_time ?? now()->format('H:i'),
                'purpose'      => $appointment->purpose ?? '-',
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
