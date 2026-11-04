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

        .rk-gulung { --rk-skala: 1; overflow-x: auto; background: var(--rk-bg); border: 1px solid var(--rk-garis); border-radius: .5rem; }
        .rk-tabel { width: 100%; min-width: 100%; border-collapse: collapse; font-size: calc(.875rem * var(--rk-skala)); color: var(--rk-teks); }
        .rk-tabel th, .rk-tabel td { padding: calc(.5rem * var(--rk-skala)) calc(.75rem * var(--rk-skala)); border-bottom: 1px solid var(--rk-garis); }
        .rk-tabel thead th { background: var(--rk-kepala); text-align: left; font-weight: 600; white-space: nowrap; }
        .rk-tabel thead th small { display: block; font-size: calc(.75rem * var(--rk-skala)); font-weight: 400; color: var(--rk-redup); }
        .rk-kanan { text-align: right; white-space: nowrap; }
        .rk-beku { position: sticky; left: 0; z-index: 1; background: var(--rk-bg); }
        .rk-tabel thead .rk-beku { background: var(--rk-kepala); }
        .rk-nol { color: var(--rk-kosong); }
        .rk-tabel tfoot td { background: var(--rk-total); font-weight: 700; }
        .rk-harian { background: var(--rk-kepala); font-weight: 600; vertical-align: top; }
        .rk-kecil { display: block; font-size: calc(.75rem * var(--rk-skala)); color: var(--rk-redup); }

        .rk-zoom { display: flex; align-items: center; justify-content: flex-end; gap: .375rem; margin-bottom: .5rem; font-size: .75rem; color: var(--rk-redup); }
        .rk-zoom button { border: 1px solid var(--rk-garis); background: var(--rk-bg); color: var(--rk-teks);
                          border-radius: .375rem; padding: .2rem .55rem; font-size: .75rem; font-weight: 600; cursor: pointer; line-height: 1.25; }

        @media (max-width: 640px) {
            .rk-bar { padding: .5rem .625rem; gap: .5rem; }
            .rk-kartu { gap: .5rem; margin-bottom: 1rem; }
            .rk-kartu > div { padding: .625rem .75rem; }
            .rk-kartu dt { font-size: .65rem; }
            .rk-kartu dd { font-size: .95rem; }
            .rk-tabel { font-size: calc(.6875rem * var(--rk-skala)); line-height: 1.25; }
            .rk-tabel th, .rk-tabel td { padding: calc(.35rem * var(--rk-skala)) calc(.275rem * var(--rk-skala)); }
            .rk-tabel thead th { white-space: normal; vertical-align: bottom; }
            .rk-tabel thead th small, .rk-kecil { font-size: calc(.5625rem * var(--rk-skala)); line-height: 1.15; word-break: break-all; }
            .rk-beku { max-width: calc(3.25rem * var(--rk-skala)); white-space: normal; }
            .rk-tabel tbody td:nth-child(2) { max-width: calc(5rem * var(--rk-skala)); }
        }
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
            <a class="rk-aksi rk-aksi--hijau" id="rk-export"
               href="{{ route('rekap.export', request()->only('dari', 'sampai', 'minggu')) }}">Export Excel</a>
            <span id="rk-export-pesan" style="font-size:.75rem; color:var(--rk-redup)"></span>
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
            <div class="rk-zoom">
                <span>Ukuran tabel:</span>
                <button type="button" id="rk-zoom-kurang" title="Perkecil">&minus;</button>
                <button type="button" id="rk-zoom-reset" title="Kembalikan ke ukuran semula">100%</button>
                <button type="button" id="rk-zoom-tambah" title="Perbesar">+</button>
            </div>
            <div class="rk-gulung" id="rk-gulung">
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
                                $barisHariIni = $barisPerTanggal[$kunci] ?? [];
                                $jumlahBaris = max(1, count($barisHariIni));
                            @endphp

                            @if ($barisHariIni === [])
                                <tr class="rk-nol">
                                    <td class="rk-beku">{{ $tanggal->translatedFormat('d M Y') }}</td>
                                    <td>-</td>
                                    @foreach ($recipients as $penerima)<td class="rk-kanan">-</td>@endforeach
                                    <td class="rk-kanan">-</td>
                                    <td class="rk-kanan">-</td>
                                </tr>
                            @else
                                @foreach ($barisHariIni as $i => $baris)
                                    <tr>
                                        @if ($i === 0)
                                            <td class="rk-beku" rowspan="{{ $jumlahBaris }}" style="vertical-align:top">
                                                {{ $tanggal->translatedFormat('d M Y') }}
                                            </td>
                                        @endif
                                        <td>
                                            {{ $namaSumber[$baris['sumberId']] ?? '?' }}
                                            @if ($baris['jumlahSlot'] > 1)
                                                <strong>#{{ $baris['urut'] }}</strong>
                                            @endif
                                            @if ($nomorSumber[$baris['sumberId']] ?? null)
                                                <span class="rk-kecil">{{ $nomorSumber[$baris['sumberId']] }}</span>
                                            @endif
                                        </td>
                                        @foreach ($recipients as $penerima)
                                            @php $nilai = $baris['nilai'][$penerima->id] ?? null; @endphp
                                            <td class="rk-kanan {{ $nilai ? '' : 'rk-nol' }}">
                                                {{ $nilai ? number_format($nilai, 0, ',', '.') : '-' }}
                                            </td>
                                        @endforeach
                                        <td class="rk-kanan" style="font-weight:600">
                                            {{ number_format($baris['total'], 0, ',', '.') }}
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
        const gulung = document.getElementById('rk-gulung');
        const btnKurang = document.getElementById('rk-zoom-kurang');
        const btnReset = document.getElementById('rk-zoom-reset');
        const btnTambah = document.getElementById('rk-zoom-tambah');

        if (gulung && btnKurang && btnReset && btnTambah) {
            let skala = 1;
            const MIN = 0.6;
            const MAX = 1.5;

            const terapkan = (nilai) => {
                skala = Math.min(MAX, Math.max(MIN, Math.round(nilai * 100) / 100));
                gulung.style.setProperty('--rk-skala', skala);
                btnReset.textContent = Math.round(skala * 100) + '%';
            };

            btnKurang.addEventListener('click', () => terapkan(skala - 0.1));
            btnTambah.addEventListener('click', () => terapkan(skala + 0.1));
            btnReset.addEventListener('click', () => terapkan(1));

            let jarakAwal = null;
            let skalaAwal = 1;

            const hitungJarak = (t) => Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY);

            gulung.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    jarakAwal = hitungJarak(e.touches);
                    skalaAwal = skala;
                }
            }, { passive: true });

            gulung.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2 && jarakAwal) {
                    e.preventDefault();
                    terapkan(skalaAwal * (hitungJarak(e.touches) / jarakAwal));
                }
            }, { passive: false });

            gulung.addEventListener('touchend', () => {
                jarakAwal = null;
            });
        }

        const tombol = document.getElementById('rk-export');
        const pesan = document.getElementById('rk-export-pesan');
        if (! tombol) return;

        const ua = navigator.userAgent;
        const iOS = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
        const layarPenuh = window.matchMedia('(display-mode: standalone)').matches
            || navigator.standalone === true;

        // Hanya iOS yang bermasalah dengan unduhan biasa. Browser lain
        // dibiarkan memakai tautan seperti biasa.
        if (! iOS && ! layarPenuh) return;

        const namaDari = (header) => {
            const cocok = (header || '').match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
            return cocok ? decodeURIComponent(cocok[1]) : 'rekap-transfer.xlsx';
        };

        const kabari = (teks) => { if (pesan) pesan.textContent = teks; };

        tombol.addEventListener('click', async (e) => {
            e.preventDefault();

            const semula = tombol.textContent;
            tombol.textContent = 'Menyiapkan…';
            tombol.style.pointerEvents = 'none';
            kabari('');

            try {
                const res = await fetch(tombol.href, { credentials: 'same-origin' });
                if (! res.ok) throw new Error('server membalas ' + res.status);

                const blob = await res.blob();
                const nama = namaDari(res.headers.get('Content-Disposition'));
                const berkas = new File([blob], nama, { type: blob.type });

                if (navigator.canShare?.({ files: [berkas] })) {
                    try {
                        await navigator.share({ files: [berkas], title: nama });
                        kabari('Berkas dikirim ke lembar Bagikan.');
                        return;
                    } catch (err) {
                        if (err.name === 'AbortError') { kabari(''); return; }
                        kabari('Lembar Bagikan ditolak, mencoba cara unduh biasa.');
                    }
                }

                const alamat = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = alamat;
                a.download = nama;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(alamat), 30000);
                kabari('Berkas diunduh: ' + nama);
            } catch (err) {
                kabari('Gagal: ' + err.message);
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
