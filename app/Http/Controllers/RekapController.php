<?php

namespace App\Http\Controllers;

use App\Models\Recipient;
use App\Models\SourceAccount;
use App\Models\Transaction;
use App\Support\RekapExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        return view('rekap', $this->buildPivot($request));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $data = $this->buildPivot($request);

        $nama = 'rekap-transfer-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'.xlsx';
        $path = storage_path('app/'.$nama);

        (new RekapExcel($data))->simpanKe($path);

        return response()->download($path, $nama)->deleteFileAfterSend();
    }

    /**
     * Menyusun matriks tanggal x rekening sumber x penerima. Satu hari bisa
     * punya beberapa batch transfer, dan tiap batch tampil sebagai baris
     * tersendiri, sama seperti rekap manual yang selama ini dipakai.
     */
    private function buildPivot(Request $request): array
    {
        // Tanggal yang diisi manual selalu menang. Tanpa itu, dipakai rentang
        // pintas yang dipilih, dan tanpa itu juga, minggu berjalan.
        $rentang = $request->input('rentang', 'minggu');
        $sampai = now()->startOfDay();
        $senin = null;

        if ($rentang === 'minggu') {
            // Minggu kalender Senin sampai Minggu, bukan tujuh hari ke belakang,
            // supaya rekapnya sejajar dengan cara orang membaca kalender.
            $senin = $request->filled('minggu')
                ? Carbon::parse($request->input('minggu'))->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);

            $dari = $senin->copy();
            $sampai = $senin->copy()->addDays(6);
        } else {
            $dari = match ($rentang) {
                'tahun' => now()->subYear()->startOfDay(),
                'bulan' => now()->subMonth()->startOfDay(),
                default => now()->subDays(6)->startOfDay(),
            };
        }

        if ($request->filled('dari')) {
            $dari = Carbon::parse($request->input('dari'))->startOfDay();
            $rentang = null;
        }

        if ($request->filled('sampai')) {
            $sampai = Carbon::parse($request->input('sampai'))->startOfDay();
            $rentang = null;
        }

        if ($sampai->lt($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $rows = Transaction::query()
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->selectRaw('tanggal, source_account_id, recipient_id, SUM(nominal_transfer) as total')
            ->groupBy('tanggal', 'source_account_id', 'recipient_id')
            ->get();

        $matrix = [];
        $sumberPerTanggal = [];
        $totalPerSumber = [];
        $dailyTotals = [];
        $recipientTotals = [];
        $grandTotal = 0;

        foreach ($rows as $row) {
            $key = Carbon::parse($row->tanggal)->toDateString();
            $sumberId = $row->source_account_id;
            $total = (int) $row->total;

            $matrix[$key][$sumberId][$row->recipient_id] = $total;
            $totalPerSumber[$key][$sumberId] = ($totalPerSumber[$key][$sumberId] ?? 0) + $total;
            $dailyTotals[$key] = ($dailyTotals[$key] ?? 0) + $total;
            $recipientTotals[$row->recipient_id] = ($recipientTotals[$row->recipient_id] ?? 0) + $total;
            $grandTotal += $total;
        }

        foreach ($matrix as $key => $perSumber) {
            $sumberPerTanggal[$key] = array_keys($perSumber);
        }

        // Hanya tampilkan penerima yang benar-benar punya transaksi di periode
        // ini, supaya tabelnya tidak melebar oleh kolom kosong.
        $recipients = Recipient::query()
            ->whereIn('id', array_keys($recipientTotals))
            ->orderBy('nama')
            ->get();

        $jumlahHari = $dari->diffInDays($sampai) + 1;
        $dates = [];

        if ($jumlahHari <= 31) {
            for ($d = $dari->copy(); $d->lte($sampai); $d->addDay()) {
                $dates[] = $d->copy();
            }
        } else {
            foreach (array_keys($dailyTotals) as $key) {
                $dates[] = Carbon::parse($key);
            }

            usort($dates, fn (Carbon $a, Carbon $b) => $a <=> $b);
        }

        $hariTerisi = count($dailyTotals);

        // Daftar minggu untuk pilihan cepat, dari minggu berjalan ke belakang.
        $pilihanMinggu = [];
        $kursor = now()->startOfWeek(Carbon::MONDAY);

        for ($i = 0; $i < 16; $i++) {
            $pilihanMinggu[] = $kursor->copy();
            $kursor->subWeek();
        }

        return [
            'rentang' => $rentang,
            'senin' => $senin,
            'pilihanMinggu' => $pilihanMinggu,
            'mingguIni' => now()->startOfWeek(Carbon::MONDAY),
            'ringkas' => $jumlahHari > 31,
            'dari' => $dari,
            'sampai' => $sampai,
            'dates' => $dates,
            'recipients' => $recipients,
            'namaSumber' => SourceAccount::pluck('nama', 'id'),
            'matrix' => $matrix,
            'sumberPerTanggal' => $sumberPerTanggal,
            'totalPerSumber' => $totalPerSumber,
            'dailyTotals' => $dailyTotals,
            'recipientTotals' => $recipientTotals,
            'grandTotal' => $grandTotal,
            'hariTerisi' => $hariTerisi,
            'rataHarian' => $hariTerisi > 0 ? (int) round($grandTotal / $hariTerisi) : 0,
        ];
    }
}
