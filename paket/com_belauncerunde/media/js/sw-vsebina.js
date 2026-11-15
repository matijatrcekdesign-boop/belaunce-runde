const CACHE = 'br-pwa-v2';

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

// Push obvestila (naloga 24). iOS zahteva, da vsak push prikaže obvestilo — brez tihih pushov.
self.addEventListener('push', (e) => {
    let p = {};
    try { p = e.data ? e.data.json() : {}; } catch (x) {}
    const naslov = p.title || 'Runde Belaunce';
    e.waitUntil(self.registration.showNotification(naslov, {
        body: p.body || '',
        tag: p.tag || undefined,
        renotify: !!p.tag,
        icon: '/media/com_belauncerunde/images/icon-192.png',
        badge: '/media/com_belauncerunde/images/icon-192.png',
        data: { url: p.url || '/runde' }
    }));
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    // Odpremo samo naslov na istem izvoru (obvestilo nikoli ne preusmeri drugam).
    const cilj = new URL((e.notification.data && e.notification.data.url) || '/runde', self.location.origin);
    if (cilj.origin !== self.location.origin) { return; }
    e.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((okna) => {
        for (const o of okna) { if ('focus' in o) { o.navigate(cilj.href); return o.focus(); } }
        return clients.openWindow(cilj.href);
    }));
});
