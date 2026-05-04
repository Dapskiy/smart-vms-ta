<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Carbon;

class VisitIdService
{
    public static function generate(): string
    {
        $today = Carbon::today();
        $dateString = $today->format('Ymd');

        // Find the highest sequence number for today
        $lastAppointment = Appointment::whereDate('created_at', $today)
            ->whereNotNull('visit_id')
            ->latest('id')
            ->first();

        $nextSequence = 1;
        if ($lastAppointment) {
            // Extract sequence from last visit_id (e.g., VST-20260504-00001 -> 00001)
            $lastVisitId = $lastAppointment->visit_id;
            if (preg_match('/VST-\d{8}-(\d+)$/', $lastVisitId, $matches)) {
                $nextSequence = (int)$matches[1] + 1;
            }
        }

        return sprintf('VST-%s-%04d', $dateString, $nextSequence);
    }
}
