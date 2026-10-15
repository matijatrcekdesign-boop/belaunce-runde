# ARCHITECTURE.md — pkg_belauncerunde

**Stabilna referenca.** Posodobi samo, ko se arhitektura dejansko spremeni. Kar je označeno *(načrtovano)*, še ni implementirano.

---

## Pregled

**Namen:** člani kolesarskega društva Belaunce objavljajo skupinske vožnje (runde), drugi se nanje prijavijo. Seznam je javen. Podrobnosti: `docs/osnova-projekta.md`.

**Razširitve v paketu `pkg_belauncerunde`:**

| Razširitev | Tip | Naloga | Odgovornost |
|---|---|---|---|
| `com_belauncerunde` | komponenta | 01+ | šifranti, runde, prijave, gostje, frontend pogledi, maili, CSV |
| `plg_authentication_belauncerunde` | vtičnik (authentication) | 04 | porabi magic link žeton, zagotovi uporabnika in potrdi prijavo |
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
| Vtičnik za prijavo | `Belaunce\Plugin\Authentication\Belauncerunde` |
| Vtičnik za opravila | `Belaunce\Plugin\Task\Belauncerunde` *(načrtovano)* |

## Podatkovni model

Shema je v `osnova-projekta.md` razdelek 5. Tabele se dodajajo postopoma po nalogah:

| Tabela | Naloga |
|---|---|
| `#__belaunce_tipi`, `#__belaunce_tezavnosti` | 01 |
| `#__belaunce_runde` | 02 |
| `#__belaunce_zetoni`, `#__belaunce_clani`, `#__belaunce_poskusi` | 04 (0.4.0) |
| `#__belaunce_prijave` | 06 |
| `#__belaunce_gostje` | 08 |

Standardni stolpci ogrodja ostanejo angleški (`id`, `ordering`, `checked_out`, `checked_out_time`). Stolpec za objavo se imenuje `stanje`; v razredu `Table` se poveže z `setColumnAlias('published', 'stanje')`, da delujejo Joomlini gumbi za objavo.

## Ključne komponente *(dopolnjuje se po nalogah)*

- `Administrator\Service\AcymailingAdapter` — edini dostop do AcyMailinga, samo branje (naloga 03, D45)
- `Administrator\Helper\CasHelper` — pretvorba UTC ↔ `casovni_pas`
- `Administrator\Helper\OmejitevHelper` — omejevanje poskusov prek `#__belaunce_poskusi` (naloga 04)
- `Administrator\Service\ZetonService` — izdaja in poraba žetonov za magic link (naloga 04)
- `Administrator\Service\ClanService` — Joomla uporabnik člana: ustvari, poveži, zagotovi skupino Člani (naloga 04)
- `Site\Service\Router` — SEF `/runde`, `/runde/{id}-{alias}` (naloga 05)

## Prijava z magic linkom

`GET view=prijava` → `POST prijava.poslji` → mail s povezavo → `GET view=povezava` → potrditveni `POST povezava.prijavi` → Joomlin `login()` → `plg_authentication_belauncerunde` → Joomlin `onUserLogin` in seja. Site del uporablja isti `administrator/services/provider.php` kot administracija. Privzeti site pogled je do naloge 05 `prijava`; `tmpl/prijava/default.xml` omogoča tudi menijsko postavko.

Kontroler `PrijavaController` najprej preveri POST in CSRF, nato stanje gosta, filtrira in normalizira email ter pred vsakim dostopom do AcyMailinga zabeleži poskuse. `OmejitevHelper` hrani SHA-256 emaila oziroma IP-ja in šteje poskuse v zadnjih 60 minutah. Vhodni IP je samo `REMOTE_ADDR`. Neveljaven email, nečlan, presežena omejitev in napaka pošte dobijo isto sporočilo in preusmeritev kot uspešna zahteva.

