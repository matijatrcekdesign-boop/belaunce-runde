# Belaunce – aplikacija za kolesarske runde
**Osnova projekta (vir resnice)** · stanje: 8. 10. 2026 · faza: priprava na razvoj (naloga 01)

Priloga: `docs/osnutek-vmesnika.pdf` (5 zaslonov: seznam, stran runde, nova runda, moje runde, namizje). PDF je **wireframe**: razpored in funkcije so osnova, barve in slog prevzame predloga belaunce.cc. Kjer se PDF in ta dokument razlikujeta, velja ta dokument.

> **Pravilo:** ta dokument je edini vir resnice. Izvajalec (Codex) odločitev ne spreminja. Vsaka sprememba odločitve se najprej zapiše sem (nova ali spremenjena oznaka D…), šele nato v naloge in kodo.

---

## 1. Cilj
Člani kolesarskega društva si olajšajo skupne vožnje: kdor gre na kolo in želi družbo, objavi **rundo**, drugi se prijavijo. Seznam je javen (privabljanje novih članov), objava se deli v obstoječo Viber skupino.

## 2. Kontekst
- Spletna stran **belaunce.cc**, Joomla 6.
- Člani **niso registrirani** na strani. Imajo jih samo v **AcyMailing** (~120 naslovov; ločene liste za člane in druge naročnike; najnovejša verzija).
- Glavni komunikacijski kanal: **Viber skupina** (samo člani društva).
- Razvijalec/lastnik: Matija (Joomla, PHP, Bootstrap 5, SQL).
- Na isti strani je nameščena komponenta **com_kisegonma** (ture). Imena tabel, jezikovnih ključev in CSS razredov ne smejo trčiti.

