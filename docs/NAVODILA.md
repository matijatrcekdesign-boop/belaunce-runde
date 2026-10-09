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

### Šifranti: tipi in težavnosti
Šifranti so v administraciji pod *Komponente → Runde Belaunce*:

- **Tipi**: začetne vrednosti so MTB, Cestno in Gravel.
- **Težavnosti**: začetni vrednosti sta Berlingo in Turbo.

Za dodajanje izberi **Nov**, izpolni obvezni **Naziv** in po potrebi **Alias** ter **Opis**. Če alias pustiš prazen, ga komponenta ustvari iz naziva. Zapise lahko objaviš, skriješ, razvrstiš z vlečenjem, iščeš po nazivu ali aliasu ter izbrišeš.

Tipa ali težavnosti, ki ju uporablja vsaj ena runda, ni mogoče izbrisati. Skrit tip ali težavnost ostane vidna v obrazcu obstoječe runde z oznako **(skrito)**.

## Za vodje rund
*(naloge 07, 09)*

## Za člane
*(naloge 04, 06, 09)*

## Za goste
*(naloga 08)*
