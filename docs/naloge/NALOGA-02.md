# NALOGA 02: Backend runde

**Veja:** `feature/NALOGA-02-runde` (iz `develop`) · **PR v:** `develop` · **Verzija:** 0.2.0

## Cilj
Admin lahko v administraciji ustvari, ureja, odpove, obnovi in izbriše runde ter zamenja vodjo. Seznam rund ima filtre in je privzeti pogled komponente. Časi se v bazi hranijo v UTC, vnašajo in prikazujejo pa v časovnem pasu iz možnosti (privzeto Europe/Ljubljana), pravilno tudi ob prehodu na poletni/zimski čas. Šifrantov, ki jih uporablja runda, ni mogoče izbrisati.

## Povezane odločitve
D3, D11, D13, D14, D15, D18 (samo seznam; statistika je naloga 13), D28, D36, D42; `osnova-projekta.md` razdelki 4, 4.1, 5, 7, 11; `ARCHITECTURE.md` "Pravila za Joomlo 6" **1–15**; `AGENTS.md` v celoti.

## Obseg

**V nalogi:**
1. Tabela `#__belaunce_runde` (namestitveni SQL in `updates/mysql/0.2.0.sql`).
2. `Administrator\Helper\CasHelper` — pretvorba časa UTC ↔ `casovni_pas`.
3. Polja obrazca `TipField` in `TezavnostField` (seznama iz šifrantov, ponovno uporabna v nalogi 07).
4. Pogled seznama **Runde** (privzeti pogled komponente, prvi v podmeniju) in pogled urejanja **Runda**.
5. Akciji seznama **Odpovej** in **Obnovi** (stanje 2 ↔ 1).
6. Zaščita šifrantov: tip ali težavnosti, ki ju uporablja vsaj ena runda, ni mogoče izbrisati.
7. Nove možnosti v `config.xml` (spodaj).
8. Verzija **0.2.0** v vseh manifestih in `posodobitve.xml`; `CHANGELOG.md`, `ARCHITECTURE.md`, `NAVODILA.md`.

**Izrecno NI v nalogi:**
- frontend (naloge 05–07), prijave in gostje (06, 08), maili ob odpovedi (10), statistika in CSV (13)
- polje `dodatno` v obrazcu (stolpec obstaja, obrazec ga ne prikazuje)
- vdelave trase (naloga 07) — tukaj samo validacija, da je URL `http`/`https`
- paketne (batch) akcije

## Datoteke / mesta (nove ali spremenjene)

```
paket/pkg_belauncerunde.xml, posodobitve.xml                     # verzija 0.2.0
paket/com_belauncerunde/belauncerunde.xml                         # verzija, submenu Runde (prvi)
paket/com_belauncerunde/administrator/
├── config.xml                                                    # nova polja
├── forms/{filter_runde.xml, runda.xml}
├── language/{sl-SI,en-GB}/com_belauncerunde.ini, .sys.ini
├── sql/install.mysql.utf8.sql, sql/updates/mysql/0.2.0.sql
├── sql/uninstall.mysql.utf8.sql                                  # + DROP runde
├── src/Controller/{DisplayController (privzeti pogled), RundaController, RundeController}.php
├── src/Field/{TipField, TezavnostField}.php
├── src/Helper/CasHelper.php
├── src/Model/{RundaModel, RundeModel}.php
├── src/Model/{TipModel, TezavnostModel}.php                      # zaščita brisanja
├── src/Table/RundaTable.php
├── src/View/{Runde, Runda}/HtmlView.php
└── tmpl/{runde/default.php, runda/edit.php}
```

## Podatki / shema

