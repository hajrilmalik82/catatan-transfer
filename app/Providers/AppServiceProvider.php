<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama hari dan bulan tampil dalam bahasa Indonesia di halaman rekap.
        Carbon::setLocale('id');

        $this->jalankanMigrasiOtomatis();
    }

    /**
     * Di hosting tanpa akses SSH, migrasi baru dijalankan otomatis saat daftar
     * berkas di database/migrations bertambah sesudah deploy lewat GitHub Actions.
     */
    private function jalankanMigrasiOtomatis(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $berkas = glob(database_path('migrations/*.php')) ?: [];
        $sidik = md5(implode('|', array_map('basename', $berkas)));
        $penanda = storage_path('framework/cache/data/migrasi.sidik');

        if (is_file($penanda) && trim((string) file_get_contents($penanda)) === $sidik) {
            return;
        }

        Artisan::call('migrate', ['--force' => true]);
        @file_put_contents($penanda, $sidik);
    }
}
