# Navodila za uporabo — Runde Belaunce

Uporabniška navodila nastajajo postopoma z nalogami. Vsaka naloga dopolni svoj razdelek.

## Za administratorja

### Namestitev in posodobitev
1. V repozitoriju zaženi `bash orodja/gradnja.sh`.
2. Nastali ZIP iz mape `dist/` namesti v Joomli prek *Sistem → Namesti → Razširitve → Naloži paket* ali prek CLI.
3. Po namestitvi preveri, da je v meniju *Komponente* viden vnos **Runde Belaunce**.
4. V *Komponente → Runde Belaunce → Možnosti* preveri zavihek **Splošno**. Skupina članov mora biti nastavljena na **Člani**.

Pri odstranitvi paket privzeto ohrani tabele s podatki. Če želiš tabele izbrisati, pred odstranitvijo v možnostih izklopi **Ohrani podatke ob odstranitvi**.

### Menijska postavka za runde

1. V *Meniji → Main Menu → Nov* izberi tip **Runde Belaunce → Seznam rund**.
2. Nastavi naslov (npr. Runde), alias **runde**, stanje **Objavljeno** in dostop **Public**, da seznam deluje tudi odjavljenim obiskovalcem.
3. V osnovnih možnostih nastavi **Število rund na stran** (privzeto 20) in **Prikaži filtre** (privzeto Da). Za preverjanje paginacije lahko začasno nastaviš 2 rundi na stran.
4. V globalnih nastavitvah Joomle vključi **Search Engine Friendly URLs**. Za pot brez `index.php` mora biti pravilno nastavljeno tudi prepisovanje URL-jev na strežniku.
5. Odpri `/runde` in klikni naslov runde. Povezava mora imeti obliko `/runde/{id}-{alias}`.

Brez te menijske postavke Joomla uporablja `/component/belauncerunde/…`; to je pričakovano. Seznam je dosegljiv tudi neposredno na `index.php?option=com_belauncerunde&view=runde`. Pogleda posamezne runde ni mogoče izbrati kot tip menijske postavke. Prijava za člane ostaja samostojna menijska postavka oziroma pogled `view=prijava`.

Filtra se oddata z gumbom **Filtriraj**, izbira je v URL-ju in povezavo lahko deliš. **Počisti** vrne na seznam brez filtrov. Če filtre v menijski postavki skriješ, se obrazec ne prikaže; filtri v že deljenih povezavah še vedno veljajo.

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

### Ko član ni več član
Članstvo določa aktivni naročnik AcyMailinga z aktivno naročnino na listo članov. Odjava samo z liste Runde ne vpliva na dostop. Ob porabi vsake povezave se članstvo preveri znova; če je član medtem izstopil, prijava ne uspe. Dnevna sinhronizacija in blokiranje prideta z nalogo 12, zato ta verzija obstoječih sej še ne blokira samodejno.

Joomla uporabnika člana **ne briši**, temveč ga blokiraj (D47): brisanje lahko prek AcyMailingove nastavitve izbriše tudi naročnika in njegovo članstvo. Če je uporabnik spet član in se prijavi z novo povezavo, se račun odblokira. Računa zunaj skupine Člani magic link ne prevzame, tudi če ima isti email.

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
4. Po želji dodaj trajanje v minutah (1–1440), opombe kot navadno besedilo ter povezavo na traso. Če trajanje pustiš prazno, se za prikaz konca uporabi privzeto trajanje iz možnosti. Prijava gostov bo na voljo z nalogo 08.
5. Izberi **Shrani rundo**. Odpre se stran nove runde; si njen vodja in že šteješ kot en udeleženec. Posebna prijava ni potrebna. Ob napaki se vnos ohrani in obrazec prikaže obvestilo.

Rundo urejaš in odpoveš samo kot njen vodja do štarta. Obiskovalci in prijavljeni nečlani gumba za ustvarjanje ne vidijo.

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

## Kaj vidi obiskovalec

Seznam in posamezne runde so javni, tudi v zasebnem oknu brez prijave. Seznam prikazuje naslov, datum in uro štarta, tip, težavnost, lokacijo, dolžino in polno ime vodje. Ura je v časovnem pasu iz možnosti komponente (privzeto Europe/Ljubljana), ne v časovnem pasu Joomle ali obiskovalca.

Odpovedane runde ostanejo vidne z oznako **Odpovedano**. Runda izgine s seznama šele po začetku in trajanju; če trajanje ni vpisano, se uporabi privzeto trajanje iz možnosti. Njena neposredna povezava ostane dostopna tudi pozneje.

Stran runde prikaže še trajanje, opombe in veljavno povezavo na traso v novem oknu. Če vodja ne obstaja več, je njegovo ime prazno. Sprememba aliasa ne pokvari stare povezave: runda se poišče po ID-ju, kanonična povezava v izvorni kodi pa kaže trenutni URL. Neobstoječa ali nejavna runda vrne 404. Od verzije 0.6.0 je na strani tudi število udeležencev; njihova imena vidijo samo člani. Nečlanom se izpiše poziv za včlanitev. Prijava gostov in njihovi kontakti pridejo z nalogo 08.

## Za goste
*(naloga 08)*

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

Samodejni izbris podatkov gostov po 30 dneh in obvestila o spremembah oziroma odpovedi pridejo v nalogah 12 in 10.
