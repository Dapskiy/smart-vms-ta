<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

                $last = null;

                // Jika sudah berupa array (karena cache atau driver DB otomatis decode JSON)
                if (is_array($raw)) {
                    if (isset($raw['data'])) {
                        // Format baru terbungkus JSON
                        $raw = $raw['data'];
                    } else {
                        // Format lama (array base64)
                        $last = end($raw);
                    }
                }

                if ($last === null) {
                    if (!is_string($raw) || $raw === '') return null;

                    // Coba decode JSON jika raw berupa string JSON (untuk kolom bertipe JSON di DB)
                    $jsonDecoded = json_decode($raw, true);
                    if (is_array($jsonDecoded)) {
                        if (isset($jsonDecoded['data'])) {
                            $raw = $jsonDecoded['data'];
                        } else {
                            $last = end($jsonDecoded);
                        }
                    }
                }

                if ($last === null) {
                    // Dekripsi berulang (maks 3x) untuk data terenkripsi di DB
                    $current = $raw;
                    for ($i = 0; $i < 3; $i++) {
                        try {
                            $current = Crypt::decryptString($current);
                        } catch (\Throwable $e) {
                            break;
                        }
                    }

                    $decoded = json_decode($current, true);
                    if (is_array($decoded)) {
                        $last = end($decoded);
                    } else if (is_string($decoded)) {
                        $last = $decoded;
                    } else {
                        $last = $current;
                    }
                }

                if (empty($last)) return null;

                // 2. Jika $last adalah path file di storage
                if (is_string($last) && !str_starts_with($last, 'data:image')) {
                    try {
                        if (Storage::disk('local')->exists($last)) {
                            $encryptedContent = Storage::disk('local')->get($last);
                            $decryptedBinary = Crypt::decrypt($encryptedContent);
                            // Mengembalikan base64 data URI
                            return 'data:image/jpeg;base64,' . base64_encode($decryptedBinary);
                        }
                    } catch (\Throwable $e) {
                        return null;
                    }
                }

                // 3. Jika $last adalah base64 string langsung (data lama)
                return $last;
            },

            // ── SETTER ───────────────────────────────────────────────
            set: function (mixed $value): string {
                if ($value === null) return '';

                $items = is_array($value) ? $value : [$value];
                $storedPaths = [];

                foreach ($items as $item) {
                    if (empty($item)) continue;

                    // Jika item sudah merupakan path file (bukan base64), biarkan saja
                    if (is_string($item) && !str_starts_with($item, 'data:image')) {
                        $storedPaths[] = $item;
                        continue;
                    }

                    // Jika item adalah base64 image string
                    if (is_string($item) && str_starts_with($item, 'data:image')) {
                        try {
                            // Extract data binary dari base64
                            $parts = explode(',', $item);
                            $base64Data = isset($parts[1]) ? $parts[1] : $parts[0];
                            $binaryData = base64_decode($base64Data);

                            // Enkripsi data biner tersebut
                            $encryptedData = Crypt::encrypt($binaryData);

                            // Simpan ke storage privat local disk
                            $filename = 'face-photos/' . Str::uuid() . '.enc';
                            Storage::disk('local')->put($filename, $encryptedData);

                            $storedPaths[] = $filename;
                        } catch (\Throwable $e) {
                            // Jika gagal, simpan as-is (base64) agar tidak crash
                            $storedPaths[] = $item;
                        }
                    } else {
                        $storedPaths[] = $item;
                    }
                }

                // Simpan array path ke DB dalam bentuk terenkripsi
                $payload = json_encode($storedPaths);
                $encrypted = Crypt::encryptString($payload);

                // Kembalikan JSON terbungkus agar kompatibel dengan kolom bertipe JSON di DB (PostgreSQL)
                return json_encode(['data' => $encrypted]);
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
