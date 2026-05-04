<?php

namespace App\Models;

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
    ];

    // Otomatis generate token saat PIC membuat appointment baru
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = Str::random(10);
            }

            // Pengaman Backend: Auto-koreksi ke 'walk-in' (sesuai database enum)
            if (in_array($model->type, ['walkin', 'walk_in', 'walk-in'])) {
                $model->type = 'walk-in';
            }
        });

        static::updating(function ($model) {
            if (in_array($model->type, ['walkin', 'walk_in', 'walk-in'])) {
                $model->type = 'walk-in';
            }
        });
    }

    /**
     * Boot the model and hook into events (untuk walk-in status).
     */
    protected static function booted(): void
    {
        parent::booted();

        // Event 'creating' ini akan dieksekusi TEPAT SEBELUM data disimpan ke database
        static::creating(function (Appointment $appointment) {
            
            // Cek apakah tipenya walk-in (sesuaikan string 'walkin' dengan value yang Anda simpan di DB)
            if (in_array($appointment->type, ['walkin', 'walk_in', 'walk-in'])) {
                // Jika walk-in, bypass pending dan langsung set ke active
                $appointment->status = 'active';
            } 
            // Jika bukan walk-in dan statusnya masih kosong, set ke pending
            elseif (empty($appointment->status)) {
                $appointment->status = 'pending';
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
}