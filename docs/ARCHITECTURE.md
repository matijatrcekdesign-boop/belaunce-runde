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
| `plg_task_belauncerunde` | vtičnik (task) | 12 | sinhronizacija članov, izbris gostov, čiščenje žetonov in poskusov |

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
| Vtičnik za opravila | `Belaunce\Plugin\Task\Belauncerunde` |

## Podatkovni model

Shema je v `osnova-projekta.md` razdelek 5. Tabele se dodajajo postopoma po nalogah:

| Tabela | Naloga |
|---|---|
| `#__belaunce_tipi`, `#__belaunce_tezavnosti` | 01 |
| `#__belaunce_runde` | 02 |
| `#__belaunce_zetoni`, `#__belaunce_clani`, `#__belaunce_poskusi` | 04 (0.4.0) |
| `#__belaunce_prijave` | 06 (0.6.0) |
| `#__belaunce_gostje` | 08 (0.8.0) |

Standardni stolpci ogrodja ostanejo angleški (`id`, `ordering`, `checked_out`, `checked_out_time`). Stolpec za objavo se imenuje `stanje`; v razredu `Table` se poveže z `setColumnAlias('published', 'stanje')`, da delujejo Joomlini gumbi za objavo.

## Ključne komponente *(dopolnjuje se po nalogah)*

- `Administrator\Service\AcymailingAdapter` — edini dostop do AcyMailinga, samo branje (naloga 03, D45)
- `Administrator\Helper\CasHelper` — pretvorba UTC ↔ `casovni_pas`
- `Administrator\Helper\OmejitevHelper` — omejevanje poskusov prek `#__belaunce_poskusi` (naloga 04)
- `Administrator\Service\ZetonService` — izdaja in poraba žetonov za magic link (naloga 04)
- `Administrator\Service\ClanService` — Joomla uporabnik člana: ustvari, poveži, zagotovi skupino Člani (naloga 04)
- `Site\Service\Router` — SEF `/runde`, `/runde/{id}-{alias}` (naloga 05)

## Prijava z magic linkom

`GET view=prijava` → `POST prijava.poslji` → mail s povezavo → `GET view=povezava` → potrditveni `POST povezava.prijavi` → Joomlin `login()` → `plg_authentication_belauncerunde` → Joomlin `onUserLogin` in seja. Site del uporablja isti `administrator/services/provider.php` kot administracija. Privzeti site pogled je od naloge 05 `runde`; `tmpl/prijava/default.xml` še naprej omogoča menijsko postavko za prijavo.

Kontroler `PrijavaController` najprej preveri POST in CSRF, nato stanje gosta, filtrira in normalizira email ter pred vsakim dostopom do AcyMailinga zabeleži poskuse. `OmejitevHelper` hrani SHA-256 emaila oziroma IP-ja in šteje poskuse v zadnjih 60 minutah. Vhodni IP je samo `REMOTE_ADDR`. Neveljaven email, nečlan, presežena omejitev in napaka pošte dobijo isto sporočilo in preusmeritev kot uspešna zahteva.

`ZetonService` razveljavi prejšnje neporabljene povezave člana in izda 32 naključnih bajtov, v bazi pa shrani samo SHA-256. `preveri()` je namenjen GET prikazu in ne piše v bazo. `porabi()` najprej izvede pogojni `UPDATE` in zahteva natanko eno spremenjeno vrstico; šele potem prebere zapis. Ponoven POST istega žetona zato ne uspe. Metodi `pociscenje()` za žetone in poskuse kliče dnevno vzdrževalno opravilo.

Avtentikacijo opravi vtičnik (D48), ker samo standardni Joomlin tok sproži uporabniške dogodke in piškotek za daljšo prijavo. Kontroler posreduje `username = ''`, surovi `zeton`, `remember` iz možnosti in `autoregister = false`. Vtičnik na odjemalcu `site` ob lastnem žetonu prevzame odgovor in v `finally` ustavi propagacijo tudi ob napaki. Žeton porabi pred ponovnim preverjanjem AcyMailing članstva in pripravo uporabnika; neuspeh po porabi zahteva novo povezavo. Ob prijavah brez žetona in v administraciji se ne odzove.

`ClanService` najprej išče povezavo po AcyMailing ID, nato članski račun po emailu. Zastarelo povezavo odstrani, ob spremenjenem AcyMailing ID posodobi povezavo obstoječega člana. **Članstvo se uveljavlja prek skupine Člani, komponenta polja `block` nikoli ne spreminja** (odločitev A po testu na DDEV). Blokiran račun zavrne s kodo `COM_BELAUNCERUNDE_NAPAKA_BLOKIRAN`; adminove namerne blokade ne razveljavi.

Obstoječi povezavi v `#__belaunce_clani` zaupamo: po ponovni potrditvi AcyMailing članstva neblokiranemu povezanemu uporabniku vrnemo manjkajočo skupino Člani prek `User::save()`, brez odvzema drugih skupin, in zabeležimo `SKUPINA_VRNJENA` z ID-jema. Iskanje po emailu ostane omejeno na račune, ki skupino že imajo. Email ali uporabniško ime računa brez naše povezave in brez skupine Člani ostane zavrnjeno s `COM_BELAUNCERUNDE_NAPAKA_EMAIL_ZASEDEN`. Samo email torej ni podlaga za dodelitev skupine.

Od 0.12.0 `ClanService::uskladiSkupino()` centralizira vrnitev in odvzem skupine Člani. Prijava ga uporabi šele po potrditvi zaupanja vredne povezave, Scheduler pa samo za vrstice `#__belaunce_clani`. Metoda preskoči blokiran račun, ne spreminja `block` in pri shranjevanju ohrani druge skupine. Če je bila Člani edina skupina, `User::save()` v jedru 6.1 praznega seznama ne prenese v mapiranje; po njem metoda z vezanima ID-jema izbriše samo to vrstico iz `#__user_usergroup_map` ([Table/User.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Table/User.php), `store()`).

Novega uporabnika ustvari samo v skupini Člani z emailom kot uporabniškim imenom in praznim vhodnim geslom; naključno geslo določi `User::bind()` (D49), `block` pa ni del vhodnega polja. `zadnja_prijava` se hrani v UTC. Naša koda ne piše v AcyMailing; povezavo `cms_id` lahko nastavi njegov uporabniški vtičnik. Razlog za odločitev A je na DDEV potrjena sinhronizacija blokade v `acym_user.active`, ki bi bivšemu članu ustavila tudi pošto z drugih klubskih list.

Za **Zapomni si me** morata biti vklopljena **Authentication – Cookie** in **System – Remember Me** (D43). Življenjsko dobo določa `cookie_lifetime` v vtičniku Cookie. Namestitveni skript naš vtičnik vklopi idempotentno (D51) in opozori na izklopljena jedrna vtičnika. Paket zato namešča vtičnik pred komponento, gradnja pa v zanki zapakira vse mape `paket/plg_*`.

