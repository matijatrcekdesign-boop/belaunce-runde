# Navodila za uporabo — Runde Belaunce

Navodila za člane, vodje, goste in administratorje paketa Runde Belaunce.

## Namestitev aplikacije

V Chromu na strani rund izberi **Namesti aplikacijo**, ko se gumb pojavi, ali uporabi ikono za namestitev v naslovni vrstici. Na iPhonu v Safariju izberi **Deli → Dodaj na domači zaslon**; namig na strani lahko zapreš. Nameščena aplikacija se odpre v samostojnem oknu. Za ogled rund in prijavo je še vedno potrebna internetna povezava; brez nje aplikacija pokaže kratko obvestilo. Na ločeni Joomla namestitvi mora imeti javna menijska postavka seznama alias `runde`. Če je alias drugačen, ročno popravi `start_url` v `media/com_belauncerunde/manifest.webmanifest`; pri namestitvi Joomle v podmapo popravi še `scope`.

## Videz rund

Kartice na seznamu in v pogledu **Moje runde** imajo barvno zgornjo črto, čip tipa in krogec z začetnico vodje. Barvo določi šifrant tipov v administraciji; tip brez barve dobi nevtralno barvo. Tip, težavnost in odpoved so vedno navedeni tudi z besedilom. Modri merilnik težavnosti prikazuje objavljene ravni po vrstnem redu v šifrantu. Stran runde pokaže dolžino, trajanje in težavnost v ploščicah; dolžina uporablja decimalno vejico in nima odvečne ničle. Na telefonu se kartice zložijo v en stolpec, tipke pa imajo povečano območje za dotik.

## Za administratorja

### Barva tipa

V *Komponente → Runde Belaunce → Tipi* odpri tip in izberi **Barva tipa**. Shrani in preveri barvni kvadratek ter hex vrednost na seznamu tipov. Barva se uporabi na vseh karticah in podstraneh rund tega tipa. Prazna barva pomeni nevtralno sivo. Začetne barve so MTB `#2a76e0`, Cestno `#f2b974` in Gravel `#8f592f`.

### Povezava za članstvo

V *Komponente → Runde Belaunce → Možnosti* nastavi **Povezava za članstvo** na notranjo pot ali celotno povezavo `https://…`. Nečlan na strani runde ob pozivu vidi gumb **Postani član**. Če je polje prazno, ostane samo besedilo poziva.

### Namestitev in posodobitev
1. V repozitoriju zaženi `bash orodja/gradnja.sh`.
2. Nastali ZIP iz mape `dist/` namesti v Joomli prek *Sistem → Namesti → Razširitve → Naloži paket* ali prek CLI.
3. Po namestitvi preveri, da je v meniju *Komponente* viden vnos **Runde Belaunce**.
4. V *Komponente → Runde Belaunce → Možnosti* preveri zavihek **Splošno**. Skupina članov mora biti nastavljena na **Člani**.

Pri odstranitvi paket privzeto ohrani podatke (`Ohrani podatke ob odstranitvi = Da`): osem tabel in štiri poštne predloge ostanejo. Če želiš podatke izbrisati, najprej naredi varnostno kopijo, nato v možnostih nastavi **Ohrani podatke ob odstranitvi = Ne**. Skript odstrani tabele `#__belaunce_tipi`, `#__belaunce_tezavnosti`, `#__belaunce_runde`, `#__belaunce_prijave`, `#__belaunce_gostje`, `#__belaunce_zetoni`, `#__belaunce_clani` in `#__belaunce_poskusi` ter predloge `com_belauncerunde.prijava`, `com_belauncerunde.gost_potrditev`, `com_belauncerunde.runda_sprememba` in `com_belauncerunde.runda_odpoved`. Skupina **Člani** in Joomla uporabniki ostanejo v obeh primerih. Pri neuspehu odstranitve preveri kodo v dnevniku in stanje tabel; skript odstranitve ne prekine. Lastna datoteka dnevnika ostane in se po potrebi ročno odstrani po pravilih hrambe dnevnikov.

### Dnevnik brez IP-ja in čiščenje pred produkcijo

Lasten dnevnik `administrator/logs/com_belauncerunde.php` po popravku zapisuje samo datum, raven, kategorijo in sporočilo, ločene s tabulatorji. Stara datoteka na DDEV vsebuje vrstice z IP-jem in glavo s stolpcem `clientip`; namestitev popravka jih ne odstrani.

Pred ponovnim preverjanjem na DDEV in **pred zagonom na produkciji izbriši datoteko dnevnika `com_belauncerunde.php`** iz Joomline nastavljene mape dnevnikov (na DDEV `administrator/logs/`). Ob naslednjem dogodku jo zapisovalnik ustvari znova z novo glavo. Starega testnega dnevnika ne prenašaj na produkcijo.

