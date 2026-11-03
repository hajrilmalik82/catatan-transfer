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

    @if ($sourceAccounts->isEmpty())
        <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
            Belum ada rekening sumber yang aktif.
            <a href="/admin/source-accounts" class="font-medium underline">Tambahkan dulu</a>
            sebelum mengimport.
        </div>
    @else
        <form method="POST" action="{{ route('import.preview') }}" enctype="multipart/form-data"
              class="space-y-5 rounded-lg bg-white p-5 shadow-sm">
            @csrf

            <div>
                <label for="source_account_id" class="block text-sm font-medium">Rekening Sumber</label>
                <select id="source_account_id" name="source_account_id" required
                        class="mt-1 w-full rounded border-gray-300 shadow-sm">
                    @foreach ($sourceAccounts as $akun)
                        <option value="{{ $akun->id }}" @selected(old('source_account_id') == $akun->id)>
                            {{ $akun->nama }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Hanya dipakai kalau berkasnya tidak menyebutkan nomor rekening pengirim.
                    Kalau menyebutkan, rekening sumber dibaca langsung dari berkas.
                </p>
            </div>

            <div>
                <label for="berkas" class="block text-sm font-medium">Berkas PDF</label>
                <input id="berkas" name="berkas" type="file" accept="application/pdf" required
                       class="mt-1 w-full rounded border border-gray-300 p-2 text-sm shadow-sm">
                <p class="mt-1 text-xs text-gray-500">
                    Bukti transfer Mandiri "Status Transaksi", satu transaksi per halaman. Maksimal 10 MB.
                </p>
            </div>

            <button type="submit"
                    class="w-full rounded bg-blue-600 px-4 py-2.5 font-medium text-white hover:bg-blue-700">
                Baca Berkas
            </button>
        </form>

        <p class="mt-4 text-xs text-gray-500">
            Berkasnya hanya dibaca, tidak disimpan di server. Hasil bacaan ditampilkan dulu untuk kamu
            periksa sebelum masuk ke database.
        </p>
    @endif

</div>
</body>
</html>
