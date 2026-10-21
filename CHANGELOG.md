# Changelog

Vse pomembne spremembe paketa `pkg_belauncerunde`. Oblika po [Keep a Changelog](https://keepachangelog.com/sl/1.1.0/), verzije po semantičnem verzioniranju.

## [Neizdano]
### Dodano
- Dokumentacija projekta (`docs/`), navodila za izvajalca (`AGENTS.md`).

## [0.10.0] - 2026-10-20
### Dodano
- Transakcijski maili ob spremembi časa ali lokacije in ob odpovedi runde, tako s frontenda kot iz administratorskega obrazca ali seznama (D56).
- Vodja vedno prejme obvestilo (D57); člani in gostje z emailom dobijo vsak svoje sporočilo, enaki naslovi se združijo, dodatno Joomlino kopiranje Bcc je za ti predlogi izključeno.
- Uredljivi predlogi `runda_sprememba` in `runda_odpoved` s sl-SI/en-GB nizi, lokalnim časom D42 in novimi enkratnimi odjavnimi povezavami gostov.
- Zavihek Maili z nastavljivima sprožilnima poljema; možen je izklop obeh, odpoved pa vedno sproži pošto.
- Opozorilo ob napaki pošiljanja ob ohranjenem zapisu runde, dnevniki brez osebnih podatkov ter posodobitveni SQL 0.10.0 brez sprememb sheme.

## [0.9.0] - 2026-10-20
### Dodano
- Zasebni članski pogled **Moje runde** na `/runde/moje`, z zavihkoma Prihajajoče in Pretekle, ločenima sklopoma vodenih rund in prijav ter paginacijo preteklih rund.
- Menijska postavka z nastavljivim številom preteklih rund na stran in prikazom zavihka Pretekle, preusmeritve za neprijavljene in nečlane ter `noindex, nofollow`.
- Urejanje in potrjena odpoved prek obstoječega delnega prikaza ter odjava s povratkom na Moje runde prek zaprte vrednosti `vrni=moje`.
- Skupni SQL izraz števila udeležencev v `PrijaveModel`, uporabljen tako na strani runde kot na članskem seznamu, ter uporaba že prebranih aliasov pri gradnji povezav brez poizvedb v zanki.
- Prevodi sl-SI/en-GB, dokumentacija in posodobitveni SQL 0.9.0 brez sprememb sheme ali podatkov.

## [0.8.0] - 2026-10-19
### Dodano
- Prijava gostov brez računa na odprte objavljene runde pred štartom, z imenom in vsaj enim preverjenim kontaktom.
- Tabela gostov, namestitveni in posodobitveni SQL, soljen SHA-256 omrežnega naslova in hash enkratnega žetona za odjavo.
- Honeypot, minimalni čas v seji, transakcijska omejitev gostov na naslov in rundo ter izbirna Joomla captcha (privzeto izklopljena).
- Poštna predloga `com_belauncerunde.gost_potrditev` z lokalnim časom; stran `/runde/odjava-gosta` in odjava samo s POST in CSRF.
- Gostje v skupnem številu udeležencev, imena z značko »gost« za člane in kontakt samo za vodjo runde.
- Nastavitve zaščite, prevodi sl-SI/en-GB in navodila za goste, vodje ter administratorja.

### Popravljeno
- Lasten zapisovalnik izrecno določi štiri polja brez IP-ja obiskovalca; privzeti stolpec `CLIENTIP` se ne zapisuje več. Dodano pravilo 24, navodilo za izbris starega dnevnika pred produkcijo in merilo sprejema 21.

## [0.7.0] - 2026-10-19
### Dodano
- Nastavljiva omejitev 3/5/10 novih rund na člana v drsečih 24 urah (privzeto 5), preverjena ob odpiranju in tik pred zapisom; štejejo tudi odpovedane runde, urejanje ostane dovoljeno.
- Frontend obrazec za ustvarjanje in urejanje lastne runde z objavljenimi šifranti, lokalnim časom prek `CasHelper` in ohranitvijo vnosa ob napaki.
- Zaščiteni POST akciji `runda.shrani` in `runda.odpovej`; preverjanje članstva oziroma lastništva v kontrolerju in modelu, zaklep ob štartu ter prepoved sprememb odpovedane runde.
- Gumbi za ustvarjanje, urejanje in odpoved na seznamu in strani runde ter potrditev odpovedi z Bootstrap modalom brez dodatnih skript.
- SEF poti `/runde/nova` in `/runde/uredi-{id}-{alias}` ob ohranjenih dolgih povezavah in obstoječih URL-jih rund.
- Preverjene vdelave tras Strava, Komoot in Bikemap z zaprtim seznamom gostiteljev in poti ter posamičnim izklopom v možnostih komponente.
- Prevodi sl-SI/en-GB, navodila za člane in vodje, viri oblik vdelav ter navodilo za CSP `frame-src`.
- Posodobitveni SQL 0.7.0 brez sprememb sheme in podatkov; vodja ostane udeleženec brez nove vrstice prijave (D54).

### Popravljeno
- Izpraznjena seja obrazca in napak se obravnava kot prazno polje: nova runda ponovno naloži privzetke, urejanje obstoječe pa podatke iz baze, vključno z opombami, trajanjem in traso. Neuspešni vnos se ohrani; enako so normalizirana branja seje v administratorskih obrazcih.

## [0.6.0] - 2026-10-18
### Dodano
- Tabela članskih prijav z unikatnim parom runda/uporabnik ter namestitvenim, posodobitvenim in odstranitvenim SQL.
- Zaščiteni akciji **Pridem** in **Odjavi se**, preverjanje članske skupine v kontrolerju in modelu ter idempotentna prijava in odjava do štarta runde.
- Število udeležencev za javnost in seznam imen samo za člane; obstoječi vodja je prvi in se šteje brez vrstice prijave (D54).
- Transakcijsko ohranjanje D54 tudi ob administratorski zamenjavi vodje z že prijavljenim članom.
- Dokončan tok D40: po prijavi z magic linkom se ID runde takoj odstrani iz seje in uporabi ista metoda za potrditev udeležbe.
- Zaprte kode povratnih obvestil, pojasnila ob odpovedi in štartu ter obvestilo in `noindex, follow` za končane runde.
- Open Graph oznake z javnimi podatki, lokalnim časom in kanoničnim URL-jem brez opomb in imen udeležencev.

### Popravljeno

- Tok D40 in običajni akciji udeležbe za medtem izbrisano ali nejavno rundo vrnejo na seznam z opozorilom namesto na 404; odpovedana ali štartana runda ostane ločeno obravnavana kot zaprta.
- Dnevnik zabeleži dodajanje oziroma odstranjevanje prijave samo ob dejanski spremembi, ne ob ponovitvi iste zahteve.

## [0.5.0] - 2026-10-16
### Dodano
- Javni seznam prihajajočih in še trajajočih rund s filtri GET po tipu in težavnosti ter Joomlino paginacijo.
- Javna stran runde s časom prek `CasHelper`, oznako odpovedi, varnimi opombami in preverjeno povezavo na traso.
- SEF router po vzorcu `com_contact`: `/runde` in `/runde/{id}-{alias}` ob menijski postavki za seznam; stare povezave delujejo po ID-ju, canonical uporablja trenutni alias.
- Tip menijske postavke **Seznam rund** z nastavitvama števila rund na stran in vidnosti filtrov.
- Minimalni slog prek WebAssetManagerja in prevodi v slovenščini ter angleščini.
- Posodobitveni SQL za verzijo 0.5.0 brez sprememb sheme ali podatkov.

### Spremenjeno
- Privzeti pogled strani je javni seznam rund; prijava članov ostaja dostopna prek svojega pogleda in menijske postavke.

## [0.4.0] - 2026-10-14
### Dodano
- Site obrazec za prijavo člana z emailom in potrditev enkratne povezave prek POST.
- Tabele za SHA-256 žetone, povezave članov in hashirane ključe omejitev poskusov.
- `OmejitevHelper`, `ZetonService` in `ClanService` za omejitve, atomarno porabo in članske Joomla račune.
- Avtentikacijski vtičnik s ponovnim preverjanjem AcyMailing članstva in zavrnitvijo tujega računa; povezanemu članu se po vrnitvi na listo zagotovi skupina Člani.
- Nastavitve omejitev, veljavnosti povezave in Joomlinega »Zapomni si me«.
- Uredljiva Joomlina predloga maila `com_belauncerunde.prijava` v slovenščini in angleščini.
- Samodejni vklop našega vtičnika in opozorilo na potrebna jedrna piškotkovna vtičnika.

### Spremenjeno
- Paket namesti avtentikacijski vtičnik pred komponento, gradnja pa zapakira vse mape `paket/plg_*`.
- Namestitev in odstranitev upoštevata novo poštno predlogo ter možnost ohranitve podatkov.

### Popravljeno
- Ustvarjanje članskega računa poda `User::bind()` spremenljivko namesto literala, kot zahteva podajanje po referenci.
- Komponenta ob zagonu enkrat na zahtevo registrira lasten dnevnik `com_belauncerunde.php` za oba odjemalca.
- Diagnostika neuspešne avtentikacije vsebuje razred izjeme, datoteko in vrstico brez sporočila izjeme.
- Prijavni obrazec uporablja filter `string` z validacijo emaila, povezavi na domačo stran pa neposredni `Uri::root()`.
- Prijavni pogled sam izriše povratno informacijo iz zaprtega seznama kod stanja v URL-ju; vse zahteve za pošto ohranijo enoten odgovor, sistemska sporočila pa ostanejo.
- Članstvo se uveljavlja prek skupine Člani brez spreminjanja `block`: blokiran račun je zavrnjen, manjkajoča skupina se vrne samo uporabniku z našo povezavo, druge skupine pa ostanejo.

## [0.3.0] - 2026-10-10
### Dodano
- Bralni `AcymailingAdapter` za preverjanje članstva po AcyMailing listi.
- Nastavitve AcyMailing list članov in rund v možnostih komponente.
- Administrativno orodje **Preveri člana** za diagnostiko emaila in števila članov liste.

### Spremenjeno
- Obvestila pri zaščitenem brisanju tipov in težavnosti uporabljajo množinske jezikovne nize.

## [0.2.0] - 2026-10-09
### Dodano
- Administracijska tabela, seznam in obrazec za runde z odpovedjo, obnovo, brisanjem in zamenjavo vodje.
- Pretvorba časa rund med UTC in časovnim pasom iz možnosti komponente.
- Filtri seznama rund po tipu, težavnosti, stanju, vodji in obdobju.
- Polji šifrantov za tip in težavnost ter zaščita brisanja uporabljenih šifrantov.
- Nastavitve za časovni pas rund, privzeto lokacijo in privzeto trajanje.

## [0.1.0] - 2026-10-08
### Dodano
- Okostje paketa `pkg_belauncerunde` in komponente `com_belauncerunde`.
- Namestitveni SQL za šifranta tipov in težavnosti z začetnimi vrednostmi.
- Administracijska CRUD pogleda za tipe in težavnosti.
- Namestitveni skript za pripravo skupine **Člani** in možnost ohranitve podatkov ob odstranitvi.
- Gradbena skripta `orodja/gradnja.sh` in update server `posodobitve.xml`.
