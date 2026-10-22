# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-22
**Fokus seje:** NALOGA 12 (Scheduler: sinhronizacija članov, izbris gostov, čiščenje) — pregled in test na DDEV
**Stanje:** NALOGA 12 ✅ združena (PR #12). NALOGA 13 naslednja (specifikacijo pripravi arhitekt).

---

## Kje smo ostali

- **NALOGA 12** (0.12.0): vsa merila ✅ na DDEV, brez popravkov kode po pregledu. Nov vtičnik `plg_task_belauncerunde` ponuja Schedulerju eno dnevno opravilo `belaunce_vzdrzevanje`, ki izvede tri neodvisne dele v svojih `try/catch` (napaka enega ne ustavi ostalih): sinhronizacija članov, izbris gostov, čiščenje žetonov in poskusov.
- Potrjeno z ročnim zagonom in SQL:
  - **Sinhronizacija:** član, odstranjen z AcyMailing liste članov, izgubi skupino „Člani" (`SKUPINA_ODVZETA`); vrnjen na listo jo dobi nazaj (`SKUPINA_VRNJENA`). Polje `block` se NIKOLI ne spremeni (D53), `acym_user.active` ostane 1 — s tem je zaprto staro vprašanje D52 (AcyMailing zrcali samo blokado, ne spremembe skupine).
  - **Varnostni pogoj:** ob prazni ali nenastavljeni listi oz. nenameščenem AcyMailingu se sinhronizacija preskoči (`SINHRONIZACIJA_PRESKOCENA`); `AcymailingAdapter::idjiClanov(..., strogo=true)` ob napaki branja vrže izjemo, da prazen rezultat ne pomeni množičnega odvzema.
  - **Blokiran član** se v celoti preskoči (`PRESKOCENO_BLOKIRAN`), skupina in block nespremenjena. Nepovezan uporabnik (brez vrstice v `#__belaunce_clani`) se ne obdeluje.
  - **Izbris gostov:** meja `zacetek + 30 dni < UTC_TIMESTAMP()` v obe smeri (gost stare runde izbrisan, gost nedavne ostane), ne glede na stanje runde.
  - **Čiščenje:** potekli/porabljeni žetoni in stari zapisi poskusov izbrisani prek obstoječih `ZetonService::pociscenje()` in `OmejitevHelper::pociscenje()`.
  - **Dnevnik:** samo kode in ID-ji/števila, nikjer emaila, imena, telefona, IP, hasha ali žetona (pravili 22, 24, D55).

### Ključne rešitve naloge 12 (za pregled prihodnjih sprememb)

- **D53 centraliziran v `ClanService::uskladiSkupino(int $uporabnikId, bool $najBoClan): string`** (kode VRNJENA / ODVZETA / BREZ_SPREMEMBE / PRESKOCENO_BLOKIRAN). Prijava (`preveriUporabnika`) in vzdrževanje uporabljata isto pot, zato je pravilo „samo skupina, nikoli block" na enem mestu. Block se preveri pred spremembo skupine.
- **Odvzem edine skupine:** jedro 6.1 `Table\User::store()` ob praznem `groups` (`is_array && count`) preskoči zapis `#__user_usergroup_map`, zato se stara vrstica ne izbriše. Rešitev: po `User::save()` še vezan `DELETE` samo vrstice skupine „Člani". Preverjeno v izvorni kodi.
- **`VzdrzevanjeService`** (Administrator\Service): orkestracija treh delov; vsak javi svojo kodo in število. Deluje brez prijavljenega uporabnika (odvisnosti iz `Factory::getContainer()`).
- **Dnevnik izven konteksta komponente:** vtičnik si sam registrira zapisovalnik z obliko `{DATETIME}\t{PRIORITY}\t{CATEGORY}\t{MESSAGE}` (pravilo 24), ker `BelauncerundeComponent::boot()` zanj ne teče.
- **Namestitev:** namestitveni skript v zanki vklopi oba vtičnika (authentication + task), paketni manifest navaja oba PRED komponento (pravilo 18, D51).

## Način dela

Pregled arhitekta (gradnja + `php -l` + preverjanje jedra 6.1) in test na DDEV, ki ga je zaradi Scheduler opravila (teče iz urnika/cron, ne iz brskalnika) vodil Matija prek admin vmesnika in SQL. Tehnike, ki so se obnesle:
- **Priprava in preverjanje stanja prek SQL** okoli ročnega zagona opravila (odvzem/vrnitev člana na listi, blokada, gost na stari rundi, potekel žeton), nato branje `administrator/logs/com_belauncerunde.php` — resnični izpis, ne izolirana predpostavka.
- **Ročni zagon opravila** z gumbom *Run/Test* v *System → Scheduled Tasks* izvede nalogo takoj, ne glede na nastavljeni interval.
- Testni gost in žeton vstavljena z upoštevanjem sheme (`#__belaunce_zetoni.ip` je VARCHAR(45) surov IP, NE hash; `#__belaunce_gostje.ip` je CHAR(64) hash).

## Naslednji korak (nova seja)

1. Preberi `docs/SESSION.md`, `TASKS.md`, `osnova-projekta.md`, `ARCHITECTURE.md`, `AGENTS.md`.
2. Matija preda **NALOGO 13** (backend statistika in izvoz v CSV — D18, razdelek 18.6) Codexu (veja `feature/NALOGA-13-statistika`).
3. Arhitekt napiše specifikacijo naloge 13 (vir: osnova D18, razdelek 7 in 11; obstoječi `Administrator\Model\RundeModel` in backend seznam rund iz naloge 02; `CasHelper` za prikaz časa; brez osebnih podatkov v izvozu), pregleda PR, zažene gradnjo s PHP, testira na DDEV.
4. Test na DDEV (posodobitev 0.12.0 → 0.13.0).

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, predpona `dipxn_`. Mailpit: `https://joomla-dev.ddev.site:8026`. Repozitorij je v `~/belaunce-runde`.
- Namestitev prek CLI: `ddev exec php cli/joomla.php extension:install --path=/var/www/html/tmp/pkg_belauncerunde-X.Y.Z.zip` (ZIP najprej kopiraj v `~/joomla-dev/tmp/`).
- **Scheduler:** na DDEV je ročno ustvarjeno opravilo tipa „Belaunce vzdrževanje" (`belaunce_vzdrzevanje`) z dnevnim intervalom; zaganja se ročno z *Run*. Na produkciji je potreben pravi strežniški cron (kontrolni seznam).
- `ddev share`: cloudflared nameščen, a naključni `trycloudflare.com` vrne 421; test telefona (naloga 11, merilo 14) na kopiji produkcije.
- **Stanje testnih podatkov po nalogi 12:** vse vrnjeno v prvotno stanje. Član 634 je spet član s skupino „Člani" (ID 10), `block=0`, na listi članov (ID 6). Testni gost in potekel žeton izbrisana. Runda 4 ima prvotni `zacetek` 2026-10-13 19:44:09. Runda 8 „Omejitev tri" ostaja ODPOVEDANA. Lista članov v Možnostih vrnjena na ID 6. Testni uporabnik 636 (`test-prazen@belaunce.cc`) obstaja, a NIMA vrstice v `#__belaunce_clani` (ni povezan član).
- Možnosti: skupina Člani 10, lista članov 6, lista Runde 9, privzeto trajanje 180, omejitev rund na dan 3 (test), omejitev gostov 2, captcha izklopljena, sprožilna polja maila zacetek+lokacija, Viber predloga prazna (privzetek).
- Admin: 631 (`info@mtdesign.si`, prikazno ime „Matija"). Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635** (`testtujec`). Testni uporabnik: **636** (`test-prazen@belaunce.cc`).
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- **Naloga 11, merilo 14:** deljenje v Viber na pravem telefonu (odpre Viber s pripravljenim besedilom; Open Graph predogled) — potrditi na kopiji produkcije pred zagonom.
- **Naloga 12, merilo 11** (napaka enega dela ne ustavi ostalih): pokrito s pregledom kode (ločeni `try/catch`), ni simulirano ob zagonu — po želji dodatno preveriti.
- **Naloga 12, merilo 13** (regresija prijave): shranjevanje skupine ob magic-link prijavi uporablja isto `ClanService::uskladiSkupino()` po poti VRNJENA, ki jo je zagon opravila potrdil; polni vpis v brskalniku ni bil ponovljen.
- Bikemap vdelava (naloga 07) še ni preizkušena na pravi poti; pred izidom jo je treba potrditi ali ponudnika izklopiti.
- `GOST_CAPTCHA_MANJKA` se zapiše dvakrat na oddajo — kozmetično (naloga 08).
- Predloga j4starter ne izpisuje sistemskih sporočil ne debug plošče (D41); rešitev je naloga 17 (otroška predloga).
- Merilo 20 naloge 04 (test odstranitve paketa) in zamenjava zastarelega `getError()` sta preložena na nalogo 14.
- Videz: niz dolžine „40.0 km" in velika začetnica imena dneva — za nalogo 16 (vizualni pregled).
- Ob zaključku v1: povzetek načina dela (naloga 15 v `TASKS.md`).

## Opomba o menjavi seje

Nova seja nadaljuje pri nalogi 13; ves kontekst je v `docs/`. Chrome ostane prijavljen (zadnji uporabnik 634 Matija), DDEV in testni podatki ostanejo v zgornjem stanju.
