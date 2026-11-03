<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1e3a8a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Catatan Transfer">
<link rel="apple-touch-icon" href="/icon-192.png">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
