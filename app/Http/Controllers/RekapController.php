<?php

namespace App\Http\Controllers;

use App\Support\RekapData;
use App\Support\RekapExcel;
use App\Support\RekapPdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    /**
     * Tampilan publik rekapan transfer (hanya lihat, tanpa login).
     */
    public function public(Request $request, RekapData $rekap): View
    {
        return view('rekap-public', $rekap->build($request));
    }

    public function export(Request $request, RekapData $rekap): BinaryFileResponse
    {
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
