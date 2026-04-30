<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('visitor_id')
                    ->relationship('visitor', 'name'),
                Select::make('pic_id')
                    ->relationship('pic', 'name'),
                TextInput::make('type')
                    ->required()
                    ->default('appointment'),
                Textarea::make('purpose')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('visit_date')
                    ->required(),
                DateTimePicker::make('expected_arrival_time'),
                DateTimePicker::make('expected_departure_time'),
                TextInput::make('pax')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('vehicle_number'),
                TextInput::make('token'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
            ]);
    }
}
