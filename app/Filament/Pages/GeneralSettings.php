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
        return 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'General Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'General Settings';
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
                Section::make('Company Identity')
                    ->description('Set your core company brand details here.')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('This name will be used by the AI Chatbot to greet visitors.'),
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
            ->title('Settings Saved')
            ->success()
            ->send();
    }
}
