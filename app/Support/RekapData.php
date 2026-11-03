<?php

namespace App\Support;

use App\Models\Recipient;
use App\Models\SourceAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Menyiapkan data rekap. Dipakai bersama oleh halaman rekap di panel dan
 * oleh pengunduhan Excel.
 */
class RekapData
{
    /**
     * Menyusun matriks tanggal x rekening sumber x penerima. Satu hari bisa
     * punya beberapa batch transfer, dan tiap batch tampil sebagai baris
     * tersendiri, sama seperti rekap manual yang selama ini dipakai.
     */
    public function build(Request $request): array
    {
        // Tampilannya selalu per minggu kalender Senin sampai Minggu. Filter
        // tanggal manual menimpa rentangnya, tapi navigasi mingguan tetap ada.
        $manual = $request->filled('dari') || $request->filled('sampai');

        $senin = match (true) {
            $request->filled('minggu') => Carbon::parse($request->input('minggu'))->startOfWeek(Carbon::MONDAY),
            $request->filled('dari') => Carbon::parse($request->input('dari'))->startOfWeek(Carbon::MONDAY),
            default => now()->startOfWeek(Carbon::MONDAY),
        };

        $dari = $request->filled('dari')
            ? Carbon::parse($request->input('dari'))->startOfDay()
            : $senin->copy();

        $sampai = $request->filled('sampai')
            ? Carbon::parse($request->input('sampai'))->startOfDay()
            : ($manual ? $dari->copy() : $senin->copy()->addDays(6));

        if ($sampai->lt($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $rows = Transaction::query()
            // Kolom tanggal ikut menyimpan jam, jadi perbandingannya memakai
            // whereDate; kalau tidak, transaksi di hari terakhir rentang luput.
            ->whereDate('tanggal', '>=', $dari->toDateString())
            ->whereDate('tanggal', '<=', $sampai->toDateString())
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
            'manual' => $manual,
            'senin' => $senin,
            'pilihanMinggu' => $pilihanMinggu,
            'mingguIni' => now()->startOfWeek(Carbon::MONDAY),
            'ringkas' => $jumlahHari > 31,
            'dari' => $dari,
            'sampai' => $sampai,
            'dates' => $dates,
            'recipients' => $recipients,
            'namaSumber' => SourceAccount::pluck('nama', 'id'),
            'nomorSumber' => SourceAccount::pluck('nomor_rekening', 'id'),
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
