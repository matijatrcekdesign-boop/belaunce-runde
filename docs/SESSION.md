# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-27
**Fokus seje:** NALOGA 19 (PWA brez push) — pregled, test na DDEV, merge
**Stanje:** NALOGA 19 ✅ združena (PR #19, `691eb0b`, 0.18.0). Naslednja: **NALOGA 22** (0.18.1, PWA nizi prek jezikovnih ključev) — specifikacija pripravljena, čaka predajo Codexu. Po njej je v1 funkcijsko zaokrožen; ostaneta test na živo (merila 4–6 naloge 19) in naloga 15 (povzetek načina dela).

---

## Kje smo ostali

- **NALOGA 19** (0.18.0, PR #19) združena 2026-10-27. Povzetek v TASKS.md → Končano. Pregled: diff proti specu ✅, `php -l` ✅, `xmllint` ✅, JSON/SVG validnost ✅, `gradnja.sh` ✅. Test na DDEV (arhitekt, Chrome): manifest veljaven in sprejet kot namestljiv (beforeinstallprompt), SW `scope: "/"` + `Service-Worker-Allowed: /`, brez push, ikone dosegljive, offline → „Ni povezave…" (Matija potrdil). Merila 4–6 (telefon, iOS) na živo.
- **Ugotovitev pri pregledu PR #19 → NALOGA 22:** `namesti.js` ima tri uporabniku vidne nize trdo zapisane v slovenščini (gumb, iOS namig, aria-label). Uveljavljeni vzorec (naloga 11, `deli.js`) uporablja `Joomla.Text._()` + `Text::script()` + sl-SI/en-GB. Matija je izbral: združi PR #19 zdaj, nize uskladi kot mini-nalogo 22 (0.18.1). Specifikacija: `docs/naloge/NALOGA-22.md`.
- **Odločitve:** D60–D63 vse pokrite v kodi (18, 19, 20). V1 obseg nalog je s tem skoraj zaključen.

## Naslednji korak

1. Matija preda **NALOGO 22** Codexu (specifikacija `docs/naloge/NALOGA-22.md`, 0.18.1).
2. Arhitekt pregleda PR, test na DDEV (sl-SI/en-GB besedila gumba in namiga).
3. Pred izidom v1: ročni test merila 7 že potrjen; merila 4–6 (naloga 19) na kopiji produkcije; naloga 15 (povzetek načina dela) z Matijo; naloga 17 (otroška predloga, D41) po izidu.

## Testni podatki po seji (nepovrnjeno / stanje)

- DDEV Možnosti: `acym_baza`/`acym_predpona` prazni, lista članov = ID 6, lista Runde = ID 9 (privzeto).
- V Matijevem Chromu je za `joomla-dev.ddev.site` registriran service worker (scope `/`, network-first brez predpomnjenja). Po želji odstranljiv v DevTools → Application → Service Workers. Po namestitvi 0.18.0 iz develop je tako ali tako prisoten.

## Način dela (povzetek, poln dokument v `docs/NACIN-DELA.md`)

3 vloge: **Matija** (testira na DDEV, združuje PR-je, edini pisec v `osnova-projekta.md`) · **Codex** (implementira eno nalogo, commita, odpre PR) · **arhitekt/Claude** (specifikacije, pregled PR-jev, test na DDEV, zaključna dokumentacija). Arhitekt NIKOLI ne pusha in ne piše produkcijske kode. Naloge Codexu kot **en plain-text blok v code box**. Zaključna dokumentacija kot **ZIP, ki se razširi z `-d docs/`**, Matija commita.

Praktično: DDEV v vgrajenem brskalniku Claude aplikacije ne deluje (lokalni certifikat) — test na DDEV prek **Claude in Chrome**; Matija se v admin prijavi sam. Pri PWA je arhitekt preveril manifest, SW glave in registracijo prek `fetch()`/`navigator.serviceWorker` v konzoli strani; offline render in pravo napravo potrjuje Matija.

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, baza `db`, predpona `dipxn_`. Mailpit `https://joomla-dev.ddev.site:8026`. Repozitorij `~/belaunce-runde` (GitHub `matijatrcekdesign-boop/belaunce-runde`, javen).
- Univerzalna namestitev (katerakoli veja/verzija):
  `git checkout <veja> && git pull && bash orodja/gradnja.sh`, nato
  `ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1); cp "$ZIP" ~/joomla-dev/tmp/; cd ~/joomla-dev; ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"`.
- Admin: 631 (`info@mtdesign.si`). Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635**. Testni uporabnik: **636**.
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- **Naloga 19, merila 4–6:** namestitev na telefonu in iOS Safari namig — na kopiji produkcije (DDEV tunel 421).
- **Naloga 18, produkcija:** `information_schema` za tujo bazo + `SELECT` pravica uporabnika poddomene — na kopiji produkcije.
- **Naloga 11, merilo 14:** Viber na pravem telefonu — na kopiji produkcije.
- **Bikemap vdelava (naloga 07):** potrditi na pravi poti ali ponudnika izklopiti pred izidom.
- **Dvojniki mail predlog:** preveriti, da registracija ne ustvari dvojnikov (mini-popravek, ni blokersko).
- Predloga j4starter ne izpisuje sistemskih sporočil (D41) → naloga 17 (po izidu v1).
- Povzetek načina dela (naloga 15) — osnutek `docs/NACIN-DELA.md`, dokončati ob izidu.
