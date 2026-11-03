{{-- Tombol pasang aplikasi di halaman login. Hanya muncul kalau browser
     memang mendukung, atau berupa petunjuk singkat di iPhone. --}}
<div id="pwa-pasang" style="display:none; margin-top:1.5rem; text-align:center">
    <button type="button" id="pwa-tombol"
            style="width:100%; border:0; border-radius:.5rem; background:#1e3a8a; color:#fff;
                   padding:.75rem 1rem; font-size:.875rem; font-weight:600; cursor:pointer">
        Pasang aplikasi di ponsel
    </button>
    <p id="pwa-petunjuk" style="display:none; margin:.75rem 0 0; font-size:.75rem; line-height:1.5; color:#6b7280">
        Buka lewat Safari, ketuk tombol <strong>Bagikan</strong> di bawah, lalu pilih
        <strong>Add to Home Screen</strong>.
    </p>
</div>

<script>
(() => {
    const kotak = document.getElementById('pwa-pasang');
    const tombol = document.getElementById('pwa-tombol');
    const petunjuk = document.getElementById('pwa-petunjuk');

    // Sudah dibuka dari ikon layar utama, tidak perlu ditawari lagi.
    const terpasang = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    if (terpasang) return;

    const iOS = /iphone|ipad|ipod/i.test(navigator.userAgent);

    if (iOS) {
        kotak.style.display = 'block';
        tombol.style.display = 'none';
        petunjuk.style.display = 'block';
        return;
    }

    // Chrome menahan tawaran pemasangan lewat event ini; kita simpan lalu
    // munculkan lagi saat tombolnya ditekan.
    let tawaran = null;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        tawaran = e;
        kotak.style.display = 'block';
    });

    tombol.addEventListener('click', async () => {
        if (! tawaran) return;
        tombol.disabled = true;
        tawaran.prompt();
        await tawaran.userChoice;
        tawaran = null;
        kotak.style.display = 'none';
    });

    window.addEventListener('appinstalled', () => { kotak.style.display = 'none'; });
})();
</script>
