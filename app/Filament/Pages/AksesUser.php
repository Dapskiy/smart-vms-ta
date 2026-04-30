<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AksesUser extends Page
{
    protected static ?string $navigationGroup = 'Konfigurasi';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.akses-user';
}
