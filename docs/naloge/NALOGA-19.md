# NALOGA 19 — PWA: namestitev kot samostojen pogled, BREZ push

Veja:    feature/NALOGA-19-pwa  (iz najnovejšega develop)
Verzija: 0.18.0 — uskladi VSE: paket/pkg_belauncerunde.xml, paket/com_belauncerunde/belauncerunde.xml,
         paket/plg_authentication_belauncerunde/belauncerunde.xml, paket/plg_task_belauncerunde/belauncerunde.xml,
         posodobitve.xml, CHANGELOG.md (naloga 18 = 0.17.0 in naloga 21 = 0.17.1 sta že v develop)
PR v:    develop
Odvisno: D62 (potrjeno: PWA brez push v v1; push je ločena faza 2 po izidu), D60 (poddomena = ločena namestitev)

------------------------------------------------------------
CILJ
------------------------------------------------------------
Komponenta postane namestljiva kot samostojna "aplikacija" (Add to Home
Screen / Install App) na telefonu in namizju: lastna ikona, zagon brez
naslovne vrstice brskalnika. BREZ push obvestil (ta so faza 2, ločena
naloga, po izidu in testiranju — NE delaj te naloge zdaj).

Omejitev D41 velja: predlog (`templates/`) se NE spreminja. Vse gre
skozi Joomlin Document API (`$this->getDocument()->...`), enako kot
`canonical`/Open Graph v nalogi 06 in 16.

------------------------------------------------------------
OBSEG (samo to, nič drugega)
------------------------------------------------------------
1. `media/com_belauncerunde/manifest.webmanifest` (statična datoteka,
   glej spodaj za vsebino) + spremljajoče ikone.
2. Nova SITE komponentna pot, ki servira service worker z ustrezno
   glavo (glej "SERVICE WORKER" — KRITIČNO, ne preskoči).
3. `media/js/namesti.js` (gumb za namestitev + iOS namig), registriran
   prek `joomla.asset.json`.
4. Klic skupne metode `Site\Helper\PwaHelper::pripni()` v VSEH site
   HtmlView razredih (seznam spodaj), tik ob obstoječem
   `useStyle('com_belauncerunde.pisave')` klicu.
5. `apple-touch-icon` in theme-color meta prek Document API (ne v
   predlogi).
6. NAVODILA.md: kratek odstavek, kako uporabnik namesti aplikacijo.
7. CHANGELOG + uskladitev verzij.

NE delaj: push obvestil, naročnine, VAPID, service worker cache
strategij nad osnovnim "offline pretvori nalaganje v obvestilo"
(glej spodaj — minimalen SW), nobene spremembe predloge (D41).

------------------------------------------------------------
MANIFEST — media/com_belauncerunde/manifest.webmanifest
------------------------------------------------------------
Statična JSON datoteka (ne generirana, Joomlin `sitename` se ne pretaka
vanjo — ime je fiksno besedilo, ker manifest mora biti servran kot
`application/manifest+json` brez PHP procesiranja):

{
  "name": "Runde Belaunce",
  "short_name": "Runde",
  "description": "Kolesarske runde Belaunce",
  "start_url": "/runde",
  "scope": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2a76e0",
  "lang": "sl-SI",
  "icons": [
    { "src": "images/ikona.svg", "sizes": "any", "type": "image/svg+xml", "purpose": "any" },
    { "src": "images/ikona-maskable.svg", "sizes": "any", "type": "image/svg+xml", "purpose": "maskable" }
  ]
}

Opomba (D60): komponenta teče na LOČENI namestitvi na poddomeni, zato
`scope: "/"` pokriva samo to poddomeno (ne glavne strani belaunce.cc) —
to je želeno. `start_url: "/runde"` predpostavlja menijsko postavko z
aliasom `runde` na TEJ namestitvi (kontrolni seznam v TASKS.md). Če je
alias drugačen, mora admin ročno popraviti to datoteko (zapiši v
NAVODILA.md). Če je Joomla nameščena v podmapi, popravi tudi `scope`.

------------------------------------------------------------
IKONE — media/com_belauncerunde/images/
------------------------------------------------------------
Ustvari DVE preprosti SVG ikoni (brez zunanjih orodij, samo besedilo):
- `ikona.svg`: kvadrat 512x512, polnilo `#2a76e0` (barva tipa MTB iz
  naloge 16, nevtralna privzeta), bela velika črka "B" na sredini
  (Belaunce), zaokroženi robovi (rx="64").
