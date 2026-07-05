<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'face_photo' => 'array',
        'face_features' => 'array',
    ];

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
