<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\RekapController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Middleware auth mengarahkan tamu ke route bernama 'login'; satu-satunya
// halaman login di aplikasi ini milik panel Filament.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/rekap', [RekapController::class, 'index'])->name('rekap');
    Route::get('/rekap/export', [RekapController::class, 'export'])->name('rekap.export');

    Route::get('/import', [ImportController::class, 'form'])->name('import');
    Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/simpan', [ImportController::class, 'simpan'])->name('import.simpan');
});
