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

            // Pengaman Backend: Auto-koreksi ke 'walkin' (sesuai database)
            if (in_array($model->type, ['walk-in', 'walk_in'])) {
                $model->type = 'walkin';
            }
        });

        static::updating(function ($model) {
            if (in_array($model->type, ['walk-in', 'walk_in'])) {
                $model->type = 'walkin';
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