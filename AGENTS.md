# AGENTS.md — navodila za izvajalca (Codex)

Projekt: **paket `pkg_belauncerunde`** za Joomla 6 (komponenta `com_belauncerunde` + vtičniki) za organizacijo kolesarskih rund na belaunce.cc.

Vloge:
- **Arhitekt (Claude):** piše naloge, pregleduje PR-je.
- **Izvajalec (ti, Codex):** implementiraš eno nalogo naenkrat, commitaš in odpreš PR.
- **Matija:** testira na DDEV, združuje PR-je.

Nimaš dostopa do Joomle, baze ali DDEV. Kode ne moreš zagnati v Joomli. Zato piši natančno po pravilih spodaj in v PR jasno navedi, kaj je treba ročno preveriti.

---

## 1. Preden začneš

Preberi v tem vrstnem redu:
1. `docs/osnova-projekta.md` — **edini vir resnice** (odločitve D1–D43, podatkovni model, pravila).
2. `docs/ARCHITECTURE.md` — struktura in **obvezna pravila za Joomlo 6** (izkušnje iz prejšnjega projekta).
3. `docs/naloge/NALOGA-xx.md` — naloga, ki jo izvajaš.
4. `docs/TASKS.md` — kje smo.

## 2. Zlata pravila

1. **Ne spreminjaj odločitev.** Če je kaj v nalogi nejasno, v konfliktu z md ali tehnično neizvedljivo: ne ugibaj. Zapiši v razdelek **"Vprašanja za arhitekta"** v opisu PR in tisti del izpusti.
2. **Samo obseg naloge.** Nič dodatnih funkcij, refaktoringov ali "izboljšav" izven naloge. Če opaziš težavo izven obsega, jo navedi v PR pod "Opažanja".
3. **Ne spreminjaj** `docs/osnova-projekta.md`, predloge strani ali česarkoli izven tega repozitorija. Dokumentacijo (`ARCHITECTURE.md`, `NAVODILA.md`, `CHANGELOG.md`) dopolni samo, kot zahteva naloga.
4. **Brez skrivnosti v repozitoriju:** gesla, ključi, ID-ji list, emaili članov so v nastavitvah Joomle, nikoli v kodi.
5. **Dejstev o Joomli 6 ne ugibaj.** Če nisi prepričan o API-ju, poglej izvorno kodo Joomla 6.1 (github.com/joomla/joomla-cms, veja 6.1-dev) in v PR navedi, na katero datoteko jedra si se oprl.

## 3. Veje in PR

- Veje: `main` (stabilno) ← `develop` (integracija) ← `feature/NALOGA-xx-kratko-ime`.
- Vejo vedno odpri iz najnovejšega `develop`.
- **PR vedno v `develop`, nikoli v `main`.**
- En PR = ena naloga. Commiti so majhni in smiselni, sporočila v slovenščini (npr. `NALOGA-01: namestitveni SQL za šifrante`).
- Opis PR (obvezni razdelki):
  - **Kaj je narejeno** (po merilih sprejema, z oznako ✅ / ⚠️)
  - **Spremenjene datoteke** (kratek seznam)
  - **Ročni test za Matijo** (natančni koraki na DDEV)
  - **Vprašanja za arhitekta** (ali "ni")
  - **Opažanja** (izven obsega, ali "ni")

## 4. Struktura repozitorija

```
belaunce-runde/
├── AGENTS.md
├── CHANGELOG.md
├── LICENSE
├── posodobitve.xml                  # update server (D24)
├── orodja/
│   └── gradnja.sh                   # zgradi dist/pkg_belauncerunde-X.Y.Z.zip
├── paket/
│   ├── pkg_belauncerunde.xml        # manifest paketa
│   ├── com_belauncerunde/
│   │   ├── belauncerunde.xml        # manifest komponente
│   │   ├── skript.php               # namestitveni skript
│   │   ├── administrator/           # access.xml, config.xml, forms/, language/, services/, sql/, src/, tmpl/
│   │   ├── site/                    # language/, src/, tmpl/
│   │   └── media/                   # joomla.asset.json, css/, js/
│   ├── plg_authentication_belauncerunde/   # (naloga 04)
│   └── plg_task_belauncerunde/             # (naloga 12)
├── docs/
│   ├── osnova-projekta.md           # vir resnice
│   ├── osnutek-vmesnika.pdf         # wireframe
│   ├── ARCHITECTURE.md
│   ├── DECISIONS.md
│   ├── TASKS.md
│   ├── SESSION.md
│   ├── NAVODILA.md                  # uporabniška navodila (nastaja postopoma)
│   └── naloge/NALOGA-xx.md
└── dist/                            # zgrajeni ZIP-i (v .gitignore)
```

## 5. Pravila kode

