<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Daftar bank
    |--------------------------------------------------------------------------
    | Pilihan bank yang muncul di form penerima.
    */
    'banks' => [
        'BCA' => 'BCA',
        'BNI' => 'BNI',
        'BRI' => 'BRI',
        'Mandiri' => 'Mandiri',
        'BSI' => 'BSI',
        'CIMB Niaga' => 'CIMB Niaga',
        'Permata' => 'Permata',
        'Danamon' => 'Danamon',
        'BTN' => 'BTN',
        'Lainnya' => 'Lainnya',
    ],
    /*
    |--------------------------------------------------------------------------
    | Menu dan hak akses
    |--------------------------------------------------------------------------
    | Dipakai untuk menyusun form hak akses pengguna dan memeriksanya.
    | Kuncinya disimpan apa adanya di kolom permissions.
    */
    'menu' => [
        'rekap' => ['label' => 'Rekap', 'aksi' => ['lihat']],
        'import' => ['label' => 'Import Bukti Transfer', 'aksi' => ['lihat']],
        'transaksi' => ['label' => 'Transaksi', 'aksi' => ['lihat', 'tambah', 'ubah', 'hapus']],
        'penerima' => ['label' => 'Penerima', 'aksi' => ['lihat', 'tambah', 'ubah', 'hapus']],
        'rekening_sumber' => ['label' => 'Rekening Sumber', 'aksi' => ['lihat', 'tambah', 'ubah', 'hapus']],
    ],

    'label_aksi' => [
        'lihat' => 'Lihat',
        'tambah' => 'Tambah',
        'ubah' => 'Ubah',
        'hapus' => 'Hapus',
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Sinkronisasi Spreadsheet
    |--------------------------------------------------------------------------
    | Token rahasia agar Google Spreadsheet dapat mengakses data rekap tanpa login.
    */
    'sync_token' => env('REKAP_SYNC_TOKEN', 'catatan-transfer-sync-secret'),
];
