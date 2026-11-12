# PRODUKCIJA – runde.belaunce.cc

Zapis postavitve komponente com_belauncerunde v živo na poddomeni. Verzija paketa ob postavitvi: 0.18.1.
Zadnja posodobitev: 2026-11-10.

## Platforma

- Poddomena: `runde.belaunce.cc` — LOČENA sveža namestitev **Joomla 6.2.0** (ne isti install kot glavni belaunce.cc). Odločitev D60.
- Admin Tools Professional 7.9.3 in FF Explorer nameščena na poddomeni.
- Paket nameščen prek admina (Sistem → Namesti → Naloži paketno datoteko), zgrajen z `orodja/gradnja.sh`.

## Cross-DB AcyMailing (D61, naloga 18)

AcyMailing je na glavni strani; poddomena ga bere iz njegove baze na isti MySQL instanci.
- **Zunanja baza AcyMailing:** `vft26375_belaunce`
- **Predpona tabel oddaljene namestitve:** `dipxn_`
- Poddomena uporablja **istega MySQL uporabnika** kot glavna baza → dodaten `GRANT SELECT` ni bil potreben.
- Preverjeno v živo: *Preveri člana* za pravega člana vrne »Member« (branje iz oddaljene baze deluje; `information_schema` dostopen).

## AcyMailing liste (Možnosti → AcyMailing)

- **Lista članov:** `Člani` (ID 6)
- **Lista Runde:** `Runde` (ID 9) — nova lista, ustvarjena v AcyMailingu glavne strani 2026-11-05 (produkcija prej ni imela liste za runde; na DDEV je bila ID 9 »Runde - lista za app«). Javni obrazec za goste vpisuje na to listo.

## Meniji (Main Menu, mainmenu)

- **Seznam rund** — tip *Runde Belaunce → Seznam rund*, alias `runde`, `component_id` 258 (»Belaunce Rides Component«), nastavljena kot **domača stran** (koren poddomene kaže seznam rund).
- **Prijava za člane** — tip *Runde Belaunce → Prijava*, alias `prijava`.
- Opomba: `component_id` se ob shranjevanju menijske postavke ne izpelje sam iz povezave — če postavko ročno ustvarjaš prek POST/klona, mora biti 258, sicer Joomla javi »Component not found«. Prek izbirnika tipa v vmesniku se nastavi samodejno.

## Čisti URL-ji

- Prek Admin Tools *.htaccess Maker* ustvarjen varnostni `.htaccess` (SymLinksIfOwnerMatch, brez HSTS, blokada directory listinga, zaščita backend/frontend, izjeme `.well-known` + restore datoteke).
- **Dodana izjema:** `media/com_belauncerunde/manifest.webmanifest` v »Exceptions from Server Protection« — brez nje je `.webmanifest` vračal 403 (zaščita strežnika dovoli le določene pripone; `.svg`/`.js`/`.css` so, `.webmanifest` ni) in PWA ne bi delovala.
- V Global Config → Site vklopljen **Use URL Rewriting** (sef_rewrite=1). Naslovi: `/runde`, `/prijava` (prej `/index.php/runde`).

## Varnost (Admin Tools WAF)

- Vtičnik **System - Admin Tools** vklopljen → WAF teče.
- `ipwl` (omejitev admina na IP seznam): **izklopljen** (da ni nevarnosti zaklepa admina).
- `tsrenable` (bananje ponavljajočih kršiteljev): **vklopljen, prag 5 poskusov** (`tsrstrikes=5`).
- Skrivni URL za admin in geslo .htpasswd: **nista** nastavljena (lockout tveganje).
- Preverjeno, da WAF ne blokira: admin, koren, `/runde`, `/prijava`, manifest, SVG ikona, service worker, pogled za goste, dolgi parametri v URL-ju (torej tudi žeton v magic-link povezavi ni blokiran).

## Pošta / SMTP (Global Config → Server → Mail)

- Mailer: **SMTP**, From: `info@belaunce.cc`, From Name: `Belaunce - Runde`, port **465**, varnost **SSL/TLS**, avtenticiran predal glavne domene belaunce.cc (ista domena → SPF/DKIM veljajo).
- SES ni uporabljen (SES je verificiran za majice-kape.si, ne za belaunce.cc; za nizko količino belaunce.cc SMTP zadošča).
- Prej je bila `mail()` funkcija na strežniku onemogočena — zato je bil SMTP nujen.

## Preverjeno v živo (2026-11-10)

- **SMTP test** (Joomla »Send Test Mail«): pošta uspešno poslana prek SMTP.
- **Magic-link prijava:** sprožena za člana na Gmailu, povezava prispela v **Prejeto** (ne spam), prijava uspela.
- **Ustvarjen Joomla uporabnik** na poddomeni (aktiviran, neblokiran) ob prvi prijavi.

### Opomba: »Linked Joomla user: No« v diagnostiki je pričakovano

V cross-DB postavitvi polje »Linked Joomla user« v *Preveri člana* bere povezavo iz AcyMailinga (`user_id` naročnika), ki na glavni bazi kaže na uporabnika GLAVNE strani, ne na novega lokalnega uporabnika poddomene. Zato bo na poddomeni vedno »No«, čeprav prijava deluje in je lokalni uporabnik ustvarjen. Ni napaka; na prijavo, prepoznavo članstva in prijavo na runde ne vpliva.

## Nastavitve strani

- Dolžina seje: **45 minut** (Global Config → lifetime).

## Ostane do polnega zagona

- [ ] Na telefonu: **Viber deljenje** (naloga 11, m14), **namestitev PWA** Android + iOS Safari (naloga 19, m4–6), **Bikemap** vdelava na pravi rundi (najprej v adminu ustvari testno rundo).
- [ ] **Preusmeritve** starih povezav `belaunce.cc/runde/...` (Viber zgodovina) na poddomeno.
- [ ] `utf8mb4` na tabelah komponente (na svežem Joomla 6.2 verjetno že; hitra potrditev).
- [ ] Po objavi članom: po želji WAF `tsrenable` ostane 5 poskusov ali poostriš; `ipwl` lahko kasneje vklopiš, če dodaš svoj IP na Exclusive Allow seznam.

## Grafična prilagoditev videza (2026-11-12)

Mobile-first prilagoditev po pregledu oblikovanja (bolj »aplikacija«, manj spletna stran).

**Komponenta `media/css/site.css` (v gitu):**
- `--br-radius` 8 → **5 px** (kartice, paneli, stat-ploščice).
- `--br-chip-radius` 7 → **3 px** (čipi, značke `.br-tag`/`.br-chip`).
- Gumbi `.btn` `border-radius` → **3 px**; filter gumb `.br-filtri__toggle` iz pill `50rem` → **3 px**.
- Nov `.btn-secondary` (glavni akcijski gumb, npr. »Odpri rundo«): **bela** pisava na modri `#6ab7e3`
  (hover `#58a7d4`). Opomba: bela na svetli modri ima nizek kontrast (~2,2:1) — zavestna izbira naročnika.

**Predloga `belauncetemplate/style.css` (NI v gitu — samo produkcija, FF Explorer):**
- Sistemska obvestila `.alert-info` (prej peach `#fcd8cf`/`#902508`, izpeljano iz `--bs-info` #f03e0d)
  → jantarno-črna kombinacija: ozadje `#FFE3A5`, besedilo `#212125`, rob `#f6cf7a`.

Oba popravka uveljavljena v živo prek FF Explorer in potrjena s svežim prenosom CSS datotek.
