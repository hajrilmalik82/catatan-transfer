<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Bukti Transfer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('pwa-head')
</head>
<body class="bg-gray-100 text-gray-900">
<div class="mx-auto max-w-2xl p-4 sm:p-6">

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Import Bukti Transfer</h1>
        <a href="/admin" class="text-sm text-blue-700 hover:underline">&larr; Kembali ke panel</a>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('sukses') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    @if (session('teksMentah'))
        <details class="mb-4 rounded-lg bg-gray-50 p-4 text-sm">
            <summary class="cursor-pointer font-medium">Lihat teks mentah hasil pembacaan</summary>
            <pre class="mt-3 max-h-80 overflow-auto whitespace-pre-wrap break-words text-xs text-gray-700">{{ session('teksMentah') }}</pre>
            <p class="mt-2 text-xs text-gray-500">
                Kirimkan cuplikan ini kalau berkas yang seharusnya terbaca malah ditolak.
            </p>
        </details>
    @endif

    <div class="mb-4 flex gap-2">
        <button type="button" id="tab-btn-pdf"
                class="flex-1 rounded-lg border px-4 py-2.5 text-sm font-medium transition">
            Unggah Berkas PDF
        </button>
        <button type="button" id="tab-btn-raw"
                class="flex-1 rounded-lg border px-4 py-2.5 text-sm font-medium transition">
            Tempel Raw Code (AI)
        </button>
    </div>

    <form id="panel-pdf" method="POST" action="{{ route('import.preview') }}" enctype="multipart/form-data"
          class="space-y-5 rounded-lg bg-white p-5 shadow-sm">
        @csrf

        <div>
            <label for="berkas" class="block text-sm font-medium">Berkas PDF</label>
            <input id="berkas" name="berkas" type="file" accept="application/pdf" required
                   class="mt-1 w-full rounded border border-gray-300 p-2 text-sm shadow-sm">
            <p class="mt-1 text-xs text-gray-500">
                Bukti transfer Mandiri "Status Transaksi", satu transaksi per halaman. Maksimal 10 MB.
                Rekening sumber dan penerima akan dicocokkan otomatis lewat nomor rekening di halaman berikutnya.
            </p>
        </div>

        <button type="submit"
                class="w-full rounded bg-blue-600 px-4 py-2.5 font-medium text-white hover:bg-blue-700">
            Baca Berkas
        </button>
    </form>

    <form id="panel-raw" method="POST" action="{{ route('import.preview') }}"
          class="hidden space-y-5 rounded-lg bg-white p-5 shadow-sm">
        @csrf

        <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-900">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span>Gunakan untuk PDF hasil scan CamScanner atau bank selain Mandiri asli.</span>
                <button type="button" id="btn-salin-prompt"
                        class="rounded bg-blue-600 px-3 py-1.5 font-medium text-white hover:bg-blue-700">
                    Salin Prompt untuk AI
                </button>
            </div>
        </div>

        <div>
            <label for="raw_code" class="block text-sm font-medium">Raw Code (JSON dari AI)</label>
            <textarea id="raw_code" name="raw_code" rows="9" required
                      placeholder='[{"ref_transaksi":"BIF38377118","tanggal":"2026-11-03","nominal":1653500,"sumber_rekening":"100055","nomor_rekening":"1100021578916","nama":"Rini Ardianty","bank":"Mandiri","remark":"Pembayaran BB Protein"}]'
                      class="mt-1 w-full rounded border border-gray-300 p-2.5 font-mono text-xs shadow-sm">{{ old('raw_code') }}</textarea>
        </div>

        <button type="submit"
                class="w-full rounded bg-blue-600 px-4 py-2.5 font-medium text-white hover:bg-blue-700">
            Baca Raw Code
        </button>
    </form>

    <p class="mt-4 text-xs text-gray-500">
        Hasil bacaan ditampilkan dulu untuk kamu periksa dan konfirmasi sebelum masuk ke database.
    </p>

    <script>
    (() => {
        const btnPdf = document.getElementById('tab-btn-pdf');
        const btnRaw = document.getElementById('tab-btn-raw');
        const panelPdf = document.getElementById('panel-pdf');
        const panelRaw = document.getElementById('panel-raw');
        const btnPrompt = document.getElementById('btn-salin-prompt');

        const aktif = ['bg-blue-600', 'text-white', 'border-blue-600'];
        const nonAktif = ['bg-white', 'text-gray-700', 'border-gray-300'];

        const pilihTab = (mode) => {
            const keRaw = mode === 'raw';
            panelPdf.classList.toggle('hidden', keRaw);
            panelRaw.classList.toggle('hidden', ! keRaw);
            btnPdf.classList.remove(...(keRaw ? aktif : nonAktif));
            btnPdf.classList.add(...(keRaw ? nonAktif : aktif));
            btnRaw.classList.remove(...(keRaw ? nonAktif : aktif));
            btnRaw.classList.add(...(keRaw ? aktif : nonAktif));
        };

        btnPdf.addEventListener('click', () => pilihTab('pdf'));
        btnRaw.addEventListener('click', () => pilihTab('raw'));

        pilihTab(@json(old('raw_code') ? 'raw' : 'pdf'));

        const promptTeks = `Baca seluruh halaman bukti transfer ini dan ubah menjadi JSON array murni (tanpa penjelasan tambahan) dengan format berikut:
[
  {
    "ref_transaksi": "ID Transaksi / Transaction ID (contoh: BIF38377118)",
    "tanggal": "YYYY-MM-DD",
    "nominal": 1653500,
    "sumber_rekening": "hanya digit nomor rekening pengirim / Sumber Dana / Sender Account (jika ada)",
    "nomor_rekening": "hanya digit nomor rekening penerima (Beneficiary Account Number)",
    "nama": "Nama penerima (Beneficiary Account Name)",
    "bank": "Singkatan bank penerima: Mandiri / BNI / BRI / BSI / BCA / CIMB Niaga / Permata / Danamon / BTN / Lainnya",
    "remark": "Isi Remark / Keterangan singkat"
  }
]
Catatan:
- "nominal" harus berupa angka bulat Rupiah (Transaction Amount yang dikirim, tanpa titik/koma/desimal).
- Hanya masukkan transaksi yang berstatus Success / Sukses.`;

        btnPrompt?.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(promptTeks);
                const asli = btnPrompt.textContent;
                btnPrompt.textContent = 'Prompt Tersalin!';
                setTimeout(() => { btnPrompt.textContent = asli; }, 2000);
            } catch {
                prompt('Salin prompt berikut:', promptTeks);
            }
        });
    })();
    </script>

</div>
</body>
</html>