## 3. Sprejete odločitve
| # | Odločitev |
|---|---|
| D1 | **Joomla komponenta** na belaunce.cc (ne samostojna aplikacija). |
| D2 | Mobile-first spletna stran. **Brez nativne aplikacije in trgovin.** PWA kasneje, če bo potreba. |
| D3 | Vsak član lahko ustvari rundo in postane njen **vodja**. Vodja ureja samo svojo rundo. **Admin ureja samo iz backenda.** |
| D4 | Članstvo = obstoječa **AcyMailing lista članov** (samo člani; urejanje samo admin). Nove člane vpiše admin. |
| D5 | Obveščanje = ločena **AcyMailing lista "Runde"** (člani nanjo že pristali; odjava po lastni izbiri ne vpliva na dostop). **Javni obrazec samo za listo "Runde".** |
| D6 | Prijava: **magic link na email**, brez gesel. Ob prvi prijavi vpiše email. |
| D7 | **Seznam in strani rund so javni** (tudi neprijavljeni). Imena prijavljenih vidijo samo člani. Javnosti je viden **vodja s polnim imenom**. |
| D8 | Vodja lahko rundo označi kot **odprto za goste** (nečlan se prijavi z imenom in kontaktom). **Že v v1.** |
| D9 | **Tedenski povzetek** rund po mailu: **šele v fazi 2.** |
| D10 | Viber v v1: gumb **"Deli v Viber"** + besedilo za kopiranje + lep predogled povezave (Open Graph). Brez bota. |
| D11 | Stari dogodki se **ne brišejo**: skrijejo se v frontendu, ostanejo v bazi (arhiv). |
| D12 | Spremembe so pričakovane: besedila, pravila obveščanja, tipi in težavnosti naj bodo nastavljivi (glej 11). |
| D13 | Vsaka runda ima **naslov** (polje ostane). |
| D14 | **Težavnost: dve ravni, Berlingo in Turbo** (nadomestita hitra/zmerna/počasna). |
| D15 | Vodja rundo **odpove**, brisanje samo v backendu. |
| D16 | Podatki gostov se izbrišejo **30 dni po rundi**. |
| D17 | Opozorilo gostom: samo **"Vožnja je na lastno odgovornost."** |
| D18 | Uspešnost se ocenjuje **interno**: v backendu **seznam vseh rund** in statistika (dolžina, trajanje, število udeležencev), možen **izvoz v CSV**. |
| D19 | **Hibrid prijave:** magic link + samodejno ustvarjen Joomla uporabnik ob prvi prijavi (glej 6). |
| D20 | Responsive z **Bootstrap 5** (glej 15). |
| D21 | Razvojne zahteve: Joomla smernice, posodabljanje, čista in varna koda, komentarji, lokalni razvoj, dokumentacija (glej 16). |
| D22 | Delitev dela: **Claude = arhitekt, Codex = izvajalec** (glej 17). |
| D23 | **Trasa:** shrani se samo povezava, za znane ponudnike (Strava, Komoot, Bikemap) samodejna vdelava (glej 15). |
| D24 | **Update server na GitHubu** (repozitorij + posodobitveni XML). |
| D25 | **Zaščita obrazca za goste:** honeypot + minimalni čas izpolnjevanja + omejitev poskusov; podpora za Joomla Captcha API (privzeto izklopljeno), brez Googla (glej 9). |
| D26 | **Predloga belaunce.cc je osnova videza.** PDF osnutek je **wireframe**; barve, pisave in slog se prilagodijo predlogi. |
| D27 | Komponenta **`com_belauncerunde`**, imenski prostor `Belaunce\Component\Belauncerunde`. |
| D28 | **Koda v slovenščini** (podrobno v 16.1): naši identifikatorji brez šumnikov, komentarji s šumniki. Kar predpisuje Joomla, ostane v angleščini. |
| D29 | Ciljno okolje: **Joomla 6.1.x, PHP 8.3, MariaDB 10.11 (produkcija), LiteSpeed**. |
| D30 | Lokalni razvoj na **DDEV** (`~/joomla-dev`, `https://joomla-dev.ddev.site`, WSL2). DDEV ima MariaDB 11.8 in nginx; razlike do produkcije so v 16.3. |
| D31 | **Predpomnjenja strani ni.** Če se kdaj vklopi (LSCache ali Joomlino), morajo biti strani komponente izvzete. |
| D32 | Predloga je **j4starter** (poenostavljena Cassiopeia) z Joomlinim Bootstrapom 5. Komponenta sama naloži, kar potrebuje (WebAssetManager), in **ne uporablja jQueryja**. |
| D33 | Repozitorij **`matijatrcekdesign-boop/belaunce-runde`** (javen, GPL v2). Veje: `main` ← `develop` ← `feature/NALOGA-xx-…`. **PR vedno v `develop`.** |
| D34 | Transakcijski maili prek Joomla Mailerja (SMTP), pošiljatelj **info@belaunce.cc**. Funkcija `mail` je na produkciji izklopljena. |
| D35 | Namestitveni skript ustvari skupino **"Člani"** (starš: Registered), če še ne obstaja, in njen ID shrani v možnosti komponente. Backend ureja samo Super User; vloge "Urednik rund" v v1 ni. |
| D36 | **Lokacija je prosto besedilo** (obvezno), privzeta vrednost iz možnosti ("Gorenja vas"). Tabele lokacij ni. Predlogi zadnjih lokacij (`datalist`) so v backlogu. |
| D37 | Gost je na seznamu prijavljenih označen z **značko "gost"** (Bootstrap `badge`, druga barva + besedilo). |
| D38 | Omejitve poskusov in veljavnost povezave so **nastavljive v možnostih** (spustni seznami s fiksnimi vrednostmi, glej 6.1). |
| D39 | Razširitve se gradijo in nameščajo kot **paket `pkg_belauncerunde`** (ZIP), **tudi na DDEV**. Ročno kopiranje datotek in simbolne povezave niso dovoljeni, ker skrijejo napake v manifestih in jezikovnih datotekah. |
| D40 | Neprijavljen klik "Pridem": ID runde se shrani k žetonu (`_zetoni.runda_id`). Stran povezave ponudi **"Prijavi me in potrdi udeležbo na rundi *X*"**; en POST prijavi in vpiše na rundo. Če je runda medtem odpovedana ali že štartala, se izvede samo prijava z obvestilom. |
| D41 | **Predloga se ureja kasneje, izven projekta.** Izvajalec predloge ne spreminja. Popravek prikaza sistemskih sporočil v predlogi je pogoj pred zagonom (glej `docs/TASKS.md`). |
| D42 | Časi se shranjujejo v **UTC**. Časovni pas strani ostane UTC, zato komponenta za prikaz in vnos uporablja **lasten parameter `casovni_pas`** (privzeto `Europe/Ljubljana`). |
| D43 | Prijava z magic linkom uporablja Joomlin mehanizem **"Zapomni si me"** (seja strani traja 15 min). Vtičnik *System – Remember Me* mora biti vklopljen. |

