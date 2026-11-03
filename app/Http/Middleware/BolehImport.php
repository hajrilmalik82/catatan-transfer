<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Halaman import ada di luar panel, jadi haknya diperiksa di sini. */
class BolehImport
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->bolehAkses('import'), 403, 'Tidak punya hak akses ke menu import.');

        return $next($request);
    }
}
