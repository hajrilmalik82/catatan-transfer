<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Periksa Hasil Baca</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="mx-auto max-w-[1200px] p-4 sm:p-6">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Periksa Hasil Baca</h1>
            <p class="text-sm text-gray-600">
                {{ count($baris) }} transaksi terbaca
            </p>
        </div>
        <a href="{{ route('import') }}" class="text-sm text-blue-700 hover:underline">Ganti berkas</a>
    </div>

    @if ($gagal)
        <div class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
            @foreach ($gagal as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    @php
        $sumberBelumDikenal = collect($baris)->whereNull('source_account_id')->count();
        $belumDikenal = collect($baris)->whereNull('recipient_id')->count();
    @endphp

    @if ($sumberBelumDikenal > 0)
        <div class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <strong>{{ $sumberBelumDikenal }} rekening sumber belum dikenal.</strong>
            Pilih rekening sumber yang sesuai di kolom Sumber, atau biarkan "Buat rekening sumber baru".
        </div>
    @endif

    @if ($belumDikenal > 0)
        <div class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <strong>{{ $belumDikenal }} nomor rekening penerima belum dikenal.</strong>
            Pilih penerima yang sesuai, atau biarkan "Buat penerima baru" kalau memang orang baru.
        </div>
    @endif

    <details class="mb-4 rounded-lg bg-white p-4 text-sm shadow-sm">
        <summary class="cursor-pointer font-medium text-gray-700">Lihat teks mentah hasil pembacaan</summary>
        <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs text-gray-700">{{ $teksMentah }}</pre>
    </details>

    <form method="POST" action="{{ route('import.simpan') }}">
        @csrf

        @php
            $hitungTanggal = collect($baris)->countBy('tanggal')->sortDesc();
            $tanggalUtama = $hitungTanggal->keys()->first();
        @endphp

        <div class="mb-4 flex flex-wrap items-end gap-4 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <label for="tanggal" class="block text-xs font-medium text-gray-600">
                    Tanggal pencatatan
                </label>
                <input id="tanggal" name="tanggal" type="date" value="{{ $tanggalUtama }}" required
                       class="mt-1 rounded border-gray-300 text-sm shadow-sm">
                @if ($hitungTanggal->count() > 1)
                    <p class="mt-1 max-w-sm text-xs text-amber-700">
                        Berkas memuat {{ $hitungTanggal->count() }} tanggal berbeda
                        ({{ $hitungTanggal->keys()->join(', ') }}), biasanya karena ada transaksi
                        yang dibuat lewat tengah malam. Satu tanggal di atas dipakai untuk semua baris.
                    </p>
                @endif
            </div>
            <div>
                <label for="biaya_admin" class="block text-xs font-medium text-gray-600">
                    Biaya admin per transaksi
                </label>
                <input id="biaya_admin" name="biaya_admin" type="number" min="0" step="1" value="0" required
                       class="mt-1 w-40 rounded border-gray-300 text-sm shadow-sm">
            </div>
            <p class="text-xs text-gray-500">
                Dipakai untuk semua baris. Transfer Mandiri biasanya 0; BI-Fast dipotong per transaksi.
            </p>
        </div>

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left font-semibold">Sumber</th>
                    <th class="px-3 py-3 text-left font-semibold">Terbaca dari PDF</th>
                    <th class="px-3 py-3 text-right font-semibold">Nominal</th>
                    <th class="px-3 py-3 text-left font-semibold">Remark</th>
                    <th class="px-3 py-3 text-left font-semibold">Dicatat sebagai</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach ($baris as $i => $row)
                    <tr class="{{ $row['duplikat'] ? 'bg-amber-50' : '' }}">
                        <td class="px-3 py-2 align-top">
                            @if ($row['sumber_rekening'] ?? null)
                                <div class="mb-1 text-xs text-gray-500">No. Rek: {{ $row['sumber_rekening'] }}</div>
                            @else
                                <div class="mb-1 text-xs text-gray-400">No. rek tidak terbaca</div>
                            @endif
                            <select name="pilihan_sumber[{{ $i }}]"
                                    data-sumber-rek="{{ $row['sumber_rekening'] ?? '' }}"
                                    class="select-sumber w-56 rounded border-gray-300 text-sm shadow-sm">
                                <option value="baru" @selected(! $row['source_account_id'])>
                                    + Buat rekening sumber baru{{ ($row['sumber_rekening'] ?? null) ? ': '.$row['sumber_rekening'] : '' }}
                                </option>
                                @foreach ($sourceAccounts as $akun)
                                    <option value="{{ $akun->id }}" @selected($row['source_account_id'] === $akun->id)>
                                        {{ $akun->label }}
                                    </option>
                                @endforeach
                                <option value="lewati">Lewati baris ini</option>
                            </select>
                            @if ($row['source_account_id'])
                                <div class="mt-1 text-xs text-emerald-700">cocok lewat nomor rekening</div>
                            @endif
                            @if ($row['duplikat'])
                                <span class="mt-1 block text-xs text-amber-700">sudah pernah diimport</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium">{{ $row['nama'] }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $row['bank'] ?? 'bank tidak terbaca' }} {{ $row['nomor_rekening'] }}
                            </div>
                            <div class="text-xs text-gray-400">Ref {{ $row['ref_transaksi'] }}</div>
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap align-top">
                            Rp{{ number_format($row['nominal'], 0, ',', '.') }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            <input type="text" name="remark[{{ $i }}]" value="{{ $row['remark'] }}"
                                   class="w-44 rounded border-gray-300 text-sm shadow-sm">
                        </td>
                        <td class="px-3 py-2 align-top">
                            <select name="pilihan[{{ $i }}]"
                                    class="w-64 rounded border-gray-300 text-sm shadow-sm">
                                <option value="baru" @selected(! $row['recipient_id'])>
                                    + Buat penerima baru: {{ $row['nama'] }}
                                </option>
                                @foreach ($recipients as $penerima)
                                    <option value="{{ $penerima->id }}" @selected($row['recipient_id'] === $penerima->id)>
                                        {{ $penerima->nama }} ({{ $penerima->bank }} {{ $penerima->nomor_rekening }})
                                    </option>
                                @endforeach
                                <option value="lewati" @selected($row['duplikat'])>Lewati baris ini</option>
                            </select>
                            @if ($row['recipient_id'])
                                <div class="mt-1 text-xs text-emerald-700">cocok lewat nomor rekening</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="2" class="px-3 py-3">TOTAL</td>
                    <td class="px-3 py-3 text-right">
                        Rp{{ number_format(collect($baris)->sum('nominal'), 0, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-5 flex gap-3">
            <button type="submit"
                    class="rounded bg-blue-600 px-5 py-2.5 font-medium text-white hover:bg-blue-700">
                Simpan ke Transaksi
            </button>
            <a href="{{ route('import') }}"
               class="rounded bg-gray-200 px-5 py-2.5 font-medium text-gray-800 hover:bg-gray-300">
                Batal
            </a>
        </div>
    </form>

    <script>
    (() => {
        const daftarSelect = document.querySelectorAll('.select-sumber');

        daftarSelect.forEach((el) => {
            el.addEventListener('change', () => {
                const rek = el.dataset.sumberRek;

                daftarSelect.forEach((lain) => {
                    if (lain !== el && lain.dataset.sumberRek === rek && lain.value === 'baru') {
                        lain.value = el.value;
                    }
                });
            });
        });
    })();
    </script>

</div>
</body>
</html>
