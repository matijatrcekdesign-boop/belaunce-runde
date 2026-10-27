const CACHE = 'br-pwa-v1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (dogodek) => {
    dogodek.waitUntil(clients.claim());
});

self.addEventListener('fetch', (dogodek) => {
    // Obdelamo samo GET navigacije na istem izvoru zunaj administracije.
    const zahteva = dogodek.request;
    const url = new URL(zahteva.url);

    if (zahteva.mode !== 'navigate' || zahteva.method !== 'GET'
        || url.origin !== self.location.origin
        || url.pathname.indexOf('/administrator') === 0) {
        return;
    }

    // Osebnih strani ne hranimo v cache; brez omrežja vrnemo le sporočilo.
    dogodek.respondWith(
        fetch(zahteva).catch(() => new Response(
            'Ni povezave. Poskusi znova, ko bo internet na voljo.',
            { status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' } }
        ))
    );
});
