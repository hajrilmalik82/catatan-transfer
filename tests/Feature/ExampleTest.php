<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /*
     | Aplikasi ini tidak punya halaman depan sendiri — semua pintu masuknya
     | lewat panel. Dua tes di bawah tidak menguji logika apa pun, hanya
     | memastikan aplikasinya masih bisa menyala dan pengalihannya utuh.
     | Gunanya sebagai penjaga terakhir sebelum berkas dikirim ke hosting:
     | kalau ada yang merusak booting, langkah deploy berhenti di sini.
     */
    public function test_halaman_depan_mengarah_ke_panel(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_halaman_masuk_mengarah_ke_panel(): void
    {
        $this->get('/login')->assertRedirect('/admin/login');
    }
}
