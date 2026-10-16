# NALOGA 05: Javni seznam rund, stran runde in SEF router

**Veja:** `feature/NALOGA-05-seznam` (iz `develop`) · **PR v:** `develop` · **Verzija:** 0.5.0

## Cilj
Obiskovalec (tudi neprijavljen) vidi na strani seznam prihajajočih rund, ga filtrira po tipu in težavnosti ter odpre posamezno rundo. URL-ji so čisti: `/runde` in `/runde/{id}-{alias}`.

## Povezane odločitve
D1, D2, D36, D37, D41, D42; `osnova-projekta.md` razdelek 5 (podatkovni model, oblika URL-ja) in 7; `ARCHITECTURE.md` "Pravila za Joomlo 6" **1–23**; `AGENTS.md` v celoti.

**Potrjena oblika URL-jev (prej odprto vprašanje):** `/runde` za seznam, `/runde/{id}-{alias}` za posamezno rundo. Runda se **vedno** poišče po `id`; alias je samo berljiv dodatek. Po izidu se ti URL-ji ne spreminjajo, ker bodo povezave ostale v Viber zgodovini.

## Obseg

**V nalogi:**
1. Site pogled **`runde`** — javni seznam prihajajočih rund s filtri in paginacijo.
2. Site pogled **`runda`** — minimalna javna stran runde (glej 4).
3. **`Site\Service\Router`** (`RouterView`) in registracija v ponudniku komponente.
4. Tip menijske postavke za seznam rund.
5. `media/joomla.asset.json` in minimalen lasten CSS (predpona `br-`).
6. Verzija 0.5.0 v vseh manifestih in `posodobitve.xml`; `sql/updates/mysql/0.5.0.sql` brez sprememb sheme (samo komentar); `CHANGELOG.md`, `ARCHITECTURE.md`, `NAVODILA.md`.

**Izrecno NI v nalogi:**
- "Pridem", odjava, seznam prijavljenih, členski del strani runde (naloga 06)
- gostje (naloga 08), frontend obrazec za ustvarjanje rund (naloga 07)
- Open Graph, `noindex` za končane runde, deljenje v Viber (nalogi 06 in 11)
- spremembe predloge strani (D41)
- kakršnekoli spremembe administracije

## 1. Pogled `runde` (seznam)

### 1.1 Katere runde se prikažejo
- **Prihajajoče:** runda je na seznamu, dokler ni končana — enak izraz kot v administraciji (`RundeModel`): `DATE_ADD(zacetek, INTERVAL COALESCE(trajanje_min, :privzeto_trajanje) MINUTE) >= UTC_TIMESTAMP()`.
- `stanje IN (1, 2)` — **odpovedane runde ostanejo na seznamu**, označene z značko „Odpovedano" (Bootstrap `badge bg-danger`). Člani morajo videti, da je runda odpadla.
- Razvrstitev: `zacetek` naraščajoče.
- Paginacija prek Joomlinega `Pagination` (`ListModel::getPagination()`), privzeto 20 na stran.

### 1.2 Filtri
Filtra **tip** in **težavnost**, oba spustna seznama, ki se oddata z GET (ne POST — stanje mora biti v URL-ju, da je povezavo mogoče deliti). Uporabi `ListModel` z `filter_tip` in `filter_tezavnost` v `populateState()`, vrednosti filtriraj z `getUint()`. Vrednost 0 pomeni „vse".

Možnosti filtrov so objavljeni šifranti po `ordering`. Gumb **Počisti** vrne na `/runde` brez parametrov.

### 1.3 Prikaz posamezne runde na seznamu
Naslov (povezava na stran runde), datum in ura, tip, težavnost, lokacija, dolžina, ime vodje. Čas **vedno** skozi `Administrator\Helper\CasHelper::izUtc()` — v bazi je UTC, prikaz je v `casovni_pas` (D42). Ne uporabljaj Joomlinega `HTMLHelper::_('date', …)` brez eksplicitnega pasu.

Ime vodje dobi z `LEFT JOIN` na `#__users` (enako kot `RundeModel` v administraciji). Če vodja ne obstaja več, pokaži prazno in ne podiraj izpisa.

