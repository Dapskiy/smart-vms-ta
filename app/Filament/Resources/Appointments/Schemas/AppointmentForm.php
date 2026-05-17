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
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Closure;
use App\Models\Appointment;
use App\Services\VisitIdService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AppointmentForm
{
    private static $optionsCache = [];

    /**
     * Generate time options dari 07:00 sampai 18:00 dengan interval 30 menit
     */
    private static function generateTimeOptions(?Get $get = null): array
    {
        $visitDate = $get ? $get('visit_date') : null;
        $roomId = $get ? $get('room_id') : null;
        $currentId = $get ? $get('id') : null;
        $cacheKey = "{$visitDate}_{$roomId}_{$currentId}";

        if (isset(self::$optionsCache[$cacheKey])) {
            return self::$optionsCache[$cacheKey];
        }

        $options = [];
        $startTime = strtotime('07:00');
        $endTime = strtotime('23:00');
        $interval = 30 * 60; // 30 menit dalam detik

        $bookedTimes = [];
        if ($get) {
            $visitDate = $get('visit_date');
            $roomId = $get('room_id');
            $currentId = $get('id');

            if ($visitDate && $roomId) {
                $query = Appointment::where('visit_date', $visitDate)
                    ->where('room_id', $roomId)
                    ->where('should_book_room', true)
                    ->where('status', '!=', 'cancelled');

                if ($currentId) {
                    $query->where('id', '!=', $currentId);
                }

                $bookedTimes = $query->get(['room_start_time', 'room_end_time']);
            }
        }

        for ($time = $startTime; $time <= $endTime; $time += $interval) {
            $timeString = date('H:i', $time);
            $label = $timeString;

            $isBooked = false;
            foreach ($bookedTimes as $booked) {
                $start = substr($booked->room_start_time, 0, 5);
                $end = substr($booked->room_end_time, 0, 5);

                if ($timeString >= $start && $timeString < $end) {
                    $isBooked = true;
                    break;
                }
            }

            if ($isBooked) {
                $label .= ' (Booked)';
            }

            $options[$timeString] = $label;
        }

        self::$optionsCache[$cacheKey] = $options;
        return $options;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Display Visit ID
                Placeholder::make('visit_id')
                    ->label('ID Kunjungan')
                    ->content(fn($record) => $record?->visit_id ?? VisitIdService::generate())
                    ->visible(fn($record) => $record !== null),

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
                    ->relationship('pic', 'name', fn($query) => $query->where('is_available', true))
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->department ? "{$record->name} - {$record->department->name}" : $record->name)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([
                        fn($get) => function (string $attribute, $value, Closure $fail) use ($get) {
                            $visitDate = $get('visit_date');
                            if (!$visitDate)
                                return;

                            $query = Appointment::where('pic_id', $value)
                                ->where('visit_date', $visitDate)
                                ->whereNotIn('status', ['cancelled', 'completed']);

                            $currentId = $get('id');
                            if ($currentId) {
                                $query->where('id', '!=', $currentId);
                            }

                            if ($query->exists()) {
                                $fail('PIC ini sudah memiliki jadwal pada tanggal tersebut.');
                            }
                        },
                    ]),

                Textarea::make('purpose')
                    ->label('Tujuan/Perihal')
                    ->required()
                    ->columnSpanFull(),

                DatePicker::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->default(now())
                    ->hidden(fn(Get $get) => $get('type') === 'walk-in')
                    ->required(fn(Get $get) => $get('type') !== 'walk-in')
                    ->native(false)
                    ->live(),

                TimePicker::make('visit_time')
                    ->label('Jam')
                    ->hidden(fn(Get $get) => $get('should_book_room') === true)
                    ->required(fn(Get $get) => !$get('should_book_room'))
                    ->native(false)
                    // NOTES: Default value akan diatur oleh ->mountUsing() di ListAppointments
                    // Untuk Appointment: kosong (user isi manual)
                    // Untuk Walk-in: otomatis jam sekarang
                    ->default(null),

                TextInput::make('pax')
                    ->label('Total Orang')
                    ->numeric()
                    ->default(1)
                    ->required(),

                // --- Checkbox untuk Pesan Ruangan ---
                Checkbox::make('should_book_room')
                    ->label('Pesan Ruang Meeting?')
                    ->default(false)
                    ->live(),

                // --- Ruang Meeting Selection dengan Validasi Jadwal Bentrok ---
                Select::make('room_id')
                    ->label('Ruang Meeting')
                    ->relationship('room', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(fn(Get $get) => $get('should_book_room'))
                    ->rules([
                        fn($get) => function (string $attribute, $value, Closure $fail) use ($get) {
                            // Jika checkbox tidak dicheck, skip validasi
                            if (!$get('should_book_room')) {
                                return;
                            }

                            // Ambil input tanggal dan waktu ruangan dari form
                            $visitDate = $get('visit_date');
                            $roomStartTime = $get('room_start_time');
                            $roomEndTime = $get('room_end_time');

                            // Jika ada input kosong, skip validasi
                            if (!$visitDate || !$roomStartTime || !$roomEndTime) {
                                return;
                            }

                            // Cek apakah ada jadwal di ruangan yang overlap dengan time range
                            $query = Appointment::where('room_id', $value)
                                ->where('visit_date', $visitDate)
                                ->where('should_book_room', true)
                                ->where('status', '!=', 'cancelled');

                            // Query untuk cek time overlap
                            // Overlap terjadi jika: new_start < existing_end AND new_end > existing_start
                            $query->where(function ($q) use ($roomStartTime, $roomEndTime) {
                                $q->where(function ($subQ) use ($roomStartTime, $roomEndTime) {
                                    $subQ->where('room_start_time', '<', $roomEndTime)
                                        ->where('room_end_time', '>', $roomStartTime);
                                });
                            });

                            // Jika form Edit, jangan bentrok dengan data appointment yang sekarang diubah
                            $currentId = $get('id');
                            if ($currentId) {
                                $query->where('id', '!=', $currentId);
                            }

                            if ($query->exists()) {
                                $fail('Maaf, ruangan ini sudah di-booking pada jam tersebut. Silakan pilih ruangan atau waktu lain.');
                            }
                        },
                    ]),

                // --- Jam Mulai Ruangan ---
                Select::make('room_start_time')
                    ->label('Jam Mulai (Ruangan)')
                    ->options(fn(Get $get) => self::generateTimeOptions($get))
                    ->disableOptionWhen(fn(string $value, Get $get) => str_contains(self::generateTimeOptions($get)[$value] ?? '', '(Booked)'))
                    ->visible(fn(Get $get) => $get('should_book_room') && $get('room_id'))
                    ->required(fn(Get $get) => $get('should_book_room')),

                // --- Jam Selesai Ruangan ---
                Select::make('room_end_time')
                    ->label('Jam Selesai (Ruangan)')
                    ->options(fn(Get $get) => self::generateTimeOptions($get))
                    ->disableOptionWhen(fn(string $value, Get $get) => str_contains(self::generateTimeOptions($get)[$value] ?? '', '(Booked)'))
                    ->visible(fn(Get $get) => $get('should_book_room') && $get('room_id'))
                    ->required(fn(Get $get) => $get('should_book_room')),

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
                            
                            /* Fading untuk opsi yang di-booked */
                            select option:disabled {
                                color: #9ca3af !important; /* text-gray-400 */
                                background-color: #f3f4f6 !important; /* bg-gray-100 */
                                opacity: 0.6;
                            }
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
