<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function show($token)
    {
        // Cari janji temu berdasarkan token, pastikan statusnya masih aktif ('scheduled')
        $appointment = Appointment::where('token', $token)
                                  ->where('status', 'scheduled')
                                  ->firstOrFail(); // Otomatis nembak 404 kalau token salah/sudah dipakai

        // Lempar data appointment ke view
        return view('guest.registration', compact('appointment'));
    }

    public function store(Request $request, $token)
    {
        // Nanti di sini kita buat logic:
        // 1. Validasi input & upload foto wajah
        // 2. Simpan ke tabel visitors
        // 3. Update visitor_id di tabel appointments
    }
}
