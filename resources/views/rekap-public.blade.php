<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Transfer</title>
    <style>
        :root {
            --rk-bg: #ffffff;
            --rk-latar: #f8fafc;
            --rk-garis: #e2e8f0;
            --rk-teks: #0f172a;
            --rk-redup: #64748b;
            --rk-kepala: #f1f5f9;
            --rk-total: #dbeafe;
            --rk-kosong: #cbd5e1;
            --rk-biru: #2563eb;
            --rk-hijau: #059669;
            --rk-merah: #dc2626;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --rk-bg: #1e293b;
                --rk-latar: #0f172a;
                --rk-garis: #334155;
                --rk-teks: #f8fafc;
                --rk-redup: #94a3b8;
                --rk-kepala: #0f172a;
                --rk-total: #1e3a8a;
                --rk-kosong: #475569;
            }
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--rk-latar);
            color: var(--rk-teks);
            line-height: 1.5;
        }

        .rk-wadah {
            max-width: 100%;
            margin: 0 auto;
        }

        .rk-kop {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: var(--rk-bg);
            border: 1px solid var(--rk-garis);
            border-radius: .75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }
        .rk-kop-kiri {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .rk-judul {
            font-size: 1.375rem;
            font-weight: 700;
            margin: 0;
            color: var(--rk-teks);
        }
        .rk-badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .625rem;
            border-radius: 9999px;
            font-size: .75rem;
            font-weight: 600;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .rk-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: .75rem;
            background: var(--rk-bg);
            border: 1px solid var(--rk-garis);
            border-radius: .5rem;
            padding: .75rem 1rem;
            margin-bottom: .75rem;
        }
        .rk-tombol {
            border-radius: .375rem;
            border: 1px solid var(--rk-garis);
            padding: .375rem .75rem;
            font-size: .875rem;
            color: var(--rk-teks);
            text-decoration: none;
            background: var(--rk-bg);
            display: inline-block;
        }
        .rk-tombol:hover {
            border-color: var(--rk-redup);
        }
        .rk-tombol--mati {
            color: var(--rk-kosong);
            cursor: not-allowed;
        }
        .rk-tombol--mati:hover {
            border-color: var(--rk-garis);
        }
        .rk-aksi {
            border-radius: .375rem;
            padding: .5rem 1rem;
            font-size: .875rem;
            font-weight: 500;
            color: #fff;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: opacity .15s ease;
        }
        .rk-aksi:hover { opacity: .9; }
        .rk-aksi--biru { background: var(--rk-biru); }
        .rk-aksi--hijau { background: var(--rk-hijau); }
        .rk-aksi--merah { background: var(--rk-merah); }
        .rk-label {
            display: block;
            font-size: .75rem;
            font-weight: 500;
            color: var(--rk-redup);
        }
        .rk-input {
            margin-top: .25rem;
            border-radius: .375rem;
            border: 1px solid var(--rk-garis);
            background: var(--rk-bg);
            color: var(--rk-teks);
            padding: .375rem .5rem;
            font-size: .875rem;
        }
        .rk-ket {
            font-size: .875rem;
            color: var(--rk-redup);
            margin: .5rem 0 1rem;
        }

        .rk-kartu {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        @media (min-width: 1024px) {
            .rk-kartu { grid-template-columns: repeat(4, minmax(0,1fr)); }
        }
        .rk-kartu > div {
            background: var(--rk-bg);
            border: 1px solid var(--rk-garis);
            border-radius: .5rem;
            padding: 1rem;
        }
        .rk-kartu dt {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--rk-redup);
        }
        .rk-kartu dd {
            margin: .25rem 0 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--rk-teks);
        }

        .rk-gulung {
            --rk-skala: 1;
            overflow-x: auto;
            background: var(--rk-bg);
            border: 1px solid var(--rk-garis);
            border-radius: .5rem;
            -webkit-overflow-scrolling: touch;
        }
        .rk-tabel {
            width: 100%;
            min-width: 100%;
            border-collapse: collapse;
            font-size: calc(.875rem * var(--rk-skala));
            color: var(--rk-teks);
        }
        .rk-tabel th, .rk-tabel td {
            padding: calc(.5rem * var(--rk-skala)) calc(.75rem * var(--rk-skala));
            border: 1px solid var(--rk-garis);
        }
        .rk-tabel thead th {
            background: var(--rk-kepala);
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        .rk-tabel thead th small {
            display: block;
            font-size: calc(.75rem * var(--rk-skala));
            font-weight: 400;
            color: var(--rk-redup);
        }
        .rk-kanan { text-align: right; white-space: nowrap; }
        .rk-beku {
            position: sticky;
            left: 0;
            z-index: 1;
            background: var(--rk-bg);
            box-shadow: inset -1px 0 0 var(--rk-garis);
        }
        .rk-tabel thead .rk-beku { background: var(--rk-kepala); }
        .rk-nol { color: var(--rk-kosong); }
        .rk-tabel tfoot td { background: var(--rk-total); font-weight: 700; }
        .rk-harian { background: var(--rk-kepala); font-weight: 600; vertical-align: top; }
        .rk-kecil {
            display: block;
            font-size: calc(.75rem * var(--rk-skala));
            color: var(--rk-redup);
        }

        .rk-zoom {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .375rem;
            margin-bottom: .5rem;
            font-size: .75rem;
            color: var(--rk-redup);
        }
        .rk-zoom button {
            border: 1px solid var(--rk-garis);
            background: var(--rk-bg);
            color: var(--rk-teks);
            border-radius: .375rem;
            padding: .2rem .55rem;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
            line-height: 1.25;
        }

        .rk-ada {
            cursor: pointer;
            text-decoration: underline dotted var(--rk-redup);
            text-underline-offset: 3px;
        }
        .rk-ada--aktif {
            background: var(--rk-total);
            border-radius: .25rem;
        }
        .rk-info {
            position: fixed;
            z-index: 50;
            display: none;
            max-width: 17rem;
            padding: .5rem .75rem;
            border-radius: .5rem;
            background: #111827;
            color: #f9fafb;
            font-size: .75rem;
            line-height: 1.4;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,.25);
            pointer-events: none;
        }
        .rk-info-judul { font-weight: 600; margin-bottom: .15rem; }
        .rk-info-remark { color: #fbbf24; font-weight: 600; word-break: break-word; }
        .rk-info-ref { font-size: .6875rem; opacity: .75; margin-top: .15rem; }

        @media (max-width: 640px) {
            body { padding: .5rem; }
            .rk-kop { padding: .75rem; }
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
</head>
<body>
    <div class="rk-wadah">
        <header class="rk-kop">
            <div class="rk-kop-kiri">
                <h1 class="rk-judul">Rekap Transfer</h1>
                <span class="rk-badge">Publik &bull; Hanya Lihat</span>
            </div>
        </header>

        @php
            $sebelum = $senin->copy()->subWeek();
            $sesudah = $senin->copy()->addWeek();
        @endphp
        <div class="rk-bar">
            <a class="rk-tombol" href="{{ route('rekap.public', ['minggu' => $sebelum->toDateString()]) }}">&larr; Minggu sebelumnya</a>

            <form method="GET" action="{{ route('rekap.public') }}" style="display:flex; gap:.5rem; align-items:center">
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
                <a class="rk-tombol" href="{{ route('rekap.public', ['minggu' => $sesudah->toDateString()]) }}">Minggu berikutnya &rarr;</a>
            @else
                <span class="rk-tombol rk-tombol--mati">Minggu berikutnya &rarr;</span>
            @endif
        </div>

        <form method="GET" action="{{ route('rekap.public') }}" class="rk-bar">
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
            <a class="rk-aksi rk-aksi--merah" id="rk-export-pdf"
               href="{{ route('rekap.export.pdf', request()->only('dari', 'sampai', 'minggu')) }}">Export PDF</a>
            <span id="rk-export-pesan" style="font-size:.75rem; color:var(--rk-redup)"></span>
        </form>

        <p class="rk-ket">
            Menampilkan {{ $dari->translatedFormat('l, d M Y') }} sampai {{ $sampai->translatedFormat('l, d M Y') }}.
            @if ($ringkas) Rentangnya panjang, jadi hanya tanggal yang ada transaksinya yang ditampilkan. @endif
            @if ($manual)
                <a href="{{ route('rekap.public') }}" style="color:var(--rk-biru)">Kembali ke tampilan mingguan</a>
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
                                            @php
                                                $nilai = $baris['nilai'][$penerima->id] ?? null;
                                                $rincian = $baris['info'][$penerima->id] ?? null;
                                            @endphp
                                            <td class="rk-kanan {{ $nilai ? 'rk-ada' : 'rk-nol' }}"
                                                @if ($nilai)
                                                    data-penerima="{{ $penerima->nama }}"
                                                    data-tanggal="{{ $tanggal->translatedFormat('d M Y') }}"
                                                    data-remark="{{ $rincian['remark'] ?? '' }}"
                                                    data-ref="{{ $rincian['ref'] ?? '' }}"
                                                @endif>
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
            <div id="rk-info" class="rk-info"></div>
        @endif
    </div>

    <script>
    (() => {
        const kotakInfo = document.getElementById('rk-info');
        let selAktif = null;

        const tutupInfo = () => {
            if (! kotakInfo) return;
            kotakInfo.style.display = 'none';
            if (selAktif) {
                selAktif.classList.remove('rk-ada--aktif');
                selAktif = null;
            }
        };

        const tampilkanInfo = (sel) => {
            if (! kotakInfo) return;
            if (selAktif === sel && kotakInfo.style.display === 'block') {
                tutupInfo();
                return;
            }

            if (selAktif) selAktif.classList.remove('rk-ada--aktif');
            selAktif = sel;
            sel.classList.add('rk-ada--aktif');

            const penerima = sel.dataset.penerima || '';
            const tanggal = sel.dataset.tanggal || '';
            const remark = sel.dataset.remark || '(tanpa remark)';
            const ref = sel.dataset.ref || '';

            kotakInfo.innerHTML = '';

            const elJudul = document.createElement('div');
            elJudul.className = 'rk-info-judul';
            elJudul.textContent = `${penerima} • ${tanggal}`;
            kotakInfo.appendChild(elJudul);

            const elRemark = document.createElement('div');
            elRemark.className = 'rk-info-remark';
            elRemark.textContent = remark;
            kotakInfo.appendChild(elRemark);

            if (ref) {
                const elRef = document.createElement('div');
                elRef.className = 'rk-info-ref';
                elRef.textContent = `Ref: ${ref}`;
                kotakInfo.appendChild(elRef);
            }

            kotakInfo.style.display = 'block';

            const rect = sel.getBoundingClientRect();
            const infoRect = kotakInfo.getBoundingClientRect();

            let top = rect.top - infoRect.height - 8;
            if (top < 8) {
                top = rect.bottom + 8;
            }

            let left = rect.left + (rect.width / 2) - (infoRect.width / 2);
            left = Math.max(8, Math.min(left, window.innerWidth - infoRect.width - 8));

            kotakInfo.style.top = `${top}px`;
            kotakInfo.style.left = `${left}px`;
        };

        document.addEventListener('click', (e) => {
            const sel = e.target.closest('.rk-ada');
            if (sel) {
                tampilkanInfo(sel);
            } else {
                tutupInfo();
            }
        });

        window.addEventListener('scroll', tutupInfo, { passive: true });
        document.getElementById('rk-gulung')?.addEventListener('scroll', tutupInfo, { passive: true });

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

        const pesan = document.getElementById('rk-export-pesan');
        const kabari = (teks) => { if (pesan) pesan.textContent = teks; };

        const tanganiEkspor = (tombol, fallbackNama) => {
            if (! tombol) return;

            const ua = navigator.userAgent;
            const iOS = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
            const layarPenuh = window.matchMedia('(display-mode: standalone)').matches
                || navigator.standalone === true;

            if (! iOS && ! layarPenuh) return;

            const namaDari = (header) => {
                const cocok = (header || '').match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
                return cocok ? decodeURIComponent(cocok[1]) : fallbackNama;
            };

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
        };

        tanganiEkspor(document.getElementById('rk-export'), 'rekap-transfer.xlsx');
        tanganiEkspor(document.getElementById('rk-export-pdf'), 'rekap-transfer.pdf');
    })();
    </script>
</body>
</html>