### Tabela `#__belaunce_runde`
```sql
CREATE TABLE IF NOT EXISTS `#__belaunce_runde` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `naslov` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(255) NOT NULL,
  `tip_id` INT UNSIGNED NOT NULL,
  `tezavnost_id` INT UNSIGNED NOT NULL,
  `lokacija` VARCHAR(255) NOT NULL,
  `zacetek` DATETIME NOT NULL,
  `dolzina_km` DECIMAL(5,1) UNSIGNED NOT NULL,
  `trajanje_min` SMALLINT UNSIGNED NULL,
  `opombe` TEXT NULL,
  `trasa_url` VARCHAR(2048) NULL,
  `odprto_za_goste` TINYINT(1) NOT NULL DEFAULT 0,
  `stanje` TINYINT NOT NULL DEFAULT 1,
  `odpovedano` DATETIME NULL,
  `vodja_id` INT UNSIGNED NOT NULL,
  `ustvaril_id` INT UNSIGNED NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  `spremenil_id` INT UNSIGNED NULL,
  `spremenjeno` DATETIME NULL,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  `dodatno` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stanje_zacetek` (`stanje`, `zacetek`),
  KEY `idx_vodja` (`vodja_id`),
  KEY `idx_tip` (`tip_id`),
  KEY `idx_tezavnost` (`tezavnost_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
- `stanje`: **1 = objavljena, 2 = odpovedana**. Brez tujih ključev (Joomla jih ne uporablja); celovitost zagotavlja koda.
- Vsi `DATETIME` so v **UTC**.
- `alias` ni unikaten (URL bo `/runde/{id}-{alias}`, iskanje po `id`). Ustvari se iz naslova, če je prazen.
- `RundaTable`: `_supportNullValue = true`; `setColumnAlias('published', 'stanje')` **ne** uporabljaj (stanje nima Joomlinega pomena objave).
- `RundaTable::store()`: nov zapis → `ustvarjeno` = zdaj (UTC), `ustvaril_id` = trenutni uporabnik; obstoječ → `spremenjeno`, `spremenil_id`.
- Sprememba `stanje` na 2 nastavi `odpovedano` = zdaj (UTC); sprememba na 1 nastavi `odpovedano` = NULL. To velja za shranjevanje obrazca **in** za akciji Odpovej/Obnovi (ena metoda v modelu, npr. `RundaModel::nastaviStanje(array $pks, int $stanje)`).
- `uninstall.mysql.utf8.sql`: dodaj `DROP TABLE IF EXISTS #__belaunce_runde` (pred šifranti).

### Možnosti (`config.xml`, zavihek Splošno — dodaj pod obstoječa polja)
| Polje | Tip | Privzeto | Opis |
|---|---|---|---|
| `casovni_pas` | `timezone` | `Europe/Ljubljana` | Časovni pas za vnos in prikaz časov (D42) |
| `privzeta_lokacija` | `text`, max 255 | `Gorenja vas` | Predizpolnjena lokacija nove runde (D36) |
| `privzeto_trajanje_min` | `list`: 120, 180, 240, 300 (prikaz "2 h" … "5 h") | 180 | Za določanje, kdaj je runda brez trajanja pretekla (4.1) |

### `CasHelper` (D42)
Statične metode, časovni pas iz `ComponentHelper::getParams('com_belauncerunde')->get('casovni_pas', 'Europe/Ljubljana')`; neveljaven pas → `Europe/Ljubljana` in zapis v `Log`.
- `vUtc(string $lokalni): string` — `'Y-m-d H:i:s'` v časovnem pasu → `'Y-m-d H:i:s'` UTC
- `izUtc(string $utc): string` — obratno
- `zdajUtc(): string` — trenutni čas UTC v `'Y-m-d H:i:s'`
- Uporabljaj `\DateTimeImmutable` in `\DateTimeZone`. **Ne** uporabljaj uporabnikovega ali globalnega časovnega pasu Joomle (strani je nastavljen UTC, D42).

### Obrazec `runda.xml`
`addfieldprefix="Belaunce\Component\Belauncerunde\Administrator\Field"`.