**Merilo sprejema 21 (naloga 08):** Po popravku nova vrstica v `administrator/logs/com_belauncerunde.php` nima stolpca z IP naslovom; vsebuje samo datum, raven, kategorijo in sporočilo. Po namestitvi paketa in izbrisu starega dnevnika na DDEV sproži zavrnjeno prijavo gosta (npr. izpolni honeypot). Preveri, da ima glava `#Fields` samo `datetime`, `priority`, `category`, `message`, vsaka nova podatkovna vrstica pa štiri polja, ločena s tabulatorji, brez `clientip` ali IP-ja obiskovalca.

### Menijska postavka za runde

1. V *Meniji → Main Menu → Nov* izberi tip **Runde Belaunce → Seznam rund**.
2. Nastavi naslov (npr. Runde), alias **runde**, stanje **Objavljeno** in dostop **Public**, da seznam deluje tudi odjavljenim obiskovalcem.
3. V osnovnih možnostih nastavi **Število rund na stran** (privzeto 20) in **Prikaži filtre** (privzeto Da). Za preverjanje paginacije lahko začasno nastaviš 2 rundi na stran.
4. V globalnih nastavitvah Joomle vključi **Search Engine Friendly URLs**. Za pot brez `index.php` mora biti pravilno nastavljeno tudi prepisovanje URL-jev na strežniku.
5. Odpri `/runde` in klikni naslov runde. Povezava mora imeti obliko `/runde/{id}-{alias}`.

Brez te menijske postavke Joomla uporablja `/component/belauncerunde/…`; to je pričakovano. Seznam je dosegljiv tudi neposredno na `index.php?option=com_belauncerunde&view=runde`. Pogleda posamezne runde ni mogoče izbrati kot tip menijske postavke. Prijava za člane ostaja samostojna menijska postavka oziroma pogled `view=prijava`.

Filtra se oddata z gumbom **Filtriraj**, izbira je v URL-ju in povezavo lahko deliš. **Počisti** vrne na seznam brez filtrov. Če filtre v menijski postavki skriješ, se obrazec ne prikaže; filtri v že deljenih povezavah še vedno veljajo.

### Menijska postavka Moje runde

1. V *Meniji → Main Menu → Nov* izberi tip **Runde Belaunce → Moje runde**.
2. Nastavi naslov **Moje runde**, alias **moje**, nadrejeno postavko **Runde**, stanje **Objavljeno** in dostop **Public**. Dostop preveri komponenta; tako neprijavljeni obiskovalec dobi naš obrazec za prijavo članov.
3. Nastavi **Število preteklih rund na stran** (privzeto 20) in **Prikaži zavihek Pretekle** (privzeto Da). Za test paginacije nastavi število na 1. Če zavihek skriješ, tudi neposredna povezava na pretekle odpre prihajajoče.
4. Ob vključenem SEF odpri `/runde/moje`. Deluje tudi `index.php?option=com_belauncerunde&view=moje`, za pretekle dodaj `&pogled=pretekle`.

### Runde v administraciji
Runde so privzeti pogled pod *Komponente → Runde Belaunce*. Seznam omogoča iskanje po naslovu ali lokaciji, iskanje po ID z obliko `id:12`, filtre po tipu, težavnosti, stanju, vodji in obdobju ter razvrščanje po začetku, naslovu, stanju in ID.

Za novo rundo izberi **Nov**. Lokacija je predizpolnjena iz možnosti komponente, vodja pa s trenutnim uporabnikom. Obvezni podatki so naslov, tip, težavnost, lokacija, začetek, dolžina in vodja. Čas vneseš v časovnem pasu, nastavljenem v možnostih komponente; v bazi se shrani kot UTC.

Rundo lahko v obrazcu ali na seznamu označiš kot **Odpovedano** z gumbom **Odpovej**. Gumb **Obnovi** jo vrne v stanje **Objavljena**. Zamenjava vodje se naredi v obrazcu runde z izbiro drugega uporabnika v polju **Vodja**. Brisanje runde je mogoče samo v administraciji.

V *Možnosti → Splošno* so za runde pomembna polja **Časovni pas rund**, **Privzeta lokacija** in **Privzeto trajanje**. Privzeto trajanje se uporablja pri filtrih in oznaki preteklih rund, kadar runda nima vpisanega trajanja.

### Nastavitev AcyMailinga
V *Komponente → Runde Belaunce → Možnosti → AcyMailing* izberi:

- **Lista članov**: lista, ki določa članstvo v društvu in dovoljenje za poznejšo prijavo z magic linkom.
- **Lista Runde**: lista za obvestila o rundah; v v1 je nastavitev samo pripravljena za poznejši povzetek.

Na DDEV sta preverjena ID-ja **6** za člane in **9** za listo Runde. Na produkciji sta ID-ja lahko drugačna, zato list vedno izberi po imenu v spustnem seznamu.

