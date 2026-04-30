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
                // 1. Visitor (Muncul hanya jika Walk-in, dan bisa buat baru di tempat)
                Select::make('visitor_id')
                    ->label('Tamu')
                    ->relationship('visitor', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->company})")
                    ->searchable(['name', 'company'])
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),
                        TextInput::make('company')
                            ->label('Instansi / Perusahaan')
                            ->required(),
                        TextInput::make('phone')
                            ->label('No. Telepon / WA')
                            ->required(),
                    ])
                    ->visible(fn ($get) => $get('type') === 'walk-in' || request()->query('type') === 'walk-in')
                    ->required(fn ($get) => $get('type') === 'walk-in' || request()->query('type') === 'walk-in'),

                // 2. Otomatisasi PIC (Set default ke user yang sedang login)
                Select::make('pic_id')
                    ->relationship('pic', 'name')
                    ->default(Auth::id())
                    ->required(),

                Hidden::make('type')
                    ->default('walk-in'),

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
