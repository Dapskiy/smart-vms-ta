<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor; 

class VisitorFacePhotoController extends Controller
{
    
    /**
     * Tampilkan galeri foto wajah visitor (hingga 4 foto).
     *
     * Urutan foto sesuai arahan modal scan wajah:
     *   1. Wajah Lurus (Straight)
     *   2. Tengok Kanan (Turn Right)
     *   3. Tengok Kiri (Turn Left)
     *   4. Senyum (Smile) — jika tersedia
     *
     * GET /admin/visitors/{visitor}/face-photo
     */
    public function show(Visitor $visitor)
    {
        $photos = $visitor->getAllFacePhotos();

        if (empty($photos)) {
            abort(404, 'Foto wajah belum tersedia.');
        }

        // Label sesuai urutan modal scan wajah
        $labels = [
            'Wajah Lurus',
            'Tengok Kanan',
            'Tengok Kiri',
            'Senyum',
        ];

        // Ambil maksimal 4 foto terakhir (terbaru)
        $latestPhotos = array_slice($photos, -4);

        return view('admin.visitor-face-gallery', [
            'visitor' => $visitor,
            'photos' => $latestPhotos,
            'labels' => $labels,
        ]);
    }
}