Mail uporablja Joomlino predlogo `com_belauncerunde.prijava` z oznakami `{IME}`, `{POVEZAVA}`, `{VELJAVNOST}`, `{SITENAME}` (D50). `postflight` jo ustvari samo, če še ne obstaja; adminove spremembe ostanejo. Privzeta besedila so v site jezikovnih datotekah, absolutna povezava se sestavi z `Uri::root()` brez odvisnosti od menija. Pri odstranitvi se predloga in tri tabele izbrišejo samo pri `ohrani_podatke = 0`. Dnevnik prijave vsebuje samo kode in ID-je.

ID runde je v žetonu in po uspešni avtentikaciji v seji `com_belauncerunde.runda_po_prijavi`. Od verzije 0.6.0 ga kontroler `PovezavaController` po uspešni prijavi takoj prebere in odstrani ter potrdi udeležbo po spodnjem toku D40.

## Frontend seznam in router

`Site\Model\RundeModel` bere javna polja rund s stanjem 1 ali 2, poveže naziva šifrantov in polno ime vodje prek `LEFT JOIN` ter jih razvrsti po začetku naraščajoče (pri istem času po ID-ju). Seznam vključuje runde do konca: `DATE_ADD(zacetek, INTERVAL COALESCE(trajanje_min, :privzeto_trajanje) MINUTE) >= UTC_TIMESTAMP()`. Filtra `filter_tip` in `filter_tezavnost` se bereta iz GET brez shranjevanja izbire v seji; paginacija ohrani oba filtra. Odmik se bere kot `limitstart` iz glavnega vhoda aplikacije, kjer ga jedrni `SiteRouter::parsePaginationData()` pretvori iz SEF parametra `start`. Menijska parametra sta `list_limit` (20) in `prikazi_filtre` (1).

`Site\Model\RundaModel` poišče objavljeno ali odpovedano rundo izključno po ID-ju, brez časovne omejitve. Zato končane runde izginejo s seznama, njihove strani pa ostanejo dostopne. `Site\View\Runda\HtmlView` pri manjkajočem ali nejavnem zapisu vrže `GenericDataException` s kodo 404. Oba pogleda oblikujeta čas šele po `CasHelper::izUtc()` in naložita `com_belauncerunde.site` prek WebAssetManagerja. Opombe ostanejo escapirano besedilo; trasa se izpiše samo za preverjen HTTP/HTTPS URL. Kontaktni podatki gostov se od 0.8.0 berejo samo za identiteto vodje posamezne runde (glej Gostje). Od verzije 0.6.0 se imena udeležencev berejo samo za člana; javnost vidi število in javno ime vodje.

Potrjeni SEF poti sta `/runde` in `/runde/{id}-{alias}`. `Site\Service\Router` razširi `RouterView`, registrira `runde` in podrejeni `runda` s ključem `id` ter pripne `MenuRules`, `StandardRules` in `NomenuRules` v tem vrstnem redu po vzorcu jedra 6.1 `components/com_contact/src/Service/Router.php`. Registrira tudi obstoječa pogleda `prijava` in `povezava`, da ostane tok prijave dosegljiv pri vključenem SEF.

Skupni ponudnik `administrator/services/provider.php` registrira `RouterFactory` in jo preda komponenti prek `RouterFactoryInterface`; razred komponente implementira `RouterServiceInterface` in uporablja `RouterServiceTrait` iz `Joomla\CMS\Component\Router`. Konstruktor routerja sprejme tudi neuporabljeni nullable argument za kategorije, ker ga jedrna `libraries/src/Component/Router/RouterFactory.php` vedno posreduje pred bazo.

Router pri gradnji bere trenutni alias z vezanim ID-jem; pri razčlenjevanju vrne številčni ID in ne zavrne starega aliasa ali manjkajočega zapisa. Pogled doda absolutni `<link rel="canonical">` s trenutnim URL-jem prek `Route::_()`. Sprememba aliasa tako ne pokvari povezav iz Viber zgodovine. Za čisti poti mora obstajati javna menijska postavka tipa **Seznam rund** z aliasom `runde` in vključen SEF; brez postavke Joomla uporabi `/component/belauncerunde/…`.

## Udeležba na rundi

`#__belaunce_prijave` hrani `runda_id`, `uporabnik_id` in UTC `ustvarjeno`; unikatni par runda/uporabnik preprečuje podvojitev. Obstoj vrstice pomeni udeležbo, odjava jo izbriše. **D54:** vodji se vrstica nikoli ne vstavlja, ne ponudi se mu gumba za prijavo ali odjavo. `PrijaveModel::izrazStevilaUdelezencev()` na enem mestu določa vsoto prijav, obstoječega vodje in gostov; uporabljata ga `steviloUdelezencev()` in Moje runde. Če uporabnika vodje ni več, ga ne šteje. Drugje se gostov ne prišteva. `RundaTable::store()` v isti transakciji kot shranjevanje runde odstrani morebitno prijavo trenutnega vodje, zato D54 velja tudi pri administratorski zamenjavi vodje z že prijavljenim članom.

`RundaController` pri obeh akcijah `runda.pridem` in `runda.odjava` preveri POST, CSRF in članstvo. Članstvo preveri tudi `PovezavaController` v toku D40 ter model pred branjem imen in pred zapisom. Enotna metoda `PrijaveModel::jeClan()` zahteva prijavljeno Joomlino identiteto in skupino iz `skupina_clani`. ID uporabnika vedno pride iz identitete, ID runde iz POST oziroma porabljenega žetona, nikoli povratni URL. Pogled ločeno preveri članstvo, predloga pa imena izriše samo članu.

`PrijaveModel::pridem()` je ista metoda za običajni POST in D40. Skupna pravila zavrnejo nejavno, odpovedano ali že štartano rundo in ohranijo samodejno udeležbo vodje brez vrstice. SQL zapis še enkrat preveri stanje, UTC štart in vodjo. `INSERT IGNORE ... SELECT` skupaj z unikatnim ključem prenese sočasne prijave, `DELETE` pa odstrani samo lastno vrstico. Ponovitev prijave ali odjave ni napaka. Dnevnik vsebuje samo kode in ID-je; dodajanje ali odstranjevanje zabeleži samo pri `getAffectedRows() > 0`.

Pri D40 kontroler takoj po uspešnem `login()` prebere in **takoj odstrani** `com_belauncerunde.runda_po_prijavi`, nato uporabi `pridem()`. Pred novim poskusom prijave odstrani tudi morebiten ostanek predhodnega neuspeha. Ob odprti rundi preusmeri na njeno stran s `stanje=prijavljen`; ob odpovedi ali štartu obstoječe runde s `stanje=runda_zaprta`. Če `rundaZaUdelezbo()` vrne `null` (runda ne obstaja ali ni v stanju 1 ali 2), model vrne ločeno kodo `runda_ni_najdena`, tudi ob ponovnem branju po zapisu brez sprememb. Oba kontrolerja pri tej kodi preusmerita na `view=runde&stanje=runda_ni_najdena`. Seznam izriše opozorilo `alert-warning` z `role="alert"`, ki potrdi uspešno prijavo na stran in pojasni, da runde ni več. Tako uspešni prijavi ne sledi 404. Brez ID-ja runde ostane dosedanja preusmeritev `view=prijava&stanje=uspeh`.

