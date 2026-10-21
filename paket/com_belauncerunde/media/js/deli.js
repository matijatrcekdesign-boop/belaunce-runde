(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-br-deli]').forEach((gumb) => {
            const besedilo = document.getElementById(gumb.dataset.brDeliCilj);
            const blok = gumb.closest('.br-deli');
            const stanje = blok ? blok.querySelector('[role="status"]') : null;

            if (!besedilo || !stanje) {
                return;
            }

            // Ročno kopiranje ostane mogoče tudi ob zavrnjenem dovoljenju brskalnika.
            const kopirajRocno = () => {
                besedilo.focus();
                besedilo.select();
                stanje.textContent = Joomla.Text._('COM_BELAUNCERUNDE_DELI_KOPIRAJ_ROCNO');
            };

            if (gumb.dataset.brDeli === 'sistem' && typeof navigator.share === 'function') {
                gumb.hidden = false;
                gumb.addEventListener('click', async () => {
                    stanje.textContent = '';
                    try {
                        // Povezava je že v besedilu; ločen url bi jo podvojil.
                        await navigator.share({ text: besedilo.value });
                    } catch (napaka) {
                        if (!napaka || napaka.name !== 'AbortError') {
                            kopirajRocno();
                        }
                    }
                });
            }

            if (gumb.dataset.brDeli === 'kopiraj') {
                gumb.hidden = false;
                gumb.addEventListener('click', async () => {
                    stanje.textContent = '';
                    if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
                        kopirajRocno();
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(besedilo.value);
                        stanje.textContent = Joomla.Text._('COM_BELAUNCERUNDE_DELI_KOPIRANO');
                    } catch (napaka) {
                        kopirajRocno();
                    }
                });
            }
        });
    });
})();
