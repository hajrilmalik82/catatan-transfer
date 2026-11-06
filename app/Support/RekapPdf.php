<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menyusun rekap menjadi berkas PDF siap cetak dan arsip dalam 1 tabel utuh.
 * Lebar halaman menyesuaikan secara dinamis (minimal A4 Landscape) agar seluruh
 * penerima tampil bersamaan tanpa dipisah-pisah.
 */
class RekapPdf
{
    private const A4_W = 841.89; // Lebar standar A4 Landscape (point)

    private const A4_H = 595.28; // Tinggi standar A4 Landscape (point)

    private const MARGIN_L = 28.0;

    private const MARGIN_R = 28.0;

    private const MARGIN_T = 28.0;

    private const MARGIN_B = 28.0;

    // Palet warna selaras dengan tampilan rekap dan rekap Excel
    private const C_NAVY = [0.118, 0.227, 0.541];        // #1E3A8A (kepala tabel & aksen)

    private const C_LIGHT_BLUE = [0.859, 0.918, 0.996];  // #DBEAFE (total footer)

    private const C_DARK_BLUE = [0.145, 0.388, 0.922];   // #2563EB

    private const C_GRAY_BG = [0.953, 0.961, 0.969];     // #F3F4F6 (kartu ringkasan)

    private const C_GRAY_BORDER = [0.820, 0.835, 0.855]; // #D1D5DB (garis pembatas)

    private const C_GRAY_TEXT = [0.420, 0.447, 0.502];   // #6B7280 (label redup)

    private const C_DARK_TEXT = [0.067, 0.094, 0.153];   // #111827 (teks utama)

    private const C_WHITE = [1.0, 1.0, 1.0];

    private const C_ZEBRA = [0.976, 0.980, 0.984];       // #F9FAFB (belang baris)

    private float $pageW = self::A4_W;

    private array $pages = [];

    private string $currentStream = '';

    private int $pageNumber = 0;

    /**
     * Metrik lebar karakter font Helvetica standar (unit 1/1000 ukuran font).
     */
    private static array $widths = [
        ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, '\'' => 191,
        '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
        '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556,
        '8' => 556, '9' => 556, ':' => 278, ';' => 278, '<' => 584, '=' => 584, '>' => 584, '?' => 556,
        '@' => 1015, 'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778,
        'H' => 722, 'I' => 278, 'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778,
        'P' => 667, 'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944,
        'X' => 667, 'Y' => 667, 'Z' => 611, '[' => 278, '\\' => 278, ']' => 278, '^' => 469, '_' => 556,
        '`' => 333, 'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556,
        'h' => 556, 'i' => 222, 'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556,
        'p' => 556, 'q' => 556, 'r' => 333, 's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722,
        'x' => 500, 'y' => 500, 'z' => 500, '{' => 334, '|' => 260, '}' => 334, '~' => 584,
    ];

    /** @param array<string, mixed> $data */
    public function __construct(private array $data) {}

    public function simpanKe(string $path): void
    {
        file_put_contents($path, $this->output());
    }

    public function output(): string
    {
        $this->pages = [];
        $this->pageNumber = 0;

        $this->render();

        return $this->buildPdfString();
    }

    private function render(): void
    {
        /** @var Collection $recipients */
        $recipients = $this->data['recipients'] ?? collect();
        $totalRecipients = count($recipients);

        $wTanggal = 64.0;
        $wTransaksi = 98.0;
        $wTotalTx = 78.0;
        $wTotalHarian = 78.0;
        $fixedW = $wTanggal + $wTransaksi + $wTotalTx + $wTotalHarian;

        $standardUsableW = self::A4_W - self::MARGIN_L - self::MARGIN_R;
        $minRecipW = 75.0;

        if ($totalRecipients === 0) {
            $this->pageW = self::A4_W;
            $this->addPage();
            $y = $this->renderHeader(true);
            $this->renderEmptyTable($y);

            return;
        }

        // Tampilkan 1 tabel utuh tanpa dipisah.
        // Jika muat di A4 standar, bagikan sisa lebarnya secara proporsional.
        // Jika penerima banyak, lebarkan halaman agar semua kolom tetap masuk bersama.
        if ($fixedW + ($totalRecipients * $minRecipW) <= $standardUsableW) {
            $this->pageW = self::A4_W;
            $wRecip = ($standardUsableW - $fixedW) / $totalRecipients;
        } else {
            $wRecip = $minRecipW;
            $usableW = $fixedW + ($totalRecipients * $wRecip);
            $this->pageW = $usableW + self::MARGIN_L + self::MARGIN_R;
        }

        $this->renderTableForRecipients($recipients, $wTanggal, $wTransaksi, $wRecip, $wTotalTx, $wTotalHarian);
    }