Če je komponenta na ločeni Joomla namestitvi na poddomeni, AcyMailing pa na glavni strani, morata biti bazi na isti MySQL instanci; uporabnik baze poddomene potrebuje pravico `SELECT` za bazo AcyMailinga (npr. `GRANT SELECT ON glavna_baza.* TO 'uporabnik'@'host'`). V *Možnosti → AcyMailing* vpiši **Zunanja baza AcyMailing** in **Predpona tabel oddaljene namestitve**, shrani, ponovno izberi **Lista članov** in **Lista Runde** iz oddaljene baze ter znova shrani. Nato rezultat preveri v **Preveri člana**.

### Preveri člana
Orodje je v administraciji pod *Komponente → Runde Belaunce → Preveri člana* in je namenjeno hitremu testu po posodobitvah AcyMailinga ali spremembah list.

Vpiši email in izberi **Preveri**. Rezultat pove, ali je naslov član, neveljaven, neaktiven, odjavljen, ni naročnik ali ni na listi članov. Pri članu se prikaže še AcyMailing ID, ime in podatek, ali je naročnik povezan z Joomla uporabnikom. Orodje emaila ne zapisuje v dnevnik in ne spreminja AcyMailing podatkov.

### Nastavitve prijave
V *Komponente → Runde Belaunce → Možnosti → Prijava* nastavi:

- **Nove runde na člana v 24 urah**: 3, 5 ali 10 (privzeto 5). Omejitev šteje ustvarjene runde v zadnjih 24 urah, tudi odpovedane. Ob doseženi meji član še vidi **Ustvari rundo**, vendar obrazec pokaže obvestilo na seznamu. Urejanje obstoječe runde pred štartom ostane dovoljeno; odpoved ne sprosti kvote.
- **Zahteve na email na uro**: 3, 5 ali 10 (privzeto 3).
- **Zahteve na IP na uro**: 10, 20 ali 50 (privzeto 20).
- **Veljavnost povezave**: 10, 15 ali 30 minut (privzeto 15).
- **Zapomni si me**: privzeto vključeno. V *Sistem → Vtičniki* vklopi oba vtičnika **Authentication – Cookie** in **System – Remember Me**. Trajanje piškotka ureja nastavitev `cookie_lifetime` v vtičniku Cookie (privzeto 60 dni).

Paket ob namestitvi sam vklopi **Authentication - Belaunce runde**. Če manjka kateri od obeh jedrnih vtičnikov za daljšo prijavo, namestitev prikaže opozorilo. Emaili nečlanov in presežene omejitve uporabniku vedno pokažejo isto sporočilo kot uspešno pošiljanje; tehnične težave preveri v dnevniku kategorije `com_belauncerunde`.

### Besedilo maila
V *Sistem → Predloge za pošto (System → Mail Templates)* poišči `com_belauncerunde.prijava`. Uredi zadevo in telo. Razpoložljive oznake so `{IME}`, `{POVEZAVA}`, `{VELJAVNOST}` in `{SITENAME}`; oznako `{POVEZAVA}` ohrani, da se član lahko prijavi. Posodobitev paketa tvojih sprememb ne prepiše. Pošiljanje uporablja Joomline nastavitve pošte; na produkciji nastavi SMTP in dogovorjenega pošiljatelja, na DDEV Mailpit.

### Urejanje besedila za Viber

V **Komponente → Runde Belaunce → Možnosti → Viber** uredi **Predloga besedila** in shrani. Prazno polje pomeni privzeto besedilo z emojiji v trenutnem jeziku strani. Prelome v lastni predlogi vneseš s tipko Enter. Posodobitev paketa shranjene predloge ne prepiše.

Razpoložljive oznake so:

| Oznaka | Vsebina |
|---|---|
| `{NASLOV}` | Naslov runde |
| `{DAN}` | Polno prevedeno ime dneva |
| `{DATUM}` | Lokalni datum |
| `{URA}` | Lokalna ura |
| `{LOKACIJA}` | Lokacija štarta |
| `{TIP}` | Tip vožnje |
| `{TEZAVNOST}` | Težavnost |
| `{DOLZINA}` | Dolžina s km |
| `{TRAJANJE}` | Trajanje z minutami; prazno, če ni vpisano |
| `{VODJA}` | Javno polno ime vodje; prazno, če ni več na voljo |
| `{POVEZAVA}` | Absolutna kanonična povezava na rundo |

Ohrani `{POVEZAVA}`, da prejemniki lahko odprejo rundo in se prijavijo. Neznane oznake ostanejo dobesedno zapisane. Imen udeležencev ali gostov, njihovih kontaktov, opomb in trase ni mogoče vključiti z oznakami. Datum in ura uporabljata časovni pas komponente, tudi ob prehodu med poletnim in zimskim časom.

