(() => {
    'use strict';

    let odlozenPoziv = null;
    let gumb = null;

    const samostojno = () => window.matchMedia('(display-mode: standalone)').matches
        || navigator.standalone === true;

    window.addEventListener('beforeinstallprompt', (dogodek) => {
        if (samostojno()) {
            return;
        }

        dogodek.preventDefault();
        odlozenPoziv = dogodek;

        if (gumb) {
            gumb.hidden = false;
        }
    });

    window.addEventListener('appinstalled', () => {
        odlozenPoziv = null;

        if (gumb) {
            gumb.hidden = true;
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const koren = document.querySelector('.br-runde, .br-runda, .br-moje, .br-obrazec, .br-prijava, .br-odjava-gosta, .br-povezava');

        if (!koren) {
            return;
        }

        // Gumb nastane samo v komponenti in ostane skrit do ponudbe brskalnika.
        const blok = document.createElement('div');
        blok.className = 'br-namesti mb-3';
        gumb = document.createElement('button');
        gumb.type = 'button';
        gumb.className = 'btn btn-primary br-namesti-gumb';
        gumb.textContent = Joomla.Text._('COM_BELAUNCERUNDE_PWA_NAMESTI');
        gumb.hidden = samostojno() || !odlozenPoziv;
        blok.appendChild(gumb);
        koren.prepend(blok);

        gumb.addEventListener('click', async () => {
            if (!odlozenPoziv) {
                return;
            }

            const poziv = odlozenPoziv;
            odlozenPoziv = null;

            try {
                await poziv.prompt();
                await poziv.userChoice;
            } catch (napaka) {
                console.debug('Namestitev ni na voljo.', napaka);
            } finally {
                gumb.hidden = true;
            }
        });

        // iOS Safari nima beforeinstallprompt; namig uporabnik lahko trajno zapre.
        const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const safari = /Safari/.test(navigator.userAgent) && !/CriOS|FxiOS|EdgiOS/.test(navigator.userAgent);
        let zaprto = false;

        try {
            zaprto = localStorage.getItem('br-pwa-ios-namig-zaprt') === '1';
        } catch (napaka) {
            // Brez localStorage se namig še vedno lahko zapre za trenutno stran.
        }

        if (ios && safari && !samostojno() && !odlozenPoziv && !zaprto) {
            const namig = document.createElement('div');
            namig.className = 'alert alert-info br-namesti-namig d-flex align-items-center gap-2';
            namig.setAttribute('role', 'note');
            const besedilo = document.createElement('span');
            besedilo.textContent = Joomla.Text._('COM_BELAUNCERUNDE_PWA_IOS_NAMIG');
            const zapri = document.createElement('button');
            zapri.type = 'button';
            zapri.className = 'btn-close ms-auto';
            zapri.setAttribute('aria-label', Joomla.Text._('COM_BELAUNCERUNDE_PWA_ZAPRI'));
            zapri.addEventListener('click', () => {
                namig.remove();

                try {
                    localStorage.setItem('br-pwa-ios-namig-zaprt', '1');
                } catch (napaka) {
                    // Shranjevanje ni pogoj za zapiranje vidnega namiga.
                }
            });
            namig.append(besedilo, zapri);
            koren.prepend(namig);
        }

        // Service worker dobimo prek Joomlinih možnosti, ker je URL lahko podmapi prilagojen.
        const moznosti = Joomla.getOptions('com_belauncerunde.pwa', {});

        if ('serviceWorker' in navigator && moznosti.swUrl) {
            (async () => {
                try {
                    await navigator.serviceWorker.register(moznosti.swUrl, { scope: '/' });
                } catch (napaka) {
                    console.debug('Service worker ni na voljo.', napaka);
                }
            })();
        }
    });
})();
