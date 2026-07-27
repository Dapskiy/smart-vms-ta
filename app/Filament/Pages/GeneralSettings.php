<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

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
        $setting->save();

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan')
            ->success()
            ->send();
    }
}