**Označeno kot *predlog*:** izhodišča, ki jih arhitekt lahko spremeni po dogovoru z Matijo.

## 4. Podatki runde
- **tip:** MTB / cestno / gravel (iz tabele, razširljivo)
- **težavnost:** dve ravni, **Berlingo** in **Turbo** (iz tabele, razširljivo; opis ravni določi društvo)
- **datum in ura štarta**
- **lokacija:** prosto besedilo, privzeto "Gorenja vas" (D36)
- **dolžina** (okvirno, km, obvezno), **trajanje** (okvirno, neobvezno)
- **opombe** (tekst)
- **trasa** (neobvezno): povezava na Strava / Komoot / Bikemap ali podobno (glej 15)
- **naslov**
- **odprto za goste** (da/ne)
- **vodja** (Joomla uporabnik), **stanje** (objavljena / odpovedana)

### 4.1 Pravila runde (V1, V2, V7, V8)
- **Pretekla runda:** runda je pretekla, ko mine `zacetek + trajanje_min`. Če trajanja ni, se prišteje privzeto trajanje iz možnosti (privzeto 3 h). Rok 30 dni za izbris gostov teče od `zacetek`.
- **Vodja** je ob objavi samodejno prijavljen na svojo rundo in se ne more odjaviti; lahko samo odpove rundo.
- **Po štartu** (`zacetek` je mimo) je runda zaklenjena: ni urejanja, odpovedi ali prijav v frontendu. Admin v backendu lahko vse.
- **Mail ob spremembi** sprožijo spremembe polj `zacetek` in `lokacija` ter odpoved. Ostala polja ga ne sprožijo. Seznam polj je nastavljiv.

## 5. Podatkovni model
Vse tabele: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` (izrecno, ker ima produkcijska baza privzeto utf8mb3). Časi so `DATETIME` v UTC. Predpona tabel: `#__belaunce_`.

| Tabela | Stolpci | Opombe |
|---|---|---|
| `#__belaunce_tipi` | id, naziv, alias, opis (null), stanje, ordering, checked_out (null), checked_out_time (null) | šifrant; začetne vrednosti: MTB, Cestno, Gravel |
| `#__belaunce_tezavnosti` | id, naziv, alias, opis (null), stanje, ordering, checked_out (null), checked_out_time (null) | šifrant; začetne vrednosti: Berlingo, Turbo |
| `#__belaunce_runde` | id, naslov, alias, tip_id, tezavnost_id, lokacija, zacetek, dolzina_km, trajanje_min (null), opombe (null), trasa_url (null), odprto_za_goste, stanje, odpovedano (null), vodja_id, ustvaril_id, ustvarjeno, spremenil_id (null), spremenjeno (null), checked_out (null), checked_out_time (null), dodatno (null, JSON kot besedilo) | indeksi: (stanje, zacetek), vodja_id, tip_id, tezavnost_id; stanje: 1 = objavljena, 2 = odpovedana |
| `#__belaunce_prijave` | id, runda_id, uporabnik_id, ustvarjeno | UNIQUE (runda_id, uporabnik_id); obstoj vrstice = "pridem", odjava vrstico izbriše |
| `#__belaunce_gostje` | id, runda_id, ime, telefon (null), email (null), odjava_zeton_hash (null), ip, ustvarjeno | vsaj eno od telefon/email; izbris 30 dni po rundi |
| `#__belaunce_zetoni` | id, acym_uporabnik_id, zeton_hash, runda_id (null), potece, porabljen (null), ip, ustvarjeno | UNIQUE zeton_hash; hranjen je samo hash (SHA-256) |
| `#__belaunce_clani` | uporabnik_id (PK), acym_uporabnik_id (UNIQUE), ustvarjeno, zadnja_prijava (null) | povezava Joomla uporabnik ↔ AcyMailing |
| `#__belaunce_poskusi` | id, tip, kljuc, ustvarjeno | indeks (tip, kljuc, ustvarjeno); `kljuc` je hash emaila ali IP, ne surova vrednost |