### Ko član ni več član
Članstvo določa aktivni naročnik AcyMailinga z aktivno naročnino na listo članov. Odjava samo z liste Runde ne vpliva na dostop. Ob porabi vsake povezave se članstvo preveri znova; če je član medtem izstopil, prijava ne uspe. Dnevno vzdrževalno opravilo odvzame skupino **Člani** povezanim uporabnikom, ki niso več aktivni člani; za delovanje ga mora administrator nastaviti v Schedulerju.

Joomla uporabnika člana **ne briši in ne blokiraj** (D47). Izbris lahko prek AcyMailingove nastavitve izbriše tudi naročnika; blokada lahko ustavi pošto z drugih klubskih list. Dostop odvzemi samo z odstranitvijo skupine **Člani**. Ob vrnitvi na listo vzdrževalno opravilo skupino spet doda. Računa zunaj skupine Člani magic link ne prevzame, tudi če ima isti email.

### Šifranti: tipi in težavnosti
Šifranti so v administraciji pod *Komponente → Runde Belaunce*:

- **Tipi**: začetne vrednosti so MTB, Cestno in Gravel.
- **Težavnosti**: začetni vrednosti sta Berlingo in Turbo.

Za dodajanje izberi **Nov**, izpolni obvezni **Naziv** in po potrebi **Alias** ter **Opis**. Če alias pustiš prazen, ga komponenta ustvari iz naziva. Zapise lahko objaviš, skriješ, razvrstiš z vlečenjem, iščeš po nazivu ali aliasu ter izbrišeš.

Tipa ali težavnosti, ki ju uporablja vsaj ena runda, ni mogoče izbrisati. Skrit tip ali težavnost ostane vidna v obrazcu obstoječe runde z oznako **(skrito)**.

## Za vodje rund

### Kako vodja odpove rundo

Na strani svoje objavljene runde ali na njeni kartici na seznamu pred štartom izberi **Odpovej**. Odpre se potrditveno okno. **Prekliči** ga zapre, **Odpovej** pa rundo označi kot odpovedano. Prijave članov ostanejo shranjene. Odpovedana runda ostane javna z značko **Odpovedano**, urejanje in ponovna odpoved nista mogoča. Vrnitev v objavljeno stanje je mogoča samo v administraciji.

Za spremembo svoje runde pred štartom izberi **Uredi**, popravi podatke in klikni **Shrani rundo**. Vodje ne moreš zamenjati in runde ne moreš izbrisati na strani. Ob štartu sta urejanje in odpoved zaklenjena, tudi če runda zaradi trajanja še ostaja na seznamu.

### Trasa in vdelave

V obrazec vpiši povezavo na traso, nikoli kode HTML. Javne HTTPS trase Strava (`/routes/…`), Komoot (`/tour/…`) in Bikemap (`/en/r/…` oziroma `/r/…`) se samodejno vdelajo. Pod traso je vedno tudi povezava v novem oknu. Drugi veljavni HTTP/HTTPS naslovi se prikažejo kot povezava; `javascript:` in `data:` nista dovoljena. Kratke delilne povezave zamenjaj s polnim naslovom trase. Zasebna trasa pri ponudniku za obiskovalce morda ne bo vidna.

Administrator lahko v *Komponente → Runde Belaunce → Možnosti → Trasa* izklopi vdelavo za vsakega ponudnika posebej; shranjeni URL ostane in se izpiše kot povezava. Če je vklopljen CSP, v direktivi **frame-src** dovoli izvore vključenih ponudnikov: `https://strava-embeds.com`, `https://www.komoot.com`, `https://widgets.bikemap.net`.

## Za člane

### Kako član ustvari rundo

1. Prijavi se kot član društva. Na seznamu ali strani runde izberi **Ustvari rundo** (`/runde/nova`).
2. Vpiši naslov, tip, težavnost, datum in uro štarta, lokacijo ter dolžino. Lokacija je privzeto **Gorenja vas**, če je administrator ni spremenil. Začetek je prazen, **Odprto za goste** je privzeto **Ne**.
3. Uro vpiši v časovnem pasu rund (privzeto **Europe/Ljubljana**). Poletni in zimski čas komponenta upošteva sama; v bazi shrani UTC.
4. Po želji dodaj trajanje v minutah (1–1440), opombe kot navadno besedilo ter povezavo na traso. Če trajanje pustiš prazno, se za prikaz konca uporabi privzeto trajanje iz možnosti. Če izbereš **Odprto za goste**, se lahko gost prijavi na objavljeno rundo do štarta.
5. Izberi **Shrani rundo**. Odpre se stran nove runde; si njen vodja in že šteješ kot en udeleženec. Posebna prijava ni potrebna. Ob napaki se vnos ohrani in obrazec prikaže obvestilo.

