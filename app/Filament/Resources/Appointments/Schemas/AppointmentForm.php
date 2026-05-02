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
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} ({$record->company})")
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
                    ->visible(fn($get) => $get('type') === 'walk-in' || request()->query('type') === 'walk-in')
                    ->required(fn($get) => $get('type') === 'walk-in' || request()->query('type') === 'walk-in'),

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

                // CSS Injector - Diperbaiki agar tidak merusak layout
                Placeholder::make('nopol_css')
                    ->hiddenLabel() // Menghilangkan label "Nopol css"
                    ->extraAttributes(['style' => 'display: none;'])
                    ->content(new HtmlString('
                        <style>
                            /* Hilangkan gap dan padding bawaan grid/group */
                            .nopol-grid { gap: 0 !important; }
                            .nopol-grid > * { padding: 0 !important; }
                            
                            /* Hapus radius kanan untuk prefix (B) */
                            .nopol-grid > div:nth-child(1) .fi-input-wrapper { 
                                border-top-right-radius: 0 !important; 
                                border-bottom-right-radius: 0 !important; 
                            }
                            
                            /* Hapus semua radius & overlap border untuk number (1234) */
                            .nopol-grid > div:nth-child(2) .fi-input-wrapper { 
                                border-radius: 0 !important; 
                                margin-left: -1px; 
                            }
                            
                            /* Hapus radius kiri & overlap border untuk suffix (XYZ) */
                            .nopol-grid > div:nth-child(3) .fi-input-wrapper { 
                                border-top-left-radius: 0 !important; 
                                border-bottom-left-radius: 0 !important; 
                                margin-left: -1px; 
                            }
                            
                            /* Tengahkan teks input dan jadikan kapital */
                            .nopol-grid input { 
                                text-align: center !important; 
                                text-transform: uppercase !important; 
                                font-weight: 500;
                            }
                        </style>
                    ')),

                // Vehicle Number split into 3 columns - Unified Joined Look
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

                // 3. Sembunyikan Token & Status (Di-handle otomatis)
                Hidden::make('token'),
                Hidden::make('status')
                    ->default('pending'), // <--- SUDAH SAYA UBAH MENJADI PENDING
            ]);
    }
}