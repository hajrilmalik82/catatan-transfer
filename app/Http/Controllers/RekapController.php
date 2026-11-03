<?php

namespace App\Http\Controllers;

use App\Models\Recipient;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        return view('rekap', $this->buildPivot($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildPivot($request);

        $filename = 'rekap-transfer-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            // BOM supaya Excel membaca UTF-8 dengan benar.
            fwrite($out, "\xEF\xBB\xBF");

            $header = ['Tanggal'];
            foreach ($data['recipients'] as $recipient) {
                $header[] = $recipient->nama;
            }
            $header[] = 'Total Harian';
            fputcsv($out, $header);

            foreach ($data['dates'] as $date) {
                $key = $date->toDateString();
                $row = [$date->format('Y-m-d')];
                foreach ($data['recipients'] as $recipient) {
                    $row[] = $data['matrix'][$key][$recipient->id] ?? 0;
                }
                $row[] = $data['dailyTotals'][$key] ?? 0;
                fputcsv($out, $row);
            }

            $footer = ['TOTAL'];
            foreach ($data['recipients'] as $recipient) {
                $footer[] = $data['recipientTotals'][$recipient->id] ?? 0;
            }
            $footer[] = $data['grandTotal'];
            fputcsv($out, $footer);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Menyusun matriks tanggal x penerima, bentuk yang sama dengan rekap
     * manual yang selama ini dipakai.
     */
    private function buildPivot(Request $request): array
    {
        $dari = $request->filled('dari')
            ? Carbon::parse($request->input('dari'))->startOfDay()
            : now()->startOfMonth();

        $sampai = $request->filled('sampai')
            ? Carbon::parse($request->input('sampai'))->startOfDay()
            : now()->endOfMonth()->startOfDay();

        if ($sampai->lt($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $rows = Transaction::query()
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->selectRaw('tanggal, recipient_id, SUM(nominal_transfer) as total')
            ->groupBy('tanggal', 'recipient_id')
            ->get();

        $matrix = [];
        $dailyTotals = [];
        $recipientTotals = [];
        $grandTotal = 0;

        foreach ($rows as $row) {
            $key = Carbon::parse($row->tanggal)->toDateString();
            $total = (int) $row->total;

            $matrix[$key][$row->recipient_id] = $total;
            $dailyTotals[$key] = ($dailyTotals[$key] ?? 0) + $total;
            $recipientTotals[$row->recipient_id] = ($recipientTotals[$row->recipient_id] ?? 0) + $total;
            $grandTotal += $total;
        }

        // Hanya tampilkan penerima yang benar-benar punya transaksi di periode
        // ini, supaya tabelnya tidak melebar oleh kolom kosong.
        $recipients = Recipient::query()
            ->whereIn('id', array_keys($recipientTotals))
            ->orderBy('nama')
            ->get();

        $dates = [];
        for ($d = $dari->copy(); $d->lte($sampai); $d->addDay()) {
            $dates[] = $d->copy();
        }

        $hariTerisi = count($dailyTotals);

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'dates' => $dates,
            'recipients' => $recipients,
            'matrix' => $matrix,
            'dailyTotals' => $dailyTotals,
            'recipientTotals' => $recipientTotals,
            'grandTotal' => $grandTotal,
            'hariTerisi' => $hariTerisi,
            'rataHarian' => $hariTerisi > 0 ? (int) round($grandTotal / $hariTerisi) : 0,
        ];
    }
}
