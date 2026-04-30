<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum; // <--- 1. Tambahkan import BackedEnum di sini
use Filament\Pages\Page;

class AksesRole extends Page
{
    // Mengubah tipe data agar sesuai dengan aturan Filament
    protected static UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?int $navigationSort = 4;

    // <--- 2. Ubah tipe data navigationIcon di sini
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.akses-role';
}