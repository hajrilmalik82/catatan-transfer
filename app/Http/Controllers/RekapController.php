<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\RekapData;
use App\Support\RekapExcel;
use App\Support\RekapPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    /**
     * Tampilan publik rekapan transfer (hanya lihat, tanpa login).
     */
    public function public(Request $request, RekapData $rekap): Response
    {
        $aktif = Setting::get('rekap_publik_aktif', true);

        if (! $aktif) {
            // Admin yang sedang login dan punya izin rekap tetap dapat melihat preview
            if ($request->user()?->bolehAkses('rekap')) {
                return response()->view('rekap-public', array_merge($rekap->build($request), ['publikAktif' => false]));
            }

            return response()->view('rekap-disabled', [], 403);
        }

        return response()->view('rekap-public', array_merge($rekap->build($request), ['publikAktif' => true]));
    }

    public function export(Request $request, RekapData $rekap): BinaryFileResponse
    {
        $aktif = Setting::get('rekap_publik_aktif', true);

        if (! $aktif && ! $request->user()?->bolehAkses('rekap')) {
            abort(403, 'Tampilan publik sedang dinonaktifkan.');
        }

        $data = $rekap->build($request);

        $nama = 'rekap-transfer-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'.xlsx';
        $path = storage_path('app/'.uniqid('rekap-transfer-', true).'.xlsx');

        (new RekapExcel($data))->simpanKe($path);

        // Tipe dan nama berkas dinyatakan tegas. Safari di iOS kadang keliru
        // menebaknya dan membuka berkas sebagai teks alih-alih mengunduh.
        return response()->download($path, $nama, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
        ])->deleteFileAfterSend();
    }

    public function exportPdf(Request $request, RekapData $rekap): BinaryFileResponse
    {
        $aktif = Setting::get('rekap_publik_aktif', true);

        if (! $aktif && ! $request->user()?->bolehAkses('rekap')) {
            abort(403, 'Tampilan publik sedang dinonaktifkan.');
        }

        $data = $rekap->build($request);

        $nama = 'rekap-transfer-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'.pdf';
        $path = storage_path('app/'.uniqid('rekap-transfer-', true).'.pdf');

        (new RekapPdf($data))->simpanKe($path);

        return response()->download($path, $nama, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
        ])->deleteFileAfterSend();
    }
}
