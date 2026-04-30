<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AksesRole extends Page
{
    protected static ?string $navigationGroup = 'Konfigurasi';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.akses-role';
}
