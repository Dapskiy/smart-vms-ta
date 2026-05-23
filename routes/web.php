<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\GuestRegistrationForm;
use App\Http\Controllers\AppointmentCheckoutController;
use App\Http\Controllers\Guest\FaceCheckinController;
use App\Http\Controllers\Guest\FaceCheckoutController;
use App\Http\Controllers\Guest\FaceValidationController;
use App\Http\Controllers\Admin\VisitorFacePhotoController;

Route::get('/', function () {
    return view('welcome');
});

// Membuat rute bernama 'login' yang otomatis melempar user ke halaman Filament
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Route untuk menampilkan form pendaftaran tamu via Livewire
Route::get('/invitation/{token}', GuestRegistrationForm::class)->name('guest.invitation');

// Route untuk checkout individu per visitor
Route::post('/admin/appointments/checkout', [AppointmentCheckoutController::class, 'checkout'])
    ->middleware(['auth', 'verified'])
    ->name('filament.admin.resources.appointments.checkout');

// ── Admin: Lihat foto wajah visitor (terenkripsi → didekripsi on-demand) ──
// Dilindungi auth — hanya admin yang sudah login Filament yang bisa akses
Route::get('/admin/visitors/{visitor}/face-photo', [VisitorFacePhotoController::class, 'show'])
    ->middleware(['auth'])
    ->name('admin.visitor.face-photo');

// Route untuk face check-in dari kiosk publik (no auth)
Route::post('/kiosk/face-checkin', [FaceCheckinController::class, 'checkin'])
    ->name('kiosk.face.checkin');

// Route untuk face check-out mandiri dari kiosk publik (no auth)
Route::post('/kiosk/face-checkout', [FaceCheckoutController::class, 'checkout'])
    ->name('kiosk.face.checkout');

// Route validasi duplikasi wajah sebelum registrasi (no auth)
Route::post('/kiosk/face-check-duplicate', [FaceValidationController::class, 'checkDuplicate'])
    ->name('kiosk.face.check-duplicate');