Rundo urejaš in odpoveš samo kot njen vodja do štarta. Obiskovalci in prijavljeni nečlani gumba za ustvarjanje ne vidijo.

### Kako deliš rundo v Viber

1. Kot član ali vodja odpri objavljeno rundo pred štartom. Pod gumbom za udeležbo oziroma obrazcem gosta je **Deli rundo**. Blok je na voljo tudi neprijavljenim obiskovalcem in nečlanom.
2. Na telefonu z nameščenim Viberjem izberi **Deli v Viber**, nato pogovor oziroma skupino društva in pošlji pripravljeno besedilo. Povezava v sporočilu vodi na javno stran runde; predogled uporablja njene Open Graph podatke.
3. Če brskalnik ponuja **Deli …**, lahko uporabiš sistemsko deljenje in izbereš Viber ali drugo aplikacijo. Preklic izbire ne izpiše napake.
4. **Kopiraj besedilo** kopira celotno sporočilo. Ob neuspehu se besedilo označi in prikaže navodilo za ročno kopiranje. Tudi brez JavaScripta je polje **Besedilo za objavo** vidno: označi ga, kopiraj in prilepi v Viber. Povezava **Deli v Viber** prav tako deluje brez JavaScripta.

V **Moje runde → Prihajajoče** je **Deli v Viber** tudi ob gumbih na karticah obeh sklopov, **Runde, ki jih vodim** in **Prijavljen sem**. Odpovedane runde, runde po štartu in zavihek **Pretekle** nimajo deljenja. Na javnem seznamu rund deljenja ni. Besedilo vključuje samo izbrane javne podatke in ime vodje; imen drugih udeležencev, gostov, kontaktov, opomb in trase ne vsebuje.

### Prijava za člane
Odpri stran za prijavo (`index.php?option=com_belauncerunde&view=prijava` oziroma menijsko postavko **Prijava za člane**). Vpiši email, s katerim si včlanjen v društvo, in klikni **Pošlji povezavo**. Vedno se prikaže obvestilo, da je bila povezava poslana, če društvo pozna naslov. Če maila ni, preveri pravi naslov in neželeno pošto ali piši društvu.

Odpri povezavo iz maila in klikni **Prijavi me**. Samo odpiranje povezave te še ne prijavi; zato je ne porabijo mailni skenerji. Povezava velja privzeto 15 minut in jo lahko uporabiš samo enkrat. Nova zahteva razveljavi prejšnjo povezavo. Pri neveljavni ali potekli povezavi izberi **Zahtevaj novo povezavo**. Če si že prijavljen, obrazca ni; prikazana sta obvestilo in povezava na domačo stran.

Ob prvi prijavi se račun ustvari samodejno, gesla ne izbiraš. Naslednje prijave uporabijo isti račun. Če je vključena daljša prijava, Joomlin piškotek ohrani prijavo tudi po izteku kratke seje; odjaviš se z običajno Joomlino odjavo.

### Prijava na rundo

Kot prijavljen član na strani prihajajoče runde vidiš imena udeležencev in gumb **Pridem**. Po kliku je udeležba potrjena, tvoje ime se pojavi na seznamu in gumb se spremeni v **Odjavi se**. Z odjavo odstraniš svojo prijavo. Ponoven klik ali ponovna oddaja istega obrazca ne podvoji prijave. Vodja je udeležen samodejno, prvi na seznamu z oznako **Vodja**, in nima gumba za prijavo ali odjavo.

Če nisi prijavljen, vidiš število udeležencev in javno ime vodje. **Pridem** odpre prijavni obrazec za izbrano rundo. Zahtevaj povezavo, odpri mail in klikni **Prijavi me in potrdi udeležbo na rundi …**. Po uspešni prijavi se samodejno potrdi tudi udeležba in odpre stran runde. Samo odprtje povezave v mailu še ne potrdi udeležbe. Če je bila runda medtem odpovedana ali je štartala, se prijaviš na stran, udeležba pa se ne zapiše; na strani runde je obvestilo.

Prijavljen uporabnik brez članske skupine vidi samo število in poziv za včlanitev. Imena udeležencev vidijo samo člani; njihovi emaili, telefoni in IP-ji se na strani runde ne izpisujejo.

### Odpovedana in končana runda

Odpovedana runda ostane vidna z značko **Odpovedano** in pojasnilom; prijava in odjava nista mogoči. Tudi po štartu se udeležbe ne da več spreminjati, runda pa ostane na seznamu do svojega konca. Če trajanja nima, velja privzeto trajanje iz možnosti komponente.

Po koncu neposredna povezava še vedno deluje in pokaže **Ta runda je končana** ter povezavo na prihajajoče runde. Iskalnikom stran naroči, naj končane runde ne indeksirajo. Predogled deljene povezave vsebuje naslov, datum in uro, tip, težavnost ter dolžino; imen udeležencev in opomb v predogledu ni.

