# ARCHITECTURE.md — pkg_belauncerunde

**Stabilna referenca.** Posodobi samo, ko se arhitektura dejansko spremeni. Kar je označeno *(načrtovano)*, še ni implementirano.

---

## Pregled

**Namen:** člani kolesarskega društva Belaunce objavljajo skupinske vožnje (runde), drugi se nanje prijavijo. Seznam je javen. Podrobnosti: `docs/osnova-projekta.md`.

**Razširitve v paketu `pkg_belauncerunde`:**

| Razširitev | Tip | Naloga | Odgovornost |
|---|---|---|---|
| `com_belauncerunde` | komponenta | 01+ | šifranti, runde, prijave, gostje, frontend pogledi, maili, CSV |
| `plg_authentication_belauncerunde` | vtičnik (authentication) | 04 *(načrtovano)* | potrdi prijavo z magic link žetonom |
| `plg_task_belauncerunde` | vtičnik (task) | 12 *(načrtovano)* | sinhronizacija članov, izbris gostov, čiščenje žetonov in poskusov |

## Tehnologija

- Joomla **6.1.x** (DDEV in produkcija: 6.1.4)
- PHP 8.3
- Baza: produkcija **MariaDB 10.11** (privzeto utf8mb3), DDEV **MariaDB 11.8** (utf8mb4) — razlike v `osnova-projekta.md` 16.3
- Lokalno: WSL2 + DDEV (`~/joomla-dev`, `https://joomla-dev.ddev.site`, nginx)
- Produkcija: belaunce.cc (LiteSpeed, Imunify360, SSH)
- Frontend: Bootstrap 5 iz jedra Joomle (predloga j4starter), vanilla JS, brez jQueryja
- AcyMailing (zadnja verzija) — vir članstva (samo branje)

## Imenski prostori

| Del | Imenski prostor |
|---|---|
| Komponenta, admin | `Belaunce\Component\Belauncerunde\Administrator` |
| Komponenta, site | `Belaunce\Component\Belauncerunde\Site` |
| Vtičnik za prijavo | `Belaunce\Plugin\Authentication\Belauncerunde` *(načrtovano)* |
| Vtičnik za opravila | `Belaunce\Plugin\Task\Belauncerunde` *(načrtovano)* |

## Podatkovni model

Shema je v `osnova-projekta.md` razdelek 5. Tabele se dodajajo postopoma po nalogah:

| Tabela | Naloga |
|---|---|
| `#__belaunce_tipi`, `#__belaunce_tezavnosti` | 01 |
| `#__belaunce_runde` | 02 |
| `#__belaunce_zetoni`, `#__belaunce_clani`, `#__belaunce_poskusi` | 04 |
| `#__belaunce_prijave` | 06 |
| `#__belaunce_gostje` | 08 |

Standardni stolpci ogrodja ostanejo angleški (`id`, `ordering`, `checked_out`, `checked_out_time`). Stolpec za objavo se imenuje `stanje`; v razredu `Table` se poveže z `setColumnAlias('published', 'stanje')`, da delujejo Joomlini gumbi za objavo.

## Ključne komponente *(načrtovano, dopolnjuje se po nalogah)*

- `Administrator\Helper\AcymailingAdapter` — edini dostop do AcyMailinga (naloga 03)
- `Administrator\Helper\CasHelper` — pretvorba UTC ↔ `casovni_pas` (naloga 02)
- `Administrator\Helper\OmejitevHelper` — omejevanje poskusov prek `#__belaunce_poskusi` (naloga 04)
- `Site\Service\Router` — SEF `/runde`, `/runde/{id}-{alias}` (naloga 05)

## Pravila za Joomlo 6 (obvezna, iz izkušenj s com_kisegonma)

1. **`tmpl/{pogled}/{postavitev}.php` je v korenu dela razširitve** (`administrator/tmpl/…`, `site/tmpl/…`), nikoli pod `src/View/…/tmpl/`.
2. **`RouterServiceInterface` / `RouterServiceTrait`** sta v imenskem prostoru `Joomla\CMS\Component\Router\…`, ne `Joomla\CMS\Extension\…`.
3. **Vsak pogled, ki ga je mogoče izbrati kot tip menijske postavke**, ima spremljevalno datoteko `tmpl/{pogled}/{postavitev}.xml` (`<metadata><layout title="…" option="…"/><message>…</message></metadata>`).
4. **Nizi za tip menijske postavke** (`*_MENU_TITLE`, `*_MENU_OPTION`, `*_MENU_DESC`) so v **administratorjevi** `.sys.ini`, tudi za frontend poglede (preverjeno na jedru `com_contact`).
5. **Manifest komponente** ima `<administration><menu>` (sicer komponente ni v meniju Komponente) in `<submenu>` z vnosi za zavihke.
6. **Gumb Možnosti** je `ToolbarHelper::preferences('com_belauncerunde')` v `addToolbar()` pogleda, ne povezava v podmeniju.
7. **Jezikovne datoteke** se namestijo prek elementa `<languages>` v manifestu v globalni mapi (`language/{tag}/`, `administrator/language/{tag}/`). Obstajati morata `com_belauncerunde.ini` in `com_belauncerunde.sys.ini` za site in admin, v `sl-SI` in `en-GB`.
8. **MVCFactory:** kontrolerji dobijo `MVCFactoryInterface` v konstruktorju in uporabljajo `$this->factory->createModel(...)`. Nikoli `parent::getModel()` ali `BaseModel::getInstance()`.
9. **Dostop do baze** v razredih brez `$this->getDatabase()`: `Factory::getContainer()->get(DatabaseInterface::class)`.
10. **`services/provider.php`** je obvezen za vsako razširitev (komponenta, vsak vtičnik).
11. **Relativne poti** (slike, AJAX) se pred izpisom absolutizirajo z `Uri::root()`, ker se na SEF poteh relativne poti razrešijo napačno.
12. **Namestitev samo prek paketa** (D39). Ročna namestitev je pri kisegonmi skrila 6 napak (manjkajoči `<menu>`, `<submenu>`, `.sys.ini`, metadata XML, nizi v napačni `.sys.ini`, napačen gumb Možnosti).

## Pretok podatkov *(dopolnjuje se po nalogah)*

**Backend šifranti (naloga 01):**
`Tipi/Tezavnosti` seznam → `TipiModel::getListQuery()` → `Tipi\HtmlView` → `administrator/tmpl/tipi/default.php`; urejanje prek `TipController` (FormController) → `TipModel` (AdminModel) → `TipTable`.