- Tabele se ustvarjajo postopoma: vsaka naloga doda svoje tabele v namestitveni SQL **in** v SQL za posodobitev svoje verzije.
- **URL runde (predlog, čaka potrditev):** `/runde/{id}-{alias}`. Runda se poišče po `id`; če se alias spremeni, stara povezava še vedno deluje (kanonični URL v `<link rel="canonical">`). **URL-jev po izidu ne spreminjati** (povezave so v Viber zgodovini).

## 6. Prijava in članstvo (hibrid)
1. Obiskovalec vpiše email → komponenta preveri v AcyMailing: uporabnik aktiven in naročen na **listo članov** (ID liste v možnostih).
2. Če da: enkratna povezava (naključen žeton, v bazi samo hash, veljavnost iz možnosti).
3. Odgovor je **vedno enak** ("če naslov obstaja pri nas, smo poslali povezavo; preveri, da uporabljaš naslov, s katerim si včlanjen, ali piši adminu"). Omejitev poskusov po emailu in IP.
4. GET na povezavo pokaže gumb "Prijavi me" (ali "Prijavi me in potrdi udeležbo na rundi *X*", D40); šele POST porabi žeton (zaščita pred mailnimi skenerji).
5. **Joomla uporabnik se ustvari samo ob prvem uspešnem magic-link prijavljanju**, in samo če je email na listi članov. Ne ustvari se ob vpisu na kakršnokoli AcyMailing listo niti ob ustvarjanju ali potrditvi runde. Uporabnik je brez gesla, v skupini **"Člani"** brez dostopa do backenda; ime iz AcyMailing (če je prazno: del emaila pred `@`). Povezava v `#__belaunce_clani`. Pri naslednjih prijavah se uporabi isti uporabnik (iskanje po `acym_uporabnik_id`); če je bil blokiran in je spet na listi, se **odblokira**.
   - Če `acym_uporabnik_id` v AcyMailingu ne obstaja več: iskanje po emailu, **samo** med uporabniki v skupini "Člani"; povezava se posodobi, dogodek se zabeleži.
   - **Varnost:** magic link nikoli ne prijavi v obstoječ Joomla račun, ki ni v skupini "Člani" (npr. admin z istim emailom). Prijava ne uspe in se zabeleži.
   - Seja, odjava in dovoljenja so Joomlini; dolga seja prek "Zapomni si me" (D43).
6. **Neprijavljen klik "Pridem"** (V6, D40): odpre se obrazec za email; ID runde gre k žetonu. Povratni URL (če je potreben) se preveri kot notranji (zaščita pred odprto preusmeritvijo). Če je runda odprta za goste, se ponudita obe možnosti (prijava člana ali prijava gosta).
7. **Izstop člana:** ob prijavi preveri listo; dnevno opravilo (Joomla Scheduler) blokira uporabnike, ki niso več na listi. Njihove runde ostanejo, admin lahko zamenja vodjo.
8. Izvedba kot **vtičnik za avtentikacijo** (Joomla način). Natančen tok v Joomli 6 se preveri v nalogi 03 (spike).
- Alternativa za kasneje: šestmestna koda iz maila.

### 6.1 Nastavljive omejitve (D38)
| Nastavitev | Izbire | Privzeto |
|---|---|---|
| Zahteve za povezavo na email / uro | 3, 5, 10 | 3 |
| Zahteve za povezavo na IP / uro | 10, 20, 50 | 20 |
| Prijave gostov na IP / rundo | 1, 2, 3, 5 | 2 |
| Nove runde na člana / dan | 3, 5, 10 | 5 |
| Veljavnost povezave | 10, 15, 30 min | 15 |

