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

        $transaksi = Transaction::query()
            // Kolom tanggal ikut menyimpan jam, jadi perbandingannya memakai
            // whereDate; kalau tidak, transaksi di hari terakhir rentang luput.
            ->whereDate('tanggal', '>=', $dari->toDateString())
            ->whereDate('tanggal', '<=', $sampai->toDateString())
            ->orderBy('tanggal')
            ->orderBy('source_account_id')
            ->orderBy('id')
            ->get(['id', 'tanggal', 'source_account_id', 'recipient_id', 'nominal_transfer', 'remark', 'ref_transaksi']);

        $slot = [];
        $dailyTotals = [];
        $recipientTotals = [];
        $grandTotal = 0;

        foreach ($transaksi as $t) {
            $kunci = Carbon::parse($t->tanggal)->toDateString();
            $sumberId = $t->source_account_id;
            $penerimaId = $t->recipient_id;
            $nominal = (int) $t->nominal_transfer;
            $rincian = [
                'remark' => $t->remark,
                'ref' => $t->ref_transaksi,
            ];

            $slot[$kunci][$sumberId] ??= [];

            // Tiap baris hanya boleh memuat satu nominal per penerima. Kalau
            // orang yang sama menerima lagi di hari dan rekening sumber yang
            // sama, nominalnya turun ke baris berikutnya, bukan dijumlahkan.
            $masuk = false;

            foreach ($slot[$kunci][$sumberId] as $i => $isi) {
                if (! isset($isi['nilai'][$penerimaId])) {
                    $slot[$kunci][$sumberId][$i]['nilai'][$penerimaId] = $nominal;
                    $slot[$kunci][$sumberId][$i]['info'][$penerimaId] = $rincian;
                    $slot[$kunci][$sumberId][$i]['total'] += $nominal;
                    $masuk = true;
                    break;
                }
            }

            if (! $masuk) {
                $slot[$kunci][$sumberId][] = [
                    'nilai' => [$penerimaId => $nominal],
                    'info' => [$penerimaId => $rincian],
                    'total' => $nominal,
                ];
            }

            $dailyTotals[$kunci] = ($dailyTotals[$kunci] ?? 0) + $nominal;
            $recipientTotals[$penerimaId] = ($recipientTotals[$penerimaId] ?? 0) + $nominal;
            $grandTotal += $nominal;
        }

        // Diratakan jadi daftar baris per tanggal, siap ditampilkan.
        $barisPerTanggal = [];

        foreach ($slot as $kunci => $perSumber) {
            foreach ($perSumber as $sumberId => $daftar) {
                foreach ($daftar as $i => $isi) {
                    $barisPerTanggal[$kunci][] = [
                        'sumberId' => $sumberId,
                        'urut' => $i + 1,
                        'jumlahSlot' => count($daftar),
                        'nilai' => $isi['nilai'],
                        'info' => $isi['info'] ?? [],
                        'total' => $isi['total'],
                    ];
                }
            }
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
            'barisPerTanggal' => $barisPerTanggal,
            'dailyTotals' => $dailyTotals,
            'recipientTotals' => $recipientTotals,
            'grandTotal' => $grandTotal,
            'hariTerisi' => $hariTerisi,
            'rataHarian' => $hariTerisi > 0 ? (int) round($grandTotal / $hariTerisi) : 0,
        ];
    }
}