    private function renderTableForRecipients(Collection $recipients, float $wTanggal, float $wTransaksi, float $wRecip, float $wTotalTx, float $wTotalHarian): void
    {
        $this->addPage();
        $y = $this->renderHeader(true);

        $usableW = $this->pageW - self::MARGIN_L - self::MARGIN_R;
        $hHeader = 32.0;
        $this->renderTableHeader($y, $recipients, $wTanggal, $wTransaksi, $wRecip, $wTotalTx, $wTotalHarian, $hHeader);
        $y += $hHeader;

        $dates = $this->data['dates'] ?? [];
        $barisPerTanggal = $this->data['barisPerTanggal'] ?? [];
        $dailyTotals = $this->data['dailyTotals'] ?? [];

        $rowH = 18.0;
        $maxY = self::A4_H - self::MARGIN_B - 26.0;

        foreach ($dates as $tgl) {
            $kunci = $tgl->toDateString();
            $barisHariIni = $barisPerTanggal[$kunci] ?? [];
            $jumlahBaris = max(1, count($barisHariIni));
            $blockH = $jumlahBaris * $rowH;

            // Jika satu blok tanggal tidak muat di sisa tinggi halaman, pindah halaman baru (vertikal)
            if ($y + $blockH > $maxY) {
                $this->addPage();
                $y = $this->renderHeader(false);
                $this->renderTableHeader($y, $recipients, $wTanggal, $wTransaksi, $wRecip, $wTotalTx, $wTotalHarian, $hHeader);
                $y += $hHeader;
            }

            $tglTeks = $tgl->translatedFormat('d M Y');
            $harianTeks = isset($dailyTotals[$kunci]) ? 'Rp'.number_format($dailyTotals[$kunci], 0, ',', '.') : '-';

            if (empty($barisHariIni)) {
                $this->drawRect(self::MARGIN_L, $y, $usableW, $rowH, self::C_WHITE, self::C_GRAY_BORDER);
                $this->drawText($tglTeks, self::MARGIN_L + 6, $y + 12, 8, 'F1', self::C_GRAY_TEXT);
                $this->drawText('-', self::MARGIN_L + $wTanggal + 6, $y + 12, 8, 'F1', self::C_GRAY_TEXT);

                $curX = self::MARGIN_L + $wTanggal + $wTransaksi;
                foreach ($recipients as $rec) {
                    $this->drawText('-', $curX + $wRecip / 2, $y + 12, 8, 'F1', self::C_GRAY_TEXT, 'C');
                    $this->drawLine($curX, $y, $curX, $y + $rowH, self::C_GRAY_BORDER);
                    $curX += $wRecip;
                }
                $this->drawLine($curX, $y, $curX, $y + $rowH, self::C_GRAY_BORDER);
                $this->drawText('-', $curX + $wTotalTx / 2, $y + 12, 8, 'F1', self::C_GRAY_TEXT, 'C');

                $curX += $wTotalTx;
                $this->drawLine($curX, $y, $curX, $y + $rowH, self::C_GRAY_BORDER);
                $this->drawText('-', $curX + $wTotalHarian / 2, $y + 12, 8, 'F1', self::C_GRAY_TEXT, 'C');

                $y += $rowH;
            } else {
                $awalY = $y;

                foreach ($barisHariIni as $idx => $baris) {
                    $rowY = $y;
                    $bg = ($idx % 2 === 1) ? self::C_ZEBRA : self::C_WHITE;
                    $this->drawRect(self::MARGIN_L, $rowY, $usableW, $rowH, $bg);

                    $namaSumber = $this->data['namaSumber'][$baris['sumberId']] ?? '?';
                    if (($baris['jumlahSlot'] ?? 1) > 1) {
                        $namaSumber .= ' #'.$baris['urut'];
                    }
                    if (strlen($namaSumber) > 18) {
                        $namaSumber = substr($namaSumber, 0, 16).'..';
                    }
                    $this->drawText($namaSumber, self::MARGIN_L + $wTanggal + 5, $rowY + 12, 8, 'F2', self::C_DARK_TEXT);

                    $curX = self::MARGIN_L + $wTanggal + $wTransaksi;
                    foreach ($recipients as $rec) {
                        $nilai = $baris['nilai'][$rec->id] ?? null;
                        if ($nilai !== null) {
                            $teksNilai = 'Rp'.number_format($nilai, 0, ',', '.');
                            $fontSize = 8.0;
                            if ($this->measureText($teksNilai, $fontSize) > $wRecip - 8) {
                                $fontSize = 7.0;
                            }
                            $this->drawText($teksNilai, $curX + $wRecip - 5, $rowY + 12, $fontSize, 'F1', self::C_DARK_TEXT, 'R');
                        } else {
                            $this->drawText('-', $curX + ($wRecip / 2), $rowY + 12, 8, 'F1', self::C_GRAY_BORDER, 'C');
                        }
                        $this->drawLine($curX, $rowY, $curX, $rowY + $rowH, self::C_GRAY_BORDER);
                        $curX += $wRecip;
                    }

                    $txTeks = 'Rp'.number_format($baris['total'] ?? 0, 0, ',', '.');
                    $this->drawLine($curX, $rowY, $curX, $rowY + $rowH, self::C_GRAY_BORDER);
                    $this->drawText($txTeks, $curX + $wTotalTx - 5, $rowY + 12, 8, 'F2', self::C_DARK_TEXT, 'R');

                    $this->drawLine(self::MARGIN_L, $rowY + $rowH, self::MARGIN_L + $usableW, $rowY + $rowH, self::C_GRAY_BORDER);
                    $y += $rowH;
                }

                // Kolom Tanggal (span)
                $this->drawRect(self::MARGIN_L, $awalY, $wTanggal, $blockH, null, self::C_GRAY_BORDER);
                $midY = $awalY + ($blockH / 2) + 3;
                $this->drawText($tglTeks, self::MARGIN_L + 6, $midY, 8, 'F2', self::C_DARK_TEXT);

                // Kolom Total Harian (span)
                $totalHarianX = self::MARGIN_L + $usableW - $wTotalHarian;
                $this->drawRect($totalHarianX, $awalY, $wTotalHarian, $blockH, self::C_ZEBRA, self::C_GRAY_BORDER);
                $this->drawText($harianTeks, $totalHarianX + $wTotalHarian - 5, $midY, 8.5, 'F2', self::C_DARK_BLUE, 'R');

                $this->drawRect(self::MARGIN_L, $awalY, $usableW, $blockH, null, self::C_GRAY_BORDER);
            }
        }

        // Baris Total Per Orang
        if ($y + $rowH + 5 > $maxY) {
            $this->addPage();
            $y = $this->renderHeader(false);
            $this->renderTableHeader($y, $recipients, $wTanggal, $wTransaksi, $wRecip, $wTotalTx, $wTotalHarian, $hHeader);
            $y += $hHeader;
        }

        $totalH = 20.0;
        $this->drawRect(self::MARGIN_L, $y, $usableW, $totalH, self::C_LIGHT_BLUE, self::C_GRAY_BORDER);
        $this->drawText('TOTAL PER ORANG', self::MARGIN_L + 6, $y + 13, 8.5, 'F2', self::C_DARK_TEXT);

        $curX = self::MARGIN_L + $wTanggal + $wTransaksi;
        $recipientTotals = $this->data['recipientTotals'] ?? [];
        foreach ($recipients as $rec) {
            $totalOrang = $recipientTotals[$rec->id] ?? 0;
            $teksTotal = 'Rp'.number_format($totalOrang, 0, ',', '.');
            $this->drawLine($curX, $y, $curX, $y + $totalH, self::C_GRAY_BORDER);
            $this->drawText($teksTotal, $curX + $wRecip - 5, $y + 13, 8.5, 'F2', self::C_NAVY, 'R');
            $curX += $wRecip;
        }

        $grandTotal = $this->data['grandTotal'] ?? 0;
        $teksGrand = 'Rp'.number_format($grandTotal, 0, ',', '.');

        $this->drawLine($curX, $y, $curX, $y + $totalH, self::C_GRAY_BORDER);
        $this->drawText($teksGrand, $curX + $wTotalTx - 5, $y + 13, 8.5, 'F2', self::C_NAVY, 'R');

        $curX += $wTotalTx;
        $this->drawLine($curX, $y, $curX, $y + $totalH, self::C_GRAY_BORDER);
        $this->drawText($teksGrand, $curX + $wTotalHarian - 5, $y + 13, 8.5, 'F2', self::C_NAVY, 'R');
    }

