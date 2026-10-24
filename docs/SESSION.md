# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-24
**Fokus seje:** NALOGA 16 (vizualni pregled, videz smeri C + barva tipa iz backenda) — pregled in test na DDEV
**Stanje:** NALOGA 16 ✅ združena (PR #15, verzija 0.15.6). V1 je funkcijsko dokončan; sledita priprava na izdajo in nalogi 17, 15.

---

## Kje smo ostali

- **NALOGA 16** (0.15.6): vsa merila ✅, iterativno dodelano z Matijo prek vizualnih predlogov (artifact mockup). Enoten videz vseh javnih strani komponente, usklajen s komponento com_kisegonma na belaunce.cc.
- Potrjeno v brskalniku (Claude in Chrome) in s strani Matije:
  - **Videz smeri C:** pisavi belaunce.cc (Lexend naslovi/gumbi, Archivo besedilo, naloženi prek WebAssetManagerja z Google Fonts), čipi, kvadratne kartice z barvnim pasom tipa, merilnik težavnosti (modra, rahel odtenek), ploščice statistike na podstrani, razdelčni naslovi, krogec vodje z začetnico.
  - **Barva tipa iz backenda:** nov stolpec `barva CHAR(7)` v `#__belaunce_tipi`, izbirnik barve (Joomla color field) v urejanju tipa, stolpec v seznamu tipov; seed MTB #2a76e0 / Cestno #f2b974 / Gravel #8f592f. Frontend izpiše `style="--br-type:#…"` iz **validiranega hex-a** + `--br-type-text` iz **samodejnega kontrasta** (čista #000/#fff, po višjem razmerju → AA pri vseh barvah; MTB dobi črno).
  - **Dodatki:** gumb „Odpri rundo" na vsaki kartici; „Nazaj na runde" na vrhu podstrani (spodnja ponovitev odstranjena); povezava po prijavi/magic linku pelje na seznam rund (gumb); poenoteni gumbi in filtri (primarni v barvi besedila, lasten slog spustnikov); dolžina po slovensko („40 km"/„40,5 km").
  - **Članstvo:** možnost `clanstvo_url` (Joomla url polje, relative); gumb „Postani član" le ob **varni** povezavi (zavrne krmilne znake, `\`, `//`, `javascript:`/`data:`; zunanje v nov zavihek), sicer samo besedilo.
  - **Mobilni offcanvas filtri:** gumb „Filtriraj" odpre stransko ploščo; na namizju so filtri v toku. Ker j4starter Bootstrap NIMA `.offcanvas-md`, uporablja navaden `.offcanvas` + lasten namizni reset `@media (min-width:768px)` (sicer presledek na namizju — popravljeno).

### Ključne rešitve naloge 16 (za pregled prihodnjih sprememb)

- **`Site\Helper\VidezHelper`:** `barvaTipa()` (validiran hex, sicer privzeto #6c757d), `kontrastBesedila()` (čista #000/#fff po višjem kontrastu), `dolzina()` (slovensko), `rangTezavnosti()`, `povezavaClanstva()` (varen URL: http/https z gostiteljem ali notranja pot; zunanje v nov zavihek).
- **Barva samo prek CSS spremenljivke iz validiranega hex-a**, nikoli surova vrednost baze v `style`. Barve tipov NISO v site.css kot razredi.
- **Idempotentna zagotovitev stolpca `barva`** v `skript.php` (postflight: preveri `information_schema`, doda, nastavi seed) — zato ponovna namestitev iste verzije ne pusti manjkajočega stolpca.
- **Deljeni delci** `kartica.php` (seznam + Moje runde) in `tezavnost.php`; logika prikaza (član/nečlan/vodja, akcije, gost, deljenje) NESPREMENJENA.
- **Offcanvas:** j4starter Bootstrap nima `.offcanvas-{breakpoint}`; zato navaden `.offcanvas` + lasten namizni reset v site.css.

## Način dela

Pri videzu je pregled potekal iterativno: arhitekt je pripravil vizualne predloge kot HTML artifact (tri smeri → dodelana smer C), Matija je izbiral in dodajal želje; manjše vizualne dodelave je Matija pošiljal Codexu neposredno, arhitekt pa je ob koncu opravil celovit pregled kode (gradnja + `php -l` + jedro) in **vizualni pregled v brskalniku** (DOM/computed styles, ker se posnetki zaslona v tem okolju ne zajamejo). Kontrast in offcanvas sta bila diagnosticirana prek izmerjenih razmerij in computed `transform`.

**Dogovor za naprej:** čiste vizualne želje lahko Matija pošilja Codexu direktno (ob priloženih stalnih vizualnih pravilih — br-, brez inline CSS, dostopnost AA, pisavi, verzija). Karkoli se dotakne logike/varnosti/sheme/Joomla API-ja/dostopnosti in **vsak PR pred združitvijo** gre skozi arhitekta.

## Naslednji korak (nova seja)

V1 je funkcijsko dokončan (nalogi 01–14 in 16 ✅). Preostane:
1. **Priprava na izdajo v1** — kontrolni seznam pred zagonom na produkciji (`TASKS.md`): test namestitve na kopiji produkcije (MariaDB 10.11, utf8mb3), AcyMailing nastavitve, menijske postavke (`runde`, `prijava`, `moje`), SMTP/SPF/DKIM, cron za Scheduler, Akeeba backup, dnevnik brez IP, piškotki, ter preložena potrjevanja: **naloga 11 merilo 14** (Viber na telefonu), **Bikemap vdelava** (naloga 07).
2. **NALOGA 17** — otroška predloga (child template j4starter) za `<jdoc:include type="message" />` (D41) in po potrebi lasten slog. **Po izidu v1.**
3. **NALOGA 15** — povzetek načina dela (dokument, arhitekt z Matijo).
4. Odprta točka: možni dvojniki mail predlog (preveriti registracijo) — ni blokersko.

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, predpona `dipxn_`. Mailpit `:8026`. Repozitorij v `~/belaunce-runde`.
- Univerzalna namestitev (katerakoli veja/verzija):
  `git checkout <veja> && git pull && bash orodja/gradnja.sh`, nato
  `ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1); cp "$ZIP" ~/joomla-dev/tmp/; cd ~/joomla-dev; ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"`.
- Pisavi Lexend/Archivo na belaunce.cc že naloženi (bunny.net); komponenta jih naloži še prek Google Fonts, da se vidita tudi na DDEV.
- Scheduler: ročno opravilo „Belaunce vzdrževanje" (dnevno, *Run*). Produkcija: pravi cron.
- `ddev share`: cloudflared, a `trycloudflare.com` vrne 421; test telefona (naloga 11) na kopiji produkcije.
- **Stanje testnih podatkov:** tipi imajo barve (mtb/cestno/gravel). Član 634 je član s skupino „Člani" (ID 10), na listi 6. Runda 8 ODPOVEDANA. Testni uporabnik 636 brez vrstice v `#__belaunce_clani`. (Naslov runde 5 je bil med starejšim testom „Test; z narekovaji in =SUM(1)" — po želji povrni na „testna".)
- Admin: 631 (`info@mtdesign.si`). Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635**. Testni uporabnik: **636**.
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- **Naloga 11, merilo 14:** Viber na pravem telefonu — potrditi na kopiji produkcije pred izidom.
- **Bikemap vdelava (naloga 07):** potrditi na pravi poti ali ponudnika izklopiti pred izidom.
- **Dvojniki mail predlog:** preveriti, da registracija ne ustvari dvojnikov (mini-popravek, ni blokersko).
- `GOST_CAPTCHA_MANJKA` dvakrat na oddajo — kozmetično (naloga 08).
- Predloga j4starter ne izpisuje sistemskih sporočil ne debug plošče (D41) → naloga 17.
- Izbirnik razvrščanja na seznamu (novejše/starejše) — predlagano, zaenkrat NE delamo (javni seznam je samo prihajajoče, naraščajoče po začetku).
- Ob zaključku v1: povzetek načina dela (naloga 15).

## Opomba o menjavi seje

Nova seja nadaljuje s pripravo na izdajo v1 (kontrolni seznam) oz. nalogo 17/15; ves kontekst je v `docs/`. Chrome ostane prijavljen (uporabnik 634), DDEV in testni podatki v zgornjem stanju.
