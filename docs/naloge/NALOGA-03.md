# NALOGA 03: Adapter AcyMailing in diagnostika člana

**Veja:** `feature/NALOGA-03-acymailing` (iz `develop`) · **PR v:** `develop` · **Verzija:** 0.3.0

## Cilj
Komponenta zna iz AcyMailinga zanesljivo ugotoviti, ali je email član društva (D44). Ves dostop do AcyMailinga je v enem razredu, ki samo bere (D45). Admin v Možnostih izbere listo članov in listo "Runde" po imenu (D46). V administraciji je orodje **"Preveri člana"**, s katerim admin po vsaki posodobitvi AcyMailinga v minuti preveri, da adapter še deluje.

Naloga ne vsebuje prijave (to je naloga 04). Pripravi samo zanesljiv vir podatka "kdo je član".

## Povezane odločitve
D4, D5, D44, D45, D46; `osnova-projekta.md` razdelka 6 in 6.2; `ARCHITECTURE.md` "Pravila za Joomlo 6" **1–16** in razdelek "AcyMailing"; `AGENTS.md` v celoti.

## Obseg

**V nalogi:**
1. `Administrator\Service\AcymailingAdapter` — edini razred, ki dostopa do tabel AcyMailinga (samo `SELECT`).
2. Polje obrazca `AcymlistaField` (spustni seznam list AcyMailinga).
3. Možnosti: nov zavihek **AcyMailing** s poljema `lista_clanov` in `lista_runde`.
4. Pogled **Preveri člana** (`view=preverjanje`) v administraciji: obrazec z emailom in rezultat.
5. Dva manjša popravka iz testa naloge 02 (spodaj, razdelek "Manjši popravki").
6. Verzija 0.3.0 v vseh manifestih in `posodobitve.xml`; `updates/mysql/0.3.0.sql` brez sprememb sheme (samo komentar); `CHANGELOG.md`, `ARCHITECTURE.md`, `NAVODILA.md`.

**Izrecno NI v nalogi:**
- prijava, žetoni, ustvarjanje Joomla uporabnikov, vtičniki (naloga 04)
- kakršnokoli **pisanje** v tabele AcyMailinga
- uporaba PHP API-ja AcyMailinga (razredi `AcyMailing\Classes\…`) — D45
- frontend

## Shema AcyMailing (preverjeno na DDEV, AcyMailing Enterprise 11.1.1)

Predpona je Joomlina (`#__`). Uporabi **samo** spodnje stolpce.

| Tabela | Stolpci, ki jih uporabljamo | Pomen |
|---|---|---|
| `#__acym_user` | `id`, `name` (null), `email` (UNIQUE), `active` (tinyint), `cms_id` (int, 0 = ni povezan) | naročnik |
| `#__acym_user_has_list` | `user_id`, `list_id`, `status` (1 = naročen) | članstvo na listi (PK: user_id + list_id) |
| `#__acym_list` | `id`, `name`, `active` | lista |

**Pravilo člana (D44):** `acym_user.active = 1` **in** obstaja `acym_user_has_list` z `list_id = lista_clanov` in `status = 1`. Stolpec `confirmed` se **ne** upošteva.

## `AcymailingAdapter`

Imenski prostor `Belaunce\Component\Belauncerunde\Administrator\Service`. Dostop do baze prek `Factory::getContainer()->get(DatabaseInterface::class)` (ali vstavljen v konstruktor). Vse poizvedbe z `bind()` (pravilo 16).

| Metoda | Vrne | Opis |
|---|---|---|
| `jeNamescen(): bool` | bool | Ali tabele `#__acym_user`, `#__acym_user_has_list`, `#__acym_list` obstajajo (`$db->getTableList()`). Rezultat lahko predpomni v lastnosti. |
| `seznamList(): array` | `[id => name]` | Vse liste po imenu. Prazno, če AcyMailing ni nameščen. |
| `poisciClana(string $email, int $listaId): ?object` | `{acym_id, email, ime, cms_id}` ali `null` | Email normaliziraj (`trim`, `mb_strtolower`) in preveri z `filter_var(..., FILTER_VALIDATE_EMAIL)`. Vrne zapis samo, če velja pravilo člana. Primerjava emaila v SQL s `LOWER(email) = :email`. |
| `preveriEmail(string $email, int $listaId): string` | koda razloga | Za diagnostiko: `CLAN`, `NEVELJAVEN_EMAIL`, `NI_NAROCNIK`, `NEAKTIVEN`, `NI_NA_LISTI`, `ODJAVLJEN` (zapis na listi obstaja, `status` ≠ 1), `LISTA_NI_NASTAVLJENA`, `ACYM_NI_NAMESCEN`. |
| `jeClan(int $acymId, int $listaId): bool` | bool | Pravilo člana za znani `acym_id` (za kasnejšo ponovno prijavo in dnevno sinhronizacijo). |
| `poisciPoId(int $acymId): ?object` | `{acym_id, email, ime, active, cms_id}` ali `null` | Zapis brez preverjanja liste. |
| `idjiClanov(int $listaId): int[]` | seznam `acym_id` | Vsi trenutni člani liste (za dnevno sinhronizacijo v nalogi 12). |

Zahteve:
- `$listaId <= 0` → metode vrnejo `null` / `false` / `[]` / `LISTA_NI_NASTAVLJENA`, brez poizvedbe.
- AcyMailing ni nameščen → enako, brez izjeme (`ACYM_NI_NAMESCEN`).
- Napaka baze → `Log` (kategorija `com_belauncerunde`) in "ni član"; nikoli izjema navzven, nikoli izpis SQL uporabniku.
- Razred **nikoli** ne izvaja `INSERT`, `UPDATE`, `DELETE` na tabelah `acym_*`.
- PHPDoc razreda opiše shemo, na katero se zanaša, in verzijo, na kateri je preverjena (11.1.1).