### 6.2 Dostop do AcyMailing
Ves dostop do AcyMailinga gre prek **enega razreda (adapter)**. Če ima AcyMailing uradni PHP API, ga adapter uporabi; sicer bere tabele AcyMailinga samo za branje. Adapter ima ročni testni scenarij. Gostov se v AcyMailing nikoli ne dodaja.

## 7. Vloge in pravice
| Akcija | Neprijavljen | Član | Vodja (svoje runde) | Admin (backend) |
|---|---|---|---|---|
| Ogled seznama in runde | da | da | da | da |
| Imena prijavljenih | ne (samo število) | da | da | da |
| Kontakt gostov | ne | ne | da | da |
| Prijava "Pridem" | gost samo če je runda odprta za goste | da | (samodejno) | da |
| Ustvari rundo | ne | da | da | da |
| Uredi rundo | ne | ne | da (do štarta) | da |
| Odpovej rundo | ne | ne | da (do štarta) | da |
| Izbriši rundo | ne | ne | ne | da |
| Zamenjaj vodjo | ne | ne | ne | da |

- Odpoved namesto brisanja: runda ostane vidna z oznako "odpovedano".
- Admin lahko ustvari rundo v imenu koga drugega.
- **Gostje** se štejejo v skupno število prijavljenih. Člani vidijo ime gosta z značko "gost" (D37), kontakt vidita samo vodja in admin.

## 8. Javno proti članom
- **Javno:** naslov, datum, ura, lokacija, tip, težavnost, dolžina, trajanje, opombe, trasa, število prijavljenih, **polno ime vodje**.
- **Samo člani:** imena prijavljenih (vključno z imeni gostov), komentarji (če jih bo).
- Poziv "Še nisi član? Pridruži se društvu" na seznamu in strani runde.
- **Pretekle runde:** stran ne vrne 404, ampak "Ta runda je končana" + povezava na prihajajoče; `noindex`. Prihajajoče runde indeksirane (po želji strukturirani podatki `Event`).

## 9. Gostje (D8)
- Vodja ob ustvarjanju označi "odprto za goste".
- Gost vpiše **ime** in **telefon ali email** (vsaj eno); kontakt vidita samo vodja in admin. Brez računa.
- **Gost z emailom** dobi potrditveni mail s povezavo za odjavo (enkraten žeton, v bazi hash) in maile o spremembi/odpovedi. Za gosta s samo telefonom je odgovoren vodja.
- Zaščita (D25): honeypot, minimalni čas izpolnjevanja, omejitev prijav (IP, runda). **Joomla Captcha API** (privzeto izklopljeno; brez Googla).
- Opozorilo ob prijavi gosta: samo "Vožnja je na lastno odgovornost."
- Izbris podatkov gostov 30 dni po štartu runde (Scheduler).
- Gostov **ne dodajati** v AcyMailing.

## 10. Obveščanje in Viber
- **Transakcijski maili** (sprememba časa/lokacije, odpoved; 4.1): prijavljenim članom in gostom z emailom prek Joomla Mailerja (D34), neodvisno od AcyMailing.
- **Nove runde / tedenski povzetek:** faza 2, prek liste "Runde".
- **Javni obrazec** (AcyMailing modul) vpisuje **samo** na listo "Runde". To je nastavitev AcyMailinga (kontrolni seznam namestitve), ne koda komponente.
- **Viber (v1):** gumb "Deli v Viber" (`viber://forward?text=…` / `navigator.share()`), pripravljeno besedilo za kopiranje (emoji, datum, težavnost, povezava), Open Graph meta oznake.
- Viber bot ne more pisati v zasebno skupino. Možno kasneje: Viber kanal ali Chat Extension.

## 11. Spremenljivost (D12)
- **Nastavljivo, ne v kodi:** besedila mailov in Viber sporočila, polja, ki sprožijo mail, tipi in težavnosti, ID liste članov in liste "Runde", privzeta lokacija, privzeto trajanje, časovni pas, omejitve poskusov.
- **Nova polja:** opcijska (`NULL`), migracije prek SQL skript za posodobitev komponente.
- **Drago za spremembo:** model identitete, struktura prijav, javno/zaprto, URL-ji rund.
- Uspešnost se ocenjuje interno iz backend statistike, z izvozom v CSV. Pregled po 4–6 tednih uporabe.

