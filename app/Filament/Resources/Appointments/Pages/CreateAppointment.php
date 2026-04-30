<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        $type = request()->query('type', 'appointment');
        return $type === 'walk-in' ? 'Create Walk-in Registration' : 'Create New Appointment';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tipe berdasarkan query parameter dari URL
        $data['type'] = request()->query('type', 'appointment');
        
        return $data;
    }
}
