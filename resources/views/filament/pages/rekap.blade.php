<x-filament-panels::page>
    <style>
        .rk { --rk-bg: #fff; --rk-garis: #e5e7eb; --rk-teks: #111827; --rk-redup: #6b7280;
              --rk-kepala: #f9fafb; --rk-total: #dbeafe; --rk-kosong: #d1d5db; }
        .dark .rk { --rk-bg: #1f2937; --rk-garis: #374151; --rk-teks: #f3f4f6; --rk-redup: #9ca3af;
                    --rk-kepala: #111827; --rk-total: #1e3a8a; --rk-kosong: #4b5563; }

        .rk-bar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem;
                  background: var(--rk-bg); border: 1px solid var(--rk-garis);
                  border-radius: .5rem; padding: .75rem 1rem; margin-bottom: .75rem; }
        .rk-chip { border-radius: 9999px; padding: .375rem 1rem; font-size: .875rem; font-weight: 500;
                   border: 1px solid var(--rk-garis); color: var(--rk-teks); text-decoration: none; }
        .rk-chip--aktif { background: #2563eb; border-color: #2563eb; color: #fff; }
        .rk-tombol { border-radius: .375rem; border: 1px solid var(--rk-garis); padding: .375rem .75rem;
                     font-size: .875rem; color: var(--rk-teks); text-decoration: none; }
        .rk-tombol--mati { color: var(--rk-kosong); }
        .rk-aksi { border-radius: .375rem; padding: .5rem 1rem; font-size: .875rem; font-weight: 500;
                   color: #fff; border: 0; cursor: pointer; text-decoration: none; display: inline-block; }
        .rk-aksi--biru { background: #2563eb; } .rk-aksi--hijau { background: #059669; }
        .rk-label { display: block; font-size: .75rem; font-weight: 500; color: var(--rk-redup); }
        .rk-input { margin-top: .25rem; border-radius: .375rem; border: 1px solid var(--rk-garis);
                    background: var(--rk-bg); color: var(--rk-teks); padding: .375rem .5rem; font-size: .875rem; }
        .rk-ket { font-size: .875rem; color: var(--rk-redup); margin-bottom: 1rem; }

        .rk-kartu { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        @media (min-width: 1024px) { .rk-kartu { grid-template-columns: repeat(4, minmax(0,1fr)); } }
        .rk-kartu > div { background: var(--rk-bg); border: 1px solid var(--rk-garis);
                          border-radius: .5rem; padding: 1rem; }
        .rk-kartu dt { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--rk-redup); }
        .rk-kartu dd { margin: .25rem 0 0; font-size: 1.125rem; font-weight: 600; color: var(--rk-teks); }

        .rk-gulung { overflow-x: auto; background: var(--rk-bg); border: 1px solid var(--rk-garis); border-radius: .5rem; }
        .rk-tabel { width: 100%; min-width: 100%; border-collapse: collapse; font-size: .875rem; color: var(--rk-teks); }
        .rk-tabel th, .rk-tabel td { padding: .5rem .75rem; border-bottom: 1px solid var(--rk-garis); }
        .rk-tabel thead th { background: var(--rk-kepala); text-align: left; font-weight: 600; white-space: nowrap; }
        .rk-tabel thead th small { display: block; font-size: .75rem; font-weight: 400; color: var(--rk-redup); }
        .rk-kanan { text-align: right; white-space: nowrap; }
        .rk-beku { position: sticky; left: 0; z-index: 1; background: var(--rk-bg); }
        .rk-tabel thead .rk-beku { background: var(--rk-kepala); }
        .rk-nol { color: var(--rk-kosong); }
        .rk-tabel tfoot td { background: var(--rk-total); font-weight: 700; }
        .rk-harian { background: var(--rk-kepala); font-weight: 600; vertical-align: top; }
        .rk-kecil { display: block; font-size: .75rem; color: var(--rk-redup); }
    </style>

    <div class="rk">
            @php
                $sebelum = $senin->copy()->subWeek();
                $sesudah = $senin->copy()->addWeek();
            @endphp
            <div class="rk-bar">
                <a class="rk-tombol" href="{{ \App\Filament\Pages\Rekap::getUrl(['minggu' => $sebelum->toDateString()]) }}">&larr; Minggu sebelumnya</a>

                <form method="GET" style="display:flex; gap:.5rem; align-items:center">
                    <select name="minggu" class="rk-input" onchange="this.form.submit()">
                        @foreach ($pilihanMinggu as $pilihan)
                            <option value="{{ $pilihan->toDateString() }}" @selected($pilihan->toDateString() === $senin->toDateString())>
                                {{ $pilihan->translatedFormat('d M') }} &ndash; {{ $pilihan->copy()->addDays(6)->translatedFormat('d M Y') }}
                                @if ($pilihan->toDateString() === $mingguIni->toDateString()) (minggu ini) @endif
                            </option>
                        @endforeach
                    </select>
                </form>

                @if ($sesudah->lte($mingguIni))
                    <a class="rk-tombol" href="{{ \App\Filament\Pages\Rekap::getUrl(['minggu' => $sesudah->toDateString()]) }}">Minggu berikutnya &rarr;</a>
                @else
                    <span class="rk-tombol rk-tombol--mati">Minggu berikutnya &rarr;</span>
                @endif
            </div>

        <form method="GET" class="rk-bar">
            <div>
                <label class="rk-label" for="dari">Dari tanggal</label>
                <input class="rk-input" id="dari" type="date" name="dari" value="{{ $dari->toDateString() }}">
            </div>
            <div>
                <label class="rk-label" for="sampai">Sampai tanggal</label>
                <input class="rk-input" id="sampai" type="date" name="sampai" value="{{ $sampai->toDateString() }}">
            </div>
            <button type="submit" class="rk-aksi rk-aksi--biru">Tampilkan</button>
            <a class="rk-aksi rk-aksi--hijau" id="rk-export" target="_blank" rel="noopener"
               href="{{ route('rekap.export', request()->only('dari', 'sampai', 'minggu')) }}">Export Excel</a>
        </form>

        <p class="rk-ket">
            Menampilkan {{ $dari->translatedFormat('l, d M Y') }} sampai {{ $sampai->translatedFormat('l, d M Y') }}.
            @if ($ringkas) Rentangnya panjang, jadi hanya tanggal yang ada transaksinya yang ditampilkan. @endif
            @if ($manual)
                <a href="{{ \App\Filament\Pages\Rekap::getUrl() }}" style="color:#2563eb">Kembali ke tampilan mingguan</a>
            @endif
        </p>

        <dl class="rk-kartu">
            @foreach ([
                ['Total Penerimaan', 'Rp'.number_format($grandTotal, 0, ',', '.')],
                ['Rata-rata Harian', 'Rp'.number_format($rataHarian, 0, ',', '.')],
                ['Rekening Aktif', $recipients->count()],
                ['Hari Pencatatan', $hariTerisi],
            ] as [$judul, $nilai])
                <div><dt>{{ $judul }}</dt><dd>{{ $nilai }}</dd></div>
            @endforeach
        </dl>

        @if ($recipients->isEmpty())
            <div class="rk-gulung" style="padding:2rem; text-align:center; color:var(--rk-redup)">
                Belum ada transaksi pada rentang tanggal ini.
            </div>
        @else
            <div class="rk-gulung">
                <table class="rk-tabel">
                    <thead>
                        <tr>
                            <th class="rk-beku">Tanggal</th>
                            <th>Transaksi</th>
                            @foreach ($recipients as $penerima)
                                <th class="rk-kanan">{{ $penerima->nama }}<small>{{ $penerima->bank }} {{ $penerima->nomor_rekening }}</small></th>
                            @endforeach
                            <th class="rk-kanan">Total per Transaksi</th>
                            <th class="rk-kanan">Total Harian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dates as $tanggal)
                            @php
                                $kunci = $tanggal->toDateString();
                                $sumberHariIni = $sumberPerTanggal[$kunci] ?? [];
                                $jumlahBaris = max(1, count($sumberHariIni));
                            @endphp

                            @if ($sumberHariIni === [])
                                <tr class="rk-nol">
                                    <td class="rk-beku">{{ $tanggal->translatedFormat('d M Y') }}</td>
                                    <td>-</td>
                                    @foreach ($recipients as $penerima)<td class="rk-kanan">-</td>@endforeach
                                    <td class="rk-kanan">-</td>
                                    <td class="rk-kanan">-</td>
                                </tr>
                            @else
                                @foreach ($sumberHariIni as $i => $sumberId)
                                    <tr>
                                        @if ($i === 0)
                                            <td class="rk-beku" rowspan="{{ $jumlahBaris }}" style="vertical-align:top">
                                                {{ $tanggal->translatedFormat('d M Y') }}
                                            </td>
                                        @endif
                                        <td>
                                            {{ $namaSumber[$sumberId] ?? '?' }}
                                            @if ($nomorSumber[$sumberId] ?? null)
                                                <span class="rk-kecil">{{ $nomorSumber[$sumberId] }}</span>
                                            @endif
                                        </td>
                                        @foreach ($recipients as $penerima)
                                            @php $nilai = $matrix[$kunci][$sumberId][$penerima->id] ?? null; @endphp
                                            <td class="rk-kanan {{ $nilai ? '' : 'rk-nol' }}">
                                                {{ $nilai ? number_format($nilai, 0, ',', '.') : '-' }}
                                            </td>
                                        @endforeach
                                        <td class="rk-kanan" style="font-weight:600">
                                            {{ number_format($totalPerSumber[$kunci][$sumberId] ?? 0, 0, ',', '.') }}
                                        </td>
                                        @if ($i === 0)
                                            <td class="rk-kanan rk-harian" rowspan="{{ $jumlahBaris }}">
                                                {{ number_format($dailyTotals[$kunci] ?? 0, 0, ',', '.') }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="rk-beku">TOTAL PER ORANG</td>
                            <td></td>
                            @foreach ($recipients as $penerima)
                                <td class="rk-kanan">{{ number_format($recipientTotals[$penerima->id] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="rk-kanan">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                            <td class="rk-kanan">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <script>
    (() => {
        const tombol = document.getElementById('rk-export');
        if (! tombol) return;

        // Di browser biasa, unduhan lewat tautan sudah jalan sebagaimana mestinya.
        const layarPenuh = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
        if (! layarPenuh) return;

        // Mode aplikasi layar penuh di iOS tidak punya pengelola unduhan, jadi
        // berkasnya diambil sendiri lalu diserahkan ke lembar Bagikan.
        tombol.removeAttribute('target');

        const namaDari = (header) => {
            const cocok = (header || '').match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
            return cocok ? decodeURIComponent(cocok[1]) : 'rekap-transfer.xlsx';
        };

        tombol.addEventListener('click', async (e) => {
            e.preventDefault();

            const semula = tombol.textContent;
            tombol.textContent = 'Menyiapkan…';
            tombol.style.pointerEvents = 'none';

            try {
                const res = await fetch(tombol.href, { credentials: 'same-origin' });
                if (! res.ok) throw new Error('server membalas ' + res.status);

                const blob = await res.blob();
                const nama = namaDari(res.headers.get('Content-Disposition'));
                const berkas = new File([blob], nama, { type: blob.type });

                if (navigator.canShare?.({ files: [berkas] })) {
                    try {
                        await navigator.share({ files: [berkas], title: nama });
                        return;
                    } catch (err) {
                        // Dibatalkan pengguna, tidak perlu dianggap gagal.
                        if (err.name === 'AbortError') return;
                    }
                }

                // Jalan cadangan: unduh lewat alamat blob.
                const alamat = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = alamat;
                a.download = nama;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(alamat), 30000);
            } catch (err) {
                alert('Gagal menyiapkan berkas: ' + err.message
                    + '\n\nSebagai gantinya, buka halaman ini lewat Safari lalu unduh dari sana.');
            } finally {
                tombol.textContent = semula;
                tombol.style.pointerEvents = '';
            }
        });
    })();
    </script>
</x-filament-panels::page>
