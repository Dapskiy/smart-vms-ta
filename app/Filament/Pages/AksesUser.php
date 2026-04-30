<?php

namespace App\Filament\Pages;

use UnitEnum;
use Filament\Pages\Page;

class AksesUser extends Page
{
    // Mengubah tipe data agar sesuai dengan aturan Filament
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.akses-user';
}