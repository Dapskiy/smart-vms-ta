<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Appointment;
use App\Models\Visitor;

class GuestRegistrationForm extends Component
{
    public $token;
    public $appointment;
    
    // Field data diri tamu
    public $name;
    public $company;
    public $phone;

    public function mount($token)
    {
        $this->token = $token;
        // Cari appointment berdasarkan token yang masih 'scheduled'
        $this->appointment = Appointment::where('token', $this->token)
                                        ->where('status', 'scheduled')
                                        ->firstOrFail();
    }

    public function submit()
    {
        // 1. Validasi input
        $this->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        // 2. Simpan data ke tabel visitors
        $visitor = Visitor::create([
            'name' => $this->name,
            'company' => $this->company,
            'phone' => $this->phone,
        ]);

        // 3. Update appointment (sambungkan dengan visitor_id dan set tipe ke appointment)
        $this->appointment->update([
            'visitor_id' => $visitor->id,
            'type' => 'appointment',
            // Status tetap 'scheduled', check-in dilakukan oleh satpam
        ]);

        // 4. Beri pesan sukses
        session()->flash('success', 'Terima kasih, pendaftaran berhasil! Silakan melapor ke pos keamanan saat tiba.');
    }

    public function render()
    {
        return view('livewire.guest-registration-form')->layout('layouts.guest');
    }
}