## `AcymlistaField`
`ListField`; možnosti iz `AcymailingAdapter::seznamList()`, besedilo `ime (ID n)`. Prva možnost "– Izberi listo –" z vrednostjo 0. Če AcyMailing ni nameščen, samo ta možnost in opis polja pove, da AcyMailing ni najden.

## Možnosti (`config.xml`) — nov zavihek **AcyMailing** (pred Dovoljenji)
| Polje | Tip | Privzeto | Opis |
|---|---|---|---|
| `lista_clanov` | `acymlista` | 0 | Lista članov društva (D4). Samo ta lista določa, kdo se lahko prijavi. |
| `lista_runde` | `acymlista` | 0 | Lista za obvestila o rundah (D5). V v1 se še ne uporablja, nastavitev je pripravljena za fazo 2. |

`config.xml` mora imeti na `<fieldset>` atribut `addfieldprefix="Belaunce\Component\Belauncerunde\Administrator\Field"`.

## Pogled **Preveri člana**
- Podmeni: Runde, Tipi, Težavnosti, **Preveri člana**. Dostop samo s `core.admin` na `com_belauncerunde` (preverjeno v kontrolerju in pogledu).
- Obrazec: polje email (type `email`), gumb **Preveri**. POST s CSRF žetonom, opravilo `preverjanje.preveri`.
- Rezultat (po preusmeritvi, prek seje, ne v URL-ju): koda razloga kot berljivo besedilo (jezikovni nizi za vsako kodo), pri `CLAN` še `acym_id`, ime in ali je `cms_id` > 0 (povezan Joomla uporabnik da/ne). Email se v rezultatu prikaže samo escapiran.
- Nad obrazcem opozorilo, če `lista_clanov` ni nastavljena ali AcyMailing ni nameščen (s povezavo na Možnosti).
- Na strani je tudi kratka statistika: število trenutnih članov liste (`count(idjiClanov())`).
- Email se **ne** zapisuje v dnevnik.

## Manjši popravki (iz testa naloge 02)
1. `forms/runda.xml`: polje `dolzina_km` naj nima privzete vrednosti (nova runda ima prazno polje; `min="0.1"` ostane).
2. Obvestilo o zaščiti šifranta uporabi množinske jezikovne nize (`Text::plural`) za oba jezika: `COM_BELAUNCERUNDE_ERROR_TIP_IN_USE` (`_1`, `_2`, `_3` za sl-SI; `_1` in osnovni za en-GB), enako za težavnost. Primer en-GB: "used by 1 ride" / "used by %d rides".

## Varnostne zahteve
- Adapter: samo `SELECT`, vse z `bind()`; ime tabele samo iz konstant v razredu.
- Pogled Preveri člana: `core.admin`, CSRF, POST; email filtriran (`filter="email"`) in validiran; brez izpisa SQL napak.
- Brez zapisovanja emailov v dnevnik ali URL.

## Merila sprejema
Matija preveri na DDEV (ZIP pred namestitvijo kopiraj v `tmp/`):

1. `bash orodja/gradnja.sh` uspe; vse verzije 0.3.0. Posodobitev z 0.2.0 uspe, runde in šifranti ostanejo, verzija sheme `0.3.0`.
2. *Options → AcyMailing*: oba spustna seznama prikažeta liste AcyMailinga po imenu z ID-jem. Izberi listo članov (ID 6) in listo Runde (ID 9), shrani; `SELECT params FROM dipxn_extensions WHERE element='com_belauncerunde'` vsebuje `"lista_clanov":"6"` in `"lista_runde":"9"`.
3. *Preveri člana* je v podmeniju; pokaže število članov liste (pričakovano **124**).
4. Email člana (tvoj ali drug znan) → "Član", z ID-jem AcyMailinga in podatkom o povezanem Joomla uporabniku.
5. Email, ki ga v AcyMailingu ni → "Ni naročnik". Email naročnika, ki ni na listi članov (npr. samo na drugi listi) → "Ni na listi članov".
6. Isti email z velikimi črkami in presledki (`  Ime@Domena.SI `) → enak rezultat kot pri 4.
7. Neveljaven vnos (`abc`) → "Neveljaven email" (ali validacija obrazca).
8. V Možnostih nastavi listo članov na "– Izberi listo –" → Preveri člana pokaže opozorilo, da lista ni nastavljena. Nato vrni na ID 6.
9. Uporabnik brez `core.admin` (npr. Manager) ne vidi podmenija in z neposrednim URL-jem dobi napako dostopa.
10. `SELECT … FROM dipxn_acym_user_has_list WHERE list_id=6` pred in po testih: število vrstic in statusi so nespremenjeni (adapter ničesar ne zapiše).
11. Nova runda ima prazno polje dolžine; obvestilo o zaščiti tipa je slovnično pravilno v obeh jezikih.
12. Brez PHP opozoril; administracija deluje z izklopljenim vtičnikom za združljivost za nazaj.

## Dokumentacija
- `ARCHITECTURE.md`: razdelek **AcyMailing** (tabele, pravilo člana, adapter in njegove metode, kako preveriti ob posodobitvi AcyMailinga).
- `NAVODILA.md`: "Nastavitev AcyMailinga" (izbira list) in "Preveri člana" (kdaj in kako).
- `CHANGELOG.md`: `[0.3.0]`.

## Vprašanja
Česar ne moreš izvesti po tej nalogi, ne implementiraj po svoje: zapiši v PR pod "Vprašanja za arhitekta" in nadaljuj z ostalim.
