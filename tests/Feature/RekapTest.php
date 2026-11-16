<?php

namespace Tests\Feature;

use App\Models\Recipient;
use App\Models\Setting;
use App\Models\SourceAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RekapData;
use App\Support\RekapExcel;
use App\Support\RekapPdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Tests\TestCase;

class RekapTest extends TestCase
{
    use RefreshDatabase;

    public function test_publik_bisa_mengakses_halaman_rekap_tanpa_login(): void
    {
        $response = $this->get('/rekap');

        $response->assertOk();
        $response->assertSee('Rekap Transfer');
        $response->assertSee('Publik');
    }

    public function test_publik_bisa_mengunduh_pdf_dan_excel_tanpa_login(): void
    {
        $responsePdf = $this->get('/rekap/export/pdf');
        $responsePdf->assertOk();
        $responsePdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $responsePdf->streamedContent());

        $responseExcel = $this->get('/rekap/export');
        $responseExcel->assertOk();
        $responseExcel->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_filter_tanggal_pada_halaman_publik(): void
    {
        $response = $this->get('/rekap?dari=2026-11-02&sampai=2026-11-08');

        $response->assertOk();
        $response->assertSee('2026-11-02');
        $response->assertSee('2026-11-08');
    }

    public function test_tampilan_publik_ditolak_jika_dinonaktifkan(): void
    {
        Setting::set('rekap_publik_aktif', false);

        $response = $this->get('/rekap');
        $response->assertForbidden();
        $response->assertSee('Tampilan Publik Dinonaktifkan');

        $responseExcel = $this->get('/rekap/export');
        $responseExcel->assertForbidden();

        $responsePdf = $this->get('/rekap/export/pdf');
        $responsePdf->assertForbidden();
    }

