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
            \Filament\Actions\Action::make('create_appointment')
                ->label('New Appointment')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->url(fn (): string => AppointmentResource::getUrl('create', ['type' => 'appointment']))
                ->visible(fn () => auth()->user()->can('create appointment')),

            \Filament\Actions\Action::make('create_walkin')
                ->label('New Walk-in')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->url(fn (): string => AppointmentResource::getUrl('create', ['type' => 'walk-in']))
                ->visible(fn () => auth()->user()->can('create walk-in')),
        ];
    }
}
