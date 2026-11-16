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
                            @php
                                $kosongIds = \App\Support\RekapData::penerimaKosongPadaBaris($baris, $recipients);
                            @endphp
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
                                        $ditandaiKosong = $nilai === null && isset($kosongIds[$penerima->id]);
                                    @endphp
                                    <td class="rk-kanan {{ $nilai ? 'rk-ada' : ($ditandaiKosong ? 'rk-kosong-sel' : 'rk-nol') }}"
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

    @php
        $daftarKosong = \App\Support\RekapData::daftarKosong(compact('dates', 'barisPerTanggal', 'recipients', 'namaSumber'));
    @endphp
    @if ($daftarKosong !== [])
        <section class="rk-catatan" aria-label="Catatan Transaksi Kosong">
            <h3 class="rk-catatan-judul">Catatan Transaksi Kosong ({{ count($daftarKosong) }})</h3>
            <ul class="rk-catatan-daftar">
                @foreach ($daftarKosong as $item)
                    <li>{{ $item['teks'] }}</li>
                @endforeach
            </ul>
        </section>
    @endif
@endif
