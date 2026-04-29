<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Membuat rute bernama 'login' yang otomatis melempar user ke halaman Filament
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');