    private function renderTableHeader(float $y, Collection $recipients, float $wTgl, float $wTx, float $wRecip, float $wTotalTx, float $wTotalHarian, float $h): void
    {
        $usableW = $this->pageW - self::MARGIN_L - self::MARGIN_R;

        $this->drawRect(self::MARGIN_L, $y, $usableW, $h, self::C_NAVY, self::C_NAVY);

        $midY1 = $y + 13;
        $midY2 = $y + 24;

        // Tanggal
        $this->drawText('Tanggal', self::MARGIN_L + ($wTgl / 2), $y + 18, 8.5, 'F2', self::C_WHITE, 'C');
        $this->drawLine(self::MARGIN_L + $wTgl, $y, self::MARGIN_L + $wTgl, $y + $h, [0.3, 0.4, 0.7]);

        // Transaksi
        $txX = self::MARGIN_L + $wTgl;
        $this->drawText('Transaksi', $txX + ($wTx / 2), $y + 18, 8.5, 'F2', self::C_WHITE, 'C');
        $this->drawLine($txX + $wTx, $y, $txX + $wTx, $y + $h, [0.3, 0.4, 0.7]);

        // Penerima
        $curX = $txX + $wTx;
        foreach ($recipients as $rec) {
            $nama = $rec->nama;
            if (strlen($nama) > 17) {
                $nama = substr($nama, 0, 15).'..';
            }
            $rekening = trim(($rec->bank ?? '').' '.($rec->nomor_rekening ?? ''));
            if (strlen($rekening) > 19) {
                $rekening = substr($rekening, 0, 17).'..';
            }

            $this->drawText($nama, $curX + ($wRecip / 2), $midY1, 8, 'F2', self::C_WHITE, 'C');
            $this->drawText($rekening, $curX + ($wRecip / 2), $midY2, 7, 'F1', [0.85, 0.9, 1.0], 'C');
            $this->drawLine($curX + $wRecip, $y, $curX + $wRecip, $y + $h, [0.3, 0.4, 0.7]);
            $curX += $wRecip;
        }

        // Total Transaksi
        $this->drawText('Total Transaksi', $curX + ($wTotalTx / 2), $y + 18, 8, 'F2', self::C_WHITE, 'C');
        $this->drawLine($curX + $wTotalTx, $y, $curX + $wTotalTx, $y + $h, [0.3, 0.4, 0.7]);

        // Total Harian
        $curX += $wTotalTx;
        $this->drawText('Total Harian', $curX + ($wTotalHarian / 2), $y + 18, 8, 'F2', self::C_WHITE, 'C');
    }

