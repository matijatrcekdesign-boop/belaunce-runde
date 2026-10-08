# NALOGA 01: Okostje paketa in šifranti

**Veja:** `feature/NALOGA-01-okostje` (iz `develop`) · **PR v:** `develop` · **Verzija:** 0.1.0

## Cilj
Paket `pkg_belauncerunde` se zgradi z eno skripto in se brez napak namesti na Joomlo 6.1 prek *Namesti razširitve*. Komponenta je v administraciji vidna s podmenijem. Admin lahko ureja šifranta **Tipi** in **Težavnosti**. Namestitev ustvari skupino "Člani". Update server je pripravljen.

Ta naloga postavi vzorce (struktura, manifesti, MVC, jezik, gradnja), ki jih bodo uporabljale vse naslednje naloge, zato je natančnost pomembnejša od hitrosti.

## Povezane odločitve
D24, D27, D28, D29, D33, D35, D39; `osnova-projekta.md` razdelki 5, 16, 16.1, 18; `ARCHITECTURE.md` "Pravila za Joomlo 6"; `AGENTS.md` v celoti.

## Obseg

**V nalogi:**
1. Struktura repozitorija po `AGENTS.md` razdelek 4.
2. Manifest paketa `paket/pkg_belauncerunde.xml` z jezikovnimi datotekami paketa in update serverjem.
3. Komponenta `com_belauncerunde`, **samo administratorski del**:
   - manifest, `services/provider.php`, `access.xml`, `config.xml`
   - namestitveni skript `skript.php`
   - SQL za namestitev, posodobitev (`0.1.0.sql`) in odstranitev (kot opisano spodaj)
   - pogleda seznama **Tipi** in **Težavnosti**, pogleda urejanja **Tip** in **Tezavnost**
   - jezikovne datoteke `sl-SI` in `en-GB` (`.ini` in `.sys.ini`)
4. `orodja/gradnja.sh`
5. `posodobitve.xml`
6. Dopolnitev `docs/ARCHITECTURE.md`, `docs/NAVODILA.md`, `CHANGELOG.md`.

**Izrecno NI v nalogi:**
- frontend (site) del komponente, mapa `media/`, router (naloga 05)
- tabela rund in pogled Runde (naloga 02); zato preverjanje "tip je uporabljen v rundi" pri brisanju **še ni** (naloga 02)
- vtičniki (naloge 04, 12)
- pravice skupine "Člani" na komponenti (naloga 07)
- kakršnakoli koda za AcyMailing ali prijavo

## Datoteke / mesta

```
paket/
├── pkg_belauncerunde.xml
├── language/
│   ├── sl-SI/pkg_belauncerunde.sys.ini
│   └── en-GB/pkg_belauncerunde.sys.ini
└── com_belauncerunde/
    ├── belauncerunde.xml
    ├── skript.php
    └── administrator/
        ├── access.xml
        ├── config.xml
        ├── forms/{filter_tipi.xml, tip.xml, filter_tezavnosti.xml, tezavnost.xml}
        ├── language/{sl-SI,en-GB}/{com_belauncerunde.ini, com_belauncerunde.sys.ini}
        ├── services/provider.php
        ├── sql/install.mysql.utf8.sql
        ├── sql/uninstall.mysql.utf8.sql
        ├── sql/updates/mysql/0.1.0.sql
        ├── src/Controller/{DisplayController, TipController, TipiController, TezavnostController, TezavnostiController}.php
        ├── src/Extension/BelauncerundeComponent.php
        ├── src/Model/{TipModel, TipiModel, TezavnostModel, TezavnostiModel}.php
        ├── src/Table/{TipTable, TezavnostTable}.php
        ├── src/View/{Tipi, Tip, Tezavnosti, Tezavnost}/HtmlView.php
        └── tmpl/{tipi/default.php, tip/edit.php, tezavnosti/default.php, tezavnost/edit.php}
orodja/gradnja.sh
posodobitve.xml
```
Imena razredov in datotek lahko prilagodiš, če Joomla zahteva drugače. Razlog navedi v PR.

## Podatki / shema

### Tabeli (namestitveni SQL in `updates/mysql/0.1.0.sql`)

```sql
CREATE TABLE IF NOT EXISTS `#__belaunce_tipi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `naziv` VARCHAR(100) NOT NULL,
  `alias` VARCHAR(100) NOT NULL,
  `opis` TEXT NULL,
  `stanje` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_alias` (`alias`),
  KEY `idx_stanje` (`stanje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
`#__belaunce_tezavnosti` ima enako strukturo.

Začetne vrednosti z `INSERT IGNORE` (unikatni alias prepreči podvajanje ob ponovni namestitvi):
- tipi: `MTB`/`mtb`, `Cestno`/`cestno`, `Gravel`/`gravel` (ordering 1–3)
- težavnosti: `Berlingo`/`berlingo`, `Turbo`/`turbo` (ordering 1–2), opis prazen

Pomen `stanje`: 1 = objavljeno, 0 = neobjavljeno. V `Table` razredu poveži z `setColumnAlias('published', 'stanje')`, da delujejo standardni gumbi Objavi/Skrij.

