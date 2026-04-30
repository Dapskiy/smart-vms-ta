<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. Sembunyikan Visitor (Karena tamu belum isi form self-service)
                Hidden::make('visitor_id'),

                // 2. Otomatisasi PIC (Set default ke user yang sedang login)
                Select::make('pic_id')
                    ->relationship('pic', 'name')
                    ->default(Auth::id())
                    ->required(),

                TextInput::make('type')
                    ->required()
                    ->default('appointment'),

                Textarea::make('purpose')
                    ->required()
                    ->columnSpanFull(),

                DatePicker::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                TimePicker::make('visit_time')
                    ->label('Jam Kunjungan')
                    ->required()
                    ->native(false)
                    ->displayFormat('H:i'),

                TextInput::make('pax')
                    ->required()
                    ->numeric()
                    ->default(1),

                TextInput::make('vehicle_number'),

                // 3. Sembunyikan Token & Status (Di-handle otomatis)
                Hidden::make('token'),
                Hidden::make('status')
                    ->default('scheduled'),
            ]);
    }
}