    private function renderHeader(bool $lengkap): float
    {
        $y = self::MARGIN_T;

        if (! $lengkap) {
            $periode = sprintf(
                '%s s/d %s',
                $this->data['dari']->translatedFormat('d M Y'),
                $this->data['sampai']->translatedFormat('d M Y')
            );
            $this->drawText('REKAP TRANSFER (Lanjutan) - Periode: '.$periode, self::MARGIN_L, $y + 12, 10, 'F2', self::C_NAVY);

            return $y + 20.0;
        }

        $this->drawText('REKAP TRANSFER', self::MARGIN_L, $y + 16, 16, 'F2', self::C_NAVY);

        $periode = sprintf(
            '%s s/d %s',
            $this->data['dari']->translatedFormat('l, d F Y'),
            $this->data['sampai']->translatedFormat('l, d F Y')
        );
        $this->drawText($periode, self::MARGIN_L, $y + 30, 9, 'F3', self::C_GRAY_TEXT);

        // 4 Kartu Ringkasan
        $kartu = [
            ['TOTAL PENERIMAAN', 'Rp'.number_format($this->data['grandTotal'] ?? 0, 0, ',', '.')],
            ['RATA-RATA HARIAN', 'Rp'.number_format($this->data['rataHarian'] ?? 0, 0, ',', '.')],
            ['REKENING AKTIF', (string) count($this->data['recipients'] ?? [])],
            ['HARI PENCATATAN', (string) ($this->data['hariTerisi'] ?? 0)],
        ];

        $cardW = 105.0;
        $cardH = 32.0;
        $gap = 8.0;
        $startX = $this->pageW - self::MARGIN_R - (count($kartu) * ($cardW + $gap)) + $gap;

        foreach ($kartu as $i => [$label, $val]) {
            $cx = $startX + ($i * ($cardW + $gap));
            $this->drawRect($cx, $y, $cardW, $cardH, self::C_GRAY_BG, self::C_GRAY_BORDER);
            $this->drawText($label, $cx + 7, $y + 12, 6.5, 'F2', self::C_GRAY_TEXT);
            $this->drawText($val, $cx + 7, $y + 25, 9.5, 'F2', self::C_DARK_TEXT);
        }

        return $y + 42.0;
    }

