<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Visitor;
use Illuminate\Http\Request;
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
            $stored = $visitor->face_features ?? [];
            if (!is_array($stored)) continue;

            // Backwards compatibility for single descriptor array
            if (isset($stored[0]) && !is_array($stored[0])) {
                $stored = [$stored];
            }

            foreach ($stored as $descriptor) {
                if (!is_array($descriptor) || count($descriptor) !== count($incomingDescriptor)) {
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

        // Simpan foto wajah & descriptor jika dikirim
        if ($request->filled('face_photo')) {
            try {
                $existingPhotos = is_array($bestMatch->face_photo) ? $bestMatch->face_photo : [];
                $existingFeatures = is_array($bestMatch->face_features) ? $bestMatch->face_features : [];

                // Backwards compatibility for single descriptor array
                if (!empty($existingFeatures) && isset($existingFeatures[0]) && !is_array($existingFeatures[0])) {
                    $existingFeatures = [$existingFeatures];
                }

                $saveData = [];

                // Maksimal 10 sampel per visitor — jika sudah penuh, tidak ditambah lagi
                if (count($existingPhotos) < 10) {
                    $existingPhotos[] = $request->input('face_photo');
                    $saveData['face_photo'] = $existingPhotos;
                }
                if (count($existingFeatures) < 10) {
                    $existingFeatures[] = $incomingDescriptor;
                    $saveData['face_features'] = $existingFeatures;
                }

                if (!empty($saveData)) {
                    $bestMatch->update($saveData);
                }
                Log::info("[FACE-CHECKIN] Face data appended for visitor #{$bestMatch->id} (features: " . count($existingFeatures) . "/10)");
            } catch (\Throwable $e) {
                Log::warning("[FACE-CHECKIN] Failed to save face photo: " . $e->getMessage());
            }
        }

        // Blacklisted visitors tidak bisa check-in via kiosk
        if ($bestMatch->is_blacklisted) {
            Log::warning("[FACE-CHECKIN] Blocked blacklisted visitor #{$bestMatch->id} ({$bestMatch->name})");
            return response()->json([
                'success' => false,
                'message' => "Maaf, {$bestMatch->name} telah diblacklist dan tidak dapat melakukan check-in. Silahkan hubungi admin untuk membuka blacklist.",
            ], 403);
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
