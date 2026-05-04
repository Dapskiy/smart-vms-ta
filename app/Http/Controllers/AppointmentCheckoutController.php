<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\VisitorCheckout;
use Illuminate\Http\Request;

class AppointmentCheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'visitor_name' => 'required|string',
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);

        // Create or update checkout record
        VisitorCheckout::updateOrCreate(
            [
                'appointment_id' => $appointment->id,
                'visitor_name' => $request->visitor_name,
            ],
            [
                'checkout_time' => now()->format('H:i'),
            ]
        );

        // Check if all visitors have checked out
        $companions = $appointment->companions ?? [];
        $totalVisitors = 1 + count($companions);
        $checkedOut = $appointment->visitorCheckouts()->count();

        // If all checked out, mark appointment as completed
        if ($checkedOut >= $totalVisitors) {
            $appointment->update([
                'status' => 'completed',
                'checkout_time' => now()->format('H:i'),
            ]);
        }

        return redirect()->back()
            ->with('success', "{$request->visitor_name} telah di-checkout.");
    }
}