## Moje runde

Kot prijavljen član odpri **Moje runde**. Če še nisi prijavljen, se odpre obrazec za prijavo članov. Prijavljen uporabnik brez članske skupine se vrne na javni seznam z obvestilom.

Privzeti zavihek **Prihajajoče** ima sklopa **Runde, ki jih vodim** in **Prijavljen sem**. Tvoja vodena runda je samo v prvem. Na kartici vidiš naslov s povezavo, lokalni datum in uro, tip, težavnost, lokacijo, dolžino, ime vodje ter skupno število članov, vodje in gostov. Imen drugih udeležencev in kontaktov gostov na tem pregledu ni. Prazni sklopi pokažejo pojasnilo.

Pred štartom objavljene runde lahko svojo rundo **urediš** ali **odpoveš** s potrditvijo v pogovornem oknu; po shranjevanju ali odpovedi se odpre stran runde. Z runde, na katero si prijavljen, se lahko pred štartom **odjaviš** in se vrneš na Moje runde z obvestilom. Odpovedane runde imajo značko **Odpovedano** in nimajo teh gumbov. Runda, ki že poteka, ostane med prihajajočimi do konca, vendar nima gumbov za spremembe.

Zavihek **Pretekle** pokaže tvoje končane runde od najnovejše, z oznako, ali si jih vodil ali bil prijavljen. Upošteva se začetek in trajanje, pri praznem trajanju privzeto trajanje iz možnosti. Odpovedanih rund tu ni. Daljši seznam ima povezave med stranmi; akcij za spreminjanje preteklih rund ni.

Na vrhu je **Ustvari rundo**. Dnevna omejitev ostaja ista kot pri ustvarjanju z drugih strani; ob doseženi meji obrazec pokaže obvestilo.

## Kaj vidi obiskovalec

Seznam in posamezne runde so javni, tudi v zasebnem oknu brez prijave. Seznam prikazuje naslov, datum in uro štarta, tip, težavnost, lokacijo, dolžino in polno ime vodje. Ura je v časovnem pasu iz možnosti komponente (privzeto Europe/Ljubljana), ne v časovnem pasu Joomle ali obiskovalca.

Odpovedane runde ostanejo vidne z oznako **Odpovedano**. Runda izgine s seznama šele po začetku in trajanju; če trajanje ni vpisano, se uporabi privzeto trajanje iz možnosti. Njena neposredna povezava ostane dostopna tudi pozneje.

Stran runde prikaže še trajanje, opombe in veljavno povezavo na traso v novem oknu. Če vodja ne obstaja več, je njegovo ime prazno. Sprememba aliasa ne pokvari stare povezave: runda se poišče po ID-ju, kanonična povezava v izvorni kodi pa kaže trenutni URL. Neobstoječa ali nejavna runda vrne 404. Od verzije 0.6.0 je na strani tudi število udeležencev; njihova imena vidijo samo člani. Nečlanom se izpiše poziv za včlanitev. Imena gostov vidijo člani, njihove kontakte samo vodja runde.

## Gostje

### Kako se prijavi gost
1. Odpri stran prihodnje runde, ki jo je vodja označil kot odprto za goste. Obrazec je na voljo do štarta; na odpovedani rundi ga ni.
2. V obrazec **Prijava gosta** vpiši ime (2–100 znakov) in vsaj telefon ali email. Računa ne potrebuješ. Tudi prijavljen nečlan lahko uporabi ta obrazec; član uporablja **Pridem**.
3. Počakaj vsaj nekaj sekund in klikni **Prijavi me kot gosta**. Nad gumbom je opozorilo »Vožnja je na lastno odgovornost.« Če je vključena captcha, jo izpolni.
4. Če si vpisal email, prejmeš potrdilo z naslovom runde, lokalnim časom, lokacijo, vodjo in povezavo za odjavo. Odprtje povezave prikaže gumb **Potrdi odjavo**; šele klik izbriše prijavo. Povezava je enkratna in velja do izbrisa prijave. Pri ponovni uporabi se pokaže nevtralno obvestilo.
5. Če si vpisal samo telefon, potrditvenega maila in povezave za odjavo ni; za spremembe se obrni na vodjo. Če pošiljanje pošte odpove, prijava ostane veljavna in stran pokaže opozorilo.

### Kaj vidi vodja
Člani vidijo najprej vodjo, nato prijavljene člane in nazadnje goste po času prijave. Gost ima značko **gost**. Vodja svoje runde poleg gostovega imena vidi vpisani telefon in/ali email; drugi člani vidijo samo ime. Neprijavljeni in prijavljeni nečlani vidijo samo skupno število udeležencev in javno ime vodje. Gostje se štejejo enako kot drugi udeleženci.

