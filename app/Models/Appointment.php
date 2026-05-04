<?php

namespace App\Models;

use App\Services\VisitIdService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $attributes = [
        'status' => 'pending',
    ];

    // Tambahkan baris ini agar Laravel memformat JSON menjadi Array otomatis
    protected $casts = [
        'companions' => 'array',
        'visit_date' => 'date',
        'visit_time' => 'datetime:H:i',
        'checkin_time' => 'datetime:H:i',
        'checkout_time' => 'datetime:H:i',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Appointment $appointment) {
            // Set default token if empty
            if (empty($appointment->token)) {
                $appointment->token = Str::random(10);
            }

            // Generate visit_id if empty
            if (empty($appointment->visit_id)) {
                $appointment->visit_id = VisitIdService::generate();
            }

            // Standarisasi type 'walk-in'
            if (in_array($appointment->type, ['walkin', 'walk_in', 'walk-in'])) {
                $appointment->type = 'walk-in';
                // Walk-in otomatis active (check-in)
                $appointment->status = 'active';
            } elseif (empty($appointment->status)) {
                $appointment->status = 'pending';
            }

            // Set default visit_time jika kosong (terutama untuk appointment yang menyembunyikan field ini)
            if (empty($appointment->visit_time)) {
                $appointment->visit_time = now()->format('H:i');
            }

            // Set default visit_date jika kosong (untuk walk-in yang menyembunyikan field ini)
            if (empty($appointment->visit_date)) {
                $appointment->visit_date = now()->format('Y-m-d');
            }
        });

        static::updating(function (Appointment $appointment) {
            if (in_array($appointment->type, ['walkin', 'walk_in', 'walk-in'])) {
                $appointment->type = 'walk-in';
            }
        });
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
