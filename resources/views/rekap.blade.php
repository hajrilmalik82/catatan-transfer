<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Transfer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="mx-auto max-w-[1400px] p-4 sm:p-6">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Rekap Transfer</h1>
        <a href="/admin" class="text-sm text-blue-700 hover:underline">&larr; Kembali ke panel</a>
    </div>

    @php
        $pintas = ['minggu' => 'Per minggu', 'bulan' => '1 bulan terakhir', 'tahun' => '1 tahun terakhir'];
    @endphp

    <div class="mb-3 flex flex-wrap gap-2">
        @foreach ($pintas as $kunci => $label)
            <a href="{{ route('rekap', ['rentang' => $kunci]) }}"
               class="rounded-full px-4 py-1.5 text-sm font-medium {{ $rentang === $kunci ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($rentang === 'minggu' && $senin)
        @php
            $sebelum = $senin->copy()->subWeek();
            $sesudah = $senin->copy()->addWeek();
            $adaBerikutnya = $sesudah->lte($mingguIni);
        @endphp

        <div class="mb-3 flex flex-wrap items-center gap-2 rounded-lg bg-white p-3 shadow-sm">
            <a href="{{ route('rekap', ['rentang' => 'minggu', 'minggu' => $sebelum->toDateString()]) }}"
               class="rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50">
                &larr; Minggu sebelumnya
            </a>

            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="rentang" value="minggu">
                <select name="minggu" onchange="this.form.submit()"
                        class="rounded border-gray-300 text-sm shadow-sm">
                    @foreach ($pilihanMinggu as $pilihan)
                        <option value="{{ $pilihan->toDateString() }}"
                                @selected($pilihan->toDateString() === $senin->toDateString())>
                            {{ $pilihan->translatedFormat('d M') }} &ndash;
                            {{ $pilihan->copy()->addDays(6)->translatedFormat('d M Y') }}
                            @if ($pilihan->toDateString() === $mingguIni->toDateString()) (minggu ini) @endif
                        </option>
                    @endforeach
                </select>
            </form>

            @if ($adaBerikutnya)
                <a href="{{ route('rekap', ['rentang' => 'minggu', 'minggu' => $sesudah->toDateString()]) }}"
                   class="rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50">
                    Minggu berikutnya &rarr;
                </a>
            @else
                <span class="rounded border border-gray-200 px-3 py-1.5 text-sm text-gray-300">
                    Minggu berikutnya &rarr;
                </span>
            @endif
        </div>
    @endif

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-gray-600">Dari tanggal</label>
            <input type="date" name="dari" value="{{ $dari->toDateString() }}"
                   class="mt-1 rounded border-gray-300 text-sm shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600">Sampai tanggal</label>
            <input type="date" name="sampai" value="{{ $sampai->toDateString() }}"
                   class="mt-1 rounded border-gray-300 text-sm shadow-sm">
        </div>
        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Tampilkan
        </button>
        <a href="{{ route('rekap.export', request()->only('dari', 'sampai', 'rentang', 'minggu')) }}"
           class="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
            Export CSV
        </a>
    </form>

    <p class="mb-4 text-sm text-gray-600">
        Menampilkan {{ $dari->translatedFormat('l, d M Y') }} sampai {{ $sampai->translatedFormat('l, d M Y') }}.
        @if ($ringkas)
            Rentangnya panjang, jadi hanya tanggal yang ada transaksinya yang ditampilkan.
        @endif
    </p>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php
            $kartu = [
                ['Total Penerimaan', 'Rp'.number_format($grandTotal, 0, ',', '.')],
                ['Rata-rata Harian', 'Rp'.number_format($rataHarian, 0, ',', '.')],
                ['Rekening Aktif', $recipients->count()],
                ['Hari Pencatatan', $hariTerisi],
            ];
        @endphp
        @foreach ($kartu as [$judul, $nilai])
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ $judul }}</div>
                <div class="mt-1 text-lg font-semibold">{{ $nilai }}</div>
            </div>
        @endforeach
    </div>

    @if ($recipients->isEmpty())
        <div class="rounded-lg bg-white p-8 text-center text-sm text-gray-500 shadow-sm">
            Belum ada transaksi pada rentang tanggal ini.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                <tr>
                    <th class="sticky left-0 z-10 bg-gray-50 px-3 py-3 text-left font-semibold">Tanggal</th>
                    <th class="px-3 py-3 text-left font-semibold">Transaksi</th>
                    @foreach ($recipients as $recipient)
                        <th class="px-3 py-3 text-right font-semibold whitespace-nowrap">
                            {{ $recipient->nama }}
                            <span class="block text-xs font-normal text-gray-500">
                                {{ $recipient->bank }} {{ $recipient->nomor_rekening }}
                            </span>
                        </th>
                    @endforeach
                    <th class="px-3 py-3 text-right font-semibold">Total per Transaksi</th>
                    <th class="px-3 py-3 text-right font-semibold">Total Harian</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach ($dates as $date)
                    @php
                        $key = $date->toDateString();
                        $sumberHariIni = $sumberPerTanggal[$key] ?? [];
                        $jumlahBaris = max(1, count($sumberHariIni));
                    @endphp

                    @if ($sumberHariIni === [])
                        <tr class="text-gray-400">
                            <td class="sticky left-0 z-10 bg-white px-3 py-2 whitespace-nowrap">
                                {{ $date->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-3 py-2">-</td>
                            @foreach ($recipients as $recipient)
                                <td class="px-3 py-2 text-right">-</td>
                            @endforeach
                            <td class="px-3 py-2 text-right">-</td>
                            <td class="px-3 py-2 text-right">-</td>
                        </tr>
                    @else
                        @foreach ($sumberHariIni as $i => $sumberId)
                            <tr>
                                @if ($i === 0)
                                    <td rowspan="{{ $jumlahBaris }}"
                                        class="sticky left-0 z-10 bg-white px-3 py-2 align-top whitespace-nowrap">
                                        {{ $date->translatedFormat('d M Y') }}
                                    </td>
                                @endif
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ $namaSumber[$sumberId] ?? '?' }}
                                </td>
                                @foreach ($recipients as $recipient)
                                    @php $nilai = $matrix[$key][$sumberId][$recipient->id] ?? null; @endphp
                                    <td class="px-3 py-2 text-right whitespace-nowrap {{ $nilai ? '' : 'text-gray-300' }}">
                                        {{ $nilai ? number_format($nilai, 0, ',', '.') : '-' }}
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right font-medium whitespace-nowrap">
                                    {{ number_format($totalPerSumber[$key][$sumberId] ?? 0, 0, ',', '.') }}
                                </td>
                                @if ($i === 0)
                                    <td rowspan="{{ $jumlahBaris }}"
                                        class="bg-gray-50 px-3 py-2 text-right align-top font-semibold whitespace-nowrap">
                                        {{ number_format($dailyTotals[$key] ?? 0, 0, ',', '.') }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="2" class="sticky left-0 z-10 bg-gray-50 px-3 py-3">TOTAL PER ORANG</td>
                    @foreach ($recipients as $recipient)
                        <td class="px-3 py-3 text-right whitespace-nowrap">
                            {{ number_format($recipientTotals[$recipient->id] ?? 0, 0, ',', '.') }}
                        </td>
                    @endforeach
                    <td class="px-3 py-3 text-right whitespace-nowrap">
                        {{ number_format($grandTotal, 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-3 text-right whitespace-nowrap">
                        {{ number_format($grandTotal, 0, ',', '.') }}
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    @endif

</div>
</body>
</html>
