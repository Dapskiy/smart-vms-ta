<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class EpassController extends Controller
{
    /**
     * Tampilkan Hybrid Visitor E-Pass Card untuk tamu.
     */
    public function show(string $token)
    {
        $appointment = Appointment::with(['visitor', 'pic', 'room'])
            ->where('token', $token)
            ->orWhere('visit_id', $token)
            ->firstOrFail();

        return view('guest.hybrid-epass', [
            'appointment' => $appointment,
            'visitor'     => $appointment->visitor,
            'pic'         => $appointment->pic,
            'room'        => $appointment->room,
        ]);
    }
}
