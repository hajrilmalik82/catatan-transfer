<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman rekap dipakai sebagai dashboard. Route /admin milik Filament tidak
 * bisa ditimpa dari routes/web.php karena didaftarkan lebih dulu, jadi
 * pengalihannya dilakukan di sini.
 */
class DashboardKeRekap
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() === 'admin') {
            return redirect()->to(\App\Filament\Pages\Rekap::getUrl());
        }

        return $next($request);
    }
}