- `ikona-maskable.svg`: enako, a brez zaokroženih robov in z varnostno
  cono (logotip zavzema samo osrednjih ~80% širine/višine), ker
  maskable ikone sistem sam obreže v krog/kvadrat.
Ti dve SVG datoteki zadoščata za Android/Chrome/namizje. Za iOS
`apple-touch-icon` (zahteva PNG, SVG ni podprt):
- Ustvari `orodja/izdelaj-ikono.php` (enkratni razvojni pripomoček, PO
  VZORU `orodja/gradnja.sh` — NI del namestitve/runtime), ki z GD
  (`imagecreatetruecolor`, `imagefilledrectangle`, `imagettftext` ali
  preprosto `imagestring` če ni TTF) izriše 180x180 PNG z enako barvo
  ozadja in črko "B", in ga shrani v
  `paket/com_belauncerunde/media/com_belauncerunde/images/apple-touch-icon.png`.
  Zaženi ga enkrat lokalno, PNG rezultat COMMITAJ, skripto PUSTI v
  `orodja/` za poznejšo zamenjavo z resničnim logotipom. Če GD ni na
  voljo v tvojem okolju, generiraj PNG kako drugače (tudi ročno s
  preprostim orodjem) ali pusti enobarvni placeholder in v PR pod
  "Opažanja" izrecno napiši, da je ikona placeholder in naj jo Matija
  kasneje zamenja s pravim logotipom kluba (isti postopek velja za obe
  SVG ikoni — niso končna grafika, so funkcionalen placeholder).

------------------------------------------------------------
SERVICE WORKER — KRITIČNO (ne servraj kot statično datoteko)
------------------------------------------------------------
Service Worker mora pokrivati CEL SAJT (`scope: "/"`), ne samo mapo
`/media/com_belauncerunde/...`, kjer bi privzeto omejil svoj doseg na
to mapo. Rešitev: NE serviraj `sw.js` kot statične datoteke v `media/`.
Namesto tega naredi NOVO site komponentno pot, ki vrne JS vsebino z
glavo `Service-Worker-Allowed: /`:

1. Nov site view/task, npr. `index.php?option=com_belauncerunde&task=pwa.sw`
   (ali `view=sw&format=raw`, kar je skladno z obstoječim router
   vzorcem — izberi enostavnejše, brez SEF potreb za to pot).
2. Kontroler/view prebere statično vsebino iz
   `JPATH_ROOT . '/media/com_belauncerunde/js/sw-vsebina.js'` (fizična
   JS datoteka, NI servrana direktno — samo vir besedila), nastavi:
   `header('Content-Type: application/javascript');`
   `header('Service-Worker-Allowed: /');`
   nato `echo` vsebino in `$app->close();` (ali Joomlin ustrezen izhod
   za "raw" format, poglej primer v jedru za `format=raw` poglede, če
   obstaja — če ne, uporabi neposreden `header()` + `echo` + `exit`
   znotraj kontrolerja, dokumentiraj v PR, na katero jedrno datoteko
   6.1 si se oprl).
