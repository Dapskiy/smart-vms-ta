<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class VisitorFacePhotoController extends Controller
{
    /**
     * Decrypt dan tampilkan foto wajah visitor.
     * Hanya bisa diakses oleh user yang sudah login (auth:web / Filament session).
     *
     * GET /admin/visitors/{visitor}/face-photo
     */
    public function show(Visitor $visitor)
    {
        if (empty($visitor->face_photo)) {
            abort(404, 'Foto wajah belum tersedia untuk visitor ini.');
        }

        try {
            // Cek apakah face_photo berupa JSON array (format baru maks 10) atau string tunggal (format lama)
            $photoData = $visitor->face_photo;
            $decoded = json_decode($photoData, true);
            if (is_array($decoded)) {
                $photoData = end($decoded); // Ambil yang paling terakhir (terbaru)
            }

            // Dekripsi menggunakan APP_KEY (AES-256-CBC)
            $decrypted = Crypt::decryptString($photoData);

            // Decrypted value adalah data URI: "data:image/jpeg;base64,/9j/..."
            // Pisahkan header dari data base64
            if (!str_starts_with($decrypted, 'data:image/')) {
                abort(422, 'Format data foto tidak valid.');
            }

            $parts = explode(',', $decrypted, 2);
            if (count($parts) !== 2) {
                abort(422, 'Format data foto tidak valid.');
            }

            // Deteksi MIME type dari data URI header
            preg_match('/data:(image\/\w+);base64/', $parts[0], $matches);
            $mimeType = $matches[1] ?? 'image/jpeg';

            $imageData = base64_decode($parts[1]);

            return response($imageData, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'inline; filename="face_' . $visitor->id . '.jpg"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (DecryptException $e) {
            abort(403, 'Gagal mendekripsi foto — pastikan APP_KEY tidak berubah.');
        }
    }
}