### 1.4 Prazen seznam
Če ni nobene prihajajoče runde: prijazno sporočilo (jezikovni niz), ne prazna tabela.

## 2. Pogled `runda` (minimalna javna stran)

Prikaže: naslov, datum in uro (`CasHelper`), tip, težavnost, lokacijo, dolžino, trajanje, opombe, povezavo na traso (`trasa_url`, izpisana z `rel="noopener noreferrer" target="_blank"`), ime vodje in značko „Odpovedano", če je `stanje = 2`.

- Dostopna je **vsem** (tudi neprijavljenim). Kaj vidijo samo člani, določi naloga 06.
- Neobstoječa ali neobjavljena runda (`stanje` ni 1 ali 2) → `throw new \Joomla\CMS\MVC\View\GenericDataException(...)` oziroma 404 prek `$app->enqueueMessage()` in `setHeader('status', 404)`. Izberi Joomlin način in ga navedi v PR.
- Runda se **poišče po `id`**, nikoli po aliasu. Če se alias v URL-ju ne ujema z aliasom runde, stran vseeno deluje; v `<link rel="canonical">` postavi pravilen URL.

## 3. SEF Router

### 3.1 Preverjeni vzorec za Joomlo 6.1 (ne ugibaj)
- Razred `Belaunce\Component\Belauncerunde\Site\Service\Router` v `site/src/Service/Router.php`, razširja `Joomla\CMS\Component\Router\RouterView` (vzorec: jedro `components/com_contact/src/Service/Router.php`).
- V konstruktorju registriraj poglede z `RouterViewConfiguration` in pripni pravila po tem vrstnem redu: `MenuRules`, `StandardRules`, `NomenuRules` (jedro, vrstice 106–108).
- V `administrator/services/provider.php` dodaj `$container->registerServiceProvider(new RouterFactory('\\Belaunce\\Component\\Belauncerunde'));` in v tvorbi komponente `$component->setRouterFactory($container->get(RouterFactoryInterface::class));` (vzorec: `administrator/components/com_contact/services/provider.php`, vrstici 51 in 62).
- Razred komponente naj implementira `Joomla\CMS\Component\Router\RouterServiceInterface` in uporabi `RouterServiceTrait` (pravilo 2 — vmesnik **ni** v `Joomla\CMS\Extension\`). Obstoječa `BootableExtensionInterface` in `HTMLRegistryAwareTrait` ostaneta.

### 3.2 Konfiguracija pogledov
```php
$runde = new RouterViewConfiguration('runde');
$this->registerView($runde);

$runda = new RouterViewConfiguration('runda');
$runda->setKey('id')->setParent($runde);
$this->registerView($runda);
```
Metodi `getRundaSegment($id, $query)` in `getRundaId($segment, $query)`:
- segment je `"{id}-{alias}"`; alias preberi iz baze z `bind()`
- pri razčlenjevanju vzemi **del pred prvo pomišljajem** in ga pretvori v `int`; če segment ni oblike `{številka}-{karkoli}`, vrni `(int) $segment`
- če runda ne obstaja, vrni ID, kakršen je — 404 naj obravnava pogled, ne router

### 3.3 Pogoj za čiste URL-je
Čisti `/runde` deluje samo, če obstaja **menijska postavka** tipa „Seznam rund". Brez nje Joomla izpiše `/component/belauncerunde/…`. To ni napaka v kodi; zapiši v `NAVODILA.md` in v merila sprejema.

## 4. Tip menijske postavke
- `site/tmpl/runde/default.xml` z `<metadata><layout title="…" option="…"/><message>…</message></metadata>` (pravilo 3).
- Nizi `COM_BELAUNCERUNDE_RUNDE_MENU_TITLE`, `…_MENU_OPTION`, `…_MENU_DESC` v **administratorjevi** `com_belauncerunde.sys.ini` (pravilo 4).
- Pogled `runda` **nima** `default.xml` (ni izbirljiv kot menijska postavka).
- V `default.xml` seznama dodaj tudi parametre postavke: število rund na stran (`list_limit`, privzeto 20) in ali so filtri prikazani (`prikazi_filtre`, privzeto 1).

## 5. Media in CSS
- `paket/com_belauncerunde/media/joomla.asset.json` z enim slogom `com_belauncerunde.site`.
- V manifest dodaj `<media destination="com_belauncerunde" folder="media"><folder>css</folder><filename>joomla.asset.json</filename></media>`.
- CSS je minimalen in samo za to, česar Bootstrap 5 nima; vsi razredi s predpono `br-`. Brez lastnega JavaScripta in brez jQueryja.
- Naloži ga prek `WebAssetManager` v `HtmlView::display()`, nikoli z `<link>` v predlogi.

## 6. Varnostne zahteve
- Pogleda sta samo za branje: brez POST, brez sprememb podatkov.
- Vse poizvedbe z `bind()` in `ParameterType` (pravilo 16: vrednost po referenci).
- Vsak izpis skozi `$this->escape()`; `trasa_url` dodatno preveri (`filter_var(..., FILTER_VALIDATE_URL)` in shema `http`/`https`), sicer povezave ne izpiši.
- Filtri so `uint`; neveljavne vrednosti padejo na 0.
- Povezave skozi `Route::_()`.
- Na seznamu in strani runde se **ne** izpisujejo emaili, telefoni ali IP-ji.

## 7. Merila sprejema
Matija preveri na DDEV (ZIP pred namestitvijo kopiraj v `tmp/`):

1. `bash orodja/gradnja.sh` uspe; vse verzije 0.5.0. Posodobitev z 0.4.0 uspe, verzija sheme je `0.5.0`, obstoječi podatki so nespremenjeni.
2. Brez menijske postavke: `index.php?option=com_belauncerunde&view=runde` pokaže seznam prihajajočih rund.
3. *Menus → Main Menu → New* ponudi tip **Runde Belaunce → Seznam rund**. Ustvari postavko z aliasom `runde`.
4. `/runde` deluje in prikaže seznam; v naslovni vrstici ni `/component/`.
5. Runda na seznamu vodi na `/runde/{id}-{alias}` in stran se odpre.
6. **Čas:** runda, ki je v administraciji vpisana ob 17:00, je tudi na strani prikazana ob 17:00 — preveri z eno poletno in eno zimsko rundo (poletni/zimski čas, D42).
7. **Odpovedana runda** je na seznamu in na svoji strani označena z značko „Odpovedano".
8. **Končana runda** (začetek + trajanje v preteklosti) **ni** na seznamu, njena stran pa se še vedno odpre.
9. Filter po tipu in po težavnosti deluje, izbira ostane v URL-ju in je deljiva; gumb Počisti vrne na `/runde`.
10. Paginacija deluje (ustvari več kot 20 rund ali v postavki zniže `list_limit` na 2).
11. Prazen rezultat (filter brez zadetkov) pokaže sporočilo, ne prazne tabele.
12. Spremeni alias runde in odpri **staro** povezavo → stran deluje, `<link rel="canonical">` v izvorni kodi kaže nov URL.
13. Neobstoječ ID (`/runde/9999-karkoli`) → 404 oziroma razumljivo sporočilo, ne PHP napaka.
14. Seznam in stran runde delujeta **odjavljen** (zasebno okno).
15. Brez PHP opozoril; vse deluje z **izklopljenim** vtičnikom za združljivost za nazaj.
16. Administracija (Runde, Tipi, Težavnosti, Preveri člana, prijava članov) deluje nespremenjeno.

## 8. Dokumentacija
- `ARCHITECTURE.md`: razdelek **Frontend seznam in router** — oblika URL-jev, registracija routerja, zakaj se runda išče po ID-ju, pogoj menijske postavke.
- `NAVODILA.md`: "Menijska postavka za runde" (brez nje URL-ji niso čisti) in "Kaj vidi obiskovalec".
- `CHANGELOG.md`: `[0.5.0]`.

## Vprašanja
Česar ne moreš izvesti po tej nalogi, ne implementiraj po svoje: zapiši v PR pod "Vprašanja za arhitekta" in nadaljuj z ostalim.
