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

        // =====================================================================================
        // 🔴 [CHEAT SHEET SIDANG] - ANTI-DUPLIKASI WAJAH (VALIDASI)
        // Di sinilah kita mencegah 1 wajah didaftarkan berulang kali dengan nama berbeda.
        // Jika tingkat kemiripan (distance) di bawah threshold, sistem akan menolak registrasi 
        // dan memberitahu bahwa wajah ini sudah terdaftar atas nama si A.
        // =====================================================================================
        $threshold  = 0.55; // Relaxed threshold for robustness

        // 1. Cek terhadap Wajah PIC/Karyawan
        $pics = \App\Models\Pic::whereNotNull('face_features')->get();
        foreach ($pics as $pic) {
            $picStored = $pic->face_features ?? [];
            if (!is_array($picStored)) continue;
            if (isset($picStored[0]) && !is_array($picStored[0])) $picStored = [$picStored];

            foreach ($picStored as $descriptor) {
                if (!is_array($descriptor) || count($descriptor) !== count($incoming)) continue;

                $distance = $this->euclideanDistance($incoming, $descriptor);
                if ($distance <= $threshold) {
                    \App\Models\FaceVerificationLog::create([
                        'visitor_id' => null,
                        'visitor_name' => $pic->name,
                        'type' => 'pic-duplicate-check',
                        'euclidean_distance' => $distance,
                        'threshold' => $threshold,
                        'is_success' => false,
                        'error_message' => "Wajah terdeteksi sebagai Karyawan/PIC ({$pic->name}).",
                        'ip_address' => $request->ip(),
                    ]);

                    return response()->json([
                        'is_duplicate' => true,
                        'distance'     => round($distance, 4),
                        'matched_name' => $pic->name,
                        'message'      => "Akses Ditolak: Wajah Anda terdeteksi sebagai Karyawan/PIC ({$pic->name}). Silakan gunakan menu Absensi PIC.",
                    ]);
                }
            }
        }

        // 2. Cek terhadap Wajah Visitor Lain
        $visitors = Visitor::whereNotNull('face_features')->get();

        $bestMatch    = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($visitors as $visitor) {
            // Lewati visitor yang sedang didaftarkan (bisa update wajah sendiri)
            if ($currentId && $visitor->id === (int) $currentId) {
                continue;
            }

            $stored = $visitor->face_features ?? [];
            if (!is_array($stored)) continue;

            // Backwards compatibility for single descriptor array
            if (isset($stored[0]) && !is_array($stored[0])) {
                $stored = [$stored];
            }

            foreach ($stored as $descriptor) {
                if (!is_array($descriptor) || count($descriptor) !== count($incoming)) continue;

                $distance = $this->euclideanDistance($incoming, $descriptor);
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch    = $visitor;
                }
            }
        }

        if ($bestMatch && $bestDistance <= $threshold) {
            $companyInfo = $bestMatch->company ? " dari {$bestMatch->company}" : "";

            \App\Models\FaceVerificationLog::create([
                'visitor_id' => $bestMatch->id,
                'visitor_name' => $bestMatch->name,
                'type' => 'duplicate-check',
                'euclidean_distance' => $bestDistance,
                'threshold' => $threshold,
                'is_success' => false,
                'error_message' => "Duplikat terdeteksi dengan {$bestMatch->name}.",
                'ip_address' => $request->ip(),
            ]);
            
            return response()->json([
                'is_duplicate'  => true,
                'distance'      => round($bestDistance, 4),
                'matched_name'  => $bestMatch->name,
                'message'       => "Wajah ini sudah terdaftar atas nama \"{$bestMatch->name}\"{$companyInfo}. Setiap visitor harus memiliki wajah unik.",
            ]);
        }

        \App\Models\FaceVerificationLog::create([
            'visitor_id' => $currentId ? (int) $currentId : null,
            'visitor_name' => null,
            'type' => 'duplicate-check',
            'euclidean_distance' => $bestMatch ? $bestDistance : null,
            'threshold' => $threshold,
            'is_success' => true,
            'ip_address' => $request->ip(),
        ]);

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
