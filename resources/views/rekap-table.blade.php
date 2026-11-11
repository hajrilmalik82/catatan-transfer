<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Transfer - Spreadsheet Sync</title>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Transaksi</th>
                @foreach ($recipients as $penerima)
                    <th>{{ $penerima->nama }} ({{ $penerima->bank }} {{ $penerima->nomor_rekening }})</th>
                @endforeach
                <th>Total per Transaksi</th>
                <th>Total Harian</th>
            </tr>
        </thead>
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
                        <td>-</td>
                        @foreach ($recipients as $penerima)
                            <td>0</td>
                        @endforeach
                        <td>0</td>
                        <td>0</td>
                    </tr>
                @else
                    @foreach ($barisHariIni as $i => $baris)
                        <tr>
                            <td>{{ $tanggal->translatedFormat('d M Y') }}</td>
                            <td>
                                {{ $namaSumber[$baris['sumberId']] ?? '?' }}
                                @if ($baris['jumlahSlot'] > 1)
                                    #{{ $baris['urut'] }}
                                @endif
                                @if ($nomorSumber[$baris['sumberId']] ?? null)
                                    ({{ $nomorSumber[$baris['sumberId']] }})
                                @endif
                            </td>
                            @foreach ($recipients as $penerima)
                                @php $nilai = $baris['nilai'][$penerima->id] ?? 0; @endphp
                                <td>{{ $nilai }}</td>
                            @endforeach
                            <td>{{ $baris['total'] }}</td>
                            <td>{{ $i === 0 ? ($dailyTotals[$kunci] ?? 0) : 0 }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td>-</td>
                @foreach ($recipients as $penerima)
                    <td>{{ $recipientTotals[$penerima->id] ?? 0 }}</td>
                @endforeach
                <td>{{ $grandTotal }}</td>
                <td>{{ $grandTotal }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
