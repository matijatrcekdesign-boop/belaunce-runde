# Navodila za uporabo — Runde Belaunce

Uporabniška navodila nastajajo postopoma z nalogami. Vsaka naloga dopolni svoj razdelek.

## Za administratorja

### Namestitev in posodobitev
1. V repozitoriju zaženi `bash orodja/gradnja.sh`.
2. Nastali ZIP iz mape `dist/` namesti v Joomli prek *Sistem → Namesti → Razširitve → Naloži paket* ali prek CLI.
3. Po namestitvi preveri, da je v meniju *Komponente* viden vnos **Runde Belaunce**.
4. V *Komponente → Runde Belaunce → Možnosti* preveri zavihek **Splošno**. Skupina članov mora biti nastavljena na **Člani**.

Pri odstranitvi paket privzeto ohrani tabele s podatki. Če želiš tabele izbrisati, pred odstranitvijo v možnostih izklopi **Ohrani podatke ob odstranitvi**.

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
*(naloge 07, 09)*

## Za člane

### Prijava za člane
Odpri stran za prijavo (`index.php?option=com_belauncerunde&view=prijava` oziroma menijsko postavko **Prijava za člane**). Vpiši email, s katerim si včlanjen v društvo, in klikni **Pošlji povezavo**. Vedno se prikaže obvestilo, da je bila povezava poslana, če društvo pozna naslov. Če maila ni, preveri pravi naslov in neželeno pošto ali piši društvu.

Odpri povezavo iz maila in klikni **Prijavi me**. Samo odpiranje povezave te še ne prijavi; zato je ne porabijo mailni skenerji. Povezava velja privzeto 15 minut in jo lahko uporabiš samo enkrat. Nova zahteva razveljavi prejšnjo povezavo. Pri neveljavni ali potekli povezavi izberi **Zahtevaj novo povezavo**. Če si že prijavljen, obrazca ni; prikazana sta obvestilo in povezava na domačo stran.

Ob prvi prijavi se račun ustvari samodejno, gesla ne izbiraš. Naslednje prijave uporabijo isti račun. Če je vključena daljša prijava, Joomlin piškotek ohrani prijavo tudi po izteku kratke seje; odjaviš se z običajno Joomlino odjavo.

Če povezava omenja rundo, njen naslov v verziji 0.4.0 služi samo kot informacija. Vpis udeležbe po prijavi pride z nalogo 06.

## Za goste
*(naloga 08)*
