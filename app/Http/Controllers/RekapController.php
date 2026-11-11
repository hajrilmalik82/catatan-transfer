<?php

namespace App\Http\Controllers;

use App\Support\RekapData;
use App\Support\RekapExcel;
use App\Support\RekapPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    public function export(Request $request, RekapData $rekap): BinaryFileResponse
    {
        abort_unless($request->user()?->bolehAkses('rekap'), 403, 'Tidak punya hak akses ke menu rekap.');

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
        abort_unless($request->user()?->bolehAkses('rekap'), 403, 'Tidak punya hak akses ke menu rekap.');

        $data = $rekap->build($request);

        $nama = 'rekap-transfer-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'.pdf';
        $path = storage_path('app/'.uniqid('rekap-transfer-', true).'.pdf');

        (new RekapPdf($data))->simpanKe($path);

        return response()->download($path, $nama, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
        ])->deleteFileAfterSend();
    }

    public function sync(Request $request, RekapData $rekap)
    {
        $validToken = config('catatan.sync_token');
        abort_if(! $validToken || $request->query('token') !== $validToken, 403, 'Token sinkronisasi tidak valid.');

        $data = $rekap->build($request);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'judul' => 'REKAP TRANSFER',
                'periode' => sprintf('%s s/d %s', $data['dari']->translatedFormat('l, d F Y'), $data['sampai']->translatedFormat('l, d F Y')),
                'ringkasan' => [
                    'totalPenerimaan' => $data['grandTotal'],
                    'rataHarian' => $data['rataHarian'],
                    'rekeningAktif' => $data['recipients']->count(),
                    'hariPencatatan' => $data['hariTerisi'],
                ],
                'penerima' => $data['recipients']->map(fn ($p) => [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'bank' => $p->bank,
                    'nomorRekening' => $p->nomor_rekening,
                ])->values(),
                'tanggal' => collect($data['dates'])->map(function ($tgl) use ($data) {
                    $kunci = $tgl->toDateString();
                    $baris = $data['barisPerTanggal'][$kunci] ?? [];

                    return [
                        'tanggalFormat' => $tgl->translatedFormat('d M Y'),
                        'kunci' => $kunci,
                        'totalHarian' => $data['dailyTotals'][$kunci] ?? 0,
                        'baris' => collect($baris)->map(function ($b) use ($data) {
                            $nama = $data['namaSumber'][$b['sumberId']] ?? '?';
                            if (($b['jumlahSlot'] ?? 1) > 1) {
                                $nama .= ' #'.$b['urut'];
                            }
                            $nomor = $data['nomorSumber'][$b['sumberId']] ?? '';

                            return [
                                'sumber' => $nama,
                                'nomorSumber' => $nomor,
                                'nilai' => $b['nilai'],
                                'total' => $b['total'],
                            ];
                        })->values(),
                    ];
                })->values(),
                'totalPerPenerima' => $data['recipientTotals'],
                'grandTotal' => $data['grandTotal'],
            ]);
        }

        return response()
            ->view('rekap-table', $data)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
