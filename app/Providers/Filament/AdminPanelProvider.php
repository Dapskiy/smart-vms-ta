<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
// use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
                'danger'  => Color::Rose,
            ])
            ->font('Poppins')
            ->brandName('VISITA Enterprise')
            ->favicon(asset('favicon.png'))
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn(): string => \Illuminate\Support\Facades\Blade::render("
                    @vite('resources/css/app.css')
                    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
                    <link href=\"https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap\" rel=\"stylesheet\">
                    <style>
                        h1, h2, h3, h4, h5, h6, .fi-logo { font-family: 'Poppins', sans-serif !important; letter-spacing: -0.3px; }
                        .fi-logo { font-size: 20px !important; font-weight: 700 !important; color: #312e81 !important; }
                        .dark .fi-logo { color: #a5b4fc !important; }
                    </style>
                ")
            )
            ->renderHook(
                'panels::body.end',
                fn(): string => \Illuminate\Support\Facades\Blade::render('
                    <style>
                        /* 1. Sembunyikan text bawaan (screen-reader) Filament */
                        .fi-ta-table thead tr th:last-child span.sr-only {
                            display: none !important;
                        }
                        
                        /* 2. Paksa munculkan teks "Aksi" secara visual */
                        .fi-ta-table thead tr th:last-child::after {
                            content: "Aksi";
                            display: block;
                            font-weight: 600;
                            font-size: 0.875rem;
                            color: inherit;
                            text-align: right;
                            padding-right: 1.5rem;
                            padding-top: 0.75rem;
                            padding-bottom: 0.75rem;
                        }
                    </style>
                ')
            )
            ->renderHook(
                'panels::body.end',
                fn(): \Illuminate\Contracts\View\View => view('filament.widgets.admin-chat-widget')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn(): string => \Illuminate\Support\Facades\Blade::render("@livewire('topbar-availability-toggle')")
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\RestrictKioskIpMiddleware::class,
            ])
            ->plugins([
                // FilamentShieldPlugin::make() - disabled, using custom role resource instead
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
