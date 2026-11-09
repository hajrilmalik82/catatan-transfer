<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Menyusun rekap jadi berkas Excel dengan tata letak yang sama seperti rekap
 * manual: kartu ringkasan di atas, lalu matriks tanggal x penerima dengan sel
 * tanggal dan total harian yang digabung ke bawah.
 */
class RekapExcel
{
    private const RUPIAH = '"Rp"#,##0';

    private const BIRU_TUA = 'FF1E3A8A';

    private const BIRU_MUDA = 'FFDBEAFE';

    private const ABU = 'FFF3F4F6';

    private Worksheet $sheet;

    private int $barisAwalIsi = 0;

    /** @param array<string, mixed> $data */
    public function __construct(private array $data) {}

    public function simpanKe(string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Rekap Transfer');

        $baris = $this->tulisJudul();
        $baris = $this->tulisRingkasan($baris + 1);
        $barisKepala = $baris + 1;
        $baris = $this->tulisKepalaTabel($barisKepala);
        $this->barisAwalIsi = $baris;
        $baris = $this->tulisIsi($baris);
        $this->tulisTotal($baris);

        $this->rapikanKolom();
        // Hanya kolom Tanggal yang dikunci; Transaksi ikut bergeser bersama
        // kolom penerima supaya tabelnya tidak terasa terpotong.
        $this->sheet->freezePane('B'.($barisKepala + 2));

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    private function kolomTerakhir(): int
    {
        // Tanggal, Transaksi, penerima..., Total per Transaksi, Total Harian
        return 4 + $this->data['recipients']->count();
    }

    private function huruf(int $kolom): string
    {
        return Coordinate::stringFromColumnIndex($kolom);
    }

    private function rentang(int $dari, int $sampai, int $baris): string
    {
        return $this->huruf($dari).$baris.':'.$this->huruf($sampai).$baris;
    }

    private function tulisJudul(): int
    {
        $akhir = $this->kolomTerakhir();

        $this->sheet->setCellValue('A1', 'REKAP TRANSFER');
        $this->sheet->mergeCells($this->rentang(1, $akhir, 1));
        $this->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $this->sheet->getRowDimension(1)->setRowHeight(24);

        $this->sheet->setCellValue('A2', sprintf(
            '%s s/d %s',
            $this->data['dari']->translatedFormat('l, d F Y'),
            $this->data['sampai']->translatedFormat('l, d F Y')
        ));
        $this->sheet->mergeCells($this->rentang(1, $akhir, 2));
        $this->sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF6B7280');

        return 2;
    }

    private function tulisRingkasan(int $baris): int
    {
        $kartu = [
            ['TOTAL PENERIMAAN', $this->data['grandTotal'], true],
            ['RATA-RATA HARIAN', $this->data['rataHarian'], true],
            ['REKENING AKTIF', $this->data['recipients']->count(), false],
            ['HARI PENCATATAN', $this->data['hariTerisi'], false],
        ];

        $kolom = 1;

        foreach ($kartu as [$judul, $nilai, $uang]) {
            $kiri = $this->huruf($kolom);
            $kanan = $this->huruf($kolom + 1);

            $this->sheet->setCellValue($kiri.$baris, $judul);
            $this->sheet->mergeCells($kiri.$baris.':'.$kanan.$baris);
            $this->sheet->setCellValue($kiri.($baris + 1), $nilai);
            $this->sheet->mergeCells($kiri.($baris + 1).':'.$kanan.($baris + 1));

            $this->sheet->getStyle($kiri.$baris)->getFont()->setBold(true)->setSize(9)
                ->getColor()->setARGB('FF6B7280');
            $this->sheet->getStyle($kiri.($baris + 1))->getFont()->setBold(true)->setSize(12);

            if ($uang) {
                $this->sheet->getStyle($kiri.($baris + 1))->getNumberFormat()->setFormatCode(self::RUPIAH);
            }

            $this->warnai($kiri.$baris.':'.$kanan.($baris + 1), self::ABU);
            $this->garis($kiri.$baris.':'.$kanan.($baris + 1));

            $kolom += 2;
        }

        return $baris + 1;
    }

    private function tulisKepalaTabel(int $baris): int
    {
        $bawah = $baris + 1;
        $akhir = $this->kolomTerakhir();

        $this->sheet->setCellValue('A'.$baris, 'Tanggal');
        $this->sheet->mergeCells('A'.$baris.':A'.$bawah);
        $this->sheet->setCellValue('B'.$baris, 'Transaksi');
        $this->sheet->mergeCells('B'.$baris.':B'.$bawah);

        $kolom = 3;

        foreach ($this->data['recipients'] as $penerima) {
            $huruf = $this->huruf($kolom);
            $this->sheet->setCellValue($huruf.$baris, $penerima->nama);
            $this->sheet->setCellValue($huruf.$bawah, $penerima->bank.' '.$penerima->nomor_rekening);
            $kolom++;
        }

        $this->sheet->setCellValue($this->huruf($kolom).$baris, 'Total per Transaksi');
        $this->sheet->mergeCells($this->huruf($kolom).$baris.':'.$this->huruf($kolom).$bawah);
        $this->sheet->setCellValue($this->huruf($kolom + 1).$baris, 'Total Harian');
        $this->sheet->mergeCells($this->huruf($kolom + 1).$baris.':'.$this->huruf($kolom + 1).$bawah);

        $kepala = 'A'.$baris.':'.$this->huruf($akhir).$bawah;
        $this->warnai($kepala, self::BIRU_TUA);
        $this->sheet->getStyle($kepala)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $this->sheet->getStyle($kepala)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $this->garis($kepala);

        // Baris nomor rekening dibuat lebih kecil supaya namanya yang menonjol.
        $this->sheet->getStyle('C'.$bawah.':'.$this->huruf($akhir - 2).$bawah)->getFont()->setSize(8)->setBold(false);
        $this->sheet->getRowDimension($baris)->setRowHeight(30);

        return $bawah + 1;
    }

    private function tulisIsi(int $baris): int
    {
        $akhir = $this->kolomTerakhir();

        foreach ($this->data['dates'] as $tanggal) {
            $kunci = $tanggal->toDateString();
            $barisHariIni = $this->data['barisPerTanggal'][$kunci] ?? [];
            $jumlah = max(1, count($barisHariIni));
            $awal = $baris;

            if ($barisHariIni === []) {
                $this->sheet->setCellValue('A'.$baris, $tanggal->translatedFormat('d M Y'));
                $this->sheet->setCellValue('B'.$baris, '-');
                $baris++;
            } else {
                foreach ($barisHariIni as $i => $isi) {
                    if ($i === 0) {
                        $this->sheet->setCellValue('A'.$baris, $tanggal->translatedFormat('d M Y'));
                    }

                    $this->sheet->setCellValue('B'.$baris, $this->labelSumber($isi['sumberId'], $isi));
                    $this->sheet->getStyle('B'.$baris)->getAlignment()->setWrapText(true);

                    $kolom = 3;

                    foreach ($this->data['recipients'] as $penerima) {
                        $nilai = $isi['nilai'][$penerima->id] ?? null;

                        if ($nilai !== null) {
                            $this->sheet->setCellValue($this->huruf($kolom).$baris, $nilai);
                        }

                        $kolom++;
                    }

                    $kolomPenerimaAwal = $this->huruf(3);
                    $kolomPenerimaAkhir = $this->huruf(2 + max(1, $this->data['recipients']->count()));

                    if ($this->data['recipients']->isNotEmpty()) {
                        $this->sheet->setCellValue($this->huruf($kolom).$baris, "=SUM({$kolomPenerimaAwal}{$baris}:{$kolomPenerimaAkhir}{$baris})");
                    } else {
                        $this->sheet->setCellValue($this->huruf($kolom).$baris, 0);
                    }
                    $baris++;
                }
            }

            // Tanggal dan total harian digabung menaungi seluruh baris hari itu.
            $kolomTotalTransaksi = $this->huruf($akhir - 1);
            if ($jumlah > 1) {
                $this->sheet->mergeCells('A'.$awal.':A'.($awal + $jumlah - 1));
                $this->sheet->mergeCells($this->huruf($akhir).$awal.':'.$this->huruf($akhir).($awal + $jumlah - 1));
            }

            if ($barisHariIni === []) {
                $this->sheet->setCellValue($this->huruf($akhir).$awal, 0);
            } else {
                $barisAkhirHari = $awal + $jumlah - 1;
                $this->sheet->setCellValue($this->huruf($akhir).$awal, "=SUM({$kolomTotalTransaksi}{$awal}:{$kolomTotalTransaksi}{$barisAkhirHari})");
            }

            $this->sheet->getStyle('A'.$awal.':B'.($awal + $jumlah - 1))->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);
            $this->sheet->getStyle($this->huruf($akhir).$awal.':'.$this->huruf($akhir).($awal + $jumlah - 1))
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        return $baris;
    }

    /**
     * Nama rekening sumber dengan nomornya di baris kedua, ditulis lebih kecil
     * dan abu-abu seperti perlakuan pada kepala kolom penerima.
     */
    private function labelSumber(int $sumberId, array $isi): RichText|string
    {
        $nama = $this->data['namaSumber'][$sumberId] ?? '?';
        $nomor = $this->data['nomorSumber'][$sumberId] ?? null;

        // Penanda urutan hanya dipasang kalau sumbernya dipakai lebih sekali
        // pada hari yang sama.
        if ($isi['jumlahSlot'] > 1) {
            $nama .= ' #'.$isi['urut'];
        }

        if (! $nomor) {
            return $nama;
        }

        $teks = new RichText;
        $teks->createText($nama."\n");
        $teks->createTextRun($nomor)->getFont()->setSize(8)->getColor()->setARGB('FF6B7280');

        return $teks;
    }

    private function tulisTotal(int $baris): void
    {
        $akhir = $this->kolomTerakhir();
        $awalIsi = $this->barisAwalIsi;
        $barisSebelumTotal = max($awalIsi, $baris - 1);

        // Hanya sejajar kolom Tanggal; kolom Transaksi dibiarkan kosong.
        $this->sheet->setCellValue('A'.$baris, 'TOTAL PER ORANG');

        $kolom = 3;

        foreach ($this->data['recipients'] as $penerima) {
            $hurufKolom = $this->huruf($kolom);
            $this->sheet->setCellValue(
                $hurufKolom.$baris,
                "=SUM({$hurufKolom}{$awalIsi}:{$hurufKolom}{$barisSebelumTotal})"
            );
            $kolom++;
        }

        $hurufTotalTx = $this->huruf($kolom);
        $hurufTotalHarian = $this->huruf($kolom + 1);

        $this->sheet->setCellValue($hurufTotalTx.$baris, "=SUM({$hurufTotalTx}{$awalIsi}:{$hurufTotalTx}{$barisSebelumTotal})");
        $this->sheet->setCellValue($hurufTotalHarian.$baris, "=SUM({$hurufTotalHarian}{$awalIsi}:{$hurufTotalHarian}{$barisSebelumTotal})");

        $totalRange = 'A'.$baris.':'.$this->huruf($akhir).$baris;
        $this->warnai($totalRange, self::BIRU_MUDA);
        $this->sheet->getStyle($totalRange)->getFont()->setBold(true);

        // Seluruh badan tabel: angka rupiah, rata kanan, bergaris.
        $awalIsi = $this->barisAwalIsi;
        $isi = 'C'.$awalIsi.':'.$this->huruf($akhir).$baris;
        $this->sheet->getStyle($isi)->getNumberFormat()->setFormatCode(self::RUPIAH);
        $this->sheet->getStyle($isi)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->garis('A'.$awalIsi.':'.$this->huruf($akhir).$baris);

        // Dua kolom total dibedakan supaya mudah dibaca.
        $this->warnai($this->huruf($akhir).$awalIsi.':'.$this->huruf($akhir).($baris - 1), self::ABU);
        $this->sheet->getStyle($this->huruf($akhir - 1).$awalIsi.':'.$this->huruf($akhir).$baris)
            ->getFont()->setBold(true);
    }

    private function rapikanKolom(): void
    {
        $this->sheet->getColumnDimension('A')->setWidth(14);
        $this->sheet->getColumnDimension('B')->setWidth(28);

        $kolom = 3;

        foreach ($this->data['recipients'] as $penerima) {
            $this->sheet->getColumnDimension($this->huruf($kolom))->setWidth(18);
            $kolom++;
        }

        $this->sheet->getColumnDimension($this->huruf($kolom))->setWidth(20);
        $this->sheet->getColumnDimension($this->huruf($kolom + 1))->setWidth(20);
    }

    private function warnai(string $range, string $warna): void
    {
        $this->sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($warna);
    }

    private function garis(string $range): void
    {
        $this->sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFD1D5DB');
    }
}
