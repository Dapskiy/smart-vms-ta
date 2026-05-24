<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Illuminate\Http\Request;

class FaceValidationController extends Controller
{
    /**
     * Cek apakah deskriptor wajah sudah terdaftar pada visitor LAIN di sistem.
     * Digunakan sebelum menyimpan face_features baru agar tidak terjadi duplikasi.
     *
     * Request body:
     *   descriptor   : array[128] float - deskriptor wajah dari face-api.js
     *   visitor_id   : int|null         - ID visitor saat ini (diabaikan dari pengecekan)
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'descriptor' => 'required|array|min:128',
            'visitor_id' => 'nullable|integer',
        ]);

        $incoming   = $request->input('descriptor');
        $currentId  = $request->input('visitor_id');
        $threshold  = 0.45; // Lebih ketat dari threshold check-in (0.5)

        $visitors = Visitor::whereNotNull('face_features')->get();

        $bestMatch    = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($visitors as $visitor) {
            // Lewati visitor yang sedang didaftarkan (bisa update wajah sendiri)
            if ($currentId && $visitor->id === (int) $currentId) {
                continue;
            }

            $stored = json_decode($visitor->face_features, true);
            if (!is_array($stored)) continue;

            // Backwards compatibility for single descriptor array
            if (isset($stored[0]) && !is_array($stored[0])) {
                $stored = [$stored];
            }

            foreach ($stored as $descriptor) {
                if (count($descriptor) !== count($incoming)) continue;

                $distance = $this->euclideanDistance($incoming, $descriptor);
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch    = $visitor;
                }
            }
        }

        if ($bestMatch && $bestDistance <= $threshold) {
            $companyInfo = $bestMatch->company ? " dari {$bestMatch->company}" : "";
            
            return response()->json([
                'is_duplicate'  => true,
                'distance'      => round($bestDistance, 4),
                'matched_name'  => $bestMatch->name,
                'message'       => "Wajah ini sudah terdaftar atas nama \"{$bestMatch->name}\"{$companyInfo}. Setiap visitor harus memiliki wajah unik.",
            ]);
        }

        return response()->json([
            'is_duplicate' => false,
            'distance'     => round($bestDistance, 4),
            'message'      => 'Wajah belum terdaftar, aman untuk disimpan.',
        ]);
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $val) {
            $diff = $val - ($b[$i] ?? 0.0);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
}
