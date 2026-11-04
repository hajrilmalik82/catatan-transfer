<?php

namespace App\Support;

use Carbon\Carbon;
use Smalot\PdfParser\Parser;

/**
 * Membaca bukti transfer Mandiri "Status Transaksi" yang berisi satu
 * transaksi per halaman.
 *
 * Mandiri menerbitkan bukti dalam dua bahasa dengan label berbeda, jadi tiap
 * pola menerima keduanya. Isinya diambil berdasarkan label, bukan posisi
 * baris, karena urutan teks hasil ekstraksi tidak selalu sama dengan tampilan
 * visual PDF-nya, dan spasi sesudah label kadang hilang.
 */
class MandiriPdfParser
{
    private const POLA_ID = '(?:ID Transaksi|Transaction Id)\s*(\d{10,})';

    /**
     * Nama bank dicocokkan dari penyebutan resminya di bukti transfer.
     * Urutannya penting: pola yang lebih spesifik diperiksa lebih dulu.
     */
    private const POLA_BANK = [
        'Bank Syariah Indonesia' => 'BSI',
        'Bank Central Asia' => 'BCA',
        'Bank Negara Indonesia' => 'BNI',
        'Bank Rakyat Indonesia' => 'BRI',
        'Bank Mandiri' => 'Mandiri',
        'CIMB' => 'CIMB Niaga',
        'Permata' => 'Permata',
        'Danamon' => 'Danamon',
        'Tabungan Negara' => 'BTN',
        'BSI' => 'BSI',
        'BCA' => 'BCA',
        'BNI' => 'BNI',
        'BRI' => 'BRI',
        'BTN' => 'BTN',
    ];

    /**
     * Label sebelum bagian bank penerima. Ejaannya dibiarkan longgar karena
     * sebagian berkas memakai font yang pemetaan hurufnya rusak, sehingga
     * "Beneficiary" bisa terbaca "Benefciary" atau "BenePciary".
     */
    private const POLA_LABEL_BANK = 'Informasi Bank|Bene\S*\s*Bank';

    public function teksMentah(string $path): string
    {
        return (new Parser)->parseFile($path)->getText();
    }

    /**
     * @return array{baris: array<int, array<string, mixed>>, gagal: array<int, string>}
     */
    public function parse(string $path): array
    {
        $teks = $this->teksMentah($path);

        // Dipecah per penanda ID transaksi supaya tidak bergantung pada
        // pemisahan halaman, yang kadang meleset saat ekstraksi.
        $potongan = preg_split('/(?='.self::POLA_ID.')/u', $teks);

        $baris = [];
        $gagal = [];

        foreach ($potongan as $bagian) {
            if (! preg_match('/'.self::POLA_ID.'/u', $bagian)) {
                continue;
            }

            $hasil = $this->bacaSatu($this->rapikan($bagian));

            if (is_string($hasil)) {
                $gagal[] = $hasil;

                continue;
            }

            $baris[] = $hasil;
        }

        return ['baris' => $baris, 'gagal' => $gagal];
    }

    private function rapikan(string $teks): string
    {
        return trim(preg_replace('/\s+/u', ' ', $teks));
    }

