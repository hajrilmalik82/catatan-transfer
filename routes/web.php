<?php

use App\Http\Controllers\ImportController;
use App\Http\Middleware\BolehImport;
use App\Http\Controllers\RekapController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Middleware auth mengarahkan tamu ke route bernama 'login'; satu-satunya
// halaman login di aplikasi ini milik panel Filament.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/rekap/export', [RekapController::class, 'export'])->name('rekap.export');

    Route::middleware(BolehImport::class)->group(function () {
        Route::get('/import', [ImportController::class, 'form'])->name('import');
        Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('/import/simpan', [ImportController::class, 'simpan'])->name('import.simpan');
    });
});
