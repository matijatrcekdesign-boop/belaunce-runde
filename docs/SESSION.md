# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-11-12
**Fokus seje:** App-videz celotne komponente in predloge, ikona aplikacije, OG slika, paket 0.19.0
**Stanje:** Komponenta 0.19.0 je v živo na `runde.belaunce.cc` (datoteke urejene neposredno prek FF Explorer) in na GitHubu v `develop`. Paket `pkg_belauncerunde-0.19.0.zip` je zgrajen (`orodja/gradnja.sh`), SHA-256 v `posodobitve.xml`. Matija je pregledal delovanje kot član in potrdil, da deluje.

---

## Kje smo ostali

- **0.19.0 (2026-11-12)** — mobile-first »app« videz po canvasu (Claude Design artefakt): seznam rund, podrobnost runde (jantarni baner z datumom, kartica trase, statistika, vodja, opombe, lepljiva vrstica »Pridem na rundo«), deli (Viber/Deli/Kopiraj), prijava, Moje runde, obrazec za rundo, spodnja navigacija (Runde · Moje runde · Ustvari), glava (nazaj-puščica + logo | RUNDE). Podrobnosti: `docs/PRODUKCIJA-runde.md` (razdelek »App videz 0.19.0«).
- **Predloga `belauncetemplate` NI v repozitoriju** — spremembe (`index.php`, `style.css`) obstajajo samo na produkciji. Kopija je v `docs/predloga-belauncetemplate/` (referenca, ne del paketa).
- **Ikona aplikacije:** PNG 192/512, maskable 512, apple-touch 180 (črka B z balanco iz logotipa, bela podlaga, modri rob). Manifest uporablja PNG, `theme_color` #6ab7e3.
- **Meta za deljenje:** `og:image` 1200×630 = `/images/og-runde.png` (samo na strežniku, ni v paketu), `og:title`, `twitter:card` na seznamu in podrobnosti.
- **Izbirnik datuma:** JCLOSE »Potrdi«, JCLEAR »Počisti« (jezikovna datoteka komponente), večja modra ikona koledarja.
- **Trasa (Strava/Komoot):** neinteraktivna kartica; dotik odpre pot v novem zavihku.
- **Paket:** `dist/pkg_belauncerunde-0.19.0.zip` (ni v gitu). GitHub Release `v0.19.0` — glej `docs/TASKS.md`.

## Naslednji korak

Vrstni red in opisi v `docs/TASKS.md` (naloge 23–29) in v `docs/PROMPT-nova-seja.md`. Na kratko:
1. Push obvestila (faza 2, naloga 24) — odločeno, da je naslednja faza.
2. Predloga v repozitorij / otroška predloga (naloga 25 + 17), OG slika in ikone v paket, vektorski logotip (naloga 26).
3. QA na pravih napravah (iOS Safari, Viber predpomnilnik) (naloga 27), preusmeritve starih povezav (28).

Odprto po izidu: naloga 15 (povzetek načina dela), naloga 17 (otroška predloga, D41).

## Način dela (povzetek, poln dokument v `docs/NACIN-DELA.md`)

3 vloge: **Matija** (testira, združuje PR-je, edini pisec v `osnova-projekta.md`) · **Codex** (implementira eno nalogo, commita, odpre PR) · **arhitekt/Claude** (specifikacije, pregled PR-jev, test, zaključna dokumentacija). Arhitekt NIKOLI ne pusha in ne piše produkcijske kode. Naloge Codexu kot **en plain-text blok v code box**. Zaključna dokumentacija kot **ZIP, ki se razširi z `-d docs/`**, Matija commita.

Praktično (iz te seje): produkcijski admin se ureja prek **Claude in Chrome** (Matija se prijavi sam; arhitekt gesel ne vpisuje). Admin operacije arhitekt izvaja prek `fetch()`/živih obrazcev v konzoli strani. Na produkciji: datotečne/varnostne operacije (`.htaccess`, WAF) z Admin Tools, ki ob napaki samodejno razveljavi `.htaccess`; tveganih možnosti (skrivni URL, htpasswd, admin IP whitelist) se arhitekt izogne ali jih izrecno potrdi z Matijo.

## Okolje

- **Produkcija:** `runde.belaunce.cc` — Joomla 6.2.0, ločena namestitev; AcyMailing na glavni `belaunce.cc` (baza `vft26375_belaunce`, predpona `dipxn_`).
- **DDEV** `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, baza `db`, predpona `dipxn_`, Mailpit `:8026`. Repozitorij `~/belaunce-runde` (GitHub `matijatrcekdesign-boop/belaunce-runde`, javen).
- Namestitev paketa (DDEV): `git checkout <veja> && git pull && bash orodja/gradnja.sh`, nato `ddev exec php cli/joomla.php extension:install --path=...`. Na produkciji: naloži ZIP prek admina.
- Testni član: `matija.trcek@gmail.com` (AcyMailing 238).
- Codex (ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- Viber predpomnilnik predogleda: že deljene povezave imajo staro sliko; nove povezave bi morale dobiti `og-runde.png` (preveri na telefonu).
- iOS Safari: lepljive vrstice (`.br-sticky`, `.br-obrazec` akcijska vrstica, spodnja navigacija), izbirnik datuma, PWA namestitev — preveri na iPhonu.
- Ali želimo `og-runde.png` v paketu (`media/images`) namesto v `/images/` (zdaj `PwaHelper`/`HtmlView` kažeta na `/images/og-runde.png`)?
- Logotip je na voljo samo kot 200 px PNG — ikona in OG slika sta zato rahlo mehki; potreben SVG/PDF.
- Push obvestila (naloga 24): obseg sprožilcev potrditi z Matijo (nova prijava vodji, odpoved/sprememba runde udeležencem, opomnik).
- Predloga j4starter in sistemska sporočila (D41) → naloga 17.
- Povzetek načina dela (naloga 15) — dokončati z Matijo.