    /**
     * @return array<string, mixed>|string Baris hasil baca, atau pesan kenapa dilewati.
     */
    private function bacaSatu(string $teks): array|string
    {
        $ref = $this->ambil($teks, '/'.self::POLA_ID.'/u');

        if ($ref === null) {
            return 'Satu bagian dilewati: ID transaksi tidak terbaca.';
        }

        // Nilai statusnya dibatasi daftar tertentu, sebab tulisan "Status
        // Transaksi" juga muncul sebagai judul halaman dan judul kolom.
        $status = $this->ambil(
            $teks,
            '/(?:Status Transaksi|Transaction Status)\s*(Sukses|Success|Gagal|Failed|Pending|Ditolak|Rejected)/ui'
        );

        if ($status === null || ! in_array(mb_strtolower($status), ['sukses', 'success'], true)) {
            return "Transaksi {$ref} dilewati: statusnya bukan Sukses.";
        }

        $nominal = $this->ambil($teks, '/(?:Jumlah Nominal Debit|Total Debit Amount)\s*IDR\s*([\d.,]+)/u');

        $tujuan = $this->ambil(
            $teks,
            '/(?:Rekening Tujuan|Destination Account)\s*(.{0,120}?)\s*(?:'.self::POLA_LABEL_BANK.')/u'
        );

        // Tanggal pembuatan yang dipakai, bukan tanggal instruksi: instruksi
        // yang dibuat lewat jam potong tercatat pada hari kerja berikutnya,
        // sementara yang dicatat adalah hari pembayarannya.
        $tanggal = $this->ambil($teks, '/(?:Tanggal Pembuatan|Creation Date)\s*([A-Za-z]{3}\s*\d{1,2},\s*\d{4})/u')
            ?? $this->ambil($teks, '/(?:Tanggal Instruksi|Instruction Date)\s*([A-Za-z]{3}\s*\d{1,2},\s*\d{4})/u');

        $kosong = array_keys(array_filter([
            'nominal' => $nominal === null,
            'rekening tujuan' => $tujuan === null,
            'tanggal' => $tanggal === null,
        ]));

        if ($kosong !== []) {
            return "Transaksi {$ref} dilewati: ".implode(' dan ', $kosong).' tidak terbaca.';
        }

        // Bentuknya "1100023404608 HENDRA" atau "2115217524 - ERVAN".
        if (! preg_match('/^(\d{6,})\s*-?\s*(.*)$/u', trim($tujuan), $cocok)) {
            return "Transaksi {$ref} dilewati: nomor rekening tujuan tidak terbaca.";
        }

        return [
            'ref_transaksi' => $ref,
            'tanggal' => Carbon::createFromFormat('M j, Y', $tanggal)->toDateString(),
            'nominal' => (int) round((float) str_replace(',', '', $nominal)),
            'nomor_rekening' => $cocok[1],
            'nama' => $this->rapikanNama($cocok[2]),
            'bank' => $this->tebakBank($teks),
            'remark' => $this->ambilKeterangan($teks),
            'sumber_rekening' => $this->ambil($teks, '/(?:Sumber Dana|Source of Fund)\s*(\d{6,})/u'),
        ];
    }

    private function ambilKeterangan(string $teks): ?string
    {
        $nilai = $this->ambil($teks, '/(?:Keterangan|Rema\S*)\s*(.{0,200}?)\s*(?:Uraian|E\S?tended\s*De\S*)/u');

        return ($nilai === null || $nilai === '' || $nilai === '-') ? null : $nilai;
    }

    private function rapikanNama(string $nama): string
    {
        $nama = trim(preg_replace('/\s+/u', ' ', $nama));

        // Bukti transfer menulis nama dengan huruf kapital semua.
        return $nama === '' ? '(tanpa nama)' : mb_convert_case($nama, MB_CASE_TITLE, 'UTF-8');
    }

    private function tebakBank(string $teks): ?string
    {
        // Dibatasi ke bagian sesudah label bank penerima supaya tidak tertukar
        // dengan judul seperti "Single Transfer To Mandiri".
        $posisi = $this->posisiBank($teks);
        $cari = $posisi === false ? $teks : substr($teks, $posisi, 200);

        foreach (self::POLA_BANK as $pola => $bank) {
            if (mb_stripos($cari, $pola) !== false) {
                return $bank;
            }
        }

        return null;
    }

    private function posisiBank(string $teks): int|false
    {
        return preg_match('/'.self::POLA_LABEL_BANK.'/u', $teks, $cocok, PREG_OFFSET_CAPTURE)
            ? $cocok[0][1]
            : false;
    }

    private function ambil(string $teks, string $pola): ?string
    {
        return preg_match($pola, $teks, $cocok) ? trim($cocok[1]) : null;
    }
}
