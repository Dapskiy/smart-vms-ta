<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PicAttendance extends Model
{
    protected $fillable = [
        'pic_id',
        'type',
        'method',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Pic::class);
    }
}