| Polje | Tip / pravila |
|---|---|
| `naslov` | text, obvezno, max 255, `filter="string"` |
| `alias` | text, max 255, `filter="cmd"` |
| `tip_id` | `tip` (TipField), obvezno |
| `tezavnost_id` | `tezavnost` (TezavnostField), obvezno |
| `lokacija` | text, obvezno, max 255, `filter="string"`; nova runda dobi `privzeta_lokacija` |
| `zacetek` | `calendar`, obvezno, `showtime="true"`, `translateformat="true"`, **brez** atributa `filter` (glej spodaj) |
| `dolzina_km` | number, obvezno, `min="0.1"`, `max="999.9"`, `step="0.1"`, `filter="float"` |
| `trajanje_min` | number, neobvezno, `min="1"`, `max="1440"`, `filter="integer"`; prazno → NULL |
| `opombe` | textarea, `filter="string"` (navadno besedilo, brez HTML) |
| `trasa_url` | url, neobvezno, max 2048, `validate="url"`, `schemes="http,https"`, `filter="url"`; prazno → NULL |
| `odprto_za_goste` | radio switcher, možnosti **0 (JNO), 1 (JYES)** (pravilo 15), privzeto 0 |
| `stanje` | list: 1 = Objavljena, 2 = Odpovedana; privzeto 1 |
| `vodja_id` | `user`, obvezno; nova runda dobi trenutnega uporabnika |

**Čas (pomembno):** `CalendarField` s `filter="user_utc"` ali `server_utc` pretvarja po časovnem pasu uporabnika oz. strani (UTC) — to je napačno za D42. Zato polje nima filtra, pretvorbo pa naredi model:
- `RundaModel::loadFormData()` / `getItem()`: `zacetek` iz UTC v lokalni čas (`CasHelper::izUtc`) za prikaz.
- `RundaModel::save()` (pred `parent::save()`): `zacetek` iz lokalnega v UTC (`CasHelper::vUtc`).
- Pazi, da se pretvorba ob ponovnem prikazu obrazca po napaki validacije ne izvede dvakrat.
Preverjeno v jedru Joomla 6.1: `libraries/src/Form/Field/CalendarField.php`, vrstice ~295–320 in ~395–440.

### `TipField` / `TezavnostField`
`ListField`, možnosti iz šifranta po `ordering`. Objavljene vrednosti so izbirne; neobjavljena vrednost, ki je trenutno izbrana pri rundi, se prikaže z oznako "(skrito)", da obstoječa runda ne izgubi vrednosti.

### Seznam **Runde**
- Stolpci: izbira, začetek (lokalni čas, oblika `d. m. Y H:i`), naslov (povezava na urejanje; pod njim lokacija), tip, težavnost, dolžina (km), vodja (ime), stanje (značka: Objavljena / Odpovedana; pretekla runda ima dodatno značko "Pretekla"), ID.
- Filtri: iskanje (naslov, lokacija; `id:12`), tip, težavnost, stanje, vodja, **obdobje** (Prihajajoče / Pretekle / Vse; privzeto Vse).
- **Pretekla** (4.1): `zacetek + COALESCE(trajanje_min, privzeto_trajanje_min) minut < zdaj (UTC)`. Izračun v SQL z `DATE_ADD(... INTERVAL ... MINUTE)` in vezano vrednostjo za privzeto trajanje in trenutni čas (iz PHP, ne `NOW()`, zaradi konsistentnega UTC).
- Razvrščanje: začetek (privzeto padajoče), naslov, stanje, ID.
- Orodna vrstica: Nov, Uredi, **Odpovej**, **Obnovi**, Odkleni (Check-in), Izbriši, Možnosti. Gumbi za izbrane zapise po pravilu 14.
- Podmeni: **Runde**, Tipi, Težavnosti (v tem vrstnem redu). Privzeti pogled komponente postane `runde`.

### Zaščita šifrantov
`TipModel::canDelete()` / `TezavnostModel::canDelete()` (ali preverjanje v `delete()`): če obstaja runda z `tip_id` / `tezavnost_id` = id, brisanje zavrni s sporočilom, koliko rund ga uporablja. Ostali izbrani zapisi se izbrišejo normalno.

## Varnostne zahteve
- Vse akcije, ki spreminjajo podatke (shrani, odpovej, obnovi, izbriši, check-in), so POST s CSRF žetonom; Odpovej/Obnovi preverita `core.edit.state`.
- Pravice: `core.create`, `core.edit`, `core.edit.state`, `core.delete` na `com_belauncerunde`, preverjene v kontrolerju/modelu, ne samo pri gumbih.
- `vodja_id` mora biti obstoječ, neblokiran uporabnik (validacija v modelu).
- `tip_id`, `tezavnost_id` morata obstajati v šifrantu (validacija v modelu).
- `trasa_url`: samo `http`/`https`; shrani se samo URL.
- Vse poizvedbe z `bind()`; filtri seznama (tip, težavnost, stanje, vodja, obdobje) se pretvorijo v cela števila oz. dovoljene vrednosti.
- Izpis skozi `$this->escape()`; opombe so navadno besedilo.

