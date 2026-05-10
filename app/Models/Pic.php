<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pic extends Model
{
    protected $guarded = ['id'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'pic_id');
    }
}
