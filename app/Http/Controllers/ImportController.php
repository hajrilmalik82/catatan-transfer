<?php

namespace App\Http\Controllers;

use App\Models\Recipient;
use App\Models\SourceAccount;
use App\Models\Transaction;
use App\Support\MandiriPdfParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    private const KUNCI_SESI = 'import.baris';

    public function form()
    {
        return view('import', [
            'sourceAccounts' => SourceAccount::where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    public function preview(Request $request, MandiriPdfParser $parser)
    {
        $data = $request->validate([
            'source_account_id' => ['required', 'exists:source_accounts,id'],
            'berkas' => ['required_without:raw_code', 'nullable', 'file', 'mimes:pdf', 'max:10240'],
            'raw_code' => ['required_without:berkas', 'nullable', 'string'],
        ]);

        $dariRaw = $request->filled('raw_code');

        if ($dariRaw) {
            $teksMentah = (string) $data['raw_code'];
            $hasil = $parser->parseRawCode($teksMentah);
        } else {
            $path = $request->file('berkas')->getRealPath();
            $teksMentah = $parser->teksMentah($path);
            $hasil = $parser->parse($path);
        }

        if ($hasil['baris'] === []) {
            $pesanGagal = $hasil['gagal'] !== []
                ? implode(' ', $hasil['gagal'])
                : 'Tidak ada transaksi yang bisa dibaca dari masukan ini.';

            // Susunan teks hasil ekstraksi bisa berbeda antar berkas. Cuplikan
            // mentahnya ditampilkan supaya penyebabnya langsung kelihatan.
            return back()
                ->withErrors(['berkas' => $pesanGagal])
                ->with('teksMentah', mb_substr($teksMentah, 0, 3000))
                ->withInput();
        }

        // Pencocokan memakai nomor rekening, bukan nama, karena bank memotong
        // nama penerima pada panjang tertentu. Dicocokkan juga tanpa angka nol
        // di depan untuk mengantisipasi nomor rekening yang pernah disimpan dari Excel.
        $penerima = Recipient::pluck('id', 'nomor_rekening');
        $penerimaTanpaNol = $penerima->mapWithKeys(fn ($id, $rek) => [ltrim((string) $rek, '0') => $id]);
        $sumber = SourceAccount::whereNotNull('nomor_rekening')->pluck('id', 'nomor_rekening');
        $sudahAda = Transaction::whereIn('ref_transaksi', array_column($hasil['baris'], 'ref_transaksi'))
            ->pluck('ref_transaksi')
            ->all();

        $baris = [];
        $belumTerdaftar = [];

        foreach ($hasil['baris'] as $row) {
            $noRek = (string) $row['nomor_rekening'];
            $row['recipient_id'] = $penerima[$noRek] ?? $penerimaTanpaNol[ltrim($noRek, '0')] ?? null;
            $row['duplikat'] = in_array($row['ref_transaksi'], $sudahAda, true);

            // Rekening sumber diambil dari isi berkas. Pilihan di form hanya
            // dipakai kalau berkasnya tidak menyebutkan nomor rekening.
            $noSumber = $row['sumber_rekening'] ?? null;

            if ($noSumber === null || ($dariRaw && ! isset($sumber[$noSumber]))) {
                $row['source_account_id'] = (int) $data['source_account_id'];
            } elseif (isset($sumber[$noSumber])) {
                $row['source_account_id'] = $sumber[$noSumber];
            } else {
                $belumTerdaftar[$noSumber] = true;
                $row['source_account_id'] = null;
            }

            $baris[] = $row;
        }

        if ($belumTerdaftar !== []) {
            $nomor = implode(', ', array_keys($belumTerdaftar));

            return back()->withErrors(['berkas' => "Rekening sumber {$nomor} belum terdaftar. Tambahkan dulu di menu Rekening Sumber beserta nomor rekeningnya, lalu unggah ulang."])->withInput();
        }

        $request->session()->put(self::KUNCI_SESI, ['baris' => $baris]);

        return view('import-preview', [
            'teksMentah' => mb_substr($teksMentah, 0, 20000),
            'baris' => $baris,
            'gagal' => $hasil['gagal'],
            'recipients' => Recipient::where('aktif', true)->orderBy('nama')->get(),
            'namaSumber' => SourceAccount::pluck('nama', 'id'),
        ]);
    }

    public function simpan(Request $request)
    {
        $sesi = $request->session()->get(self::KUNCI_SESI);

        if (! $sesi) {
            return redirect()->route('import')
                ->withErrors(['berkas' => 'Sesi unggahan sudah berakhir. Silakan unggah ulang berkasnya.']);
        }

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'biaya_admin' => ['required', 'integer', 'min:0'],
            'pilihan' => ['required', 'array'],
            'pilihan.*' => ['required', 'string'],
            'remark' => ['array'],
            'remark.*' => ['nullable', 'string', 'max:255'],
        ]);

        $tersimpan = 0;
        $dilewati = 0;
        $penerimaBaru = 0;

        DB::transaction(function () use ($sesi, $data, &$tersimpan, &$dilewati, &$penerimaBaru) {
            foreach ($sesi['baris'] as $i => $row) {
                $pilihan = $data['pilihan'][$i] ?? 'lewati';

                if ($pilihan === 'lewati') {
                    $dilewati++;

                    continue;
                }

                if ($pilihan === 'baru') {
                    $penerima = Recipient::firstOrCreate(
                        ['nomor_rekening' => $row['nomor_rekening']],
                        [
                            'nama' => $row['nama'],
                            'bank' => $row['bank'] ?? 'Lainnya',
                            'aktif' => true,
                        ]
                    );

                    if ($penerima->wasRecentlyCreated) {
                        $penerimaBaru++;
                    }

                    $recipientId = $penerima->id;
                } else {
                    $recipientId = (int) $pilihan;
                }

                // ref_transaksi dari bank dipakai sebagai kunci, jadi berkas
                // yang sama diunggah dua kali tidak menghasilkan baris dobel.
                Transaction::updateOrCreate(
                    ['ref_transaksi' => $row['ref_transaksi']],
                    [
                        'tanggal' => $data['tanggal'],
                        'source_account_id' => $row['source_account_id'],
                        'recipient_id' => $recipientId,
                        'remark' => $data['remark'][$i] ?? $row['remark'],
                        'nominal_tagihan' => $row['nominal'],
                        'biaya_admin' => $data['biaya_admin'],
                    ]
                );

                $tersimpan++;
            }
        });

        $request->session()->forget(self::KUNCI_SESI);

        $pesan = "{$tersimpan} transaksi tersimpan";
        $pesan .= $penerimaBaru > 0 ? ", {$penerimaBaru} penerima baru dibuat" : '';
        $pesan .= $dilewati > 0 ? ", {$dilewati} dilewati" : '';

        return redirect()->route('import')->with('sukses', $pesan.'.');
    }
}
