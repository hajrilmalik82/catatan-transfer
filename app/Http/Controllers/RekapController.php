<?php

namespace App\Http\Controllers;

use App\Support\RekapData;
use App\Support\RekapExcel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    public function export(Request $request, RekapData $rekap): BinaryFileResponse
    {
        $data = $rekap->build($request);

        $nama = 'rekap-transfer-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'.xlsx';
        $path = storage_path('app/'.$nama);

        (new RekapExcel($data))->simpanKe($path);

        return response()->download($path, $nama)->deleteFileAfterSend();
    }
}