`ZetonService` razveljavi prejšnje neporabljene povezave člana in izda 32 naključnih bajtov, v bazi pa shrani samo SHA-256. `preveri()` je namenjen GET prikazu in ne piše v bazo. `porabi()` najprej izvede pogojni `UPDATE` in zahteva natanko eno spremenjeno vrstico; šele potem prebere zapis. Ponoven POST istega žetona zato ne uspe. Metodi `pociscenje()` za žetone in poskuse sta pripravljeni, klic iz Schedulerja pride v nalogi 12.

Avtentikacijo opravi vtičnik (D48), ker samo standardni Joomlin tok sproži uporabniške dogodke in piškotek za daljšo prijavo. Kontroler posreduje `username = ''`, surovi `zeton`, `remember` iz možnosti in `autoregister = false`. Vtičnik na odjemalcu `site` ob lastnem žetonu prevzame odgovor in v `finally` ustavi propagacijo tudi ob napaki. Žeton porabi pred ponovnim preverjanjem AcyMailing članstva in pripravo uporabnika; neuspeh po porabi zahteva novo povezavo. Ob prijavah brez žetona in v administraciji se ne odzove.

`ClanService` najprej išče povezavo po AcyMailing ID, nato članski račun po emailu. Zastarelo povezavo odstrani, ob spremenjenem AcyMailing ID posodobi povezavo obstoječega člana. **Članstvo se uveljavlja prek skupine Člani, komponenta polja `block` nikoli ne spreminja** (odločitev A po testu na DDEV). Blokiran račun zavrne s kodo `COM_BELAUNCERUNDE_NAPAKA_BLOKIRAN`; adminove namerne blokade ne razveljavi.

Obstoječi povezavi v `#__belaunce_clani` zaupamo: po ponovni potrditvi AcyMailing članstva neblokiranemu povezanemu uporabniku vrnemo manjkajočo skupino Člani prek `User::save()`, brez odvzema drugih skupin, in zabeležimo `SKUPINA_VRNJENA` z ID-jema. Iskanje po emailu ostane omejeno na račune, ki skupino že imajo. Email ali uporabniško ime računa brez naše povezave in brez skupine Člani ostane zavrnjeno s `COM_BELAUNCERUNDE_NAPAKA_EMAIL_ZASEDEN`. Samo email torej ni podlaga za dodelitev skupine.

Novega uporabnika ustvari samo v skupini Člani z emailom kot uporabniškim imenom in praznim vhodnim geslom; naključno geslo določi `User::bind()` (D49), `block` pa ni del vhodnega polja. `zadnja_prijava` se hrani v UTC. Naša koda ne piše v AcyMailing; povezavo `cms_id` lahko nastavi njegov uporabniški vtičnik. Razlog za odločitev A je na DDEV potrjena sinhronizacija blokade v `acym_user.active`, ki bi bivšemu članu ustavila tudi pošto z drugih klubskih list.

Za **Zapomni si me** morata biti vklopljena **Authentication – Cookie** in **System – Remember Me** (D43). Življenjsko dobo določa `cookie_lifetime` v vtičniku Cookie. Namestitveni skript naš vtičnik vklopi idempotentno (D51) in opozori na izklopljena jedrna vtičnika. Paket zato namešča vtičnik pred komponento, gradnja pa v zanki zapakira vse mape `paket/plg_*`.

Mail uporablja Joomlino predlogo `com_belauncerunde.prijava` z oznakami `{IME}`, `{POVEZAVA}`, `{VELJAVNOST}`, `{SITENAME}` (D50). `postflight` jo ustvari samo, če še ne obstaja; adminove spremembe ostanejo. Privzeta besedila so v site jezikovnih datotekah, absolutna povezava se sestavi z `Uri::root()` brez odvisnosti od menija. Pri odstranitvi se predloga in tri tabele izbrišejo samo pri `ohrani_podatke = 0`. Dnevnik prijave vsebuje samo kode in ID-je.