    private function renderEmptyTable(float $y): void
    {
        $usableW = $this->pageW - self::MARGIN_L - self::MARGIN_R;
        $this->drawRect(self::MARGIN_L, $y + 10, $usableW, 60, self::C_GRAY_BG, self::C_GRAY_BORDER);
        $this->drawText(
            'Belum ada transaksi pada rentang tanggal ini.',
            $this->pageW / 2,
            $y + 44,
            10,
            'F1',
            self::C_GRAY_TEXT,
            'C'
        );
    }

    private function addPage(): void
    {
        if ($this->pageNumber > 0) {
            $this->finishCurrentPage();
        }

        $this->pageNumber++;
        $this->currentStream = '';
    }

    private function finishCurrentPage(): void
    {
        $footerY = self::A4_H - self::MARGIN_B + 10;
        $usableW = $this->pageW - self::MARGIN_L - self::MARGIN_R;
        $this->drawLine(self::MARGIN_L, $footerY, self::MARGIN_L + $usableW, $footerY, self::C_GRAY_BORDER);

        $info = 'Catatan Transfer - Dicetak pada '.Carbon::now()->translatedFormat('d M Y, H:i');
        $this->drawText($info, self::MARGIN_L, $footerY + 11, 7.5, 'F1', self::C_GRAY_TEXT);

        $pageInfo = "Halaman {$this->pageNumber} dari {{TOTAL_PAGES}}";
        $this->drawText($pageInfo, $this->pageW - self::MARGIN_R, $footerY + 11, 7.5, 'F1', self::C_GRAY_TEXT, 'R');

        $this->pages[] = $this->currentStream;
    }

    private function drawRect(float $x, float $y, float $w, float $h, ?array $fill = null, ?array $stroke = null): void
    {
        $pdfY = self::A4_H - $y - $h;
        $x = sprintf('%.2F', $x);
        $pdfY = sprintf('%.2F', $pdfY);
        $w = sprintf('%.2F', $w);
        $h = sprintf('%.2F', $h);

        $cmd = '';
        if ($fill !== null) {
            $cmd .= sprintf('%.3F %.3F %.3F rg ', $fill[0], $fill[1], $fill[2]);
        }
        if ($stroke !== null) {
            $cmd .= sprintf('%.3F %.3F %.3F RG 0.5 w ', $stroke[0], $stroke[1], $stroke[2]);
        }

        $cmd .= "{$x} {$pdfY} {$w} {$h} re ";

        if ($fill !== null && $stroke !== null) {
            $cmd .= "B\n";
        } elseif ($fill !== null) {
            $cmd .= "f\n";
        } elseif ($stroke !== null) {
            $cmd .= "s\n";
        }

        $this->currentStream .= $cmd;
    }

