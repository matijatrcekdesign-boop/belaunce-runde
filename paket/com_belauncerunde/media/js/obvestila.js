/**
 * Kartica "Obvestila na tej napravi" v Moje runde (naloga 24).
 *
 * Stanja: A) iOS v brskalniku (ne nameščena PWA) → navodilo; B) brez podpore → kartica skrita;
 * C) dovoljenje zavrnjeno → navodilo brez gumba; D) brez naročnine → [Vklopi]; E) naročen → [Izklopi].
 * Vklop teče SAMO na klik (iOS/Chrome zahtevata uporabniško gesto). SW registrira namesti.js.
 */
(() => {
    'use strict';

    const samostojno = () => window.matchMedia('(display-mode: standalone)').matches
        || navigator.standalone === true;
    const ios = () => /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const podprto = () => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    /** base64url → Uint8Array (applicationServerKey). */
    const vBajte = (niz) => {
        const b64 = (niz + '='.repeat((4 - (niz.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
        const surovo = window.atob(b64);
        return Uint8Array.from(surovo, (znak) => znak.charCodeAt(0));
    };

    /** POST kot navaden obrazec (URLSearchParams) s CSRF žetonom kot imenom polja; vrne true ob {ok:true}. */
    const poslji = async (url, polja) => {
        const telo = new URLSearchParams(polja);
        const zeton = Joomla.getOptions('csrf.token', '');

        if (zeton) {
            telo.append(zeton, '1');
        }

        const odziv = await fetch(url, {
            method: 'POST',
            body: telo,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!odziv.ok) {
            return false;
        }

        const podatki = await odziv.json();
        return !!(podatki && podatki.ok);
    };

    /** Podatki naročnine za strežnik (ključa v base64url). */
    const polja = (narocnina) => {
        const json = narocnina.toJSON();
        return {
            endpoint: narocnina.endpoint,
            p256dh: (json.keys && json.keys.p256dh) || '',
            auth: (json.keys && json.keys.auth) || ''
        };
    };

    document.addEventListener('DOMContentLoaded', () => {
        const kartica = document.querySelector('.br-obvestila');
        const moznosti = Joomla.getOptions('com_belauncerunde.push', {});

        if (!kartica || !moznosti.kljuc || !moznosti.naroci || !moznosti.odjavi) {
            return;
        }

        const besedilo = kartica.querySelector('.br-obvestila-besedilo');
        const vklopi = kartica.querySelector('.br-obvestila-vklopi');
        const izklopi = kartica.querySelector('.br-obvestila-izklopi');

        const prikazi = (sporocilo, gumbVklop, gumbIzklop) => {
            besedilo.textContent = sporocilo;
            besedilo.hidden = sporocilo === '';
            vklopi.hidden = !gumbVklop;
            izklopi.hidden = !gumbIzklop;
            kartica.hidden = false;
        };

        const stanjeD = (sporocilo = '') => prikazi(sporocilo, true, false);
        const stanjeE = () => prikazi(Joomla.Text._('COM_BELAUNCERUNDE_OBVESTILA_VKLOPLJENO'), false, true);
        const stanjeC = () => prikazi(Joomla.Text._('COM_BELAUNCERUNDE_OBVESTILA_ZAVRNJENO'), false, false);

        if (!podprto()) {
            // A) iPhone/iPad v Safariju: push deluje samo v nameščeni PWA (iOS 16.4+). B) drugje skrito.
            if (ios() && !samostojno()) {
                prikazi(Joomla.Text._('COM_BELAUNCERUNDE_OBVESTILA_IOS'), false, false);
            }
            return;
        }

        if (Notification.permission === 'denied') {
            stanjeC();
            return;
        }

        let registracija = null;

        (async () => {
            try {
                registracija = await navigator.serviceWorker.ready;
                const obstojeca = await registracija.pushManager.getSubscription();

                if (obstojeca) {
                    // Tiha osvežitev: ključi po menjavi uporabnika ali naprave ostanejo usklajeni (idempotentno).
                    stanjeE();
                    poslji(moznosti.naroci, polja(obstojeca)).catch(() => {});
                } else {
                    stanjeD();
                }
            } catch (napaka) {
                console.debug('Obvestila niso na voljo.', napaka);
            }
        })();

        vklopi.addEventListener('click', async () => {
            vklopi.disabled = true;
            let narocnina = null;

            try {
                const dovoljenje = await Notification.requestPermission();

                if (dovoljenje === 'denied') {
                    stanjeC();
                    return;
                }

                if (dovoljenje !== 'granted') {
                    stanjeD();
                    return;
                }

                registracija = registracija || await navigator.serviceWorker.ready;
                narocnina = await registracija.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: vBajte(moznosti.kljuc)
                });

                if (await poslji(moznosti.naroci, polja(narocnina))) {
                    stanjeE();
                    return;
                }

                throw new Error('naroci');
            } catch (napaka) {
                // Strežnik naročnine ni shranil: odjavimo tudi v brskalniku, da stanji ostaneta usklajeni.
                if (narocnina) {
                    try { await narocnina.unsubscribe(); } catch (x) {}
                }
                stanjeD(Joomla.Text._('COM_BELAUNCERUNDE_OBVESTILA_NAPAKA'));
            } finally {
                vklopi.disabled = false;
            }
        });

        izklopi.addEventListener('click', async () => {
            izklopi.disabled = true;

            try {
                registracija = registracija || await navigator.serviceWorker.ready;
                const narocnina = await registracija.pushManager.getSubscription();

                if (narocnina) {
                    await poslji(moznosti.odjavi, { endpoint: narocnina.endpoint });
                    await narocnina.unsubscribe();
                }

                stanjeD();
            } catch (napaka) {
                stanjeE();
            } finally {
                izklopi.disabled = false;
            }
        });
    });
})();
