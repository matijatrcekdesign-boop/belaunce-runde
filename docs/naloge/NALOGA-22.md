NALOGA 22 — PWA nizi v namesti.js prek jezikovnih ključev (sl-SI + en-GB)

Veja:    feature/NALOGA-22-pwa-nizi  (iz NAJNOVEJŠEGA develop — vsebuje 0.18.0 / PR #19, merge 691eb0b)
Verzija: 0.18.1 — uskladi VSE: paket/pkg_belauncerunde.xml, paket/com_belauncerunde/belauncerunde.xml,
         paket/plg_authentication_belauncerunde/belauncerunde.xml, paket/plg_task_belauncerunde/belauncerunde.xml,
         posodobitve.xml, CHANGELOG.md
PR v:    develop
Odvisno: naloga 19 (PWA). Najdeno ob pregledu PR #19.
Pred delom preberi: docs/osnova-projekta.md, docs/ARCHITECTURE.md, AGENTS.md.
Ob nejasnosti ali konfliktu NE ugibaj — zapiši pod "Vprašanja za arhitekta" in del izpusti.

------------------------------------------------------------
CILJ
------------------------------------------------------------
`media/js/namesti.js` ima tri uporabniku vidne nize trdo zapisane v
slovenščini. Uskladi jih z uveljavljenim vzorcem projekta (naloga 11,
`deli.js`): nizi gredo v jezikovne datoteke (sl-SI + en-GB), JS jih bere
prek `Joomla.Text._()`, PHP jih da na voljo prek `Text::script()`.
Vedenje in videz ostaneta IDENTIČNA; spremeni se samo vir besedil.

------------------------------------------------------------
OBSEG (samo to, nič drugega)
------------------------------------------------------------
1. paket/com_belauncerunde/media/js/namesti.js — zamenjaj tri literale.
2. paket/com_belauncerunde/site/src/Helper/PwaHelper.php — dodaj tri
   Text::script() klice (in `use Joomla\CMS\Language\Text;`).
3. paket/com_belauncerunde/site/language/sl-SI/com_belauncerunde.ini
   in .../en-GB/com_belauncerunde.ini — trije novi ključi.
4. CHANGELOG.md + verzija 0.18.1 (v manifestih in posodobitve.xml;
   joomla.asset.json „version" lahko tudi posodobiš na 0.18.1 zaradi
   doslednosti — ni obvezno, gradnja.sh ga ne preverja).

NE spreminjaj: logike namesti.js (samo besedila), SW, manifesta, ikon,
PwaController, drugih viewov, predlog (D41).

------------------------------------------------------------
JEZIKOVNI KLJUČI (site, .ini)
------------------------------------------------------------
sl-SI (paket/com_belauncerunde/site/language/sl-SI/com_belauncerunde.ini):
COM_BELAUNCERUNDE_PWA_NAMESTI="Namesti aplikacijo"
COM_BELAUNCERUNDE_PWA_IOS_NAMIG="Dodaj na domači zaslon: pritisni Deli, nato 'Dodaj na domači zaslon'."
COM_BELAUNCERUNDE_PWA_ZAPRI="Zapri"

en-GB (paket/com_belauncerunde/site/language/en-GB/com_belauncerunde.ini):
COM_BELAUNCERUNDE_PWA_NAMESTI="Install app"
COM_BELAUNCERUNDE_PWA_IOS_NAMIG="Add to home screen: tap Share, then 'Add to Home Screen'."
COM_BELAUNCERUNDE_PWA_ZAPRI="Close"

(Ključe vstavi k obstoječim nizom komponente, npr. v bližino
COM_BELAUNCERUNDE_DELI_* — ohrani obstoječ slog datoteke.)

------------------------------------------------------------
PwaHelper::pripni() — dodaj Text::script klice
------------------------------------------------------------
Na vrh datoteke dodaj (če še ni):
   use Joomla\CMS\Language\Text;

V metodi pripni(), PRED klicem useScript('com_belauncerunde.namesti')
(da so nizi na voljo, ko se skripta izvede), dodaj:

   Text::script('COM_BELAUNCERUNDE_PWA_NAMESTI');
   Text::script('COM_BELAUNCERUNDE_PWA_IOS_NAMIG');
   Text::script('COM_BELAUNCERUNDE_PWA_ZAPRI');

(Enak vzorec kot Runda/HtmlView za COM_BELAUNCERUNDE_DELI_*.)

------------------------------------------------------------
namesti.js — zamenjave (samo te tri vrstice)
------------------------------------------------------------
- gumb.textContent = 'Namesti aplikacijo';
  → gumb.textContent = Joomla.Text._('COM_BELAUNCERUNDE_PWA_NAMESTI');

- besedilo.textContent = "Dodaj na domači zaslon: pritisni Deli, nato 'Dodaj na domači zaslon'.";
  → besedilo.textContent = Joomla.Text._('COM_BELAUNCERUNDE_PWA_IOS_NAMIG');

- zapri.setAttribute('aria-label', 'Zapri');
  → zapri.setAttribute('aria-label', Joomla.Text._('COM_BELAUNCERUNDE_PWA_ZAPRI'));

Ničesar drugega v namesti.js ne spreminjaj (logika, razredi, localStorage
ostanejo enaki). `Joomla.Text` je na voljo, ker ima skripta odvisnost
`core` (joomla.asset.json), enako kot deli.js.

------------------------------------------------------------
MERILA SPREJEMA
------------------------------------------------------------
1.  Namestitveni gumb prikaže besedilo iz jezikovne datoteke: sl-SI
    „Namesti aplikacijo", en-GB „Install app".
2.  iOS namig in aria-label gumba za zapiranje prav tako iz jezikovnih
    datotek (po jeziku strani).
3.  Vedenje nespremenjeno: gumb se pokaže/skrije enako kot prej, iOS
    namig se zapre in si zapomni zaprtje (localStorage) enako kot prej.
4.  V namesti.js ni več nobenega trdo zapisanega uporabniškega niza.
5.  Trije ključi obstajajo v sl-SI IN en-GB.
6.  Verzija 0.18.1 usklajena; gradnja.sh uspe (zažene arhitekt).
7.  V diffu samo: namesti.js, PwaHelper.php, obe .ini, CHANGELOG,
    manifesti, posodobitve.xml (po želji joomla.asset.json).

------------------------------------------------------------
ROČNI TEST (DDEV)
------------------------------------------------------------
1. Namesti 0.18.1 (ukazi spodaj).
2. Jezik strani sl-SI, odpri /runde → gumb „Namesti aplikacijo"
   (če ga Chrome ponudi; sicer preveri prek DevTools, da dobi besedilo
   iz Joomla.Text._, npr. v konzoli: Joomla.Text._('COM_BELAUNCERUNDE_PWA_NAMESTI')).
3. Preklopi jezik strani na en-GB (ali doda ?lang=en) → gumb „Install app",
   namig in aria-label v angleščini.
4. iOS namig (če je dostopen iOS/Safari ali prek emulacije): besedilo iz
   jezikovne datoteke; zapri in ponovno naloži → namig se ne pokaže
   (localStorage), enako kot prej.
5. Preveri, da registracija SW in namestitev delujeta nespremenjeno.

------------------------------------------------------------
NAMESTITEV NA DDEV
------------------------------------------------------------
Pred združitvijo (veja PR):
cd ~/belaunce-runde && git fetch origin && git checkout feature/NALOGA-22-pwa-nizi && git pull && bash orodja/gradnja.sh
ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1)
cp "$ZIP" ~/joomla-dev/tmp/
cd ~/joomla-dev
ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"

Po združitvi (develop):
cd ~/belaunce-runde && git checkout develop && git pull && bash orodja/gradnja.sh
ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1)
cp "$ZIP" ~/joomla-dev/tmp/
cd ~/joomla-dev
ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"

------------------------------------------------------------
PR — obvezni razdelki
------------------------------------------------------------
Kaj je narejeno / Spremenjene datoteke / Ročni test / Vprašanja za
arhitekta / Opažanja.
