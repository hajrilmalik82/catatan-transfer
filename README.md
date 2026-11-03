# Catatan Transfer

Aplikasi pencatatan transfer pembayaran ke penerima, pengganti rekap manual di spreadsheet.

Dibuat untuk kasus yang sederhana: satu atau beberapa rekening perusahaan mengirim pembayaran ke banyak penerima setiap hari, dan semuanya perlu dicatat, direkap per tanggal, lalu dicocokkan dengan mutasi bank.

## Kenapa bukan spreadsheet

Rekap manual biasanya disusun dengan satu kolom per penerima. Begitu ada penerima baru, strukturnya harus diubah dan rumus di seluruh bulan sebelumnya ikut disesuaikan. Nomor rekening diketik ulang tiap periode tanpa validasi, dan tidak ada catatan siapa menginput apa.

Di sini penerima dan rekening sumber menjadi data master. Menambah penerima tidak mengubah apa pun, dan rekapnya tetap tersusun otomatis.

## Fitur

- Data master rekening sumber dan penerima
- Pencatatan transaksi dengan filter tanggal, rekening sumber, dan penerima
- Penanganan potongan admin bank: nominal tagihan, biaya admin, dan nominal yang benar-benar terkirim disimpan terpisah dan dijaga tetap konsisten
- Halaman rekap berbentuk pivot tanggal x penerima, lengkap dengan total harian dan total per penerima
- Export CSV yang langsung terbaca rapi di Excel

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- SQLite (bawaan PHP) atau MySQL/MariaDB

## Instalasi

```bash
git clone <url-repo-ini> catatan-transfer
cd catatan-transfer
composer install
cp .env.example .env
php artisan key:generate
```

Secara default aplikasi memakai SQLite dan tidak butuh konfigurasi tambahan. Untuk MySQL, sesuaikan `DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`.

Siapkan database dan akun untuk login:

```bash
touch database/database.sqlite   # lewati kalau memakai MySQL
php artisan migrate
php artisan make:filament-user
```

Jalankan:

```bash
php artisan serve
```

Panel ada di `/admin`, halaman rekap di `/rekap`.

## Langkah pertama setelah login

Isi data master lebih dulu:

1. **Rekening Sumber** - nama rekening atau channel yang dipakai mengirim, diketik bebas.
2. **Penerima** - nama penerima pembayaran beserta bank dan nomor rekeningnya.

Setelah itu transaksi baru bisa dicatat. Biaya admin diisi per transaksi, bukan disetel di rekening sumber, karena potongannya bisa berbeda tergantung jenis transfer yang dipakai.

Tidak ada nama atau nomor rekening yang ditulis di dalam kode. Semuanya diinput lewat panel dan tersimpan di database, yang tidak ikut masuk ke repositori.

## Catatan teknis

Nominal disimpan sebagai bilangan bulat rupiah, bukan desimal, supaya tidak ada selisih akibat pembulatan. `nominal_transfer` tidak diinput langsung melainkan selalu dihitung dari `nominal_tagihan` dikurangi `biaya_admin`, sehingga ketiganya tidak bisa saling bertentangan.

## Dibangun dengan

Laravel dan Filament.
