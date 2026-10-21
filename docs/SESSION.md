# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-21
**Fokus seje:** NALOGA 11 (Deli v Viber) — pregled in test na DDEV
**Stanje:** NALOGA 11 ✅ združena (PR #11). NALOGA 12 naslednja (specifikacijo pripravi arhitekt).

---

## Kje smo ostali

- **NALOGA 11** (0.11.0): vsa preverljiva merila ✅ na DDEV, brez popravkov kode po pregledu. Gumb „Deli v Viber" (`viber://forward?text=…`), sistemsko deljenje (`navigator.share`), polje z besedilom za kopiranje in nastavljiva predloga v *Možnosti → Viber*. Open Graph je iz naloge 06.
- Potrjeno v brskalniku z resničnim izpisom: blok se pokaže samo na objavljeni rundi pred štartom (stanje 1, pred zacetek), viden je vsem obiskovalcem (D59); odpovedana, štartana in končana runda nimajo bloka ne `viber://` ne `deli.js`. Besedilo ima pravilen dan/datum/uro v zimskem in poletnem času; povezava je enaka canonical in `og:url`. `decodeURIComponent(href)` se ujema s poljem (emoji, šumniki, prelomi, brez `+`). Naslov z `<b>`, `"` in `&` je escapiran, `href` ostane cel. Besedilo nikoli ne vsebuje imen udeležencev, gostov, kontaktov, opomb ali trase (preverjeno kot vodja runde z gostom). Kopiranje izpiše potrditev v `aria-live`, brez clipboard API označi besedilo; sistemsko deljenje pošlje samo `{text}`, preklic je tih. Prazna predloga vrne privzetek, lastna predloga z emojiji se shrani in uporabi, neznana oznaka ostane dobesedna.
- **Merilo 14 (pravi telefon z Viberjem) PRELOŽENO** na test na kopiji produkcije: DDEV `ddev share` (cloudflared, naključni gostitelj) vrača 421 Misdirected Request, zato se stran prek tunela ne postreže. Dodano na kontrolni seznam pred zagonom.

### Ključne rešitve naloge 11 (za pregled prihodnjih sprememb)

- **`Site\Helper\DeljenjeHelper`**: `besedilo()` zamenja zaprt seznam oznak z `strtr()` v enem prehodu (oznake znotraj vrednosti se ne razširijo), čas pretvori izključno `CasHelper::izUtc()` + `Date(..., 'UTC')->format(..., false)` (brez dvojne pretvorbe). `viberPovezava()` uporabi `rawurlencode()` (%20, %0A) in povezave NIKOLI ne pošlje skozi `Route::_()` ali `Uri`.
- **Deljivost** (stanje 1 in ne startala) izračuna strežnik (pogled), ne JavaScript; neodvisna od identitete. Stran runde uporabi isto spremenljivko `$kanonicna` kot canonical in `og:url`.
- **Predloga `deli.php`**: vse skozi `$this->escape()`, prvi LF za začetno `<textarea>` je namerno zavržen (ohrani začetni prelom predloge). Gumba sta `hidden`, dokler ju JS ne pokaže.
- **`deli.js`** (asset `com_belauncerunde.deli`, `defer`, odvisnost `core`): vanilla IIFE, samo `textContent`, `AbortError` tiho prezrt, brez `execCommand`. Sistemsko deljenje brez ločenega `url`, da povezave ne podvoji.
- **Moje runde**: `MojeModel` dobi samo dodatni stolpec `trajanje_min`; brez novih poizvedb (meja treh ohranjena). Povezava „Deli v Viber" je samo na deljivih prihajajočih karticah obeh list.
- Privzeto besedilo in obliki datuma/ure so v SITE jezikovnih datotekah (`COM_BELAUNCERUNDE_VIBER_PRIVZETO`, `_VIBER_DATUM_OBLIKA`, `_VIBER_URA_OBLIKA`); `\n` v `.ini` pretvori `Language::_()`.

## Način dela

Nova seja z arhitektovim pregledom (gradnja + `php -l` + preverjanje jedra) in testom v brskalniku (Claude in Chrome), Matija na SQL in administraciji ter združevanju PR-jev. Tehnike, ki so se obnesle:
- **Priprava prejemnikov/stanja prek SQL** (vodja, gost, odprto za goste na testno rundo), nato ogled skozi pravi frontend tok — resnični izpis, ne izolirana predpostavka. Po testu stanje vrnjeno nazaj.
- **Branje strani prek `fetch()` + `DOMParser`** v konzoli je hitrejše od klikanja; za interakcije (klik „Kopiraj", lažni `navigator.share`/izklop `clipboard`) pa prava dejanja na strani.
- Nestandardne sheme (`viber://`) ni mogoče odpreti ne testirati v namiznem brskalniku; pravi test je samo na telefonu na javni domeni.

## Naslednji korak (nova seja)

1. Preberi `docs/SESSION.md`, `TASKS.md`, `osnova-projekta.md`, `ARCHITECTURE.md`, `AGENTS.md`.
2. Matija preda **NALOGO 12** (Scheduler: sinhronizacija članov [odvzem/vrnitev skupine „Člani", nikoli blokada — D47, D52, D53], izbris gostov 30 dni, čiščenje žetonov in poskusov) Codexu (veja `feature/NALOGA-12-scheduler`).
3. Arhitekt napiše specifikacijo naloge 12 (vir: osnova-projekta.md 6, 6.1, 9, 18.5; D16, D47, D52, D53; ARCHITECTURE pravili 22 in 24 za dnevnik izven konteksta komponente; pripravljeni metodi `ZetonService::pociscenje()` in `OmejitevHelper` iz naloge 04), pregleda PR, zažene gradnjo s PHP, testira na DDEV.
4. Test na DDEV (posodobitev 0.11.0 → 0.12.0).

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, predpona `dipxn_`. Mailpit: `https://joomla-dev.ddev.site:8026`. Repozitorij je v `~/belaunce-runde`.
- Namestitev prek CLI: `ddev exec php cli/joomla.php extension:install --path=/var/www/html/tmp/pkg_belauncerunde-X.Y.Z.zip` (ZIP najprej kopiraj v `~/joomla-dev/tmp/`).
- `ddev share`: ngrok ni nameščen; nameščen cloudflared (provider `cloudflared.sh`), a naključni `trycloudflare.com` gostitelj vrne 421 (DDEV usmerjevalnik ga ne pozna). Za test telefona na DDEV bi bil potreben poimenovan cloudflare tunel (račun); sicer test na kopiji produkcije.
- Mail predlogi komponente: `com_belauncerunde.runda_sprememba`, `com_belauncerunde.runda_odpoved`, `com_belauncerunde.gost_potrditev`, `com_belauncerunde.prijava`.
- **Stanje testnih podatkov po nalogi 11:** runda 5 „testna" vrnjena v prvotno stanje (vodja 631, naslov „testna", odprto_za_goste 0, testni gost pobrisan; lokacija „Žiri"). Runda 8 „Omejitev tri" ostaja ODPOVEDANA (stanje 2). Runde 1, 2, 4, 6, 7 kot prej. *Možnosti → Viber → Predloga besedila* je prazna (privzetek). Testni član 636 še obstaja (sinhronizacija skupine je naloga 12).
- Možnosti: skupina Člani 10, lista članov 6, lista Runde 9, privzeto trajanje 180, omejitev rund na dan 3 (test), omejitev gostov 2, captcha izklopljena, sprožilna polja maila zacetek+lokacija.
- Admin: 631 (`info@mtdesign.si`, prikazno ime „Matija"). Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635** (`testtujec`). Testni član: **636** (`test-prazen@belaunce.cc`).
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- **Naloga 11, merilo 14:** deljenje v Viber na pravem telefonu (odpre Viber s pripravljenim besedilom; Open Graph predogled) — potrditi na kopiji produkcije pred zagonom.
- Bikemap vdelava (naloga 07) še ni preizkušena na pravi poti; pred izidom jo je treba potrditi ali ponudnika izklopiti.
- `GOST_CAPTCHA_MANJKA` se zapiše dvakrat na oddajo — kozmetično (naloga 08).
- Ali AcyMailing reagira tudi na spremembo **skupine** uporabnika (ne samo blokade) — preveriti pred nalogo 12.
- Predloga j4starter ne izpisuje sistemskih sporočil ne debug plošče (D41); rešitev je naloga 17 (otroška predloga).
- Merilo 20 naloge 04 (test odstranitve paketa) in zamenjava zastarelega `getError()` sta preložena na nalogo 14.
- Videz: niz dolžine „40.0 km" in velika začetnica imena dneva — za nalogo 16 (vizualni pregled).
- Ob zaključku v1: povzetek načina dela (naloga 15 v `TASKS.md`).

## Opomba o menjavi seje

Nova seja nadaljuje pri nalogi 12; ves kontekst je v `docs/`. Chrome ostane prijavljen (zadnji uporabnik 634 Matija), DDEV in testni podatki ostanejo v zgornjem stanju.
