<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

use Filament\Forms\Components\Toggle;

class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Konfigurasi';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pengaturan Umum';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Pengaturan Umum';
    }

    protected string $view = 'filament.pages.general-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = Setting::first();
        if ($setting) {
            $this->form->fill([
                'company_name' => $setting->company_name,
                'company_description' => $setting->company_description,
                'show_visitor_face_photo' => $setting->show_visitor_face_photo,
            ]);
        } else {
            $this->form->fill([
                'show_visitor_face_photo' => true,
            ]);
        }
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('page_GeneralSettings') ?? false;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Identitas Perusahaan')
                    ->description('Atur detail merek dan identitas perusahaan Anda di sini.')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama ini akan digunakan oleh AI Chatbot saat menyapa pengunjung.'),
                        Textarea::make('company_description')
                            ->label('Deskripsi Perusahaan')
                            ->rows(4)
                            ->maxLength(1000)
                            ->helperText('Deskripsi singkat tentang perusahaan (misal: "perusahaan ini bergerak di bidang garmen..."). Ini akan menjadi konteks tambahan bagi AI Chatbot saat berinteraksi dengan tamu.'),
                    ]),
                Section::make('Keamanan & Privasi')
                    ->description('Atur tampilan fitur privasi di dalam dashboard Admin.')
                    ->schema([
                        Toggle::make('show_visitor_face_photo')
                            ->label('Tampilkan Tombol Preview Foto Wajah Pengunjung')
                            ->helperText('Jika dinonaktifkan, tombol untuk melihat foto mentah pengunjung akan disembunyikan di menu Manajemen Pengunjung (berguna jika penguji mempermasalahkan privasi).')
                            ->default(true),
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $setting = Setting::first();
        if (!$setting) {
            $setting = new Setting();
        }
        
        $setting->company_name = $data['company_name'];
        $setting->company_description = $data['company_description'] ?? null;
        $setting->show_visitor_face_photo = $data['show_visitor_face_photo'] ?? true;
        $setting->save();

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan')
            ->success()
            ->send();
    }
}