ID runde je v žetonu in po uspešni avtentikaciji v seji `com_belauncerunde.runda_po_prijavi`. **V nalogi 04 ga nihče ne bere in udeležbe ne vpisuje**; to prevzame naloga 06. Naslov runde na potrditvenem gumbu je trenutno informativen.

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
13. Če `provider.php` kliče `setRegistry()`, mora razred komponente uporabljati `HTMLRegistryAwareTrait` (sicer fatalna napaka ob vsakem pogledu).
14. Seznami v administraciji: pogled doda `addControlField('task')` in `addControlField('boxchecked','0')`, predloga jih izpiše z `renderControlFields()`; ročnih skritih polj `task` in žetona ne dodajaj. Enako velja za obrazce urejanja: pogled doda `addControlField('task')`, predloga izpiše `renderControlFields()`.
15. Polja s `layout="joomla.form.field.radio.switcher"` imajo možnosti vedno v vrstnem redu `0` (`JNO`), `1` (`JYES`), kot v jedru.
16. `bind()` sprejme vrednost po referenci: nikoli ne podajaj izraza (npr. `(int) $x`) in v zanki ne uporabljaj iste spremenljivke za več vezav.
17. Obvezno polje `type=number` z atributom `min` ob prazni vrednosti prikaže `min` (jedro, `layouts/joomla/form/field/number.php`). Če mora biti polje prazno, `min` in `max` izpusti in meje preveri v `Table::check()`.
18. **Vtičniki se namestijo izklopljeni** (razen urejevalnikov; jedro `Installer/Adapter/PluginAdapter.php:489`). Namestitveni skript komponente jih mora vklopiti, v manifestu paketa pa so navedeni **pred** komponento (`Installer/Adapter/PackageAdapter.php:147` namešča otroke po vrsti iz manifesta).
19. **`Authentication::authenticate()` vedno bere `$credentials['username']`** (jedro `libraries/src/Authentication/Authentication.php:169`), zato mora ta ključ obstajati, tudi če je prazen niz.
20. **Vtičnik za avtentikacijo ne sme računati na vrstni red.** `plg_authentication_joomla` ob praznem geslu nastavi `STATUS_FAILURE` in `error_message`, zato lasten vtičnik ob uspehu nastavi **vsa** polja odgovora (`status`, `type`, `username`, `email`, `fullname`, `error_message = ''`) in pokliče `$event->stopPropagation()`. Ob tujih prijavah (brez lastnega ključa v `$credentials`) se vtičnik ne odzove.
21. **`plg_user_joomla::onUserLogin` poišče uporabnika z `loadUserByUsername($response->username)`** (jedro `plugins/user/joomla/src/Extension/Joomla.php:516`). Uporabnik mora torej obstajati **pred** klicem `login()`, `username` v odgovoru pa se mora ujemati na znak. Samodejno registracijo jedra izklopi z `$options['autoregister'] = false`, sicer bi bil uporabnik ustvarjen v skupini iz `com_users`, ne v "Člani".

## Pretok podatkov *(dopolnjuje se po nalogah)*

**Backend šifranti (naloga 01):**
`Tipi/Tezavnosti` seznam → `TipiModel::getListQuery()` / `TezavnostiModel::getListQuery()` → `Tipi\HtmlView` / `Tezavnosti\HtmlView` → `administrator/tmpl/tipi/default.php` / `administrator/tmpl/tezavnosti/default.php`; urejanje prek `TipController` / `TezavnostController` (`FormController`) → `TipModel` / `TezavnostModel` (`AdminModel`) → `TipTable` / `TezavnostTable`.

Ključni razredi naloge 01:
- `Belaunce\Component\Belauncerunde\Administrator\Extension\BelauncerundeComponent`
- `Belaunce\Component\Belauncerunde\Administrator\Model\TipiModel`
- `Belaunce\Component\Belauncerunde\Administrator\Model\TezavnostiModel`
- `Belaunce\Component\Belauncerunde\Administrator\Table\TipTable`
- `Belaunce\Component\Belauncerunde\Administrator\Table\TezavnostTable`