### Odstranitev in ohranitev podatkov
- Manifest komponente **nima** elementa `<uninstall><sql>`, sicer bi Joomla tabele vedno izbrisala.
- `skript.php` v `uninstall()` prebere možnost `ohrani_podatke`. Če je `0`, izvede `sql/uninstall.mysql.utf8.sql` (`DROP TABLE IF EXISTS` za obe tabeli). Če je `1` (privzeto), tabele ostanejo.
- Skupina "Člani" se ob odstranitvi **ne** briše.

### Skupina "Člani" (D35) — `skript.php`, `postflight` ob `install` in `update`
1. Če je možnost `skupina_clani` že nastavljena in skupina s tem ID obstaja: nič.
2. Sicer poišči skupino z naslovom `Člani`, katere starš je skupina **Registered**. Če obstaja, uporabi njen ID.
3. Sicer jo ustvari (starš: Registered). Registered poišči po naslovu `Registered`; če je ni, uporabi ID 2 in to zapiši v dnevnik.
4. ID shrani v parametre komponente (`#__extensions.params`, ključ `skupina_clani`), ne da bi prepisal ostale parametre.
5. Skupino ustvari prek API-ja Joomle (model/tabela `com_users`), ne z neposrednim `INSERT`, ker skupine uporabljajo drevesno strukturo (`lft`/`rgt`). **Preveri v jedru Joomla 6.1**, kateri razred je pravi (npr. `Joomla\CMS\Table\Usergroup`), in ga navedi v PR.

### Možnosti (`config.xml`)
Zavihek **Splošno**:
| Polje | Tip | Privzeto | Opis |
|---|---|---|---|
| `skupina_clani` | `usergrouplist` | (nastavi namestitev) | Skupina, v katero se ustvarijo člani |
| `ohrani_podatke` | `radio` (Da/Ne, `btn-group`) | 1 | Ohrani tabele ob odstranitvi komponente |

Zavihek **Dovoljenja**: standardno polje `rules` za `com_belauncerunde`.

Ostala polja (AcyMailing, omejitve, časovni pas, maili) dodajo kasnejše naloge.

### ACL (`access.xml`)
Razdelek `component`: `core.admin`, `core.options`, `core.manage`, `core.create`, `core.delete`, `core.edit`, `core.edit.state`, `core.edit.own`.

### Administracija
- Meni *Komponente → Runde Belaunce* s podmenijem **Tipi**, **Težavnosti** (`<menu>` + `<submenu>`, `ARCHITECTURE.md` pravilo 5).
- Privzeti pogled komponente je za zdaj `tipi` (naloga 02 ga zamenja z `runde`).
- **Seznam** (oba šifranta): stolpci izbira, razvrščanje (drag & drop, kot v jedru), naziv (povezava na urejanje, pod njim alias), stanje (preklop), ID. Iskanje po nazivu, filter po stanju, razvrščanje po stolpcih, paginacija, orodna vrstica Nov / Uredi / Objavi / Skrij / Izbriši / Možnosti. Zaklenjen zapis (checked_out) prikazan z ikono ključavnice in možnostjo odklepa.
- **Urejanje:** naziv (obvezno, največ 100 znakov), alias (če je prazen, se ustvari iz naziva; unikaten), opis (urejevalnik ali textarea, neobvezno), stanje. Gumbi Shrani, Shrani in zapri, Shrani in nov, Prekliči.
- Brisanje: s potrditvijo.

### Paket in update server
- `pkg_belauncerunde.xml`: `type="package"`, `method="upgrade"`, `<packagename>belauncerunde</packagename>`, verzija `0.1.0`, `<blockChildUninstall>true</blockChildUninstall>`, `<files folder="packages">` s `com_belauncerunde.zip`, jezikovne datoteke paketa (`PKG_BELAUNCERUNDE`, `PKG_BELAUNCERUNDE_XML_DESCRIPTION`).
- `<updateservers>`: `<server type="extension" name="Runde Belaunce">https://raw.githubusercontent.com/matijatrcekdesign-boop/belaunce-runde/main/posodobitve.xml</server>`
- `posodobitve.xml`: element `pkg_belauncerunde`, tip `package`, verzija `0.1.0`, `downloadurl` = `https://github.com/matijatrcekdesign-boop/belaunce-runde/releases/download/v0.1.0/pkg_belauncerunde-0.1.0.zip`, `<sha256>` (prazno, izpolni se ob izdaji; skripta gradnje izpiše vrednost), `targetplatform name="joomla" version="6\.[0-9]+"`.