3. `sw-vsebina.js` (minimalna vsebina, BREZ push handlerjev):

   const CACHE = 'br-pwa-v1';
   self.addEventListener('install', (e) => {
     self.skipWaiting();
   });
   self.addEventListener('activate', (e) => {
     e.waitUntil(clients.claim());
   });
   self.addEventListener('fetch', (e) => {
     // SW sme posegati SAMO v navigacije (odpiranje strani) na istem izvoru
     // in zunaj /administrator. Vse ostalo (slike, skripte, zunanje vdelave
     // Strava/Komoot/Bikemap, pisave, POST obrazci) brskalnik obdela sam —
     // brez respondWith.
     const zahteva = e.request;
     const url = new URL(zahteva.url);
     if (zahteva.mode !== 'navigate' || zahteva.method !== 'GET'
         || url.origin !== self.location.origin
         || url.pathname.indexOf('/administrator') === 0) {
       return;
     }
     // Minimalen "network-first": offline ne prikaže cache vsebine,
     // samo prepreči, da bi se aplikacija sesula brez omrežja.
     e.respondWith(
       fetch(zahteva).catch(() => new Response(
         'Ni povezave. Poskusi znova, ko bo internet na voljo.',
         { status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' } }
       ))
     );
   });

   (Namenoma BREZ `push` ali `notificationclick` listenerjev — faza 2.)

4. Registracija v `namesti.js` (glej spodaj) kliče
   `navigator.serviceWorker.register(url, { scope: '/' })`, kjer je
   `url` pot do koraka 1 (absolutna, prek `Joomla.getOptions()` ali
   vdelanega `data-*` atributa, NE hardkodiran niz — glej vzorec
   `deli.js` iz naloge 11 za posredovanje podatkov iz PHP v JS brez
   inline <script>).

------------------------------------------------------------
NAMESTITVENI GUMB IN iOS NAMIG — media/js/namesti.js
------------------------------------------------------------
Vanilla JS (brez jQuery), IIFE, `defer`, registriran v
`joomla.asset.json` kot `com_belauncerunde.namesti` z odvisnostjo
`core`. Vedenje:

a) Registrira SW (glej zgoraj) na `DOMContentLoaded`, v `try/catch`;
   neuspeh (star brskalnik) se tiho prezre (`console.debug`, ne
   `alert`).
b) Poslušalnik `beforeinstallprompt`: `e.preventDefault()`, shrani
   dogodek v modulski spremenljivki, pokaže skrit gumb "Namesti
   aplikacijo" (razred `br-namesti-gumb`, viden samo zdaj — enak vzorec
   skritih gumbov kot `deli.js` iz naloge 11). Klik na gumb pokliče
   `deferred.prompt()`, po odločitvi gumb skrije ne glede na izid.
c) Če `window.matchMedia('(display-mode: standalone)').matches` ali
   `navigator.standalone === true` (iOS), gumb in namig se NE prikažeta
   (aplikacija je že nameščena/zagnana samostojno).
d) iOS Safari nima `beforeinstallprompt`: zaznaj iOS
   (`/iPad|iPhone|iPod/.test(navigator.userAgent)` in
   `!window.MSStream`) in Safari (ne Chrome/Firefox na iOS, ki nimajo
   dodaj-na-zaslon prek JS); če pogoj (b) ni sprožen in je to
   iOS+Safari, pokaži enkratni nevsiljiv namig "Dodaj na domači zaslon:
   pritisni Deli, nato 'Dodaj na domači zaslon'" z gumbom za zapiranje.
   Zapiranje shrani zastavico v `localStorage` (per-viewer udobje, v
   `try/catch`, glede na pravila iz artifacts/workspace o `localStorage`
   — tu je legitimna uporaba, ker gre za brskalnikovo lastno stran, ne
   za artifact).

------------------------------------------------------------
KLIC PwaHelper::pripni() — Site\Helper\PwaHelper
------------------------------------------------------------
Nova datoteka `site/src/Helper/PwaHelper.php`:

   public static function pripni(\Joomla\CMS\Document\Document $document): void
   {
       $document->addHeadLink(
           Uri::root(true) . '/media/com_belauncerunde/manifest.webmanifest',
           'manifest'
       );
       $document->setMetaData('theme-color', '#2a76e0');
       $document->setMetaData('apple-mobile-web-app-capable', 'yes');
       $document->setMetaData('apple-mobile-web-app-title', 'Runde');
       $document->addHeadLink(
           Uri::root(true) . '/media/com_belauncerunde/images/apple-touch-icon.png',
           'apple-touch-icon'
       );
       $document->getWebAssetManager()->useScript('com_belauncerunde.namesti');
   }

Dodaj klic `PwaHelper::pripni($this->getDocument());` v `display()` ali
`populateState()`/na mestu, kjer te view že kličejo
`getWebAssetManager()->useStyle('com_belauncerunde.pisave')`, v VSEH
naslednjih site view razredih:
  - `site/src/View/Runde/HtmlView.php`
  - `site/src/View/Runda/HtmlView.php`
  - `site/src/View/Moje/HtmlView.php`
  - `site/src/View/Obrazec/HtmlView.php`
  - `site/src/View/Prijava/HtmlView.php`
  - `site/src/View/Odjavagosta/HtmlView.php`
  - `site/src/View/Povezava/HtmlView.php`