    private function drawLine(float $x1, float $y1, float $x2, float $y2, array $color): void
    {
        $py1 = sprintf('%.2F', self::A4_H - $y1);
        $py2 = sprintf('%.2F', self::A4_H - $y2);
        $px1 = sprintf('%.2F', $x1);
        $px2 = sprintf('%.2F', $x2);

        $this->currentStream .= sprintf(
            "%.3F %.3F %.3F RG 0.5 w %s %s m %s %s l s\n",
            $color[0], $color[1], $color[2], $px1, $py1, $px2, $py2
        );
    }

    private function drawText(string $text, float $x, float $y, float $size, string $font = 'F1', array $color = self::C_DARK_TEXT, string $align = 'L'): void
    {
        $text = $this->cleanText($text);
        $tw = $this->measureText($text, $size);

        if ($align === 'R') {
            $x -= $tw;
        } elseif ($align === 'C') {
            $x -= ($tw / 2);
        }

        $pdfY = sprintf('%.2F', self::A4_H - $y);
        $x = sprintf('%.2F', $x);
        $escaped = $this->escape($text);

        $this->currentStream .= sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg 1 0 0 1 %s %s Tm (%s) Tj ET\n",
            $font, $size, $color[0], $color[1], $color[2], $x, $pdfY, $escaped
        );
    }

    private function cleanText(string $text): string
    {
        $text = str_replace(['–', '—'], '-', $text);
        $text = str_replace(['“', '”', '"'], '"', $text);
        $text = str_replace(['‘', '’', '\''], '\'', $text);
        $text = str_replace('·', '-', $text);

        return $text;
    }

    private function escape(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);

        return iconv('UTF-8', 'Windows-1252//TRANSLIT', $text) ?: $text;
    }

    private function measureText(string $text, float $size): float
    {
        $w = 0;
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $c = $text[$i];
            $w += self::$widths[$c] ?? 556;
        }

        return ($w / 1000.0) * $size;
    }

    private function buildPdfString(): string
    {
        if ($this->pageNumber > 0 && ! isset($this->pages[$this->pageNumber - 1])) {
            $this->finishCurrentPage();
        }

        $totalCount = count($this->pages);
        $objects = [];
        $objId = 1;

        $catalogId = $objId++;
        $pagesId = $objId++;

        $f1Id = $objId++;
        $objects[$f1Id] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $f2Id = $objId++;
        $objects[$f2Id] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $f3Id = $objId++;
        $objects[$f3Id] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>';

        $pageObjIds = [];
        foreach ($this->pages as $streamContent) {
            $streamContent = str_replace('{{TOTAL_PAGES}}', (string) $totalCount, $streamContent);
            $contentId = $objId++;
            $len = strlen($streamContent);
            $objects[$contentId] = "<< /Length {$len} >>\nstream\n{$streamContent}\nendstream";

            $pageId = $objId++;
            $pageObjIds[] = $pageId;
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R /F3 %d 0 R >> >> /Contents %d 0 R >>',
                $pagesId, $this->pageW, self::A4_H, $f1Id, $f2Id, $f3Id, $contentId
            );
        }

        $kids = implode(' ', array_map(fn ($id) => "{$id} 0 R", $pageObjIds));
        $objects[$pagesId] = "<< /Type /Pages /Kids [{$kids}] /Count {$totalCount} >>";
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        $out = "%PDF-1.4\n";
        $offsets = [];

        ksort($objects);
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($out);
            $out .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($out);
        $maxId = max(array_keys($objects));
        $out .= "xref\n";
        $out .= '0 '.($maxId + 1)."\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $off = $offsets[$i] ?? 0;
            $out .= sprintf("%010d 00000 n \n", $off);
        }

        $out .= "trailer\n";
        $out .= '<< /Size '.($maxId + 1)." /Root {$catalogId} 0 R >>\n";
        $out .= "startxref\n";
        $out .= "{$xrefOffset}\n";
        $out .= "%%EOF\n";

        return $out;
    }
}
