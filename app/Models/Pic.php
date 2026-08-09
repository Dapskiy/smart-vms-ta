<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Pic extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'department_id',
        'phone',
        'email',
        'is_available',
        'face_photo',
        'face_features',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'face_features' => 'array',
    ];

    // =====================================================================================
    // 🔴 [CHEAT SHEET SIDANG] - ENKRIPSI DATA BIOMETRIK (AES-256)
    // Sama seperti Visitor, data biometrik PIC juga dienkripsi 
    // menggunakan algoritma AES-256-CBC secara transparan (on-the-fly) via Mutator.
    // =====================================================================================

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
                            $filename = 'pic-photos/' . Str::uuid() . '.enc';
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'pic_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(PicAttendance::class);
    }
}
