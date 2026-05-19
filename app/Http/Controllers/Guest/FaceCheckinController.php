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
     */
    public function checkin(Request $request)
    {
        $request->validate([
            'descriptor' => 'required|array|min:128',
        ]);

        $incomingDescriptor = $request->input('descriptor');

        // Find all visitors with stored face features
        $visitors = Visitor::whereNotNull('face_features')->get();

        $bestMatch     = null;
        $bestDistance  = PHP_FLOAT_MAX;
        $threshold     = 0.5; // Euclidean distance threshold

        foreach ($visitors as $visitor) {
            $stored = json_decode($visitor->face_features, true);
            if (!is_array($stored) || count($stored) !== count($incomingDescriptor)) {
                continue;
            }

            $distance = $this->euclideanDistance($incomingDescriptor, $stored);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch    = $visitor;
            }
        }

        if (!$bestMatch || $bestDistance > $threshold) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah tidak dikenali dalam sistem.',
            ], 404);
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
