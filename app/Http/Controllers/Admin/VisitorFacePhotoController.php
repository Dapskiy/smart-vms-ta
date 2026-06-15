<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor; 

class VisitorFacePhotoController extends Controller
{
    
    /**
     * Tampilkan foto wajah visitor sebagai HTTP image response.
     *
     * Model Accessor facePhoto() sudah menangani:
     *   - Dekripsi AES-256-CBC (Crypt::decryptString)
     *   - Unwrap JSON array → ambil elemen terakhir
     *   - Mengembalikan string "data:image/..." tunggal siap render
     *
     * Controller ini HANYA bertanggung jawab mengubah string data URI
     * menjadi binary HTTP image response.
     *
     * GET /admin/visitors/{visitor}/face-photo
     */
    public function show(Visitor $visitor)
    {
        // Accessor mengembalikan string data URI tunggal (atau null)
        $photoData = $visitor->face_photo;

        if (empty($photoData) || !is_string($photoData)) {
            abort(404, 'Foto wajah belum tersedia.');
        }

        // ── FALLBACK DIAGNOSTIK ────────────────────────────────────────
        // Jika bukan data URI (misal: sisa data lama / format tidak dikenal),
        // tampilkan sebagai plain text agar terlihat isinya di browser.
        // Hapus blok ini setelah semua data berhasil termigrasi ke enkripsi baru.
        if (!str_starts_with($photoData, 'data:image/')) {
            return response($photoData)->header('Content-Type', 'text/plain; charset=utf-8');
        }

        // ── RENDER GAMBAR ──────────────────────────────────────────────
        // Format data URI: "data:image/jpeg;base64,/9j/4AAQ..."
        $parts = explode(',', $photoData, 2);

        if (count($parts) !== 2 || empty($parts[1])) {
            abort(422, 'Format data URI tidak lengkap.');
        }

        // Ekstrak MIME type: "data:image/jpeg;base64" → "image/jpeg"
        preg_match('/data:(image\/[\w+]+);base64/', $parts[0], $matches);
        $mimeType = $matches[1] ?? 'image/jpeg';

        // Decode base64 → binary
        $imageData = base64_decode($parts[1], strict: true);

        if ($imageData === false) {
            abort(422, 'Payload base64 tidak valid atau corrupt.');
        }

        return response($imageData, 200, [
            'Content-Type'           => $mimeType,
            'Content-Length'         => strlen($imageData),
            'Content-Disposition'    => 'inline; filename="face_' . $visitor->id . '.jpg"',
            'Cache-Control'          => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
