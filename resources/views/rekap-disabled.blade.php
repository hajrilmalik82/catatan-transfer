<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Transfer - Dinonaktifkan</title>
    <style>
        :root {
            --rk-bg: #ffffff;
            --rk-latar: #f8fafc;
            --rk-garis: #e2e8f0;
            --rk-teks: #0f172a;
            --rk-redup: #64748b;
            --rk-biru: #2563eb;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --rk-bg: #1e293b;
                --rk-latar: #0f172a;
                --rk-garis: #334155;
                --rk-teks: #f8fafc;
                --rk-redup: #94a3b8;
            }
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 1.5rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--rk-latar);
            color: var(--rk-teks);
        }

        .rk-kartu-pesan {
            background: var(--rk-bg);
            border: 1px solid var(--rk-garis);
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            max-width: 28rem;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,.05), 0 2px 4px -2px rgba(0,0,0,.05);
        }

        .rk-ikon {
            width: 3.5rem;
            height: 3.5rem;
            margin: 0 auto 1.25rem;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }

        .rk-judul {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 .5rem;
        }

        .rk-ket {
            font-size: .875rem;
            color: var(--rk-redup);
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="rk-kartu-pesan">
        <div class="rk-ikon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        <h1 class="rk-judul">Tampilan Publik Dinonaktifkan</h1>
        <p class="rk-ket">
            Halaman rekapan transfer publik saat ini sedang ditutup atau dinonaktifkan oleh administrator.
        </p>
    </div>
</body>
</html>
