<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Transfer</title>
</head>
<body>
    @php
        $totalKolom = 4 + $recipients->count();
    @endphp
    <table border="1">
        <!-- 1. Judul & Rentang Tanggal -->
        <tr>
            <th colspan="{{ $totalKolom }}" style="font-size: 16px; font-weight: bold; text-align: left;">
                REKAP TRANSFER
            </th>
        </tr>
        <tr>
            <td colspan="{{ $totalKolom }}" style="font-style: italic; color: #6b7280; text-align: left;">
                {{ $dari->translatedFormat('l, d F Y') }} s/d {{ $sampai->translatedFormat('l, d F Y') }}
            </td>
        </tr>
        <tr>
            <td colspan="{{ $totalKolom }}"></td>
        </tr>

        <!-- 2. Ringkasan Kartu -->
        <tr>
            <th colspan="2" style="background-color: #f3f4f6; text-align: left; font-size: 10px;">TOTAL PENERIMAAN</th>
            <th colspan="2" style="background-color: #f3f4f6; text-align: left; font-size: 10px;">RATA-RATA HARIAN</th>
            <th colspan="2" style="background-color: #f3f4f6; text-align: left; font-size: 10px;">REKENING AKTIF</th>
            <th colspan="2" style="background-color: #f3f4f6; text-align: left; font-size: 10px;">HARI PENCATATAN</th>
            @if ($totalKolom > 8)
                <th colspan="{{ $totalKolom - 8 }}"></th>
            @endif
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f3f4f6; font-weight: bold; font-size: 13px;">Rp{{ number_format($grandTotal, 0, ',', '.') }}</td>
            <td colspan="2" style="background-color: #f3f4f6; font-weight: bold; font-size: 13px;">Rp{{ number_format($rataHarian, 0, ',', '.') }}</td>
            <td colspan="2" style="background-color: #f3f4f6; font-weight: bold; font-size: 13px;">{{ $recipients->count() }}</td>
            <td colspan="2" style="background-color: #f3f4f6; font-weight: bold; font-size: 13px;">{{ $hariTerisi }}</td>
            @if ($totalKolom > 8)
                <td colspan="{{ $totalKolom - 8 }}"></td>
            @endif
        </tr>
        <tr>
            <td colspan="{{ $totalKolom }}"></td>
        </tr>

        <!-- 3. Kepala Tabel -->
        <thead>
            <tr style="background-color: #1e3a8a; color: #ffffff;">
                <th rowspan="2" style="text-align: center; vertical-align: middle;">Tanggal</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle;">Transaksi</th>
                @foreach ($recipients as $penerima)
                    <th style="text-align: center;">{{ $penerima->nama }}</th>
                @endforeach
                <th rowspan="2" style="text-align: center; vertical-align: middle;">Total per Transaksi</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle;">Total Harian</th>
            </tr>
            <tr style="background-color: #1e3a8a; color: #ffffff;">
                @foreach ($recipients as $penerima)
                    <th style="text-align: center; font-size: 10px; font-weight: normal;">{{ $penerima->bank }} {{ $penerima->nomor_rekening }}</th>
                @endforeach
            </tr>
        </thead>

        <!-- 4. Isi Tabel -->
        <tbody>
            @foreach ($dates as $tanggal)
                @php
                    $kunci = $tanggal->toDateString();
                    $barisHariIni = $barisPerTanggal[$kunci] ?? [];
                    $jumlahBaris = max(1, count($barisHariIni));
                @endphp

                @if ($barisHariIni === [])
                    <tr>
                        <td>{{ $tanggal->translatedFormat('d M Y') }}</td>
                        <td style="text-align: center;">-</td>
                        @foreach ($recipients as $penerima)
                            <td style="text-align: center; color: #9ca3af;">-</td>
                        @endforeach
                        <td style="text-align: center; color: #9ca3af;">-</td>
                        <td style="text-align: center; color: #9ca3af;">-</td>
                    </tr>
                @else
                    @foreach ($barisHariIni as $i => $baris)
                        <tr>
                            @if ($i === 0)
                                <td rowspan="{{ $jumlahBaris }}" style="vertical-align: middle;">{{ $tanggal->translatedFormat('d M Y') }}</td>
                            @endif
                            <td>
                                {{ $namaSumber[$baris['sumberId']] ?? '?' }}
                                @if ($baris['jumlahSlot'] > 1)
                                    #{{ $baris['urut'] }}
                                @endif
                                @if ($nomorSumber[$baris['sumberId']] ?? null)
                                    <br><small style="color: #6b7280;">{{ $nomorSumber[$baris['sumberId']] }}</small>
                                @endif
                            </td>
                            @foreach ($recipients as $penerima)
                                @php $nilai = $baris['nilai'][$penerima->id] ?? null; @endphp
                                <td style="text-align: right;">
                                    {{ $nilai !== null ? 'Rp'.number_format($nilai, 0, ',', '.') : '-' }}
                                </td>
                            @endforeach
                            <td style="text-align: right; font-weight: bold;">
                                Rp{{ number_format($baris['total'], 0, ',', '.') }}
                            </td>
                            @if ($i === 0)
                                <td rowspan="{{ $jumlahBaris }}" style="text-align: right; vertical-align: middle; font-weight: bold; background-color: #f9fafb;">
                                    Rp{{ number_format($dailyTotals[$kunci] ?? 0, 0, ',', '.') }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>

        <!-- 5. Total Footer -->
        <tfoot>
            <tr style="background-color: #dbeafe; font-weight: bold;">
                <td>TOTAL PER ORANG</td>
                <td></td>
                @foreach ($recipients as $penerima)
                    <td style="text-align: right;">
                        Rp{{ number_format($recipientTotals[$penerima->id] ?? 0, 0, ',', '.') }}
                    </td>
                @endforeach
                <td style="text-align: right;">
                    Rp{{ number_format($grandTotal, 0, ',', '.') }}
                </td>
                <td style="text-align: right;">
                    Rp{{ number_format($grandTotal, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