### `orodja/gradnja.sh`
- Bash, deluje v WSL (Ubuntu) brez dodatnih paketov razen `zip`.
- Verzijo prebere iz `paket/pkg_belauncerunde.xml`. **Ustavi se z napako**, če katerikoli manifest v `paket/` ali `posodobitve.xml` vsebuje drugo verzijo.
- Preverjanja (napaka = izhod s kodo ≠ 0):
  - `php -l` za vse `.php` datoteke (če `php` ni na voljo: opozorilo, ne napaka)
  - `xmllint --noout` za vse `.xml` (če `xmllint` ni na voljo: opozorilo)
  - prepovedani vzorci v `paket/`: `JHtml`, `JFactory`, `JText`, `JRoute`, `JUri`, `JTable`, `jQuery`, `getInstance(`
  - vsak `CREATE TABLE` v SQL datotekah vsebuje `utf8mb4`
- Zgradi `dist/pkg_belauncerunde-X.Y.Z.zip`: v korenu manifest paketa in mapa `language/`, v `packages/` ZIP vsake podrejene razširitve. Začasne datoteke v `dist/tmp`, ki se na koncu pobriše.
- Na koncu izpiše pot do ZIP in njegov SHA-256.

## Varnostne zahteve
- Vsi kontrolerji, ki spreminjajo podatke, preverjajo CSRF žeton (FormController in AdminController to naredita sama; preveri, da ne zaobideš).
- Dostop do komponente: `core.manage` v `Dispatcher` ali privzetem kontrolerju; uporabnik brez te pravice dobi napako 403 (`NotAllowed`).
- Urejanje, objava in brisanje spoštujejo `core.edit`, `core.edit.state`, `core.delete` (standardno vedenje `FormController`/`AdminController`).
- Izhod v predlogah skozi `$this->escape()`.
- Iskanje v seznamu: `bind()` s `%…%` vrednostjo, brez lepljenja nizov.
- `skript.php` ne izpisuje podrobnosti napak baze uporabniku; zapiše jih v `Log`.

## Merila sprejema
Matija preveri na DDEV (namestitev samo iz ZIP, D39):

1. `bash orodja/gradnja.sh` uspe in ustvari `dist/pkg_belauncerunde-0.1.0.zip`. Ob namerno spremenjeni verziji v enem manifestu skripta javi napako.
2. Namestitev ZIP (CLI ali UI) uspe brez napak in opozoril. *Razširitve → Upravljaj* prikaže paket in komponento s pravimi imeni (nobenega surovega ključa `PKG_…`/`COM_…`), v slovenski in angleški administraciji.
3. *Komponente → Runde Belaunce* obstaja, s podmenijem Tipi in Težavnosti.
4. `ddev mysql -e "SHOW CREATE TABLE <predpona>belaunce_tipi\G"` pokaže `utf8mb4`. V tabelah so začetne vrednosti, vsaka enkrat.
5. Ponovna namestitev istega ZIP (posodobitev) ne podvoji začetnih vrednosti in ne ustvari druge skupine "Člani".
6. Skupina "Člani" obstaja pod Registered. Njen ID je izbran v *Možnosti → Splošno → skupina_clani*.
7. Za oba šifranta delujejo: nov, urejanje, Shrani / Shrani in zapri / Shrani in nov / Prekliči, objava in skrivanje, brisanje, iskanje, filter stanja, razvrščanje po stolpcih, paginacija, drag & drop razvrščanje. Prekliči sprosti zaklep (checked_out).
8. Prazen naziv je zavrnjen s sporočilom. Prazen alias se ustvari iz naziva; podvojen alias je zavrnjen ali samodejno spremenjen (opiši v PR, kaj si izbral).
9. Gumb Možnosti odpre obe zavihka. Uporabnik brez `core.manage` ne pride v komponento.
10. Z izklopljenim vtičnikom za združljivost za nazaj (*Behaviour – Backward Compatibility*) administracija komponente deluje brez napak. Po testu vtičnik ponovno vklopi (predloga ga potrebuje).
11. Pri `error_reporting: maximum` (DDEV) ni PHP opozoril ali obvestil.
12. Odstranitev paketa z `ohrani_podatke = Da`: tabeli ostaneta; ponovna namestitev deluje in podatki so ohranjeni. Z `ohrani_podatke = Ne`: tabeli sta izbrisani. Skupina "Člani" ostane v obeh primerih.
13. *Sistem → Mesta posodobitev* prikaže mesto "Runde Belaunce".

## Dokumentacija
- `docs/ARCHITECTURE.md`: pretok za šifrante (preveri, da se ujema z implementacijo), imena ključnih razredov.
- `docs/NAVODILA.md`: razdelka "Namestitev in posodobitev" in "Šifranti: tipi in težavnosti" (za nestrokovnega admina, slovensko).
- `CHANGELOG.md`: razdelek `[0.1.0]`.
- V PR navedi, na katere datoteke jedra Joomla 6.1 si se oprl za: ustvarjanje skupine, `<languages>` v manifestu paketa, `setColumnAlias`, drag & drop razvrščanje.

## Vprašanja
Če česa ne moreš izvesti po tej nalogi (npr. API v Joomli 6.1 deluje drugače, kot je opisano), **tistega dela ne implementiraj po svoje**. Zapiši ga v PR pod "Vprašanja za arhitekta" z opisom, kaj si ugotovil v jedru Joomle, in nadaljuj z ostalimi deli.
