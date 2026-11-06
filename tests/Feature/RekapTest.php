<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RekapData;
use App\Support\RekapPdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RekapTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_tidak_bisa_mengakses_export_pdf(): void
    {
        $response = $this->get('/rekap/export/pdf');

        $response->assertRedirect('/login');
    }

    public function test_user_tanpa_izin_rekap_ditolak(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'permissions' => [],
        ]);

        $response = $this->actingAs($user)->get('/rekap/export/pdf');

        $response->assertForbidden();
    }

    public function test_user_dengan_izin_bisa_mengunduh_pdf(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['rekap'],
        ]);

        $response = $this->actingAs($user)->get('/rekap/export/pdf');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $response->streamedContent());
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
}
