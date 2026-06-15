<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Visitor extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * KEPUTUSAN CAST:
     *
     * face_features → 'encrypted:array' DIPERTAHANKAN
     *   Data biometrik float descriptor — wajib dienkripsi (sensitif, compliance).
     *   Accessor di bawah menangani transisi data lama.
     *
     * face_photo → DIHAPUS dari $casts, diganti Custom Accessor
     *   Data foto (base64 array) berukuran sangat besar.
     *   Cast 'encrypted:array' pada data besar menyebabkan overhead tinggi
     *   dan sulit di-debug. Enkripsi ditangani manual via Accessor/Mutator
     *   dengan try-catch untuk backward compatibility.
     */
    /**
     * PENTING: Kedua kolom biometrik TIDAK didaftarkan di $casts.
     * Seluruh logika enkripsi/dekripsi dikelola penuh oleh Accessor/Mutator
     * di bawah agar tidak ada konflik antara cast pipeline dan accessor pipeline.
     * Mendaftarkan kolom di $casts DAN accessor secara bersamaan
     * menyebabkan Eloquent memanggil keduanya → double-decode → crash.
     */
    protected $casts = [
        // face_features & face_photo: dikelola via Accessor/Mutator (lihat di bawah)
    ];

    // ══════════════════════════════════════════════════════════════
    //  CUSTOM ACCESSOR/MUTATOR — Backward Compatible Encryption
    // ══════════════════════════════════════════════════════════════

    /**
     * face_photo accessor — selalu mengembalikan string data URI tunggal.
     *
     * Alur dekripsi:
     *  DB string  →  decrypt (maks 3x, handle edge-case double-encrypt)
     *             →  unwrap JSON array  →  ambil elemen terakhir
     *             →  kembalikan string "data:image/..." siap render
     *
     * Fallback:
     *  - Sudah array (object cache) → ambil elemen terakhir langsung
     *  - Decrypt gagal             → coba parse sebagai plain JSON
     *  - Bukan JSON                → kembalikan as-is (diagnostik)
     */
    protected function facePhoto(): Attribute
    {
        return Attribute::make(
            // ── GETTER ───────────────────────────────────────────────
            get: function (mixed $raw): mixed {
                if ($raw === null) return null;

                // Sudah array (dari object cache) — langsung ambil elemen terakhir
                if (is_array($raw)) {
                    $last = end($raw);
                    return $last !== false ? $last : null;
                }

                if (!is_string($raw) || $raw === '') return null;

                // ── Tahap 1: Dekripsi berulang (maks 3x) ─────────────
                // Handle edge-case data yang ter-encrypt lebih dari sekali.
                $current = $raw;
                for ($i = 0; $i < 3; $i++) {
                    try {
                        $current = Crypt::decryptString($current);
                    } catch (\Throwable $e) {
                        // Bukan ciphertext — hentikan loop, lanjut ke tahap berikutnya
                        break;
                    }
                }

                // ── Tahap 2: Unwrap JSON ──────────────────────────────
                // Setter menyimpan: json_encode($array) → encrypt
                // Getter harus: decrypt → json_decode → ambil elemen terakhir
                $decoded = json_decode($current, true);

                if (is_array($decoded)) {
                    // Array foto — ambil yang terakhir (terbaru)
                    $last = end($decoded);
                    return $last !== false ? $last : null;
                }

                if (is_string($decoded)) {
                    // JSON string tunggal
                    return $decoded;
                }

                // ── Tahap 3: Bukan JSON — kembalikan as-is ───────────
                // Ini bisa berupa string data:image/... langsung (data sangat lama)
                // atau string tak dikenal (akan terlihat di Controller sebagai plain text)
                return $current;
            },

            // ── SETTER ───────────────────────────────────────────────
            // Enkripsi 1x. Setter selalu menerima array dari controller.
            set: function (mixed $value): string {
                if ($value === null) return '';

                $payload = is_array($value) ? json_encode($value) : (string) $value;
                return Crypt::encryptString($payload);
            },
        )->withoutObjectCaching();
    }

    /**
     * face_features accessor: backward compatible fallback.
     *
     * Meskipun sudah pakai cast 'encrypted:array',
     * accessor ini OVERRIDE cast untuk menangani data lama (plain JSON).
     *
     * Catatan: accessor mengoverride $casts untuk kolom yang sama.
     * Hapus 'face_features' dari $casts jika accessor ini diaktifkan.
     */
    protected function faceFeatures(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): mixed {
                // Guard 1: null
                if ($value === null) return null;

                // Guard 2: sudah array — langsung return, jangan json_decode lagi
                if (is_array($value)) return $value;

                // Guard 3: tipe tak terduga
                if (!is_string($value)) return [];

                // Coba dekripsi (data baru)
                try {
                    $decrypted = Crypt::decryptString($value);
                    $decoded   = json_decode($decrypted, true);
                    return is_array($decoded) ? $decoded : [];
                } catch (\Throwable $e) {
                    // Data lama → plain JSON
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [];
                }
            },

            set: function (mixed $value): string {
                $json = is_array($value) ? json_encode($value) : ($value ?? '[]');
                return Crypt::encryptString($json);
            },
        )->withoutObjectCaching();
    }

    // ══════════════════════════════════════════════════════════════
    //  HELPER — untuk Filament & controller
    // ══════════════════════════════════════════════════════════════

    /**
     * Cek apakah visitor sudah punya foto wajah (null-safe, tidak crash).
     * Gunakan ini di Filament: ->visible(fn($r) => $r->hasFacePhoto())
     */
    public function hasFacePhoto(): bool
    {
        try {
            $photo = $this->face_photo;
            return !empty($photo);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Cek apakah visitor sudah mendaftarkan fitur wajah.
     */
    public function hasFaceFeatures(): bool
    {
        try {
            $features = $this->face_features;
            return !empty($features);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  RELATIONS
    // ══════════════════════════════════════════════════════════════

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