Namestitveni skript `paket/com_belauncerunde/skript.php` ob namestitvi in posodobitvi pripravi skupino **Člani** pod **Registered** in njen ID shrani v parameter `skupina_clani`. Odstranitev podatkov je nadzorovana z možnostjo `ohrani_podatke`; manifest namenoma nima `<uninstall><sql>`, da Joomla tabel ne izbriše mimo te možnosti.

**Backend runde (naloga 02):**
`Runde` seznam → `RundeModel::getListQuery()` → `Runde\HtmlView` → `administrator/tmpl/runde/default.php`; urejanje prek `RundaController` (`FormController`) → `RundaModel` (`AdminModel`) → `RundaTable`. Privzeti pogled komponente je `runde`; podmeni je v vrstnem redu Runde, Tipi, Težavnosti.

Časi rund se v bazi hranijo v UTC, obrazec in seznam pa uporabljata `Administrator\Helper\CasHelper`, ki bere parameter `casovni_pas` (privzeto `Europe/Ljubljana`). `CalendarField` namenoma nima filtra `user_utc` ali `server_utc`: `RundaModel::getItem()` pretvori `zacetek` iz UTC za prikaz, `RundaModel::save()` pa pred shranjevanjem pretvori lokalni vnos nazaj v UTC.

Polji `Administrator\Field\TipField` in `Administrator\Field\TezavnostField` bereta objavljene šifrante po `ordering`; če obstoječa runda uporablja skrit šifrant, ga polje še vedno pokaže z oznako "(skrito)", da ga shranjevanje ne izgubi. `TipModel::delete()` in `TezavnostModel::delete()` zavrneta brisanje šifranta, ki ga uporablja vsaj ena runda.

`RundaTable` ne uporablja `setColumnAlias('published', 'stanje')`, ker stanje runde ni Joomlino objavljeno/skrito stanje: `1 = objavljena`, `2 = odpovedana`. Prehod v stanje 2 nastavi `odpovedano` v UTC, prehod nazaj v stanje 1 pa ga počisti.

**AcyMailing (naloga 03):**
Ves dostop do AcyMailinga gre prek `Administrator\Service\AcymailingAdapter`. Adapter uporablja samo neposredne `SELECT` poizvedbe nad preverjeno shemo AcyMailing Enterprise 11.1.1:

- `#__acym_user`: `id`, `name`, `email`, `active`, `cms_id`
- `#__acym_user_has_list`: `user_id`, `list_id`, `status`
- `#__acym_list`: `id`, `name`, `active`

Pravilo člana je D44: naročnik je član, če ima `active = 1` in zapis v `#__acym_user_has_list` za nastavljeno listo članov s `status = 1`. Stolpec `confirmed` se ne bere in ne vpliva na dostop. Adapter nikoli ne zapisuje v tabele `acym_*`.

Metode adapterja:
- `jeNamescen()` preveri obstoj treh AcyMailing tabel.
- `seznamList()` vrne seznam list za možnosti komponente.
- `poisciClana($email, $listaId)` vrne aktivnega člana liste ali `null`.
- `preveriEmail($email, $listaId)` vrne diagnostično kodo (`CLAN`, `NEVELJAVEN_EMAIL`, `NI_NAROCNIK`, `NEAKTIVEN`, `NI_NA_LISTI`, `ODJAVLJEN`, `LISTA_NI_NASTAVLJENA`, `ACYM_NI_NAMESCEN`).
- `jeClan($acymId, $listaId)` preveri znanega AcyMailing uporabnika.
- `poisciPoId($acymId)` vrne naročnika brez preverjanja liste.
- `idjiClanov($listaId)` vrne trenutne člane liste za poznejšo sinhronizacijo.

Možnosti komponente imajo zavihek **AcyMailing**, kjer admin izbere `lista_clanov` in `lista_runde` prek polja `Administrator\Field\AcymlistaField`. Pogled `preverjanje` je ročni test adapterja po posodobitvah AcyMailinga: admin vnese email, rezultat pa se prenese prek seje po preusmeritvi. Dostop do pogleda in akcije `preverjanje.preveri` zahteva `core.admin` na `com_belauncerunde`.