### Nastavitve zaščite
V **Komponente → Runde Belaunce → Možnosti → Gostje** so:

- **Prijave gostov na računalnik in rundo:** 1, 2, 3 ali 5 (privzeto 2). Omejitev velja na isti omrežni naslov in isto rundo, zato si jo ljudje v skupnem omrežju delijo. Druga runda ima svojo kvoto; odjava sprosti mesto.
- **Minimalni čas izpolnjevanja:** privzeto 3 sekunde od prikaza obrazca. Po prehitri oddaji ali poteku seje znova počakaj in oddaj obrazec.
- **Captcha za goste:** privzeto izklopljena. Ob vklopu uporabi vtičnik iz globalne nastavitve Joomle. Če vtičnik ni nastavljen ali ni na voljo, se zabeleži opozorilo in obrazec ostane uporaben brez captche. Komponenta ne namešča zunanje storitve.

Honeypot je vključen vedno. Dnevnik zavrnitev vsebuje samo kodo in ID runde; naslova ne hrani surovega. Potrditveno pošto ureja administrator v **System → Mail Templates**, predloga `com_belauncerunde.gost_potrditev`. Namestitev obstoječih popravkov predloge ne prepiše.

Podatki gosta se izbrišejo z dnevnim vzdrževalnim opravilom 30 dni po začetku runde, tudi če je bila runda odpovedana.


## Obveščanje ob spremembi

Ko vodja na strani ali administrator v administraciji spremeni začetek ali lokacijo runde, prijavljeni člani, gostje z emailom in vodja prejmejo obvestilo. Mail navaja samo spremenjena vključena polja. Začetek je v časovnem pasu komponente (privzeto Europe/Ljubljana). Vodja dobi obvestilo tudi, kadar spremembo naredi sam. Ob odpovedi prek obrazca ali administratorskega seznama prejmejo jasno obvestilo o odpovedi s prvotnim terminom in povezavo na prihajajoče runde.

Vsak naslov dobi ločeno sporočilo. Če več prejemnikov uporablja isti email, dobi samo en izvod. Gost samo s telefonom ne dobi maila; zanj je odgovoren vodja. Ustvarjanje nove runde ali sprememba drugih polj (npr. opomb, dolžine ali naslova) ne pošlje obvestila.

Gostov mail vsebuje novo povezavo za odjavo: odpri jo in odjavo potrdi z gumbom. **Po vsakem novem obvestilu velja le zadnja povezava.** Če isti email uporablja več gostov, je v enem sporočilu več odjavnih povezav. Vsaka odjavi enega gosta.

### Nastavitev sprožilnih polj

Administrator v **Komponente → Belaunce – runde → Možnosti → Maili** pri **Polja, ki sprožijo mail** izbere začetek in/ali lokacijo. Privzeto sta vključeni obe polji. Odkljukaj lokacijo in shrani, če naj pošta odide samo ob spremembi časa. Za izklop obeh vrst sprememb odkljukaj obe polji in shrani. **Odpoved vedno pošlje obvestilo**, tudi kadar ni izbrano nobeno polje.

Besedila ureja administrator v **System → Mail Templates**, predlogi `com_belauncerunde.runda_sprememba` in `com_belauncerunde.runda_odpoved`, posebej za sl-SI/en-GB. Ohraniti je treba oznake za povezavo, spremenjena polja in odjavo gosta. Namestitev in posodobitev obstoječih popravkov ne prepišeta. Pošiljatelj in SMTP se nastavita v Joomli; uporabite klubski naslov iz nastavitev. Za ti sporočili dodatna nastavitev kopiranja Bcc ne velja, ker imata vedno samo enega prejemnika.

Če pošta odpove, se runda vseeno shrani oziroma odpove in izpiše se opozorilo, da nekaterih obvestil ni bilo mogoče poslati. Preveri SMTP in po potrebi osebno obvesti udeležence; samodejnega ponovnega pošiljanja ni. Ob neuspelem pošiljanju gostu lahko prejšnja odjavna povezava že preneha veljati. Dnevnik komponente vsebuje samo kode, ID runde in število prejemnikov, brez kontaktov ali žetonov.

## Scheduler opravilo

Administrator po posodobitvi paketa v **System → Scheduled Tasks** izbere **New**, nato tip **Vzdrževanje rund Belaunce** in nastavi dnevno izvajanje. Vtičnik **Task - Runde Belaunce** se ob namestitvi vklopi samodejno; vnosa v urniku namestitev ne ustvari. Opravilo lahko administrator za preizkus zažene ročno.

