# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-25
**Fokus seje:** zaključek naloge 20, vnos D58–D63, predaja naloge 18 Codexu
**Stanje:** NALOGA 20 ✅ združena (PR #16, `6f7477d`, 0.16.0). D58–D63 vneseni v `osnova-projekta.md`. **Naloga 18 (0.17.0) predana Codexu** — čaka PR. Naloga 19 (0.18.0) sledi.

---

## Kje smo ostali

- **NALOGA 20** (0.16.0, PR #16) združena 2026-10-25. Povzetek v TASKS.md → Končano.
- **D58–D63** vneseni. D58 in D59 (manjkala od naloge 11) sta preverjena proti kodi (`DeljenjeHelper::besedilo()`, `Runda/HtmlView`, `Moje/HtmlView`) in popravljena: besedilo deljenja vsebuje tudi `{VODJA}` (javno ime vodje), `{TEZAVNOST}`, `{DOLZINA}`, `{TRAJANJE}`; deljivost = stanje 1 in runda še ni štartala, neodvisno od identitete.
- **D60 nadomesti** stari backlog zapis „poddomena = preusmeritev / domain mapping nad isto namestitvijo" — TASKS.md posodobljen (backlog + kontrolni seznam pred zagonom).
- **Vrstni red:** 18 pred 19. Razlog: brez 18 komponenta na ločeni poddomeni (D60) ne prepozna članov (magic-link, skupina „Člani", diagnostika) → blokira izid; 18 je majhna in v celoti testljiva na DDEV. PWA (19) je dodatek in je odvisna od dokončne poddomenske postavitve (`scope`, `start_url`).
- **NALOGA-18.md popravljena** po pregledu kode adapterja (develop `6f7477d`):
  - opozorilo za nepopolno/neveljavno nastavitev prek `Log::add('ACYM_NASTAVITEV_NEVELJAVNA', Log::WARNING, …)` (ne `zabeleziNapako()`, ki zahteva `\Throwable`);
  - regex preverba `acym_baza` / `acym_predpona` v konstruktorju (obramba poleg `quoteName`);
  - obstoječi predpomnilnik `$namescen` velja za obe veji;
  - novi polji v fieldset `acymailing` PRED `lista_clanov`; po menjavi baze shrani in ponovno izberi listi (ID-ji list iz oddaljene baze);
  - našteta vsa mesta zamenjave; preverjeno, da noben drug razred ne bere `#__acym_*` neposredno (ClanService dobi zapis prek adapterja).
- **NALOGA-19.md — pred predajo dopolniti:** SW naj obdeluje samo `navigate` zahteve site dela (ne `/administrator`, ne zunanjih virov — Bikemap, pisave), ostalo spusti mimo; `start_url` uskladiti z menijsko postavko na poddomenski namestitvi (D60); verzija 0.18.0; odvisnost tudi od 18.

## Naslednji korak

1. Codex odpre PR za nalogo 18 → arhitekt pregleda (diff proti specu, `php -l`, `xmllint`, `gradnja.sh`, test na DDEV po ročnem testu iz specifikacije — simulirana baza `oddaljena_test`).
2. Po merge 18: arhitekt dopolni NALOGA-19.md (točke zgoraj) in jo preda.

## Način dela (povzetek, poln dokument v `docs/NACIN-DELA.md`)

3 vloge: **Matija** (testira na DDEV, združuje PR-je, edini pisec v `osnova-projekta.md` — tu je vsebino pripravil arhitekt, Matija jo je commital) · **Codex** (implementira eno nalogo, commita, odpre PR) · **arhitekt/Claude** (specifikacije, pregled PR-jev, test na DDEV, zaključna dokumentacija). Arhitekt NIKOLI ne pusha in ne piše produkcijske kode. Naloge Codexu kot **en plain-text blok v code box**. Zaključna dokumentacija kot **ZIP, ki se razširi z `-d docs/`** (datoteke v korenu ZIP-a, brez mape `docs/`), Matija commita.

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, baza `db`, predpona `dipxn_`. Mailpit `:8026`. Repozitorij `~/belaunce-runde` (GitHub `matijatrcekdesign-boop/belaunce-runde`, javen — arhitekt ga lahko klonira za branje).
- Univerzalna namestitev (katerakoli veja/verzija):
  `git checkout <veja> && git pull && bash orodja/gradnja.sh`, nato
  `ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1); cp "$ZIP" ~/joomla-dev/tmp/; cd ~/joomla-dev; ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"`.
- Admin: 631 (`info@mtdesign.si`). Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635**. Testni uporabnik: **636**.
- Testni podatki od naloge 20 (nepovrnjeno): runda 1 „Test poletje" ima `odprto_za_goste=1`; na njej testna gosta „Test Gost" in „Test Gost Dva"; član 634 odjavljen; `mail_nova_prijava` = Da.
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- **Naloga 20:** runtime vsebina dnevnika ni bila preverjena (`ddev exec tail -n 20 administrator/logs/com_belauncerunde.php` — pričakovano `NOVA_PRIJAVA_MAIL runda_id=…`, brez imena/emaila).
- **Naloga 18:** ali produkcijski gostitelj dovoli branje `information_schema` za tujo bazo — preveriti na kopiji produkcije; alternativa je predvidena v specifikaciji (samo kot vprašanje).
- **Naloga 11, merilo 14:** Viber na pravem telefonu — na kopiji produkcije pred izidom.
- **Bikemap vdelava (naloga 07):** potrditi na pravi poti ali ponudnika izklopiti pred izidom.
- **Dvojniki mail predlog:** preveriti, da registracija ne ustvari dvojnikov (mini-popravek, ni blokersko).
- Predloga j4starter ne izpisuje sistemskih sporočil (D41) → naloga 17 (po izidu v1).
- Povzetek načina dela (naloga 15) — osnutek `docs/NACIN-DELA.md`, dokončati ob izidu.
