<?php

namespace App\Http\Middleware;

use App\Filament\Pages\Rekap;
use App\Filament\Resources\Recipients\RecipientResource;
use App\Filament\Resources\SourceAccounts\SourceAccountResource;
use App\Filament\Resources\Transactions\TransactionResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman Dashboard bawaan sudah dilepas, jadi /admin perlu diarahkan.
 * Tujuannya menyesuaikan hak akses: menu pertama yang boleh dibuka pengguna.
 * Pemblokiran halaman lain ditangani sendiri oleh Filament lewat canAccess().
 */
class BatasiPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() !== 'admin') {
            return $next($request);
        }

        $pengguna = $request->user();

        if (! $pengguna) {
            return $next($request);
        }

        $menu = $pengguna->menuPertama();

        abort_if($menu === null, 403, 'Akun ini belum diberi hak akses ke menu mana pun.');

        return redirect()->to(match ($menu) {
            'rekap' => Rekap::getUrl(),
            'import' => url('/import'),
            'transaksi' => TransactionResource::getUrl(),
            'penerima' => RecipientResource::getUrl(),
            'rekening_sumber' => SourceAccountResource::getUrl(),
        });
    }
}