Pred zagonom preveri, da je v **Možnosti → AcyMailing** izbrana prava lista članov. Opravilo skupino **Člani** odvzame povezanim uporabnikom, ki niso več aktivni člani te liste, in jo ob vrnitvi na listo spet doda. Blokiranih računov ter uporabnikov brez povezave v tabeli članov ne spreminja. Če lista ni nastavljena, ne obstaja ali AcyMailing ni dosegljiv, sinhronizacijo preskoči. Izbriše goste 30 dni po začetku runde ter počisti stare žetone in poskuse. Rezultati so v dnevniku `com_belauncerunde.php` kot kode in števila brez osebnih podatkov.

Na produkciji nastavi **pravi strežniški cron** za Joomlin Scheduler; samo spletno proženje ni dovolj. Ta korak je tudi v [kontrolnem seznamu pred zagonom](TASKS.md#kontrolni-seznam-pred-zagonom-na-produkciji-ni-naloga-za-codex).

## Statistika in izvoz

Administrator odpre **Komponente → Runde Belaunce → Statistika**. Filtri za iskanje, tip, težavnost, stanje, vodjo in obdobje veljajo za vse tri tabele ter za gumb **Izvozi CSV**. Kazalniki štejejo objavljene in odpovedane runde; skupna in povprečna dolžina sta v kilometrih. Povprečno trajanje v minutah upošteva samo runde, ki imajo trajanje vpisano. Število udeležencev vključuje prijavljene člane, obstoječega vodjo in goste. Razčlenitvi po tipu in težavnosti prikazujeta število rund ter povprečni dolžini in števili udeležencev.

**Izvozi CSV** prenese vse runde, ki ustrezajo trenutnim filtrom, ne le trenutne strani. Datoteka uporablja podpičje, UTF-8 z BOM in decimalno vejico pri dolžini, zato jo slovenski Excel razdeli v stolpce ter pravilno prikaže šumnike. Vsebuje javno ime vodje in skupno število udeležencev; imen prijavljenih, gostov in njihovih kontaktov ne vsebuje.

## Zasebnost (GDPR)

Članski račun je Joomla uporabnik z imenom, emailom in uporabniškim imenom, ki je enako emailu. Povezava s članstvom in čas zadnje prijave sta v `#__belaunce_clani`; članske prijave na runde so v `#__belaunce_prijave`. Ime vodje in udeležencev vidijo člani po pravilih prikaza runde, javnost vidi le ime vodje in skupno število. Kontakti gostov so vidni samo vodji njihove runde.

Prijava gosta v `#__belaunce_gostje` hrani ime ter vpisani telefon in/ali email. Za omejitev prijav na omrežni naslov in rundo se shrani samo SHA-256 hash IP-ja, soljen z Joomlinim `secret`; surovi IP se v to tabelo ne zapiše. `#__belaunce_zetoni` hrani hash prijavnega žetona in od 0.14.0 krajšani, z Joomlinim `secret` soljeni hash IP-ja; `#__belaunce_poskusi` hrani hash ključa omejitve poskusov. Novi zapisi ne hranijo surovega IP-ja ali žetona. Žetoni, ustvarjeni pred posodobitvijo, lahko vsebujejo surovi IP do čiščenja po največ sedmih dneh, če dnevno opravilo teče; po potrebi jih administrator prej ročno odstrani. Lasten dnevnik piše kode in ID-je brez kontaktov in surovega IP-ja. Stare dnevniške datoteke je treba obravnavati po zgornjih navodilih.

Dnevno opravilo Scheduler izbriše goste 30 dni po začetku runde, žetone po 7 dneh in poskuse po 2 dneh. Administrator mora opravilo ustvariti in zagotoviti strežniški cron. Gost se lahko z enkratno povezavo odjavi sam; za predčasen ročni izbris lahko administrator po preverjeni zahtevi izbriše ustrezno vrstico gosta neposredno iz baze. Ročni poseg v bazo naj najprej varnostno kopira in preveri pravi zapis; za individualen izbris ni uporabniškega vmesnika. Ob izbrisu runde se osebni podatki njenih gostov in članske prijave samodejno odstranijo najkasneje ob naslednjem dnevnem opravilu, če je to nastavljeno in se uspešno izvede.

Zahtevo člana za dostop ali izbris obravnava administrator prek Joomline komponente **Privacy** in upravljanja uporabnikov. Paket v prvi izdaji nima vtičnika `plg_privacy` za samodejni izvoz ali izbris svojih tabel. Jedrni Joomla račun je v upravljanju uporabnikov, vendar odločitev D47 prepoveduje samodejno ali rutinsko brisanje in blokiranje članskih računov, ker bi AcyMailing lahko izbrisal naročnika oziroma ustavil pošto z drugih list. Zahtevo za dejanski izbris računa zato obravnava administrator posamično skupaj z upravljavcem AcyMailinga; odstranitev skupine **Člani** odvzame dostop, sama pa ni izbris osebnih podatkov. Pri izvozu ali izbrisu je treba posebej pregledati tudi zapise komponentnih tabel in AcyMailing, ker jih jedrna Privacy sama ne obdela.
