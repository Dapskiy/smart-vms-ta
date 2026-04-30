<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\GuestRegistrationForm;

Route::get('/', function () {
    return view('welcome');
});

// Membuat rute bernama 'login' yang otomatis melempar user ke halaman Filament
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Route untuk menampilkan form pendaftaran tamu via Livewire
Route::get('/invitation/{token}', GuestRegistrationForm::class)->name('guest.invitation');
