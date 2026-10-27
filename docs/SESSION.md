# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-27
**Fokus seje:** NALOGA 18 (cross-DB AcyMailing) — pregled, test na DDEV, merge; priprava naloge 21 in dopolnitev naloge 19
**Stanje:** NALOGA 18 ✅ združena (PR #17, `7538ba8`, 0.17.0). Naslednja: **naloga 21** (0.17.1, mini-popravek `AcymlistaField`) — specifikacija pripravljena, čaka predajo Codexu. Nato **naloga 19** (PWA brez push, 0.18.0, specifikacija dopolnjena).

---

## Kje smo ostali

- **NALOGA 18** (0.17.0, PR #17) združena 2026-10-26. Povzetek v TASKS.md → Končano. Arhitektov pregled: diff proti specifikaciji ✅ (vseh 9 meril), `php -l` ✅, `xmllint` ✅, `gradnja.sh` ✅. Test na DDEV prek povezanega računalnika in Chroma (admin se je prijavil Matija): vseh 9 točk ročnega testa ✅, dnevnik brez imena baze/predpone/SQL (`ACYM_NASTAVITEV_NEVELJAVNA`), podvojeni zapisi ob eni napačni nastavitvi (vsak razred ustvari svoj adapter) — ne blokersko.
- **Ugotovitev pri testu → NALOGA 21:** ko AcyMailing ni dosegljiv (tudi ob tipkarski napaki v imenu baze), `AcymlistaField` ponudi samo možnost 0 in ob shranjevanju Možnosti obe listi tiho ponastavi na 0 (obstaja od naloge 03). Specifikacija: `docs/naloge/NALOGA-21.md` (shranjeni ID ostane izbran kot „ID n – trenutno ni na voljo (ohranjeno)"). Verzija 0.17.1, pred nalogo 19.
- **NALOGA-19.md dopolnjena** (D60/D62): verzija 0.18.0; service worker obdeluje SAMO navigacije (`mode === 'navigate'`, GET, isti izvor, zunaj `/administrator`) — vse ostalo (slike, skripte, zunanje vdelave Strava/Komoot/Bikemap, POST obrazci) spusti mimo; opomba o `start_url`/`scope` za ločeno poddomensko namestitev; merilo 7 prilagojeno.

## Naslednji korak

1. Matija preda **NALOGO 21** Codexu (blok v code box; arhitekt ga pripravi na zahtevo — vsebina je v `docs/naloge/NALOGA-21.md`).
2. Arhitekt pregleda PR (diff proti specu, `php -l`, `xmllint`, `gradnja.sh`), test na DDEV po ročnem testu iz specifikacije.
3. Nato predaja **NALOGE 19**.

## Testni podatki po seji (nepovrnjeno / stanje)

- DDEV Možnosti: `acym_baza` in `acym_predpona` PRAZNI, lista članov = ID 6, lista Runde = ID 9 (privzeto stanje). Testna baza `oddaljena_test` pobrisana (Matija).
- Član 634 je prijavljen na frontendu v Matijevem Chromu (magic-link test). Mailpit vsebuje testna sporočila.
- Od naloge 20 še velja: runda 1 „Test poletje" ima `odprto_za_goste=1`; na njej testna gosta „Test Gost" in „Test Gost Dva"; `mail_nova_prijava` = Da.

## Način dela (povzetek, poln dokument v `docs/NACIN-DELA.md`)

3 vloge: **Matija** (testira na DDEV, združuje PR-je, edini pisec v `osnova-projekta.md`) · **Codex** (implementira eno nalogo, commita, odpre PR) · **arhitekt/Claude** (specifikacije, pregled PR-jev, test na DDEV, zaključna dokumentacija). Arhitekt NIKOLI ne pusha in ne piše produkcijske kode. Naloge Codexu kot **en plain-text blok v code box**. Zaključna dokumentacija kot **ZIP, ki se razširi z `-d docs/`**, Matija commita.

Praktično iz te seje: DDEV spletna stran se v vgrajenem brskalniku Claude aplikacije ne naloži (lokalni certifikat) — za test na DDEV je uporaben **Claude in Chrome**; Matija se v admin prijavi sam (arhitekt gesel ne vpisuje). Brez terminala arhitekt ne more brisati/spreminjati baze — take ukaze pošlje Matiji.

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, baza `db`, predpona `dipxn_`. Mailpit `https://joomla-dev.ddev.site:8026`. Repozitorij `~/belaunce-runde` (GitHub `matijatrcekdesign-boop/belaunce-runde`, javen).
- Univerzalna namestitev (katerakoli veja/verzija):
  `git checkout <veja> && git pull && bash orodja/gradnja.sh`, nato
  `ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1); cp "$ZIP" ~/joomla-dev/tmp/; cd ~/joomla-dev; ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"`.
- Admin: 631 (`info@mtdesign.si`). Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635**. Testni uporabnik: **636**.
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- **Naloga 18, produkcija:** ali gostitelj dovoli branje `information_schema` za tujo bazo in ali ima DB uporabnik poddomene `SELECT` na AcyMailing bazi — preveriti na kopiji produkcije (alternativa v specifikaciji: `SELECT 1 FROM baza.tabela LIMIT 1` v try/catch, le po potrditvi).
- **Naloga 11, merilo 14:** Viber na pravem telefonu — na kopiji produkcije pred izidom.
- **Bikemap vdelava (naloga 07):** potrditi na pravi poti ali ponudnika izklopiti pred izidom.
- **Dvojniki mail predlog:** preveriti, da registracija ne ustvari dvojnikov (mini-popravek, ni blokersko).
- Predloga j4starter ne izpisuje sistemskih sporočil (D41) → naloga 17 (po izidu v1).
- Povzetek načina dela (naloga 15) — osnutek `docs/NACIN-DELA.md`, dokončati ob izidu.
