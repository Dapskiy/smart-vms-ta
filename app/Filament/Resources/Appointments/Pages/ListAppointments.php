<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Schemas\Schema;
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
                // FILAMENT V4: Gunakan Schema bukan Form
                ->mountUsing(function (Schema $schema): void {
                    // Untuk Appointment: visit_time KOSONG (user harus isi manual)
                    $schema->fill([
                        'visit_time' => null,
                    ]);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $data['type'] = 'appointment';
                    $data['status'] = 'pending';
                    $data['token'] = \Illuminate\Support\Str::random(10);
                    return $data;
                }),

            CreateAction::make('create_walkin')
                ->label('New Walk-in')
                ->modalHeading('Create Walk-in Registration')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->visible(fn() => auth()->user()->can('create walk-in'))
                // FILAMENT V4: Gunakan Schema bukan Form
                ->mountUsing(function (Schema $schema): void {
                    // Untuk Walk-in: visit_time OTOMATIS terisi jam saat ini
                    $schema->fill([
                        'visit_time' => now()->format('H:i'),
                    ]);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $data['type'] = 'walk-in';
                    $data['status'] = 'active';
                    $data['token'] = \Illuminate\Support\Str::random(10);
                    return $data;
                }),
        ];
    }
}