(Vsak dobi eno dodatno vrstico; to NI poseg v predlogo, D41 ostaja
veljaven — vse gre skozi komponento.)

------------------------------------------------------------
MERILA SPREJEMA
------------------------------------------------------------
1.  `manifest.webmanifest` je dosegljiv na pričakovani poti in vsebuje
    veljaven JSON (preveri z `jq` ali brskalnikovim DevTools →
    Application → Manifest).
2.  Chrome DevTools → Application → Manifest ne pokaže napak/opozoril
    (razen pričakovanih o placeholder ikonah, če je relevantno).
3.  Service worker se registrira s `scope: "/"` in glavo
    `Service-Worker-Allowed: /` (preveri v Network tab odziv na pot iz
    naloge, korak "SERVICE WORKER").
4.  Chrome (namizje/Android): ikona "Namesti" se pojavi v naslovni
    vrstici IN/ALI `beforeinstallprompt` sproži prikaz gumba
    "Namesti aplikacijo"; po namestitvi se aplikacija zažene v
    `standalone` načinu (brez naslovne vrstice brskalnika).
5.  Gumb "Namesti aplikacijo" se NE prikaže, če je aplikacija že
    nameščena/zagnana v standalone načinu.
6.  iOS Safari: namig za "Dodaj na domači zaslon" se prikaže enkrat,
    zapiranje ga ne prikaže znova (localStorage).
7.  Offline (DevTools → Network → Offline): navigacija na stran
    komponente prikaže besedilo iz fallback odgovora SW (503, "Ni
    povezave…"), ne surove brskalnikove napake. Zahteve, ki niso
    navigacije, niso na istem izvoru ali so pod /administrator, SW NE
    prestreže (v Network tabu niso "from ServiceWorker"); /administrator
    in zunanje vdelave trase delujejo kot prej.
8.  BREZ push: noben `push` ali `notificationclick` listener, nobena
    zahteva za `Notification.requestPermission()`, nobena nova tabela
    naročnin.
9.  D41 ni kršen: brez sprememb v `templates/`.
10. `gradnja.sh` uspe (php -l, xmllint, prepovedani vzorci) — zažene
    arhitekt.

------------------------------------------------------------
ROČNI TEST ZA MATIJO (DDEV)
------------------------------------------------------------
1. Namesti 0.17.0 (ukazi spodaj).
2. Chrome DevTools → Application → Manifest: preveri ime, ikone, barve
   brez napak.
3. Chrome DevTools → Application → Service Workers: `sw` je
   registriran, "Update on reload" izklopljen za realističen test.
4. Na strani `/runde`: klikni "Namesti aplikacijo" (ali ikono v naslovni
   vrstici) → namestitev uspe → aplikacija se zažene v lastnem oknu.
5. Telefon (Android Chrome, prek `ddev share` ali kopije produkcije, ker
   DDEV tunel vrača 421 — enako omejitev kot naloga 11): "Dodaj na
   začetni zaslon" se ponudi, ikona in ime sta pravilna.
6. iPhone Safari: namig za dodajanje na domači zaslon se prikaže,
   zaprtje ga ne prikaže znova po ponovnem obisku.
7. DevTools → Network → Offline → ponovno naloži stran: brez surove
   brskalnikove napake.
8. Preveri, da NI zahteve za dovoljenje za obvestila (`Notification.
   requestPermission` se nikjer ne kliče).

------------------------------------------------------------
NAMESTITEV NA DDEV (Matija)
------------------------------------------------------------
cd ~/belaunce-runde && git checkout develop && git pull && bash orodja/gradnja.sh
ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1)
cp "$ZIP" ~/joomla-dev/tmp/
cd ~/joomla-dev
ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"

------------------------------------------------------------
VPRAŠANJA ZA ARHITEKTA / OPAŽANJA
------------------------------------------------------------
Zapiši v PR, če Joomla 6.1 `format=raw` pogled zahteva drugačen pristop
za nastavitev glave `Service-Worker-Allowed` kot opisano zgoraj (navedi
jedrno datoteko, na katero si se oprl), ali če GD/druga pot do PNG
ikone ni bila na voljo v tvojem okolju — v tem primeru pusti placeholder
in izrecno navedi omejitev.