Javni in članski del kažeta število; seznam imen je vodja z značko, nato prijave po `ustvarjeno, id`, samo prek `#__users.name` in `LEFT JOIN`. Od 0.8.0 sledijo gostje po času; njihova kontakta se bereta samo vodji, IP-ja se nikoli ne bere za prikaz. Stran runde uporablja zaprti seznam `prijavljen`, `odjavljen`, `ni_clan`, `runda_zaprta`, `napaka`; seznam rund ima svoj zaprti seznam z edino kodo `runda_ni_najdena`. Neznana koda nima izpisa. Joomlina sistemska sporočila ostanejo.

Meja za akcije je **štart**, za seznam in oznako končane runde pa **konec** z `COALESCE(trajanje_min, privzeto_trajanje_min)`. Obe meji model preveri z uro baze v UTC. Končana runda ostane dosegljiva, pokaže obvestilo, povezavo na prihajajoče in `robots=noindex, follow`. Open Graph uporablja isti absolutni URL kot canonical; opis sestavljajo samo lokalni datum/ura, tip, težavnost in dolžina iz jezikovnega niza. `Document::setMetaData(..., 'property')` in jedrni `MetasRenderer` poskrbita za izris in escapiranje (Joomla 6.1: `libraries/src/Document/Document.php`, `libraries/src/Document/Renderer/Html/MetasRenderer.php`).

## Ustvarjanje in urejanje rund

Možnost `omejitev_runde_dan` v zavihku **Prijava** določa največ 3, 5 ali 10 novih rund na člana (privzeto 5). `ObrazecModel::dovoljenje()` jo preveri samo za `id = 0`, takoj po članstvu; `shrani()` ponovno pokliče isto preverjanje tik pred `store()`, zato nova zaporedna oddaja upošteva že ustvarjeno rundo. Šteje `COUNT(*)` iz `#__belaunce_runde` po `ustvaril_id` z vezanim ID-jem identitete in mejo `ustvarjeno >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)`: drsečih 24 ur po UTC uri baze, brez filtra stanja. Tudi odpovedane runde ostanejo v kvoti, da je ni mogoče obiti z ustvarjanjem in odpovedovanjem. Urejanje in odpoved ne porabita dodatne kvote ter se zaradi nje ne zavrneta. Tabela `#__belaunce_poskusi` se ne uporablja, ker omejujemo uspešno ustvarjene runde, ne ogledov ali neuspešnih oddaj obrazca. Ob meji ostane gumb **Ustvari rundo** viden, GET obrazca ali POST ustvarjanja pa preusmeri na seznam s kodo `omejitev` in prevedenim obvestilom z dejansko nastavljeno mejo. Dnevnik vsebuje samo `RUNDA_OMEJITEV` in ID uporabnika.

`Site\Model\ObrazecModel` (`FormModel`) uporablja izključno `site/forms/runda.xml`. `Site\View\Obrazec\HtmlView` ima eno postavitev za ustvarjanje in urejanje. Pred prikazom preveri pravico, doda `task` in POST `id` prek `addControlField()`, predloga pa izpiše `renderControlFields()` skupaj z Joomlinim CSRF žetonom. Obrazec uporablja `form.validate`, Bootstrap 5 in samo objavljene tipe ter težavnosti po `ordering, id`. Lokalni vnos se v UTC pretvori samo prek `CasHelper`; polje calendar ima `filter="string"`, brez `user_utc` ali `server_utc`. Povratna pretvorba zavrne neveljaven datum in neobstoječo uro ob prehodu na poletni čas. Trajanje je prazno ali celo število 1–1440; XML nima `min`/`max`.

`RundaController::shrani()` in `odpovej()` zahtevata POST in CSRF. Kontroler pokliče preverjanje pravic, model ga ponovi ob vstopu in neposredno pred `store()`. Ustvarjanje uporablja isto `PrijaveModel::jeClan()` kot udeležba. Urejanje in odpoved zahtevata prijavljeno identiteto vodje, stanje 1 in `zacetek > UTC_TIMESTAMP()`; meja je štart, nikoli konec. Admin nima posebne frontend izjeme. Tudi GET obrazca za tujo, odpovedano ali štartano rundo preusmeri na seznam z zaprto kodo stanja.

Model sprejme samo naslov, šifranta, začetek, lokacijo, dolžino, trajanje, opombe, URL trase in odprtost za goste. Ob ustvarjanju sam nastavi `vodja_id`, `stanje = 1`, `ustvaril_id`, `ustvarjeno`; pri urejanju te vrednosti in alias ohrani iz naložene tabele. ID prihaja iz ločenega POST polja, lastništvo vedno iz baze. Zapis gre prek obstoječe `Administrator\Table\RundaTable::check()` in `store()` (alias, revizija, odpoved in D54); nove prijave vodje se ne vstavlja. Odpoved nastavi samo stanje 2, čas odpovedi določi tabela. Članske prijave ostanejo. Potrditev odpovedi je Bootstrap modal, naložen prek WebAssetManagerja, z ločenim ID-jem za vsako kartico na seznamu.

Vnos ob napaki ostane lokalni v seji pod `com_belauncerunde.obrazec.{id}.data`; po uspehu se odstrani. Napake validacije se izpišejo escapirano tudi brez sistemskih sporočil predloge. Uspeh preusmeri na stran runde s kodami `ustvarjena`, `shranjena`, `odpovedana`. Zavrnitve imajo zaprt seznam `ni_clan`, `prepovedano`, `zaklenjena`, `ni_najdena`, `neveljavna_zahteva`, `napaka_zapisa`, `omejitev`; neznane kode nimajo izpisa. Dnevnik hrani samo kode in ID-je.

Router registrira `obrazec` kot otroka `runde` s ključem `id`. Segmenta sta `nova` za 0 in `uredi-{id}-{alias}`; `getObrazecId()` bere številko za `uredi-`, `nova` vrne 0. `StandardRules::parse()` sprejme otroka samo pri resničnem ključu, zato `Router::parse()` izrecno obravnava literal `nova` pod menijsko postavko seznama. Pri poteh brez menija ničelni ID podpira `NomenuRules`. Številčni segment runde se ne prekriva s predpono urejanja. Dolge povezave `index.php?option=com_belauncerunde&view=obrazec&id=…` ostanejo delujoče.

