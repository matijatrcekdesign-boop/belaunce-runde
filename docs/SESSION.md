# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-27
**Fokus seje:** NALOGA 21 (ohrani izbrano AcyMailing listo) — pregled, test na DDEV, merge
**Stanje:** NALOGA 21 ✅ združena (PR #18, `e52ae20`, 0.17.1). Naslednja: **NALOGA 19** (PWA brez push, 0.18.0) — specifikacija pripravljena v `docs/naloge/NALOGA-19.md`, čaka predajo Codexu.

---

## Kje smo ostali

- **NALOGA 18** (0.17.0, PR #17) in **NALOGA 21** (0.17.1, PR #18) sta obe združeni in testirane na DDEV. Povzetki v TASKS.md → Končano.
- **NALOGA 21 test (DDEV, prek Chroma, Matija prijavljen):** privzeto stanje nespremenjeno (lista 6, 9); nedosegljiva baza → obe izbrani listi ostaneta z oznako "ID n – trenutno ni na voljo (ohranjeno)"; ponovno shranjevanje v istem stanju ohrani izbiro; vrnitev na prazni polji → pravi seznam, 6 in 9 spet izbrana brez izgube; Preveri člana po vrnitvi → Član. Neobvezna točka 5 (ID 999 prek SQL) ni bila izvedena — lahko se opravi kadarkoli, ni blokersko.
- **D60–D63 so odslej vsi pokriti v kodi**: D60/D61 nalogo 18, D62 naslednja naloga 19, D63 naloga 20 (prejšnja seja).

## Naslednji korak

1. Matija preda **NALOGO 19** Codexu (specifikacija `docs/naloge/NALOGA-19.md`, dopolnjena po D60: SW obdeluje samo navigacije na istem izvoru zunaj `/administrator`; verzija 0.18.0).
2. Arhitekt pregleda PR (diff proti specu, `php -l`, `xmllint`, `gradnja.sh`), test na DDEV v brskalniku (Application → Manifest, Service Workers, Network → Offline) po ročnem testu iz specifikacije. Merila 4–6 (namestitev na pravem telefonu/iOS) zahtevajo kopijo produkcije — DDEV tunel vrača 421 (enako kot naloga 11).

## Testni podatki po seji (nepovrnjeno / stanje)

- DDEV Možnosti: `acym_baza` in `acym_predpona` prazni, lista članov = ID 6, lista Runde = ID 9 (privzeto stanje, nespremenjeno od prejšnje seje).
- Testna baza `oddaljena_test` je bila pobrisana v prejšnji seji (naloga 18).

## Način dela (povzetek, poln dokument v `docs/NACIN-DELA.md`)

3 vloge: **Matija** (testira na DDEV, združuje PR-je, edini pisec v `osnova-projekta.md`) · **Codex** (implementira eno nalogo, commita, odpre PR) · **arhitekt/Claude** (specifikacije, pregled PR-jev, test na DDEV, zaključna dokumentacija). Arhitekt NIKOLI ne pusha in ne piše produkcijske kode. Naloge Codexu kot **en plain-text blok v code box**. Zaključna dokumentacija kot **ZIP, ki se razširi z `-d docs/`**, Matija commita.

Praktično: DDEV v vgrajenem brskalniku Claude aplikacije ne deluje (lokalni certifikat) — za test na DDEV se uporablja **Claude in Chrome**; Matija se v admin prijavi sam (arhitekt gesel ne vpisuje), nato arhitekt preostanek testa izvede prek `fetch()` v konzoli strani (hitrejše in bolj zanesljivo kot klikanje).

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, baza `db`, predpona `dipxn_`. Mailpit `https://joomla-dev.ddev.site:8026`. Repozitorij `~/belaunce-runde` (GitHub `matijatrcekdesign-boop/belaunce-runde`, javen).
- Univerzalna namestitev (katerakoli veja/verzija):
  `git checkout <veja> && git pull && bash orodja/gradnja.sh`, nato
  `ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1); cp "$ZIP" ~/joomla-dev/tmp/; cd ~/joomla-dev; ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"`.
- Admin: 631 (`info@mtdesign.si`). Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635**. Testni uporabnik: **636**.
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- **Naloga 18, produkcija:** ali gostitelj dovoli branje `information_schema` za tujo bazo in ali ima DB uporabnik poddomene `SELECT` na AcyMailing bazi — preveriti na kopiji produkcije.
- **Naloga 19, merila 4–6:** namestitev na pravem telefonu in iOS Safari — na kopiji produkcije (DDEV tunel vrača 421, enako kot naloga 11, merilo 14).
- **Naloga 11, merilo 14:** Viber na pravem telefonu — na kopiji produkcije pred izidom.
- **Bikemap vdelava (naloga 07):** potrditi na pravi poti ali ponudnika izklopiti pred izidom.
- **Dvojniki mail predlog:** preveriti, da registracija ne ustvari dvojnikov (mini-popravek, ni blokersko).
- Predloga j4starter ne izpisuje sistemskih sporočil (D41) → naloga 17 (po izidu v1).
- Povzetek načina dela (naloga 15) — osnutek `docs/NACIN-DELA.md`, dokončati ob izidu.
