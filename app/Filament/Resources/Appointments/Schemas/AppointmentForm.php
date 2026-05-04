<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. Visitor Utama
                Select::make('visitor_id')
                    ->label('Tamu (Ketua Rombongan)')
                    ->relationship('visitor', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} ({$record->company})")
                    ->searchable(['name', 'company'])
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')->label('Nama')->required(),
                        TextInput::make('company')->label('Instansi')->required(),
                        TextInput::make('phone')->label('WA')->tel()->required(),
                    ]),

                // 2. Repeater Anggota
                Repeater::make('companions')
                    ->label('Anggota Rombongan (Opsional)')
                    ->schema([
                        TextInput::make('name')
                            ->hiddenLabel()
                            ->placeholder('Nama anggota')
                            ->required(),
                    ])
                    ->addActionLabel('Tambah Anggota')
                    ->grid(2)
                    ->columnSpanFull()
                    ->default([]),

                // 3. PIC
                Select::make('pic_id')
                    ->label('Tujuan Kunjungan (PIC)')
                    ->relationship('pic', 'name')
                    ->default(Auth::id())
                    ->required()
                    ->searchable()
                    ->preload(),

                Textarea::make('purpose')
                    ->label('Tujuan/Perihal')
                    ->required()
                    ->columnSpanFull(),

                DatePicker::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->default(now())
                    ->required()
                    ->native(false),

                TimePicker::make('visit_time')
                    ->label('Jam')
                    ->default(now()->format('H:i'))
                    ->required()
                    ->native(false),

                TextInput::make('pax')
                    ->label('Total Orang')
                    ->numeric()
                    ->default(1)
                    ->required(),

                // --- Input Plat Nomor Kendaraan ---
                Placeholder::make('nopol_css')
                    ->hiddenLabel()
                    ->extraAttributes(['style' => 'display: none;'])
                    ->content(new HtmlString('
                        <style>
                            .nopol-grid { gap: 0 !important; }
                            .nopol-grid > * { padding: 0 !important; }
                            .nopol-grid input { text-align: center !important; text-transform: uppercase !important; font-weight: 600; }
                            .nopol-prefix .fi-input-wrapper { border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
                            .nopol-number .fi-input-wrapper { border-radius: 0 !important; margin-left: -1px; }
                            .nopol-suffix .fi-input-wrapper { border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; margin-left: -1px; }
                        </style>
                    ')),

                // --- Label untuk Plat Nomor (sebagai Placeholder) ---
                Placeholder::make('plat_nomor_label')
                    ->hiddenLabel()
                    ->content(new HtmlString('<h3 class="text-base font-semibold">Plat Nomor Kendaraan</h3>'))
                    ->columnSpanFull(),

                // --- Input Plat Nomor Kendaraan ---
                Group::make([
                    TextInput::make('v_prefix')
                        ->placeholder('H')
                        ->maxLength(2)
                        ->extraAttributes(['class' => 'nopol-prefix']),
                    TextInput::make('v_number')
                        ->placeholder('1234')
                        ->maxLength(4)
                        ->extraAttributes(['class' => 'nopol-number']),
                    TextInput::make('v_suffix')
                        ->placeholder('AB')
                        ->maxLength(3)
                        ->extraAttributes(['class' => 'nopol-suffix']),
                ])
                    ->columns(3)
                    ->columnSpanFull(),

                // --- Hidden Fields Logic ---
                Hidden::make('vehicle_number')
                    ->dehydrated(true)
                    ->dehydrateStateUsing(fn($get) => strtoupper(trim("{$get('v_prefix')} {$get('v_number')} {$get('v_suffix')}"))),

                Hidden::make('type')
                    ->default(function () {
                        $queryType = request()->query('type');
                        // Handle both 'walk-in' dan 'walkin' dari query parameter
                        if (in_array($queryType, ['walk-in', 'walkin', 'walk_in'])) {
                            return 'walk-in';
                        }
                        return 'appointment';
                    }),

                Hidden::make('status')
                    ->default(function () {
                        $queryType = request()->query('type');
                        // Walk-in otomatis menjadi 'active'
                        if (in_array($queryType, ['walk-in', 'walkin', 'walk_in'])) {
                            return 'active';
                        }
                        return 'pending';
                    }),

                Hidden::make('token')
                    ->default(fn() => Str::random(10)),
            ]);
    }
}