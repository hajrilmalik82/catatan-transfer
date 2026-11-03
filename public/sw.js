// Service worker seminimal mungkin. Tidak menyimpan apa pun ke cache supaya
// halaman selalu yang terbaru; keberadaannya hanya syarat agar Chrome
// menawarkan pemasangan aplikasi.

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', (event) => event.respondWith(fetch(event.request)));
