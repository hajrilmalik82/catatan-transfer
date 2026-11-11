<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\RekapController;
use App\Http\Middleware\BolehImport;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Middleware auth mengarahkan tamu ke route bernama 'login'; satu-satunya
// halaman login di aplikasi ini milik panel Filament.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');

// Tampilan publik rekapan (tanpa login, hanya lihat, bisa unduh Excel & PDF)
Route::get('/rekap', [RekapController::class, 'public'])->name('rekap.public');
Route::get('/rekap/export', [RekapController::class, 'export'])->name('rekap.export');
Route::get('/rekap/export/pdf', [RekapController::class, 'exportPdf'])->name('rekap.export.pdf');

Route::middleware('auth')->group(function () {
    Route::middleware(BolehImport::class)->group(function () {
        Route::get('/import', [ImportController::class, 'form'])->name('import');
        Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('/import/simpan', [ImportController::class, 'simpan'])->name('import.simpan');
    });
});