## 12. Faze
**Faza 1 (MVP)**
1. Obrazec za rundo (vsa polja, privzeta lokacija, odprto za goste)
2. Javni seznam prihajajočih rund s filtrom tip/težavnost (mobilni in namizni prikaz)
3. Stran runde: "Pridem" (člani), prijava gostov, seznam prijavljenih (člani)
4. Prijava z magic linkom prek AcyMailing, samodejni Joomla uporabnik
5. "Moje runde" (vodim / prijavljen sem / pretekle), urejanje in odpoved lastne runde
6. Gumb "Deli v Viber" + Open Graph
7. Transakcijski maili ob spremembi/odpovedi
8. Samodejno skrivanje preteklih rund, arhiv v bazi
9. Javni obrazec samo za listo "Runde" (nastavitev AcyMailinga, kontrolni seznam)
10. Backend: seznam vseh rund (filtri, urejanje, brisanje, zamenjava vodje), osnovna statistika in izvoz v CSV

Razdelitev na naloge: `docs/TASKS.md`.

**Faza 2:** tedenski povzetek (AcyMailing), kode za prijavo namesto povezave, PWA, .ics v koledar, komentarji, hitra runda "grem danes ob 17h".
**Kasneje:** GPX/Strava/Komoot integracije, vremenska napoved, ponavljajoče runde, statistika sezone, Viber kanal, push obvestila.

## 13. Odprto in za preverjanje
- [ ] **AcyMailing:** na testni listi preveri, da javni obrazec res vpisuje samo na izbrano listo; določi ID liste članov in "Runde".
- [x] Osnutek vmesnika: razlike do md so znane (vodja s polnim imenom tudi na mobilnih karticah; težavnosti Berlingo/Turbo; opozorilo za goste; manjkajo zasloni trasa, obrazec gosta, prijava, odpovedano). Novega PDF ni; zasloni so opisani v nalogah.
- [x] Seja: 15 min + "Zapomni si me" (D43). Vedenje v Viberjevem vgrajenem brskalniku se preveri v nalogi 04.
- [ ] Potrditev oblike URL-jev (predlog v 5).

## 14. Izven obsega v1
Nativne aplikacije in trgovine, Viber bot, push obvestila, plačila, ocenjevanje voženj, javna imena prijavljenih, samodejni vpis gostov v AcyMailing, spremembe predloge strani (D41).

---

## 15. Tehnične zahteve
- **Responsive z Bootstrap 5**: Bootstrapovi razredi in komponente, minimalen lasten CSS s predpono `br-`. Bootstrap JS komponente se naložijo prek WebAssetManagerja (npr. `bootstrap.collapse`, `bootstrap.modal`).
- **Polje "trasa" (neobvezno, D23):** shrani se **samo URL**, ne HTML. Za podprte ponudnike (seznam nastavljiv) komponenta sama sestavi vdelavo iz preverjenega URL-ja, za druge prikaže navaden link. Oblike vdelave se preverijo v aktualni dokumentaciji ponudnikov. Če stran uporablja CSP, je treba dovoliti domene vdelav (`frame-src`).

## 16. Razvoj
- Najnovejše **Joomla 6 smernice in struktura**; pravila iz izkušenj s com_kisegonma so v `docs/ARCHITECTURE.md`.
- Paket je **nameščljiv in posodobljiv** (manifesti, SQL za namestitev in posodobitev, update server na GitHubu, D24, D39).
- **Varnost:** pripravljene SQL poizvedbe (`bind()`), CSRF žetoni na vseh obrazcih, escapiranje izhoda, validacija in filtriranje vhodov, preverjanje pravic pri vsaki akciji, omejitev poskusov, validacija URL-jev (trasa, povratni URL).
- Koda mora delovati **brez vtičnika za združljivost za nazaj** (brez `JHtml`, `JFactory`, `JText` in drugih zastarelih aliasov).
- **Uporabni komentarji**: vsak blok razloži, kaj dela in zakaj.
- **Dokumentacija kode** (`docs/ARCHITECTURE.md`) in **za uporabo** (`docs/NAVODILA.md`: člani, vodje, admin).