## Merila sprejema
Matija preveri na DDEV (namestitev samo iz ZIP; ZIP pred vsako namestitvijo kopiraj v `tmp/`):

1. `bash orodja/gradnja.sh` uspe; vse verzije so 0.2.0.
2. **Posodobitev z 0.1.0 na 0.2.0** (paket 0.1.0 je nameščen): uspe brez napak; tipi in težavnosti ostanejo nespremenjeni; tabela `dipxn_belaunce_runde` obstaja z `utf8mb4`; `SELECT version_id FROM dipxn_schemas WHERE extension_id=(SELECT extension_id FROM dipxn_extensions WHERE element='com_belauncerunde')` vrne `0.2.0`.
3. *Komponente → Runde Belaunce* odpre seznam **Runde**; podmeni Runde, Tipi, Težavnosti.
4. Nova runda: lokacija je predizpolnjena z "Gorenja vas", vodja s trenutnim uporabnikom, stanje Objavljena. Shrani z vsemi gumbi (Shrani, Shrani in zapri, Shrani in nov, Prekliči).
5. **Čas poleti:** runda z začetkom `15. 7. 2027 17:00` → v bazi `zacetek = 2027-07-15 15:00:00`; v seznamu in obrazcu se prikaže 17:00.
6. **Čas pozimi:** runda z začetkom `15. 12. 2026 17:00` → v bazi `2026-12-15 16:00:00`; prikaz 17:00.
7. Ponovno shranjevanje runde brez sprememb ne premakne časa (ni dvojne pretvorbe). Napaka validacije (npr. prazen naslov) in popravek ne premakneta časa.
8. Prazen naslov, lokacija, tip, težavnost, začetek, dolžina ali vodja → napaka. `trasa_url` = `javascript:alert(1)` ali `ftp://…` → zavrnjeno. Trajanje in trasa prazna → v bazi `NULL`.
9. Odpovej (iz seznama in iz obrazca) → stanje 2, `odpovedano` nastavljen (UTC). Obnovi → stanje 1, `odpovedano = NULL`.
10. Zamenjava vodje: v obrazcu izberi drugega uporabnika, shrani → seznam prikaže novega vodjo; `ustvaril_id` ostane nespremenjen, `spremenil_id`/`spremenjeno` nastavljena.
11. Filtri: tip, težavnost, stanje, vodja, obdobje Prihajajoče/Pretekle (preizkusi z rundo včeraj brez trajanja → Pretekla; runda pred 2 urama brez trajanja → še ni pretekla pri privzetih 3 h).
12. Brisanje runde deluje. Brisanje tipa, ki ga uporablja runda → zavrnjeno z jasnim sporočilom; neuporabljen tip se izbriše.
13. Neobjavljen tip, ki ga uporablja obstoječa runda, se v obrazcu te runde prikaže z oznako "(skrito)" in se ob shranjevanju ohrani.
14. Možnosti: nova polja časovni pas, privzeta lokacija, privzeto trajanje; sprememba privzete lokacije se pozna pri novi rundi.
15. Brez PHP opozoril pri `error_reporting: maximum`; administracija deluje z izklopljenim vtičnikom za združljivost za nazaj.
16. Odstranitev z `ohrani_podatke = Ne` izbriše tudi tabelo rund; z `Da` ostanejo vse tri.

## Dokumentacija
- `ARCHITECTURE.md`: pretok za runde, `CasHelper`, polja šifrantov, pravilo pretvorbe časa.
- `NAVODILA.md`: razdelek "Runde v administraciji" (ustvarjanje, odpoved, obnova, zamenjava vodje, brisanje, filtri, časovni pas).
- `CHANGELOG.md`: `[0.2.0]`.

## Vprašanja
Česar ne moreš izvesti po tej nalogi, ne implementiraj po svoje: zapiši v PR pod "Vprašanja za arhitekta" (z navedbo datoteke jedra Joomla 6.1, na katero se opiraš) in nadaljuj z ostalim.