Preverjeni viri Joomla **6.1-dev** (2026-10-19): `libraries/src/Component/Router/RouterView.php`, `libraries/src/Component/Router/Rules/StandardRules.php`, `libraries/src/Component/Router/Rules/NomenuRules.php`, `libraries/src/MVC/Model/FormModel.php`, `libraries/src/MVC/Model/BaseDatabaseModel.php`, `libraries/src/Form/Form.php`, `libraries/src/Form/Field/ListField.php`, `libraries/src/Form/Field/CalendarField.php` v [izvorni kodi Joomle](https://github.com/joomla/joomla-cms/tree/6.1-dev).

## Moje runde

`Site\Model\MojeModel` in `Site\View\Moje\HtmlView` izvajata zasebni pogled `view=moje` oziroma `/runde/moje`. Pogled pred branjem seznamov preveri identiteto: neprijavljenega preusmeri na `view=prijava&stanje=moje_prijava`, prijavljenega nečlana na `view=runde&stanje=ni_clan`. Pogled in model uporabljata izključno `PrijaveModel::jeClan()`; model uporabniški ID vedno pridobi iz identitete aplikacije. Dokument ima `robots=noindex, nofollow`; obstoječi `DisplayController` ohrani `Cache-Control: no-store, private` in izključeno predpomnjenje.

`getCmd('pogled')` izbere samo `pretekle`; vsaka druga vrednost pomeni `prihajajoce`. Če je menijski parameter `prikazi_pretekle` izklopljen (privzeto 1), se tudi neposredni zahtevek za pretekle vrne na prihajajoče. Meja je enaka javnemu seznamu: `DATE_ADD(zacetek, INTERVAL COALESCE(trajanje_min, :privzeto_trajanje) MINUTE) >= UTC_TIMESTAMP()` za prihajajoče in `< UTC_TIMESTAMP()` za pretekle. Prihajajoče vključujejo stanje 1 in 2, pretekle samo 1. Razvrščanje je po začetku in ID-ju naraščajoče oziroma padajoče; prikaz časa uporablja `CasHelper` (D42).

Prihajajoče imajo dve poizvedbi: `vodja_id` je ID identitete za **Runde, ki jih vodim**, za **Prijavljen sem** pa preverimo obstoj lastne prijave z `EXISTS` in izključimo istega vodjo. D54 vodji nikoli ne ustvarja vrstice prijave; dodatna izključitev prepreči podvajanje tudi pri starih neskladnih podatkih. Pretekle združijo vodjo in obstoj prijave z `OR`, brez množenja vrstic; postavka označi vlogo člana. Bere se samo aktivni zavihek: pri preteklih `COUNT(*)` in stran pomenita največ dve poizvedbi (pri praznem seznamu eno), s čimer ostanemo pod mejo treh. Joomlin `Pagination` uporablja parameter `pretekle_omejitev` (privzeto 20) in ohrani `pogled=pretekle`; zastarel odmik omejimo na zadnjo veljavno stran. Imena vodij pridejo prek `LEFT JOIN #__users`, druga imena in kontakti se sploh ne berejo.

Edini seštevek prijav, obstoječega vodje in gostov je od 0.9.0 SQL izraz `PrijaveModel::izrazStevilaUdelezencev($vzdevek)`. Vzdevek mora biti veljaven SQL identifikator; metoda ne izvaja poizvedbe. Uporabljata ga `steviloUdelezencev()` in poizvedba Moje runde. Tako ni klicev štetja v zanki. Tudi router pred izrisom prejme že prebrane aliase prek `Router::predpomniAliase()`, zato `Route::_()` na povezavah in gumbih Uredi ne bere vsakega aliasa posebej. Ta predpomnilnik velja samo za trenutno zahtevo, druge povezave ohranijo dosedanje iskanje trenutnega aliasa.

Uredi in Odpovej uporabljata obstoječi `tmpl/runda/upravljanje.php` z ID-jem modala na rundo; dovoljena sta samo vodji stanja 1 pred štartom. Odjava se ponudi samo pri lastni prijavi stanja 1 pred štartom in pošlje POST `runda.odjava`, CSRF ter `vrni=moje`. `RundaController` samo za odjavo sprejme točno vrednost `moje` in cilj sam sestavi kot `view=moje`; vse druge vrednosti, tudi URL-ji, uporabijo dosedanji cilj strani runde oziroma javnega seznama pri izbrisani rundi. POST nikoli ne določa poljubnega URL-ja ali refererja. Obstoječa kontroler in model ponovno preverita članstvo, lastno prijavo, stanje in UTC štart. Pogled izriše samo zaprte kode obvestil. Odpoved in shranjevanje še naprej preusmerita na stran runde. Pretekle nimajo akcij.

Router registrira `moje` brez ključa kot otroka `runde`; literal po standardnih pravilih ne trči s številčnim ID-jem runde ali `nova` in `uredi-`. Metadata `site/tmpl/moje/default.xml` uporablja administratorjevi `.sys.ini` za tip menijske postavke; oznaki parametrov sta v administratorjevi `.ini`. Dolgi URL ostane podprt. Za `/runde/moje` je priporočena javna podrejena menijska postavka z aliasom `moje`; javni dostop postavke omogoči preusmeritev neprijavljenega na naš obrazec.

Preverjeni viri Joomla **6.1-dev** (2026-10-20): [StandardRules.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Component/Router/Rules/StandardRules.php), [MenuRules.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Component/Router/Rules/MenuRules.php), [Pagination.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Pagination/Pagination.php) in [BaseDatabaseModel.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/MVC/Model/BaseDatabaseModel.php).

## Vdelava trase

`Site\Helper\TrasaHelper::preveriUrl()` dovoli samo absolutne HTTP/HTTPS povezave brez prijavnih podatkov in kontrolnih znakov. `vdelava()` vrne opis (`src`, jezikovni ključ naslova) ali `null`, nikoli HTML. Za iframe zahteva HTTPS brez izrecnih vrat, natančen gostitelj in prepoznano pot ter vključen parameter `trasa_strava`, `trasa_komoot` ali `trasa_bikemap` (privzeto 1, zavihek **Trasa** v možnostih).

| Vhodni gostitelji (tudi različica z www) | Vzorec poti | Sestavljen iframe src |
|---|---|---|
| `strava.com` | `/routes/{številčni id}` z neobvezno zadnjo poševnico | `https://strava-embeds.com/route/{id}?style=standard` |
| `komoot.com`, `komoot.de` | `/tour/{id}` ali `/{jezik-država}/tour/{id}`, neobvezna zadnja poševnica | `https://www.komoot.com/tour/{id}/embed?layout=classic&profile=1` |
| `bikemap.net` | `/r/{id}` ali `/{jezik}/r/{id}`, neobvezen ASCII alias in zadnja poševnica | `https://widgets.bikemap.net/r/{id}?unit=metric&extended=1&distanceMarkers=1` |

ID ostane niz številk (tudi veliki ID-ji Strava). Vhodni query in fragment se ne prenašata v iframe. Kratke povezave, aktivnosti, drugi poddomenski gostitelji ali druge poti ostanejo povezave. Ne izvajamo HTTP zahtev na uporabnikov URL in ne shranjujemo uporabnikovega HTML-ja. Iframe je escapiran v Bootstrap `ratio-4x3`, z `loading="lazy"`, `referrerpolicy="no-referrer"` in prevedenim naslovom. Povezava na izvirno traso je vedno na voljo, tudi ob vdelavi, z `target="_blank" rel="noopener noreferrer"`. Izklop ponudnika takoj pusti samo povezavo.

Uradni viri, preverjeni **2026-10-19**:

- Strava: [How to Embed a Strava Route](https://partners.strava.com/resources/how-to-embed-a-strava-route) in [uradni generator embed.js](https://strava-embeds.com/embed.js). Dokumentacija uporablja zunanji generator; njegov izvor potrjuje naslov `https://strava-embeds.com/{embedType}/{embedId}`. Uporabimo njegov route iframe neposredno, brez nalaganja generatorja ali inline skript na strani.
- Komoot: [uradni generator in dokumentacija](https://www.komoot.com/b2b/embed), primer `/tour/13584167/embed?layout=classic&profile=1`.
- Bikemap: [Embedding routes](https://help.bikemap.net/hc/en-us/articles/360017460279-Embedding-routes) ter [slika kode v istem navodilu](https://help.bikemap.net/hc/article_attachments/15548737043228), ki izrecno kaže `widgets.bikemap.net/r/13983492?unit=metric&extended=1&distanceMarkers=1`.

CSP mora v `frame-src` dovoliti `https://strava-embeds.com`, `https://www.komoot.com`, `https://widgets.bikemap.net` za vključene ponudnike. Prikaz dejanskih javnih tras in morebitne preusmeritve ponudnikov se preverijo na DDEV.

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
16. **Joomline metode `bind()` sprejmejo argument po referenci** — velja za `DatabaseQuery::bind()` in tudi za `User::bind(&$array)` (jedro `libraries/src/User/User.php:607`). Nikoli ne podajaj izraza ali literala (npr. `(int) $x`, `['a' => 1]`) in v zanki ne uporabljaj iste spremenljivke za več vezav. Napaka se pokaže kot `Error: Argument #1 could not be passed by reference` in jo `php -l` ne ujame.
17. Obvezno polje `type=number` z atributom `min` ob prazni vrednosti prikaže `min` (jedro, `layouts/joomla/form/field/number.php`). Če mora biti polje prazno, `min` in `max` izpusti in meje preveri v `Table::check()`.
18. **Vtičniki se namestijo izklopljeni** (razen urejevalnikov; jedro `Installer/Adapter/PluginAdapter.php:489`). Namestitveni skript komponente jih mora vklopiti, v manifestu paketa pa so navedeni **pred** komponento (`Installer/Adapter/PackageAdapter.php:147` namešča otroke po vrsti iz manifesta).
19. **`Authentication::authenticate()` vedno bere `$credentials['username']`** (jedro `libraries/src/Authentication/Authentication.php:169`), zato mora ta ključ obstajati, tudi če je prazen niz.
20. **Vtičnik za avtentikacijo ne sme računati na vrstni red.** `plg_authentication_joomla` ob praznem geslu nastavi `STATUS_FAILURE` in `error_message`, zato lasten vtičnik ob uspehu nastavi **vsa** polja odgovora (`status`, `type`, `username`, `email`, `fullname`, `error_message = ''`) in pokliče `$event->stopPropagation()`. Ob tujih prijavah (brez lastnega ključa v `$credentials`) se vtičnik ne odzove.
21. **`plg_user_joomla::onUserLogin` poišče uporabnika z `loadUserByUsername($response->username)`** (jedro `plugins/user/joomla/src/Extension/Joomla.php:516`). Uporabnik mora torej obstajati **pred** klicem `login()`, `username` v odgovoru pa se mora ujemati na znak. Samodejno registracijo jedra izklopi z `$options['autoregister'] = false`, sicer bi bil uporabnik ustvarjen v skupini iz `com_users`, ne v "Člani".
22. **Lastna kategorija dnevnika brez zapisovalnika ne piše nikamor.** `Log::add(..., 'com_belauncerunde')` je viden šele, ko je zanj registriran zapisovalnik (`Log::addLogger`) ali ko admin vklopi *Log Almost Everything* (jedro `Application/CMSApplication.php:1368`). Komponenta si zapisovalnik registrira sama v `BelauncerundeComponent::boot()`; vsaka koda, ki teče **izven** konteksta komponente (npr. opravila iz naloge 12), si ga mora zagotoviti posebej.
23. **`DatabaseDriver::insertObject()` preskoči lastnosti z vrednostjo `null`**, zato stolpec dobi privzeto vrednost iz baze. Zato `User` brez nastavljenega `block` dobi 0 iz sheme — in zato nastavljanje privzetkov ni nujno, je pa treba vedeti, da se `null` ne zapiše.
24. **Lasten zapisovalnik vedno brez {CLIENTIP}; privzeta oblika jedra ga vključuje.** Pri `Log::addLogger()` vedno nastavi `text_entry_format` na `"{DATETIME}\t{PRIORITY}\t{CATEGORY}\t{MESSAGE}"` (tabulatorji med štirimi polji). To velja tudi za zapisovalnike zunaj konteksta komponente, vključno s prihodnjimi opravili naloge 12. Trenutno je edina registracija v `BelauncerundeComponent::boot()`. Preverjeno v jedru Joomla 6.1-dev: [FormattedtextLogger.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Log/Logger/FormattedtextLogger.php), privzeta lastnost `format` in prepis z možnostjo `text_entry_format` v konstruktorju. Sprememba oblike ne odstrani starih vrstic ali stare glave datoteke; pred zagonom na produkciji dnevnik izbriši po navodilih v `NAVODILA.md`.

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

**Sinhronizacija stanja računa (D52, preverjeno na DDEV):** AcyMailingov uporabniški vtičnik zrcali Joomlino polje `block` v `acym_user.active` — blokada postavi 0, odblokada 1. Nastavitve za izklop ni. Posledica: blokiran Joomla uporabnik po pravilu D44 ni več član in izgubi tudi pošto z vseh drugih klubskih list. Zato komponenta polja `block` nikoli ne spreminja in se članstvo uveljavlja prek skupine "Člani" (D53). Isto velja za opravila iz naloge 12.

Možnosti komponente imajo zavihek **AcyMailing**, kjer admin izbere `lista_clanov` in `lista_runde` prek polja `Administrator\Field\AcymlistaField`. Pogled `preverjanje` je ročni test adapterja po posodobitvah AcyMailinga: admin vnese email, rezultat pa se prenese prek seje po preusmeritvi. Dostop do pogleda in akcije `preverjanje.preveri` zahteva `core.admin` na `com_belauncerunde`.

## Gostje

`Site\Model\GostModel` in `GostController` izvajata prijavo brez računa. Obrazec `site/forms/gost.xml` na strani runde je prikazan samo nečlanu (tudi prijavljenemu), kadar je `odprto_za_goste = 1`, `stanje = 1` in `zacetek > UTC_TIMESTAMP()`. Član uporablja **Pridem**. Kontroler zahteva POST in CSRF ter preveri dovoljenje; model ga ponovi ob vstopu in pod zaklepom neposredno pred zapisom. SQL vstavljanje še enkrat preveri stanje, odprtost in štart po UTC uri baze. Ime se filtrira v navadno besedilo in preveri dolžina 2–100; vsaj en kontakt je obvezen. Email uporablja `FILTER_VALIDATE_EMAIL`, telefon samo dovoljene znake in mejo 30 znakov.

**Zaščita D25:** navadno polje `spletisce` je s CSS `br-gost-dodatek` postavljeno zunaj vidnega območja in izključeno iz tipkovniškega fokusa. Izpolnjeno polje vrne isto kodo kot uspeh, ne ustvari vrstice in ne pošlje pošte. Čas dejanskega prikaza je v Joomlini seji pod `com_belauncerunde.gost.cas.{runda_id}`, nikoli v skritem vhodnem polju. Model čas ob oddaji porabi in zahteva najmanj `gostje_min_cas` sekund (privzeto 3); manjka ali prehiter čas pomeni zavrnitev. Za kvoto `gostje_omejitev` (1/2/3/5, privzeto 2) model v transakciji najprej zaklene rundo z `SELECT ... FOR UPDATE`, nato prešteje goste iste runde z istim hashem in zapiše vrstico. Vsi naši pisci gostov uporabljajo isti zaklep, zato kvota velja tudi pri sočasnih oddajah.

**IP in dnevnik:** stolpec `#__belaunce_gostje.ip` je zaradi predpisane sheme tako poimenovan, vendar tip `CHAR(64)` hrani izključno `hash('sha256', REMOTE_ADDR . secret)`; `secret` pride iz Joomline konfiguracije. Proxy glav se ne upošteva. Hash je namenjen samo omejevanju prijav, nikoli prikazu. V dnevnik gredo samo zaprte kode in ID runde, brez imen, kontaktov, omrežnih naslovov, hashov ali žetonov; besedil izjem se ne zapisuje.

**Captcha:** `gostje_captcha = 0` je privzeto. `GostCaptchaHelper` ob vklopu uporabi samo globalno nastavitev `captcha` in omogočen Joomlin vtičnik prek `Captcha::getInstance()`. Manjkajoč vtičnik povzroči opozorilo in nadaljevanje brez captche. Neuspešna verifikacija obstoječe captche je zavrnitev. Izpis je zaupan HTML vtičnika, ne uporabnikov HTML. Gradnja dopušča ta izrecno zahtevani javni API jedra, splošna prepoved ostalih klicev `getInstance()` ostane.

**Pošta in odjava:** namestitveni skript idempotentno registrira `com_belauncerunde.gost_potrditev` z oznakami `{IME}`, `{RUNDA}`, `{ZACETEK}`, `{LOKACIJA}`, `{VODJA}`, `{POVEZAVA}`, `{SITENAME}`. Besedila so v sl-SI/en-GB; čas pretvori `CasHelper`, HTML predloge escapirajo vse oznake prek `addUnsafeTags()`. Pošta gre samo gostu z emailom in šele po potrjeni transakciji. Napaka pošte ohrani prijavo in uporabniku pokaže opozorilo.

Žeton ima 32 naključnih bajtov v hex zapisu. Baza hrani samo SHA-256 in UNIQUE ključ; brez emaila je `odjava_zeton_hash = NULL`. Žeton nima časovnega roka in velja do izbrisa gosta. Surova vrednost je samo v povezavi v mailu in vhodnem GET, ne v seji, bazi, dnevniku ali skritem obrazcu. Pogled `odjavagosta` preveri hash brez osebnih podatkov in v sejo pod naključnim potrditvenim izzivom shrani samo hash. Obrazec pošlje izziv, ne surovega žetona. `gost.odjava` zahteva POST in CSRF, porabi sejni izziv, preveri hash tudi v modelu in izvede pogojni DELETE. Zato predogledovalnik pošte z GET nikoli ne odjavi gosta, dva potrditvena POST pa lahko izbrišeta samo eno vrstico. Odjava in neveljaven oziroma porabljen žeton dobita isto nevtralno obvestilo. Stran uporablja `no-store`, `noindex, nofollow` in `Referrer-Policy: no-referrer`.

Router registrira `odjavagosta` kot otroka `runde`; `build()` prevede njegov segment v `odjava-gosta`, `parse()` pa nazaj v MVC ime pred standardnimi pravili. Ob menijski postavki seznama z aliasom `runde` je pot `/runde/odjava-gosta`; brez nje ostane podprta pot brez menija in dolgi URL. Obstoječe poti rund, prijave, povezave in obrazca ostanejo pri dosedanjih pravilih.

**Prikaz D37/D54:** skupni izraz `PrijaveModel::izrazStevilaUdelezencev()` za `steviloUdelezencev()` in Moje runde določa vsoto članskih prijav, obstoječega vodje in gostov. `udelezenci()` najprej zahteva člana, nato vrne vodjo, članske prijave po `ustvarjeno, id` in goste po `ustvarjeno, id`. Za navadnega člana poizvedba gostov sploh ne izbere `telefon` in `email`; izbere ju samo, kadar se ID identitete ujema z `vodja_id` runde. Predloga dodatno omeji kontakt na vodjo in vse osebne vrednosti escapira. Javni obiskovalec in prijavljen nečlan ne dobita seznama imen. Gost ima Bootstrap značko `bg-info text-dark`, vodja `bg-secondary`.

Preverjena izvorna koda Joomla **6.1-dev** (2026-10-19): [Captcha.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Captcha/Captcha.php), [CaptchaField.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Form/Field/CaptchaField.php), [CaptchaRule.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Form/Rule/CaptchaRule.php), [MailTemplate.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Mail/MailTemplate.php), [StandardRules.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Component/Router/Rules/StandardRules.php), [BaseController.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/MVC/Controller/BaseController.php). Tip vezave NULL je preverjen v [Joomla Framework Database ParameterType.php](https://github.com/joomla-framework/database/blob/master/src/ParameterType.php).

Izbris po 30 dneh (D16) izvaja vzdrževalno opravilo; administratorsko urejanje gostov je preloženo v nalogo 13. Obvestila o spremembah/odpovedi izvaja spodnji MailService (naloga 10). AcyMailing se za goste ne uporablja.


## Deljenje v Viber

Od 0.11.0 `Site\Helper\DeljenjeHelper` sestavi navadno besedilo in povezavo `viber://forward?text=…`. Pogleda na strežniku dovolita deljenje samo za `stanje = 1` in `!startala`, torej `zacetek > UTC_TIMESTAMP()` po obstoječi uri baze. Stran runde pokaže blok vsem obiskovalcem, Moje runde pa samo na prihajajočih karticah obeh seznamov. Odpovedane, štartane in končane runde nimajo bloka ali povezave. Javni seznam rund nima deljenja.

Možnost `viber_predloga` v zavihku **Viber** je navadno besedilo. Prazna oziroma obrezana prazna vrednost uporabi site jezikovni ključ `COM_BELAUNCERUNDE_VIBER_PRIVZETO`; `Language::_()` ob prevodu pretvori zapisane `\n` v prelome. Helper normalizira CRLF/CR na LF in s `strtr()` v enem prehodu zamenja samo `{NASLOV}`, `{DAN}`, `{DATUM}`, `{URA}`, `{LOKACIJA}`, `{TIP}`, `{TEZAVNOST}`, `{DOLZINA}`, `{TRAJANJE}`, `{VODJA}`, `{POVEZAVA}`. Neznane oznake ostanejo nespremenjene, oznake znotraj vstavljenih vrednosti se ne razširjajo. Manjkajoča trajanje in vodja sta prazna. Pretvorba časa gre izključno prek `CasHelper::izUtc()`, nato `new Date($lokalni, 'UTC')` in `format(..., false)` oblikujeta že lokalno uro brez ponovne pretvorbe ter prevedeta dan in mesec.

Stran runde uporabi natanko isto spremenljivko `$kanonicna` za besedilo, canonical in `og:url`. Moje runde pripravijo absolutni `Route::_()` po `Router::predpomniAliase()` in uporabijo isti helper; SELECT doda samo `a.trajanje_min`, število poizvedb zavihka ostane nespremenjeno (največ tri). Imena udeležencev in gostov, kontakti, opombe ter trasa nikoli ne gredo v besedilo. Helper ne escapira; izpis besedila in href v predlogah vedno gre skozi `$this->escape()`.

`rawurlencode()` ohrani RFC 3986 kodiranje (`%20` za presledke, `%0A` za prelome), zato Viber prejme emoji, šumnike in znake kot eno vrednost parametra. Povezave `viber://` nikoli ne podamo skozi `Route::_()` ali `Uri`; router se uporabi samo za običajno spletno povezavo v besedilu.

Delni prikaz `site/tmpl/runda/deli.php` sledi udeležbi oziroma obrazcu gosta. Povezava Viber in označena readonly textarea sta vedno dostopni brez JavaScripta. Skrita gumba progresivno izboljša IIFE `media/js/deli.js`, ki se naloži samo na deljivi strani prek `com_belauncerunde.deli` (odvisnost `core`, `defer`). `Text::script()` pripravi oba prevoda za obvestila. Sistemsko deljenje je vidno samo ob `navigator.share` in poda samo `{text}`, ker bi ločen `url` podvojil povezavo. Preklic `AbortError` je tih. Kopiranje uporablja Clipboard API; odsotnost ali zavrnitev API oziroma druga napaka deljenja označi besedilo za ročno kopiranje. Status uporablja izključno `textContent` in `aria-live="polite"`. Na karticah ni dodatnih skript ali textarea.

Preverjeni viri Joomla **6.1-dev** (2026-10-21): [Language.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Language/Language.php) (`_`, pretvorba prelomov), [Date.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Date/Date.php) (`format`, `dayToString`), [WebAssetManager.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/WebAsset/WebAssetManager.php) (`useScript`, odvisnosti), [Text.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Language/Text.php) (`script`, Joomla.Text), [ComponentHelper.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Component/ComponentHelper.php) (`getParams`) in [Route.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Router/Route.php) (absolutna spletna povezava).

## Transakcijski maili

Od 0.10.0 zaznava sprememb sedi v `Administrator\Table\RundaTable::store()` (D56). Uporabi isto prejšnje stanje kot čas odpovedi in po uspešnem commitu izpostavi `$_sprozilnaSprememba`, `$_pravkarOdpovedana` in `$_prvotniZacetek`. Predpona `_` označuje interne javne lastnosti, ki jih Joomlin DatabaseDriver ne zapisuje kot stolpce. Vsak store zaznavo ponastavi; novi ali neuspešni zapisi je ne objavijo. Začetek primerja kot UTC DATETIME niz, lokacijo kot obrezan niz. Odpoved je prehod iz stanja, različnega od 2, v stanje 2 in ima prednost pred hkratno spremembo polj. Mail odpovedi vsebuje termin pred tem zapisom.

`Site\Model\ObrazecModel::shrani()` in `odpovej()` po vrnitvi uspešnega store pokličeta `MailService::poZapisu()`. Administratorski `RundaModel::prepareTable()` ohrani referenco na dejansko tabelo jedrnega `AdminModel::save()`; po uspešnem `parent::save()` pokliče isti servis. Paketna odpoved iz `nastaviStanje()` ga pokliče po vsakem uspešnem store. Podvojeni so samo klici, ker frontend nima jedrnega AdminModel toka. **Jedrni `onTableAfterStore` ni primerna skupna točka:** sproži se znotraj `parent::store()`, še pred našim odstranjevanjem prijave vodje in commitom. Pošta nikoli ne teče v transakciji runde.

`Administrator\Service\MailService` prebere članske prijave z users JOIN, posebej vodjo (D57) in goste z emailom. Naslove obreže in pretvori v male črke ter združi; gost samo s telefonom ni prejemnik. Za vsak naslov ustvari nov Mailer prek MailerFactoryInterface in novo Joomlino predlogo z natanko enim To. `PosameznaMailTemplate` v kopiji prebranih parametrov izprazni `copyto`, da tudi globalno Joomlino kopiranje ne doda Bcc; nastavitev v bazi ne spreminja. Vsi podatkovni tagi so označeni z `addUnsafeTags()` za varen HTML. Sporočilo o spremembi navaja samo spremenjena vključena polja, vsebuje povezavo na rundo in opozorilo o odgovornosti; odpoved vodi na prihajajoče runde. Route::link('site', ..., absolute=true) zagotovi frontend povezave tudi iz administracije. Čas oblikuje CasHelper v časovnem pasu komponente. Frontend uporabi trenutni jezik strani, backend privzeti site jezik iz com_languages.

Gostu ob pošiljanju ustvari 32 naključnih bajtov in zamenja samo `odjava_zeton_hash` s SHA-256. Surovi žeton ostane v pomnilniku za odjavno povezavo obstoječega `view=odjavagosta`; prejšnja povezava ne velja več. Če več gostov ali vodja uporablja isti email, en mail vsebuje povezavo za vsakega pripadajočega gosta. Napaka posameznega naslova ne ustavi ostalih. Če po zamenjavi žetona SMTP odpove, novi mail ni dostavljen in stara povezava ostane neveljavna; ni ponovnega pošiljanja ali vrste v tej nalogi. Servis vrne uspeh/neuspeh, nikoli ne razveljavi zapisa runde, in zapiše samo kodo, ID runde in število prejemnikov. Frontend ima zaprti opozorilni kodi `shranjena_mail`/`odpovedana_mail`, da je opozorilo vidno tudi brez sistemskih sporočil predloge; backend uporabi enqueueMessage.

Zavihek **Maili** vsebuje `mail_sprozilna_polja`: privzeto zacetek in lokacija, odpoved je neodvisna. `MailpoljaField` razširi CheckboxesField s praznim skritim članom istega seznama. Tako se ob odkljukanju obeh shrani `['']` in Joomlini privzetki ne vključijo polj nazaj. Servis primerja samo znani vrednosti zacetek in lokacija. Manjkajoči parameter pri posodobitvi pomeni oba privzetka.

Namestitveni skript idempotentno registrira `com_belauncerunde.runda_sprememba` in `com_belauncerunde.runda_odpoved` prek MailTemplate::createTemplate(), nato doda manjkajoči jezikovni vrstici sl-SI/en-GB z jezikovnimi ključi. Ker getTemplate vrača tudi rezervno vrstico language='', se obstoj posamezne jezikovne vrstice preveri z natančno poizvedbo. Obstoječih administratorskih popravkov ne prepisuje. Pri odstranitvi z ohrani_podatke=0 odstrani tudi obe predlogi. Migracija 0.10.0 je samo komentar, brez sprememb sheme ali podatkov.

Preverjeni viri Joomla **6.1-dev** (2026-10-20): [Table.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Table/Table.php) (čas dogodka onTableAfterStore), [AdminModel.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/MVC/Model/AdminModel.php) (prepareTable in save), [MailTemplate.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Mail/MailTemplate.php) (createTemplate, jezikovni fallback, addUnsafeTags in copyto), [MailerFactory.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Mail/MailerFactory.php), [LanguageFactory.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Language/LanguageFactory.php), [Route.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Router/Route.php), [CheckboxesField.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Form/Field/CheckboxesField.php) in njegov [layout](https://github.com/joomla/joomla-cms/blob/6.1-dev/layouts/joomla/form/field/checkboxes.php). Interna polja in zapis objektov sta preverjena v [Joomla Framework DatabaseDriver.php](https://github.com/joomla-framework/database/blob/3.x-dev/src/DatabaseDriver.php).

## Vzdrževalno opravilo

`plg_task_belauncerunde` objavi eno opravilo `belaunce_vzdrzevanje`; administrator ga v Schedulerju ročno ustvari z dnevnim urnikom. Vtičnik in komponenta se namestita v paketu, vtičnik pred komponento; `postflight` ga vklopi. `TaskPluginTrait` poveže tip opravila z metodo `opraviVzdrzevanje(ExecuteTaskEvent): int`. Metoda vrne `Status::OK` tudi po zabeleženi napaki posameznega dela, saj vsak del teče ločeno.

`VzdrzevanjeService` najprej sinhronizira povezane člane. Če AcyMailing ni nameščen, lista članov ni nastavljena ali izbrana lista ne obstaja, sinhronizacijo preskoči. Strogo branje `AcymailingAdapter::idjiClanov()` ob napaki vrže izjemo, da prazen rezultat ob izpadu baze ne povzroči množičnega odvzema. Za vsako vrstico `#__belaunce_clani` `ClanService::uskladiSkupino()` vrne ali odvzame izključno skupino Člani; blokirane račune preskoči. Uporabnikov brez naše povezave ne obravnava.

Drugi del z uro baze `UTC_TIMESTAMP()` izbriše goste rund, katerih začetek je starejši od 30 dni, ne glede na stanje runde. Tretji del uporabi obstoječa `ZetonService::pociscenje()` (7 dni) in `OmejitevHelper::pociscenje()` (2 dni). Shema se ne spremeni.

Scheduler teče izven zagona komponente. Vtičnik zato enkrat na izvajanje sam registrira zapisovalnik kategorije `com_belauncerunde` z obliko brez `{CLIENTIP}`. Zapisuje samo kode, ID-je in števila, nikoli osebnih vrednosti ali besedila izjem.

Preverjeni viri Joomla **6.1-dev** (2026-10-21): [Requests.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/plugins/task/requests/src/Extension/Requests.php), [provider.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/plugins/task/requests/services/provider.php), [TaskPluginTrait.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/administrator/components/com_scheduler/src/Traits/TaskPluginTrait.php), [ExecuteTaskEvent.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/administrator/components/com_scheduler/src/Event/ExecuteTaskEvent.php), [Status.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/administrator/components/com_scheduler/src/Task/Status.php), [Log.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Log/Log.php).

## Backend statistika in izvoz

`Administrator\Model\RundeFiltriTrait::dodajFiltre()` uporablja tako seznam rund kot `StatistikaModel`. Šest filtrov (iskanje, tip, težavnost, stanje, vodja, obdobje) ima isti pomen in vezave SQL. `StatistikaModel` uporablja ločen kontekst Joomlinega `ListModel`, zato izbire na statistiki ne spreminjajo filtrov seznama; ista shranjena izbira statistike velja pri CSV izvozu. Model naredi eno poizvedbo za skupne kazalnike, po eno za razčlenitvi po tipu in težavnosti ter eno za vse izvožene vrstice. Povprečno trajanje je `AVG(trajanje_min)`, zato `NULL` ne šteje kot nič. Število udeležencev vedno uporablja izraz iz `Site\Model\PrijaveModel::izrazStevilaUdelezencev('a')` (D54), ustvarjen prek MVCFactory; imen in kontaktov prijavljenih ali gostov ne bere.

Pretok je `view=statistika` → `StatistikaModel::povzetek()/poTipu()/poTezavnosti()` → `administrator/tmpl/statistika/default.php`; izvoz je `task=statistika.izvoz` → `StatistikaController::izvoz()` → `StatistikaModel::vrsticeIzvoza()` → CSV. Oba dostopa zahtevata `core.manage`; izvoz dodatno preveri GET CSRF žeton. CSV vsebuje vseh enajst predpisanih stolpcev, lokalni začetek prek `CasHelper`, podpičje, CRLF, UTF-8 BOM in decimalno vejico pri dolžini. `fputcsv()` z izklopljenim lastnim escape znakom podvoji notranje narekovaje. Besedilna polja, ki bi jih Excel razumel kot formule, dobijo začetni apostrof. Kontroler prek aplikacije nastavi glave brez predpomnjenja, pošlje glave, izpisuje vrstice in zapre aplikacijo.

Preverjeni viri Joomla **6.1-dev** (2026-10-22): [ListModel.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/MVC/Model/ListModel.php) (kontekst, stanje filtrov, obrazec), [MVCFactory.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/MVC/Factory/MVCFactory.php) (model `Site` v administraciji), [ToolbarHelper.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Toolbar/ToolbarHelper.php) (`link`), [WebApplication.php](https://github.com/joomla/joomla-cms/blob/6.1-dev/libraries/src/Application/WebApplication.php), [AbstractWebApplication.php](https://github.com/joomla-framework/application/blob/3.x-dev/src/AbstractWebApplication.php) (`setHeader`, `sendHeaders`) in [AbstractApplication.php](https://github.com/joomla-framework/application/blob/3.x-dev/src/AbstractApplication.php) (`close`).