### 16.1 Jezik kode (D28)
- **V slovenščini, samo ASCII:** naši razredi, metode, lastnosti, spremenljivke, tabele, stolpci, ključi jezikovnih nizov, imena opravil (`runda.shrani`). Primer: `RundaModel`, `$zacetek`, `tezavnost_id`.
- **V angleščini, ker to zahteva Joomla:** pripone in razredi ogrodja (`DisplayController`, `AdminModel`, `HtmlView`, `Table`), metode, ki jih prepisujemo (`display()`, `save()`, `getItem()`, `getListQuery()`), mape (`src/`, `tmpl/`, `forms/`, `services/`), ključi manifesta, standardni stolpci ogrodja (`id`, `ordering`, `checked_out`, `checked_out_time`).
- **Komentarji in dokumentacija:** slovenščina s šumniki (UTF-8).

### 16.2 Pošiljanje mailov
Pošiljatelj info@belaunce.cc (D34). Besedila v možnostih komponente. Pred zagonom preveri SPF/DKIM (kontrolni seznam).

### 16.3 Razlike DDEV ↔ produkcija (pomembno pri testiranju)
| | DDEV | Produkcija | Posledica |
|---|---|---|---|
| MariaDB | 11.8 | 10.11 | SQL brez funkcij, ki jih 10.11 nima; namestitev na produkcijsko kopijo pred zagonom |
| Collation baze | utf8mb4 | utf8mb3 | tabele vedno izrecno utf8mb4 |
| Spletni strežnik | nginx | LiteSpeed + Imunify360 | brez odvisnosti od `.htaccess`; ob čudnih napakah POST na produkciji preveri dnevnik Imunify360 |
| force_ssl | 0 | 2 | URL-ji vedno prek `Route`/`Uri`, nikoli trdo kodirani |
| `mail()` | dovoljen | izklopljen | samo SMTP |

## 17. Delitev dela
- Ta md je **edini vir resnice**. Izvajalec nima konteksta pogovora, zato morajo md, `AGENTS.md` in naloga zadoščati.
- Izvajalec **ne spreminja odločitev**. Nejasnost ali konflikt zapiše kot vprašanje v PR in ne ugiba.
- Delo v **majhnih korakih** (`docs/naloge/NALOGA-xx.md`), vsak korak ima merila sprejema.
- Po vsakem koraku **pregled arhitekta** in test na DDEV (Matija). Šele nato naslednji korak.
- Sprememba odločitve: **najprej se posodobi md**, nato koda.

## 18. Dopolnitve arhitekta (zahteve)
1. **Struktura:** imenski prostori, `services/provider.php`, WebAssetManager, `access.xml`, SQL za namestitev in posodobitev, namestitveni skript (možnost ohranitve podatkov ob odstranitvi).
2. **Jezik:** vsi nizi v jezikovnih datotekah (`sl-SI` in `en-GB`), nič trdo kodiranega.
3. **Čas:** UTC v bazi, prikaz v `casovni_pas` (D42), pozor na prehod poletni/zimski čas.
4. **GDPR:** povezava z Joomlinimi orodji za zasebnost; izbris gostov po 30 dneh.
5. **Scheduler opravila:** sinhronizacija članov, izbris gostov, čiščenje žetonov in poskusov; (faza 2) tedenski povzetek.
6. **Kakovost:** ročni testni scenariji po nalogah, Joomla coding standard.
7. **Namestitev na produkcijo:** backup (Akeeba), test na kopiji, ZIP paket, načrt povratka.
8. **Dostopnost:** osnove WCAG (kontrasti, tipkovnica, oznake obrazcev, velikost tipk; barva nikoli ni edini nosilec informacije).
9. **Zloraba:** omejitve (6.1), zapis neuspešnih prijav.
10. **Verzioniranje:** semantične verzije (0.x med razvojem, 1.0.0 ob zagonu), `CHANGELOG.md`.