### Jezik (D28)
- Naši identifikatorji **v slovenščini, samo ASCII** (brez č, š, ž): razredi, metode, spremenljivke, tabele, stolpci, jezikovni ključi, opravila. Primer: `RundaModel`, `$zacetek`, `tezavnost_id`, `COM_BELAUNCERUNDE_RUNDA_ZACETEK`.
- **Angleško ostane, kar zahteva Joomla:** razredi in pripone ogrodja (`AdminModel`, `HtmlView`, `FormController`, `Table`), prepisane metode (`display()`, `save()`, `getItem()`, `getListQuery()`, `populateState()`), mape (`src/`, `tmpl/`, `forms/`, `services/`), ključi manifesta, standardni stolpci (`id`, `ordering`, `checked_out`, `checked_out_time`).
- Komentarji in PHPDoc v slovenščini (s šumniki). Vsak razred in vsaka metoda ima PHPDoc. Vsak netrivialen blok ima komentar **kaj in zakaj**.

### Joomla 6
- Imenski prostor: `Belaunce\Component\Belauncerunde\Administrator\…` in `…\Site\…`.
- `declare(strict_types=1);` ni obvezen; `defined('_JEXEC') or die;` je v vsaki PHP datoteki.
- **Brez zastarelih aliasov:** nikoli `JHtml`, `JFactory`, `JText`, `JRoute`, `JUri`, `JTable`, `JLoader`, `BaseModel::getInstance()`, `parent::getModel()` v kontrolerjih. Uporabi `HTMLHelper`, `Factory`, `Text`, `Route`, `Uri`, MVCFactory. Koda mora delovati z izklopljenim vtičnikom za združljivost za nazaj.
- **Brez jQueryja.** JavaScript je vanilla, naložen prek WebAssetManagerja (`media/joomla.asset.json`).
- Obvezna pravila iz `docs/ARCHITECTURE.md`, razdelek "Pravila za Joomlo 6" (tmpl v korenu, `.sys.ini`, `<menu>`/`<submenu>`, `ToolbarHelper::preferences()`, MVCFactory, `provider.php`, `<languages>` v manifestu).

### Baza
- Vsaka tabela: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` (izrecno; produkcija ima privzeto utf8mb3).
- Časi so `DATETIME` v **UTC**; prazni datumi so `NULL` (nikoli `0000-00-00`).
- SQL mora delovati na **MariaDB 10.11** (produkcija), čeprav DDEV teče na 11.8.
- Vsaka poizvedba z vhodnimi podatki uporablja `bind()` z `ParameterType`. Brez lepljenja nizov v SQL.
- Sprememba sheme = nova datoteka `administrator/sql/updates/mysql/X.Y.Z.sql` **in** posodobljen `install.mysql.utf8.sql`.

### Varnost (vsaka naloga)
- CSRF: `Session::checkToken()` (ali `$this->checkToken()`) pri vsaki akciji, ki spreminja podatke. Spremembe samo prek POST.
- Pravice: preverjanje `$user->authorise(...)` ali pravil iz md 7 **v kontrolerju/modelu pri vsaki akciji**, ne samo skrivanje gumbov.
- Izhod: vse spremenljivke v predlogah skozi `$this->escape()` ali `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. URL-ji skozi `Route::_()`.
- Vhod: filtri obrazcev v `forms/*.xml` + validacija v modelu.
- Nikoli ne izpisuj surovega HTML, ki ga vpiše uporabnik.

### Nizi in videz
- Vsi nizi v `sl-SI` **in** `en-GB` jezikovnih datotekah. Nič trdo kodiranega besedila.
- Slovenski nizi s pravilnimi šumniki.
- Bootstrap 5 razredi; lasten CSS minimalen, s predpono `br-`.
- Predloge strani (`templates/`) ne spreminjaj (D41).

## 6. Ukazi

### Gradnja (ti in Matija)
```bash
bash orodja/gradnja.sh
# rezultat: dist/pkg_belauncerunde-X.Y.Z.zip
```
Skripta pred gradnjo preveri: enake verzije v vseh manifestih, `php -l` (če je PHP na voljo), `xmllint` (če je na voljo) in prepovedane vzorce (`JHtml`, `JFactory`, `JText`, `jQuery`, `getInstance(`).

Pred PR poženi gradnjo in v opis PR prilepi njen izpis.

### Namestitev na DDEV (samo Matija)
```bash
cp dist/pkg_belauncerunde-X.Y.Z.zip ~/joomla-dev/tmp/
cd ~/joomla-dev
ddev exec php cli/joomla.php extension:install --path=tmp/pkg_belauncerunde-X.Y.Z.zip
```
ali v administraciji: *Sistem → Namesti → Razširitve → Naloži paket*.

**Nikoli** ročno kopiranje datotek v Joomlo (D39).

## 7. Definicija "končano"
- Vsa merila sprejema iz naloge so izpolnjena ali jasno označena ⚠️ z razlogom.
- `orodja/gradnja.sh` uspe brez napak.
- PHPDoc in komentarji so na mestu.
- `CHANGELOG.md` ima vnos za novo verzijo.
- PR je odprt v `develop` z vsemi obveznimi razdelki.
