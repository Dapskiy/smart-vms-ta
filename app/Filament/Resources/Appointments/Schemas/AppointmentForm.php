<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Repeater;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. Visitor Utama (Ketua Rombongan)
                Select::make('visitor_id')
                    ->label('Tamu (Ketua Rombongan)')
                    ->relationship('visitor', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} ({$record->company})")
                    ->searchable(['name', 'company'])
                    ->preload()
                    ->required()
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
                    ]),

                // 2. Repeater untuk Anggota Rombongan (JSON Array)
                Repeater::make('companions')
                    ->label('Anggota Rombongan Lainnya (Opsional)')
                    ->schema([
                        TextInput::make('name')
                            ->hiddenLabel()
                            ->placeholder('Masukkan nama anggota rombongan')
                            ->required(),
                    ])
                    ->addActionLabel('Tambah Anggota Rombongan')
                    ->grid(2) // Menampilkan 2 kolom agar lebih ringkas
                    ->columnSpanFull()
                    ->default([]), // Set default ke array kosong agar aman saat create

                // 3. Otomatisasi PIC
                Select::make('pic_id')
                    ->relationship('pic', 'name')
                    ->default(Auth::id())
                    ->required(),

                Hidden::make('type')
                    ->default(fn() => in_array(request()->query('type'), ['walk_in', 'walk-in', 'walkin']) ? 'walkin' : 'appointment'),

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

                // CSS Injector untuk formating Plat Nomor
                Placeholder::make('nopol_css')
                    ->hiddenLabel()
                    ->extraAttributes(['style' => 'display: none;'])
                    ->content(new HtmlString('
                        <style>
                            .nopol-grid { gap: 0 !important; }
                            .nopol-grid > * { padding: 0 !important; }
                            
                            .nopol-grid > div:nth-child(1) .fi-input-wrapper { 
                                border-top-right-radius: 0 !important; 
                                border-bottom-right-radius: 0 !important; 
                            }
                            
                            .nopol-grid > div:nth-child(2) .fi-input-wrapper { 
                                border-radius: 0 !important; 
                                margin-left: -1px; 
                            }
                            
                            .nopol-grid > div:nth-child(3) .fi-input-wrapper { 
                                border-top-left-radius: 0 !important; 
                                border-bottom-left-radius: 0 !important; 
                                margin-left: -1px; 
                            }
                            
                            .nopol-grid input { 
                                text-align: center !important; 
                                text-transform: uppercase !important; 
                                font-weight: 500;
                            }
                        </style>
                    ')),

                // Vehicle Number
                Group::make([
                    TextInput::make('v_prefix')
                        ->label('Nopol Kendaraan')
                        ->placeholder('B')
                        ->maxLength(2)
                        ->extraInputAttributes(['style' => 'text-align: center; border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;'])
                        ->formatStateUsing(fn($record) => $record ? explode(' ', $record->vehicle_number)[0] ?? '' : ''),

                    TextInput::make('v_number')
                        ->hiddenLabel()
                        ->placeholder('1234')
                        ->maxLength(4)
                        ->extraInputAttributes(['style' => 'text-align: center; border-left: 0; border-right: 0; border-radius: 0;'])
                        ->formatStateUsing(fn($record) => $record ? explode(' ', $record->vehicle_number)[1] ?? '' : ''),

                    TextInput::make('v_suffix')
                        ->hiddenLabel()
                        ->placeholder('XYZ')
                        ->maxLength(3)
                        ->extraInputAttributes(['style' => 'text-align: center; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;'])
                        ->formatStateUsing(fn($record) => $record ? explode(' ', $record->vehicle_number)[2] ?? '' : ''),
                ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'nopol-grid', 'style' => 'align-items: end;']),

                Hidden::make('vehicle_number')
                    ->dehydrated(true)
                    ->dehydrateStateUsing(fn($get) => preg_replace('/\s+/', ' ', trim("{$get('v_prefix')} {$get('v_number')} {$get('v_suffix')}"))),

                // Hidden Data
                Hidden::make('token'),
                Hidden::make('status')
                    ->default('pending'),
            ]);
    }
}