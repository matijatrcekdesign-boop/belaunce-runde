# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-20
**Fokus seje:** NALOGA 08 (gostje) — pregled, dva popravka, test na DDEV
**Stanje:** NALOGA 08 ✅ združena. NALOGA 09 pripravljena za Codex.

---

## Kje smo ostali

- **NALOGA 08** (0.8.0): vseh 21 meril ✅. Nečlan se prijavi na rundo, ki je odprta za goste, z imenom in telefonom ali emailom; gost z emailom dobi potrditveni mail s povezavo za odjavo.
- Gostje se štejejo med udeležence **samo** v `PrijaveModel::steviloUdelezencev()`, kjer sta že prijave in vodja (D54). Član vidi ime gosta z značko „gost", kontakt vidi izključno vodja runde — in to je preverjeno že v sami poizvedbi (`CASE WHEN r.vodja_id = :vodja`), ne šele v predlogi.
- **Nova odločitev D55:** IP gosta se nikoli ne shrani surov. V `#__belaunce_gostje.ip` gre SHA-256 hash, soljen z Joomlinim `secret`, in služi izključno omejitvi prijav na rundo.

### Kaj je prinesel pregled naloge 08

1. **Dnevnik je zapisoval IP obiskovalca.** Joomlin privzeti zapis vključuje `{CLIENTIP}` (`libraries/src/Log/Logger/FormattedtextLogger.php:43`), kar bi na produkciji pomenilo IP-je gostov v `administrator/logs/com_belauncerunde.php` — ravno tisto, čemur se s hashem v tabeli izogibamo. Popravljeno z izrecno obliko zapisa pri registraciji zapisovalnika → **pravilo 24** in **merilo 21**. Pred zagonom na produkciji je treba stari dnevnik pobrisati.
2. **Rešitve, ki presegajo nalogo in jih velja ohraniti:** prijava gosta teče v transakciji s `SELECT … FOR UPDATE` na vrstici runde, zato dve sočasni oddaji ne moreta obe porabiti zadnjega prostega mesta; odjava gosta ne pošilja surovega žetona v POST, ampak naključni izziv, katerega hash čaka v seji, stran pa postavi `Referrer-Policy: no-referrer` in `Cache-Control: no-store`.

### Kar je bilo preverjeno empirično

Honeypot (izven zaslona, ne `display:none`), minimalni čas izpolnjevanja, omejitev na IP in rundo, zavrnitev na odpovedani in štartani rundi, odjava z enkratno povezavo, prikaz za obiskovalca, člana in vodjo, captcha vklopljena brez nastavljenega vtičnika. V bazi sta ostala samo hasha IP-jev, Anina vrstica je po odjavi izginila.

## Način dela

Tretja seja, v kateri je testiranje v brskalniku opravil arhitekt prek Claude in Chrome, Matija pa je nameščal pakete in poganjal SQL. Dve novi lekciji:

- **Piškotek „Zapomni si me" prepreči odjavo** v brskalniku; za teste obiskovalca je bilo treba pobrisati vrstico v `#__user_keys`. To je hkrati dokaz, da D43 deluje.
- Test „prehitre oddaje" je veljaven samo, če se obrazec odda **takoj po izrisu**; prvi poskus je bil lažno negativen, ker je med izrisom in oddajo minilo več kot 3 sekunde.

## Naslednji korak

1. Matija preda NALOGO 09 Codexu (veja `feature/NALOGA-09-moje-runde`).
2. Arhitekt pregleda PR in zažene gradnjo s PHP.
3. Test na DDEV (posodobitev 0.8.0 → 0.9.0).

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, predpona `dipxn_`. Mailpit: `https://joomla-dev.ddev.site:8026`. Repozitorij je v `~/belaunce-runde`.
- Namestitev prek CLI: `ddev exec php cli/joomla.php extension:install --path=tmp/pkg_belauncerunde-0.8.0.zip`.
- Vtičnik za združljivost za nazaj in *System – Debug* sta izklopljena.
- Runde na DDEV: 1 Test poletje, 2 Test zima (odprta za goste, 1 gost), 4 test (končana), 5 testna, 6 Arhitektov test (štartana, odprta za goste), 7 Arhitektov poletni test (odpovedana), 8 Omejitev tri (odpovedana, odprta za goste, 1 gost). Runde 6–8 so testne.
- Možnosti: skupina Člani 10, lista članov 6, lista Runde 9, privzeto trajanje 180, omejitev novih rund na dan **3** (za test; privzetek 5), omejitev gostov na IP/rundo 2, captcha za goste izklopljena, minimalni čas 3 s.
- Admin: 631. Član: **634** (`matija.trcek@gmail.com`, AcyMailing 238). Testni nečlan: **635** (`testtujec`).
- Codex (aplikacija ChatGPT) nima PHP — `php -l` in gradnjo poganja arhitekt.

## Odprta vprašanja

- Bikemap vdelava še ni preizkušena na pravi poti; pred izidom jo je treba potrditi ali ponudnika izklopiti.
- `GOST_CAPTCHA_MANJKA` se zapiše dvakrat na oddajo (izris in preverjanje) — kozmetično.
- Ali AcyMailing reagira tudi na spremembo **skupine** uporabnika — pred nalogo 12.
- Predloga j4starter ne izpisuje sistemskih sporočil (D41); rešitev je naloga 17 (otroška predloga).
- Merilo 20 naloge 04 (test odstranitve paketa) in zamenjava zastarelega `getError()` sta preložena na nalogo 14.
- Ob zaključku v1: povzetek načina dela (naloga 15 v `TASKS.md`).
