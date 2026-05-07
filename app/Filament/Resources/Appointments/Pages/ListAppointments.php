<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create_appointment')
                ->label('New Appointment')
                ->modalHeading('Create New Appointment')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->visible(fn() => auth()->user()->can('create appointment'))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['type'] = 'appointment';
                    $data['status'] = 'pending'; // Appointment harus disetujui/datang dulu
                    $data['token'] = \Illuminate\Support\Str::random(10);
                    return $data;
                }),

            CreateAction::make('create_walkin')
                ->label('New Walk-in')
                ->modalHeading('Create Walk-in Registration')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->visible(fn() => auth()->user()->can('create walk-in'))
                ->mutateFormDataUsing(function (array $data): array {
                    // SINKRONISASI: Harus 'walk-in' sesuai Enum di Migration
                    $data['type'] = 'walk-in';
                    // Walk-in otomatis langsung 'active' (sudah di lokasi)
                    $data['status'] = 'active';
                    $data['token'] = \Illuminate\Support\Str::random(10);
                    return $data;
                }),
        ];
    }
}