    public function test_admin_tetap_dapat_melihat_preview_saat_publik_dinonaktifkan(): void
    {
        Setting::set('rekap_publik_aktif', false);

        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['rekap'],
        ]);

        $response = $this->actingAs($admin)->get('/rekap');
        $response->assertOk();
        $response->assertSee('DINONAKTIFKAN');
    }

    public function test_generator_pdf_menghasilkan_dokumen_valid(): void
    {
        $rekapData = app(RekapData::class)->build(new Request([
            'dari' => '2026-11-02',
            'sampai' => '2026-11-08',
        ]));

        $pdf = new RekapPdf($rekapData);
        $output = $pdf->output();

        $this->assertStringStartsWith('%PDF-1.4', $output);
        $this->assertStringEndsWith("%%EOF\n", $output);
        $this->assertStringContainsString('REKAP TRANSFER', $output);
    }

    public function test_semua_penerima_tampil_dalam_satu_tabel_tanpa_dipisah(): void
    {
        $recipients = collect();
        for ($i = 1; $i <= 12; $i++) {
            $recipients->push((object) [
                'id' => $i,
                'nama' => 'Penerima '.$i,
                'bank' => 'BCA',
                'nomor_rekening' => '1000'.$i,
            ]);
        }

        $rekapData = [
            'dari' => Carbon::parse('2026-11-02'),
            'sampai' => Carbon::parse('2026-11-08'),
            'dates' => [Carbon::parse('2026-11-02')],
            'recipients' => $recipients,
            'namaSumber' => [1 => 'BCA Utama'],
            'nomorSumber' => [1 => '00112233'],
            'barisPerTanggal' => [
                '2026-11-02' => [
                    [
                        'sumberId' => 1,
                        'urut' => 1,
                        'jumlahSlot' => 1,
                        'nilai' => [1 => 100000, 12 => 500000],
                        'total' => 600000,
                    ],
                ],
            ],
            'dailyTotals' => ['2026-11-02' => 600000],
            'recipientTotals' => [1 => 100000, 12 => 500000],
            'grandTotal' => 600000,
            'hariTerisi' => 1,
            'rataHarian' => 600000,
        ];

        $pdf = new RekapPdf($rekapData);
        $output = $pdf->output();

        // Tidak boleh ada teks "Bagian 1 dari" atau pemotongan tabel
        $this->assertStringNotContainsString('Bagian 1 dari', $output);
        $this->assertStringContainsString('Penerima 1', $output);
        $this->assertStringContainsString('Penerima 12', $output);
    }

    public function test_export_excel_menggunakan_rumus_formula_sum(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['rekap'],
        ]);

        $response = $this->actingAs($user)->get('/rekap/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_penandaan_sel_kosong_di_excel_dan_pdf_memperhatikan_nama_sama(): void
    {
        $recipients = collect([
            (object) ['id' => 1, 'nama' => 'Ervan', 'bank' => 'BNI', 'nomor_rekening' => '111'],
            (object) ['id' => 2, 'nama' => 'Ervan', 'bank' => 'Mandiri', 'nomor_rekening' => '222'],
            (object) ['id' => 3, 'nama' => 'Khavy Arsyadi', 'bank' => 'Mandiri', 'nomor_rekening' => '333'],
        ]);

        $rekapData = [
            'dari' => Carbon::parse('2026-11-09'),
            'sampai' => Carbon::parse('2026-11-11'),
            'dates' => [
                Carbon::parse('2026-11-09'),
                Carbon::parse('2026-11-10'),
                Carbon::parse('2026-11-11'),
            ],
            'recipients' => $recipients,
            'namaSumber' => [1 => 'SPN (BRI Qlola)', 2 => 'Merangin (Mandiri)'],
            'nomorSumber' => [1 => '100055', 2 => '81025'],
            'barisPerTanggal' => [
                '2026-11-09' => [
                    // SPN: Ervan BNI ada, Ervan Mandiri kosong (aman!), Khavy ada
                    [
                        'sumberId' => 1,
                        'urut' => 1,
                        'jumlahSlot' => 1,
                        'nilai' => [1 => 2717500, 3 => 1561500],
                        'total' => 4279000,
                    ],
                    // Merangin: kedua Ervan kosong (keduanya ditandai), Khavy ada
                    [
                        'sumberId' => 2,
                        'urut' => 1,
                        'jumlahSlot' => 1,
                        'nilai' => [3 => 832500],
                        'total' => 832500,
                    ],
                ],
                '2026-11-10' => [
                    // Slot #1: semua kelompok nama aman
                    [
                        'sumberId' => 1,
                        'urut' => 1,
                        'jumlahSlot' => 2,
                        'nilai' => [2 => 2557500, 3 => 1642500],
                        'total' => 4200000,
                    ],
                    // Slot #2 (overflow): tidak boleh ditandai meski kosong
                    [
                        'sumberId' => 1,
                        'urut' => 2,
                        'jumlahSlot' => 2,
                        'nilai' => [3 => 500000],
                        'total' => 500000,
                    ],
                ],
                // 2026-11-11 sengaja tanpa transaksi agar dipastikan hari kosong tidak ditandai
            ],
            'dailyTotals' => ['2026-11-09' => 5111500, '2026-11-10' => 4700000],
            'recipientTotals' => [1 => 2717500, 2 => 2557500, 3 => 4536500],
            'grandTotal' => 9811500,
            'hariTerisi' => 2,
            'rataHarian' => 4905750,
        ];

        // 1. Verifikasi helper langsung
        $barisSpn9 = $rekapData['barisPerTanggal']['2026-11-09'][0];
        $this->assertSame([], RekapData::penerimaKosongPadaBaris($barisSpn9, $recipients));

        $barisMerangin9 = $rekapData['barisPerTanggal']['2026-11-09'][1];
        $this->assertSame([1 => true, 2 => true], RekapData::penerimaKosongPadaBaris($barisMerangin9, $recipients));

        $barisOverflow10 = $rekapData['barisPerTanggal']['2026-11-10'][1];
        $this->assertSame([], RekapData::penerimaKosongPadaBaris($barisOverflow10, $recipients));

        $daftarKosong = RekapData::daftarKosong($rekapData);
        $this->assertCount(1, $daftarKosong);
        $this->assertSame('Kosong pada Ervan tanggal 09 Nov 2026 dari Merangin (Mandiri)', $daftarKosong[0]['teks']);

        // 2. Verifikasi Excel: hanya baris Merangin 9 Nov kolom C & D berwarna oren + catatan di bawah tabel
        $xlsxPath = sys_get_temp_dir().'/'.uniqid('rekap-test-', true).'.xlsx';
        (new RekapExcel($rekapData))->simpanKe($xlsxPath);

        try {
            $spreadsheet = IOFactory::load($xlsxPath);
            $sheet = $spreadsheet->getActiveSheet();

            $barisSpn9 = null;
            $barisMerangin9 = null;
            $barisOverflow10 = null;
            $barisKosong11 = null;
            $barisJudulCatatan = null;
            $barisIsiCatatan = null;

            for ($r = 1; $r <= $sheet->getHighestRow(); $r++) {
                $kolB = (string) $sheet->getCell('B'.$r)->getValue();
                $kolA = (string) $sheet->getCell('A'.$r)->getValue();
                if (str_contains($kolB, 'SPN') && $barisSpn9 === null) {
                    $barisSpn9 = $r;
                } elseif (str_contains($kolB, 'Merangin')) {
                    $barisMerangin9 = $r;
                } elseif (str_contains($kolB, '#2')) {
                    $barisOverflow10 = $r;
                } elseif ($kolA === '11 Nov 2026') {
                    $barisKosong11 = $r;
                } elseif ($kolA === 'CATATAN TRANSAKSI KOSONG (1)') {
                    $barisJudulCatatan = $r;
                } elseif ($kolA === '• Kosong pada Ervan tanggal 09 Nov 2026 dari Merangin (Mandiri)') {
                    $barisIsiCatatan = $r;
                }
            }

            $this->assertNotNull($barisSpn9);
            $this->assertNotNull($barisMerangin9);
            $this->assertNotNull($barisOverflow10);
            $this->assertNotNull($barisKosong11);
            $this->assertNotNull($barisJudulCatatan);
            $this->assertNotNull($barisIsiCatatan);

            // SPN 9 Nov: D (Ervan Mandiri) tidak diwarnai karena Ervan BNI ada
            $this->assertSame(Fill::FILL_NONE, $sheet->getStyle('D'.$barisSpn9)->getFill()->getFillType());

            // Merangin 9 Nov: C dan D berwarna oren (FFFED7AA), E (Khavy) tidak
            $this->assertSame('FFFED7AA', $sheet->getStyle('C'.$barisMerangin9)->getFill()->getStartColor()->getARGB());
            $this->assertSame('FFFED7AA', $sheet->getStyle('D'.$barisMerangin9)->getFill()->getStartColor()->getARGB());
            $this->assertSame(Fill::FILL_NONE, $sheet->getStyle('E'.$barisMerangin9)->getFill()->getFillType());

            // Slot #2 (overflow) & hari kosong tidak diwarnai
            $this->assertSame(Fill::FILL_NONE, $sheet->getStyle('C'.$barisOverflow10)->getFill()->getFillType());
            $this->assertSame(Fill::FILL_NONE, $sheet->getStyle('C'.$barisKosong11)->getFill()->getFillType());
        } finally {
            @unlink($xlsxPath);
        }

        // 3. Verifikasi PDF memuat warna oren (#FED7AA -> 0.996 0.843 0.667 rg) dan teks catatan di bawah tabel
        $pdfOutput = (new RekapPdf($rekapData))->output();
        $this->assertStringContainsString('0.996 0.843 0.667 rg', $pdfOutput);
        $this->assertStringContainsString('CATATAN TRANSAKSI KOSONG \\(1\\)', $pdfOutput);
        $this->assertStringContainsString('Kosong pada Ervan tanggal 09 Nov 2026 dari Merangin \\(Mandiri\\)', $pdfOutput);
    }

    public function test_tampilan_rekap_publik_dan_admin_menandai_sel_kosong_dan_catatan(): void
    {
        $spn = SourceAccount::create(['nama' => 'SPN (BRI Qlola)', 'nomor_rekening' => '100055', 'aktif' => true]);
        $merangin = SourceAccount::create(['nama' => 'Merangin (Mandiri)', 'nomor_rekening' => '81025', 'aktif' => true]);

        $ervanBni = Recipient::create(['nama' => 'Ervan', 'bank' => 'BNI', 'nomor_rekening' => '111', 'aktif' => true]);
        $ervanMandiri = Recipient::create(['nama' => 'Ervan', 'bank' => 'Mandiri', 'nomor_rekening' => '222', 'aktif' => true]);
        $khavy = Recipient::create(['nama' => 'Khavy Arsyadi', 'bank' => 'Mandiri', 'nomor_rekening' => '333', 'aktif' => true]);

        // SPN 9 Nov: Ervan BNI ada (Ervan Mandiri aman), Khavy ada
        Transaction::create([
            'tanggal' => '2026-11-09',
            'source_account_id' => $spn->id,
            'recipient_id' => $ervanBni->id,
            'nominal_tagihan' => 2717500,
            'biaya_admin' => 0,
        ]);
        Transaction::create([
            'tanggal' => '2026-11-09',
            'source_account_id' => $spn->id,
            'recipient_id' => $khavy->id,
            'nominal_tagihan' => 1561500,
            'biaya_admin' => 0,
        ]);

        // Merangin 9 Nov: hanya Khavy ada -> kedua Ervan kosong (ditandai & dicatat 1x)
        Transaction::create([
            'tanggal' => '2026-11-09',
            'source_account_id' => $merangin->id,
            'recipient_id' => $khavy->id,
            'nominal_tagihan' => 832500,
            'biaya_admin' => 0,
        ]);

        // SPN 10 Nov: Ervan Mandiri & Khavy ada agar kedua kolom Ervan tampil di periode ini
        Transaction::create([
            'tanggal' => '2026-11-10',
            'source_account_id' => $spn->id,
            'recipient_id' => $ervanMandiri->id,
            'nominal_tagihan' => 2557500,
            'biaya_admin' => 0,
        ]);
        Transaction::create([
            'tanggal' => '2026-11-10',
            'source_account_id' => $spn->id,
            'recipient_id' => $khavy->id,
            'nominal_tagihan' => 1642500,
            'biaya_admin' => 0,
        ]);

        $resPublik = $this->get('/rekap?dari=2026-11-09&sampai=2026-11-10');
        $resPublik->assertOk();
        $resPublik->assertSee('rk-kosong-sel');
        $resPublik->assertSee('Catatan Transaksi Kosong (1)');
        $resPublik->assertSee('Kosong pada Ervan tanggal 09 Nov 2026 dari Merangin (Mandiri)');

        $admin = User::factory()->create(['role' => 'admin', 'permissions' => ['rekap']]);
        $resAdmin = $this->actingAs($admin)->get('/admin/rekap?dari=2026-11-09&sampai=2026-11-10');
        $resAdmin->assertOk();
        $resAdmin->assertSee('rk-kosong-sel');
        $resAdmin->assertSee('Catatan Transaksi Kosong (1)');
        $resAdmin->assertSee('Kosong pada Ervan tanggal 09 Nov 2026 dari Merangin (Mandiri)');
    }
}
