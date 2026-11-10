# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-11-10
**Fokus seje:** Postavitev v živo na poddomeni `runde.belaunce.cc` (Joomla 6.2)
**Stanje:** Komponenta 0.18.1 nameščena in konfigurirana na produkciji. Cross-DB AcyMailing, meniji, varnostni `.htaccess` + WAF, URL rewriting in SMTP urejeni in **preverjeni v živo** (prepoznava člana, dostava pošte na Gmail, magic-link prijava). Polni zapis postavitve: **`docs/PRODUKCIJA-runde.md`**. Pred objavo članom ostanejo testi na napravi (Viber, PWA, Bikemap) in preusmeritve starih povezav.

---

## Kje smo ostali

- **V1 koda zaključena in združena v `develop`:** naloge 20 (email vodji, 0.16.0), 18 (cross-DB AcyMailing, 0.17.0), 21 (ohrani listo, 0.17.1), 19 (PWA brez push, 0.18.0), 22 (PWA nizi, 0.18.1).
- **Postavitev v živo (2026-11-10)** na `runde.belaunce.cc` — glej `docs/PRODUKCIJA-runde.md`:
  - Cross-DB AcyMailing: zunanja baza `vft26375_belaunce`, predpona `dipxn_`, isti MySQL uporabnik → Preveri člana vrne »Member«.
  - Liste: Člani (6), Runde (9, nova lista na glavni strani).
  - Meniji: Seznam rund (`runde`, domača), Prijava za člane (`prijava`), component_id 258.
  - Varnostni `.htaccess` (Admin Tools) z izjemo za `.webmanifest`; URL rewriting vklopljen (čisti `/runde`, `/prijava`).
  - WAF vklopljen; `ipwl` izklopljen, `tsrenable` 5 poskusov, brez skrivnega URL-ja/htpasswd.
  - SMTP: `info@belaunce.cc`, 465/SSL, From »Belaunce - Runde«.
  - Seja 45 min.
  - Preverjeno: SMTP test OK, magic-link na Gmail v »Prejeto«, prijava uspela, ustvarjen lokalni Joomla uporabnik. »Linked Joomla user: No« je pričakovan cross-DB artefakt (ne vpliva na delovanje).

## Naslednji korak (pred objavo članom)

1. Na telefonu: Viber deljenje (naloga 11, m14), namestitev PWA Android + iOS (naloga 19, m4–6), Bikemap vdelava na pravi rundi (najprej ustvari testno rundo v adminu).
2. Preusmeritve starih `belaunce.cc/runde/...` povezav na poddomeno (Viber zgodovina).
3. `utf8mb4` na tabelah komponente (hitra potrditev — na Joomla 6.2 verjetno že).
4. Nato objava članom.

Odprto po izidu: naloga 15 (povzetek načina dela, `docs/NACIN-DELA.md`, z Matijo), naloga 17 (otroška predloga, D41).

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

- Viber na pravem telefonu (naloga 11, m14) — ostane za test na napravi.
- PWA namestitev na telefonu in iOS Safari (naloga 19, m4–6) — test na napravi.
- Bikemap vdelava (naloga 07) — potrditi na pravi rundi ali ponudnika izklopiti.
- Dvojniki mail predlog — preveriti, da registracija ne ustvari dvojnikov (ni blokersko).
- Predloga j4starter in sistemska sporočila (D41) → naloga 17 po izidu.
- Povzetek načina dela (naloga 15) — dokončati z Matijo.
