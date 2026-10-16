# NALOGA 04: Prijava z magic linkom

**Veja:** `feature/NALOGA-04-prijava` (iz `develop`) · **PR v:** `develop` · **Verzija:** 0.4.0

## Cilj
Član društva se prijavi na stran brez gesla: vpiše email, dobi enkratno povezavo, klikne gumb in je prijavljen. Ob prvi prijavi se mu ustvari Joomla uporabnik v skupini **"Člani"** (D19, D35). Seja je dolga prek Joomlinega "Zapomni si me" (D43).

To je **prva naloga s frontendom** in **prva naloga z vtičnikom** — zato je razdelek 3 (site del) in razdelek 7 (vtičnik) treba brati posebej natančno.

## Povezane odločitve
D4, D6, D19, D35, D38, D40, D43, D44, D45, D47, **D48, D49, D50, D51**; `osnova-projekta.md` razdelki 5, 6, 6.1, 6.2; `ARCHITECTURE.md` "Pravila za Joomlo 6" **1–21** (posebej nova 18–21); `AGENTS.md` v celoti.

## Obseg

**V nalogi:**
1. Tri nove tabele: `#__belaunce_zetoni`, `#__belaunce_clani`, `#__belaunce_poskusi`.
2. Site del komponente (prvič): kontrolerji, modela, pogleda `prijava` in `povezava`, jezikovne datoteke.
3. `Administrator\Helper\OmejitevHelper` — omejevanje poskusov.
4. `Administrator\Service\ZetonService` — izdaja in poraba žetonov.
5. `Administrator\Service\ClanService` — Joomla uporabnik člana.
6. Nov vtičnik `plg_authentication_belauncerunde` + njegova vključitev v paket in gradnjo.
7. Predloga maila `com_belauncerunde.prijava` (D50).
8. Nov zavihek **Prijava** v Možnostih (omejitve iz 6.1).
9. Verzija 0.4.0 v vseh manifestih in `posodobitve.xml`; `sql/updates/mysql/0.4.0.sql`; `CHANGELOG.md`, `ARCHITECTURE.md`, `NAVODILA.md`.

**Izrecno NI v nalogi:**
- vpis na rundo po prijavi (naloga 06) — žeton si ID runde samo **zapomni** (glej 5.4)
- javni seznam rund, SEF router, tip menijske postavke za runde (naloga 05)
- gostje in njihov obrazec (naloga 08)
- čiščenje žetonov in poskusov z opravilom (naloga 12) — v tej nalogi samo metodi `pociscenje()`, ki jih še nihče ne kliče
- lasten obrazec za odjavo (uporabi se Joomlin `com_users` `user.logout`)
- šestmestne kode, dvofaktorska prijava, modul za prijavo

---

## 1. Tabele

Dodaj v `administrator/sql/install.mysql.utf8.sql` **in** v novo `administrator/sql/updates/mysql/0.4.0.sql` (v obeh identični stavki, oboje z `CREATE TABLE IF NOT EXISTS`). Vsak `CREATE TABLE` mora imeti svojo vrstico z `utf8mb4` (gradnja to preverja).

```sql
CREATE TABLE IF NOT EXISTS `#__belaunce_zetoni` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `acym_uporabnik_id` INT NOT NULL,
  `zeton_hash` CHAR(64) NOT NULL,
  `runda_id` INT UNSIGNED NULL DEFAULT NULL,
  `potece` DATETIME NOT NULL,
  `porabljen` DATETIME NULL DEFAULT NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `ustvarjeno` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_zeton_hash` (`zeton_hash`),
  KEY `idx_acym` (`acym_uporabnik_id`),
  KEY `idx_potece` (`potece`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE IF NOT EXISTS `#__belaunce_clani` (
  `uporabnik_id` INT NOT NULL,
  `acym_uporabnik_id` INT NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  `zadnja_prijava` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`uporabnik_id`),
  UNIQUE KEY `idx_acym` (`acym_uporabnik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE IF NOT EXISTS `#__belaunce_poskusi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tip` VARCHAR(20) NOT NULL,
  `kljuc` CHAR(64) NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poskus` (`tip`, `kljuc`, `ustvarjeno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Dopolni tudi `administrator/sql/uninstall.mysql.utf8.sql` (iste tri tabele, `DROP TABLE IF EXISTS`). Vsi časi so **UTC**; `ip` ni nikoli primarni podatek in se v izpisih ne prikazuje.

## 2. Možnosti — nov zavihek **Prijava** (pred zavihkom AcyMailing)

| Polje | Tip | Možnosti | Privzeto |
|---|---|---|---|
| `omejitev_email_uro` | `list`, `filter="integer"` | 3, 5, 10 | 3 |
| `omejitev_ip_uro` | `list`, `filter="integer"` | 10, 20, 50 | 20 |
| `veljavnost_min` | `list`, `filter="integer"` | 10, 15, 30 | 15 |
| `zapomni_si_me` | `radio`, `layout="joomla.form.field.radio.switcher"`, `filter="integer"` | 0 (`JNO`), 1 (`JYES`) — v tem vrstnem redu (pravilo 15) | 1 |

Preostale omejitve iz `osnova-projekta.md` 6.1 (prijave gostov na IP, nove runde na člana) **ne** dodajaj — pridejo z nalogama 07 in 08.

## 3. Site del komponente

### 3.1 Manifest
V `belauncerunde.xml` dodaj **pred** `<administration>` (koren manifesta = site del):

```xml
<files folder="site">
    <folder>forms</folder>
    <folder>src</folder>
    <folder>tmpl</folder>
</files>
<languages folder="site/language">
    <language tag="sl-SI">sl-SI/com_belauncerunde.ini</language>
    <language tag="en-GB">en-GB/com_belauncerunde.ini</language>
</languages>
```

Novega `services/provider.php` ni: ponudnik komponente ostane samo v `administrator/services/provider.php` in velja za oba odjemalca.

### 3.2 Datoteke
```
paket/com_belauncerunde/site/
├── forms/prijava.xml
├── language/{sl-SI,en-GB}/com_belauncerunde.ini
├── src/
│   ├── Controller/DisplayController.php      # privzeti pogled: prijava
│   ├── Controller/PrijavaController.php      # opravilo: poslji
│   ├── Controller/PovezavaController.php     # opravilo: prijavi
│   ├── Model/PrijavaModel.php                # FormModel (obrazec za email)
│   ├── Model/PovezavaModel.php               # podatki o žetonu za prikaz
│   ├── View/Prijava/HtmlView.php
│   └── View/Povezava/HtmlView.php
└── tmpl/
    ├── prijava/default.php
    ├── prijava/default.xml                   # pravilo 3 (tip menijske postavke)
    └── povezava/default.php
```
Nizi za tip menijske postavke (`COM_BELAUNCERUNDE_PRIJAVA_MENU_TITLE`, `…_MENU_OPTION`, `…_MENU_DESC`) gredo v **administratorjevo** `com_belauncerunde.sys.ini` (pravilo 4). Pogled `povezava` ni izbirljiv kot menijska postavka, zato nima `default.xml`.

**Besedila mailov** (razdelek 8) morajo biti v **site** `com_belauncerunde.ini`: Joomla jih ob pošiljanju naloži iz `JPATH_SITE` (jedro `administrator/components/com_mails/src/Helper/MailsHelper.php`, vrstice 92–95).

### 3.3 Tok
1. **`GET index.php?option=com_belauncerunde&view=prijava`** (po želji `&runda_id=N`)
   Obrazec: polje `email` (`type="email"`, `filter="email"`, `required`), skrito `runda_id` (`filter="uint"`), CSRF žeton, gumb *Pošlji povezavo*. Če je uporabnik že prijavljen, obrazca ni — samo sporočilo in povezava na domačo stran.
2. **`POST task=prijava.poslji`** — glej 4.
3. **`GET index.php?option=com_belauncerunde&view=povezava&zeton=…`**
   Žetona **ne porabi**. Prikaže obrazec z enim gumbom *Prijavi me* (POST). Če ima žeton `runda_id` in runda obstaja, je besedilo gumba *"Prijavi me in potrdi udeležbo na rundi X"* (D40) — v tej nalogi gumb opravi **samo prijavo**, naslov runde je informativen. Neveljaven, porabljen ali potekel žeton: sporočilo in povezava na nov obrazec.
4. **`POST task=povezava.prijavi`** — glej 5.

Vsi URL-ji so nesef (`index.php?option=…`), ker router pride z nalogo 05. Povezave v predlogah skozi `Route::_()`.

## 4. `prijava.poslji`

Vrstni red je obvezen (vsak korak loči komentar):

1. `$this->checkToken()` — CSRF; samo POST.
2. Če uporabnik ni gost: preusmeri z obvestilom, da je že prijavljen.
3. Email: iz obrazca (`filter="email"`), nato `trim()` + `mb_strtolower()`; validacija z `MailHelper::isEmailAddress()`.
4. **Omejitve** (pred kakršnokoli poizvedbo v AcyMailing):
   - `OmejitevHelper::zabelezi('prijava_ip', $ip)` in, če je email veljaven, `zabelezi('prijava_email', $email)` — zabeleži se **vsak** poskus, tudi neuspešen.
   - Če `presezeno('prijava_ip', $ip, omejitev_ip_uro)` ali `presezeno('prijava_email', $email, omejitev_email_uro)`: **isto sporočilo kot ob uspehu** (ne razkrij), zapiši v dnevnik (brez emaila, samo tip omejitve) in končaj.
5. Če je email neveljaven: isto sporočilo kot ob uspehu, brez pošiljanja.
6. `AcymailingAdapter::poisciClana($email, $lista_clanov)`. Če `null` (ni član, lista ni nastavljena, AcyMailing ni nameščen): isto sporočilo, brez pošiljanja. Če lista ni nastavljena ali AcyMailing manjka, to **zabeleži v dnevnik kot opozorilo** (admin drugače ne ve, zakaj nihče ne more priti noter).
7. Član: `ZetonService::ustvari(...)` → surovi žeton; pošlji mail (razdelek 8).
8. **Vedno enako sporočilo** `COM_BELAUNCERUNDE_PRIJAVA_POSLANO` (vsebina po `osnova-projekta.md` 6, točka 3: "Če je naslov pri nas, smo poslali povezavo. Preveri, da uporabljaš naslov, s katerim si včlanjen, ali piši na info@belaunce.cc.") in preusmeritev na `view=prijava`.

Čas odziva naj bo enak za člana in nečlana v okviru razumnega — ne dodajaj umetnih zamud, samo ne vračaj različnih sporočil ali različnih preusmeritev.

## 5. `povezava.prijavi`

1. `$this->checkToken()`.
2. Če uporabnik ni gost: ne porabi žetona, preusmeri z obvestilom, da je že prijavljen.
3. Surovi žeton iz POST (`filter="cmd"` ni dovolj — uporabi `getString()` in preveri `preg_match('/^[0-9a-f]{64}$/', …)`).
4. **Prijavo opravi vtičnik (D48).** Kontroler samo:
   ```php
   $uspeh = $app->login(
       ['username' => '', 'zeton' => $surovZeton],
       ['remember' => (bool) $params->get('zapomni_si_me', 1), 'autoregister' => false]
   );
   ```
   Ključ `username` mora obstajati, tudi prazen (pravilo 19). `autoregister => false` prepreči, da bi jedro ustvarilo uporabnika v napačni skupini (pravilo 21).
5. Ob `true`: sporočilo o uspehu in preusmeritev na domačo stran (oz. na `view=prijava`, dokler ni seznama rund).
6. Ob `false`: sporočilo o napaki (jedro ga je že postavilo v vrsto, če ga vtičnik nastavi) in preusmeritev na `view=prijava`.

### 5.4 ID runde
Če ima porabljeni žeton `runda_id`, ga vtičnik po uspešni prijavi shrani v sejo: `$app->getSession()->set('com_belauncerunde.runda_po_prijavi', (int) $rundaId)`. V tej nalogi tega nihče ne bere — naloga 06 ga porabi in pobriše. V PR to izrecno omeni.

## 6. Pomožni razredi

### 6.1 `Administrator\Helper\OmejitevHelper`
| Metoda | Opis |
|---|---|
| `zabelezi(string $tip, string $surovKljuc): void` | `INSERT` v `#__belaunce_poskusi`; `kljuc = hash('sha256', $surovKljuc)`, `ustvarjeno` = UTC zdaj. Surova vrednost (email, IP) se **nikoli** ne zapiše. |
| `steviloZadnjaUra(string $tip, string $surovKljuc): int` | `COUNT(*)` za zadnjih 60 minut. |
| `presezeno(string $tip, string $surovKljuc, int $meja): bool` | `$meja > 0 && steviloZadnjaUra(...) > $meja`. |
| `pociscenje(int $dni = 2): int` | Izbriše zapise, starejše od `$dni`. Nihče je še ne kliče. |

Dovoljeni tipi so konstante razreda (`TIP_PRIJAVA_EMAIL = 'prijava_email'`, `TIP_PRIJAVA_IP = 'prijava_ip'`); neznan tip vrže `\InvalidArgumentException`.

IP dobi iz `$app->getInput()->server->getString('REMOTE_ADDR', '')`. Glav `X-Forwarded-For` **ne** uporabljaj (ponarejive).

### 6.2 `Administrator\Service\ZetonService`
| Metoda | Opis |
|---|---|
| `ustvari(int $acymId, ?int $rundaId, string $ip): string` | Prej razveljavi vse neporabljene žetone tega `acymId` (`porabljen` = zdaj). Nato `$surovi = bin2hex(random_bytes(32))` (64 hex znakov), shrani `hash('sha256', $surovi)`, `potece` = zdaj + `veljavnost_min`. Vrne **surovi** žeton. |
| `preveri(string $surovi): ?object` | Vrne zapis, če obstaja, ni porabljen in ni potekel. **Ne porabi ga.** Za `GET view=povezava`. |
| `porabi(string $surovi): ?object` | Atomarno: `UPDATE … SET porabljen = :zdaj WHERE zeton_hash = :hash AND porabljen IS NULL AND potece > :zdaj`. Če `$db->getAffectedRows() !== 1`, vrne `null`. Šele nato prebere vrstico. |
| `pociscenje(int $dni = 7): int` | Izbriše porabljene in potekle žetone, starejše od `$dni`. Nihče je še ne kliče. |

Hash je **navaden `hash('sha256', $surovi)` brez soli in brez `password_hash()`**: žeton ima 256 bitov entropije, zato soli ne potrebuje, iskanje po indeksu pa mora biti možno. Surovi žeton se nikoli ne zapiše v bazo, v dnevnik ali v URL preusmeritve.

### 6.3 `Administrator\Service\ClanService`
Metoda `zagotoviUporabnika(object $acymZapis): int` vrne ID Joomla uporabnika ali vrže `\RuntimeException` z jezikovnim ključem napake. Koraki:

1. **Obstoječa povezava:** `SELECT * FROM #__belaunce_clani WHERE acym_uporabnik_id = :id`.
   - Uporabnik ne obstaja več → izbriši zastarelo vrstico, zapiši v dnevnik, nadaljuj na 2.
   - Uporabnik **ni** v skupini "Člani" → `\RuntimeException` (`…_NAPAKA_NI_CLANSKI_RACUN`), zapiši v dnevnik. Magic link ne sme nikoli prijaviti v račun, ki ni član (`osnova-projekta.md` 6, točka 5, varnost).
   - Uporabnik je blokiran (`block = 1`) in je spet na listi → **odblokiraj** (`block = 0`, shrani prek `User::save()`), zapiši v dnevnik.
   - Vrni ID.
2. **Iskanje po emailu, samo med člani:** `#__users` `JOIN #__user_usergroup_map` na `group_id = skupina_clani`, `LOWER(email) = :email`. Če najdeš: zapiši povezavo v `#__belaunce_clani`, zapiši v dnevnik, pojdi na preverjanja iz 1.
3. **Email zaseden zunaj skupine "Člani"** (obstaja uporabnik s tem emailom ali `username`, a ni član) → `\RuntimeException` (`…_NAPAKA_EMAIL_ZASEDEN`), zapiši v dnevnik. Ne ustvarjaj drugačnega uporabniškega imena.
4. **Nov uporabnik:**
   ```php
   $uporabnik = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);
   $uporabnik->bind([
       'name'         => $ime,          // iz AcyMailinga; če prazno, del emaila pred @
       'username'     => $email,        // male črke (D49)
       'email'        => $email,
       'password'     => '',            // jedro samo nastavi naključno 32-znakovno geslo
       'password2'    => '',
       'groups'       => [$skupinaClani],
       'block'        => 0,
       'activation'   => '',
       'sendEmail'    => 0,
       'requireReset' => 0,
   ]);
   $uporabnik->save();
   ```
   Če `save()` ne uspe, `\RuntimeException` z `getError()` v dnevniku. Nato vrstica v `#__belaunce_clani`.
5. **Vedno:** `zadnja_prijava` = UTC zdaj.

`skupina_clani` je parameter komponente (D35); če je prazen ali skupina ne obstaja → `\RuntimeException` (`…_NAPAKA_SKUPINA`), v dnevnik.

**Opomba o AcyMailingu:** ustvarjanje Joomla uporabnika sproži AcyMailingov uporabniški vtičnik, ki naročnika poveže prek `cms_id` ("Create a subscriber on site account creation"). To je pričakovano in zaželeno. Naša koda v tabele `acym_*` **ne piše nikoli** (D45) — tudi `cms_id` ne nastavljamo sami.

## 7. Vtičnik `plg_authentication_belauncerunde`

### 7.1 Datoteke
```
paket/plg_authentication_belauncerunde/
├── belauncerunde.xml
├── services/provider.php
├── src/Extension/Belauncerunde.php
└── language/{sl-SI,en-GB}/plg_authentication_belauncerunde.{ini,sys.ini}
```
Imenski prostor `Belaunce\Plugin\Authentication\Belauncerunde`. Manifest po vzoru jedra `plugins/authentication/cookie/cookie.xml`, `provider.php` po vzoru `plugins/authentication/cookie/services/provider.php` (pravilo 10). Jezikovne datoteke prek `<languages>` v manifestu vtičnika.

### 7.2 Obnašanje `onUserAuthenticate(AuthenticationEvent $event)`
Razred implementira `SubscriberInterface` in prijavi samo `onUserAuthenticate`.

1. Odjemalec ni `site` → `return` (nič ne spreminjaj).
2. `$zeton = $credentials['zeton'] ?? ''`. Če je prazen → `return`. **To je ključno:** ob navadnih prijavah (admin, com_users) se vtičnik ne sme odzvati.
3. Od tu naprej **vsaka** pot konča z `$event->stopPropagation()` — tudi neuspešna. Sicer bi `plg_authentication_cookie` ob obstoječem piškotku prijavil napačnega uporabnika, `plg_authentication_joomla` pa bi povozil odgovor (pravilo 20).
4. Oblika žetona (64 hex znakov) → sicer neuspeh.
5. `ZetonService::porabi($zeton)` → `null` pomeni neveljaven, porabljen ali potekel žeton → neuspeh z `PLG_AUTHENTICATION_BELAUNCERUNDE_ZETON_NEVELJAVEN`.
6. Ponovno preveri članstvo (žeton je lahko star do 30 minut): `AcymailingAdapter::poisciPoId()` in `jeClan()` → če ni več član, neuspeh z `…_NI_CLAN`.
7. `ClanService::zagotoviUporabnika()` → ob `\RuntimeException` neuspeh z splošnim sporočilom `…_PRIJAVA_NI_USPELA`; podrobnost samo v dnevnik.
8. Uspeh — nastavi **vsa** polja odgovora:
   ```php
   $response->status        = Authentication::STATUS_SUCCESS;
   $response->type          = 'Belauncerunde';
   $response->error_message = '';
   $response->username      = $uporabnik->username;   // mora biti enak #__users.username (pravilo 21)
   $response->email         = $uporabnik->email;
   $response->fullname      = $uporabnik->name;
   ```
9. Če ima žeton `runda_id`, ga shrani v sejo (5.4).
10. Neuspeh: `$response->status = Authentication::STATUS_FAILURE;` in `$response->error_message` iz jezikovnega niza.

V dnevnik (kategorija `com_belauncerunde`) nikoli ne piši emaila, surovega žetona ali SQL-a — samo kodo dogodka in ID-je.

### 7.3 Vklop vtičnika (D51, pravilo 18)
- V `paket/pkg_belauncerunde.xml` navedi vtičnik **pred** komponento:
  ```xml
  <files folder="packages">
      <file type="plugin" id="belauncerunde" group="authentication">plg_authentication_belauncerunde.zip</file>
      <file type="component" id="com_belauncerunde">com_belauncerunde.zip</file>
  </files>
  ```
- V `skript.php` (`postflight`) vklopi vtičnik, če njegova vrstica obstaja in je `enabled = 0`:
  `UPDATE #__extensions SET enabled = 1 WHERE type = 'plugin' AND folder = 'authentication' AND element = 'belauncerunde'`.
  Idempotentno (ob posodobitvi ne spreminja ničesar, če je že 1). Rezultat zapiši v dnevnik.
- `postflight` naj tudi **preveri in v administraciji opozori** (`enqueueMessage`, `warning`), če sta `plg_system_remember` ali `plg_authentication_cookie` izklopljena (D43) — brez njiju "Zapomni si me" ne deluje.

## 8. Mail (D50)

- Ključ predloge: `com_belauncerunde.prijava`. Oznake: `{IME}`, `{POVEZAVA}`, `{VELJAVNOST}`, `{SITENAME}`.
- Registracija v `skript.php` (`postflight`): če `MailTemplate::getTemplate('com_belauncerunde.prijava', '')` vrne `null`, kliči `MailTemplate::createTemplate()` z jezikovnimi **ključi** kot zadevo in telesom (`COM_BELAUNCERUNDE_MAIL_PRIJAVA_SUBJECT`, `…_BODY`). Jedro ključ ob pošiljanju prevede (`libraries/src/Mail/MailTemplate.php`, vrstici 334 in 341). Če predloga že obstaja, je **ne prepisuj** — admin jo je morda uredil.
- Odstranitev: `MailTemplate::deleteTemplate()` samo, kadar je `ohrani_podatke = 0`.
- Pošiljanje v modelu/kontrolerju:
  ```php
  $predloga = new MailTemplate('com_belauncerunde.prijava', $app->getLanguage()->getTag());
  $predloga->addTemplateData([...]);
  $predloga->addRecipient($email, $ime);
  $predloga->send();
  ```
  `send()` lahko vrže `MailDisabledException` ali `phpmailerException`: ujemi ju, zapiši v dnevnik in uporabniku pokaži **isto** sporočilo kot ob uspehu (ne razkrij, ali email obstaja).
- `{POVEZAVA}` je absoluten URL (`Uri::root()` + `index.php?option=com_belauncerunde&view=povezava&zeton=…`), zgrajen brez `Route::_()`, da ne zavisi od menija. Surovi žeton je samo v tem URL-ju, nikjer drugje.
- Jezikovna niza za zadevo in telo gresta v **site** `com_belauncerunde.ini` (glej 3.2).

## 9. Gradnja

`orodja/gradnja.sh` mora zapakirati tudi vtičnik:
```bash
(cd paket/plg_authentication_belauncerunde && zip -qr "$PKG_TMP/packages/plg_authentication_belauncerunde.zip" .)
```
Reši to **splošno** (zanka po vseh mapah `paket/plg_*`), da naloga 12 ne zahteva nove spremembe skripte. Preverjanje verzij, `php -l`, `xmllint`, prepovedani vzorci in utf8mb4 morajo zajeti tudi novo mapo (že zajemajo, ker gledajo `paket/`). Verzija v manifestu vtičnika je prav tako `0.4.0`.

## 10. Varnostne zahteve

- CSRF na obeh POST akcijah; spremembe samo prek POST.
- Žeton: 32 bajtov iz `random_bytes()`, v bazi samo SHA-256, enkratna poraba prek `getAffectedRows()`, veljavnost iz možnosti.
- Enotni odgovor na `prijava.poslji` v vseh primerih (član, nečlan, neveljaven email, presežena omejitev, napaka pošiljanja).
- Omejitve po emailu in IP, ključi hashirani.
- Nikoli prijava v račun, ki ni v skupini "Člani"; nikoli ustvarjanje uporabnika z drugačnim uporabniškim imenom od emaila.
- Brez emailov, žetonov in SQL-a v dnevniku in v URL-jih.
- Vse poizvedbe z `bind()` in `ParameterType` (pravilo 16: vrednost po referenci, ločena spremenljivka za vsako vezavo).
- Izhod v predlogah skozi `$this->escape()`.
- Povratni URL-ji (če jih uvedeš) preverjeni z `Uri::isInternal()`.

## 11. Merila sprejema

Matija preveri na DDEV. **Pred testom:** Joomla mail na Mailpit (SMTP `localhost:1025`, brez avtentikacije in šifriranja), AcyMailing na DDEV ne pošilja navzven, lasten testni email dodan na listo članov (ID 6), vtičnika *Authentication – Cookie* in *System – Remember Me* vklopljena. ZIP pred namestitvijo kopiraj v `tmp/`.

1. `bash orodja/gradnja.sh` uspe; v ZIP-u sta `packages/com_belauncerunde.zip` **in** `packages/plg_authentication_belauncerunde.zip`; vse verzije 0.4.0.
2. Posodobitev z 0.3.0 uspe. Verzija sheme je `0.4.0`, obstoječe runde in šifranti so nespremenjeni, nove tri tabele obstajajo in so `utf8mb4`.
3. *System → Plugins*: `Authentication - Belaunce runde` je **vklopljen** takoj po namestitvi (brez ročnega vklopa).
4. *System → Mail Templates*: predloga `com_belauncerunde.prijava` obstaja; zadeva in telo sta v slovenščini.
5. Odjavljen obiskovalec na `index.php?option=com_belauncerunde&view=prijava` vidi obrazec. Vpiše **email nečlana** → sporočilo "če je naslov pri nas…"; v Mailpitu **ni** maila; v `dipxn_belaunce_zetoni` **ni** nove vrstice.
6. Vpiše **svoj (članski) email** → isto sporočilo; v Mailpitu je mail s povezavo; v `dipxn_belaunce_zetoni` je ena vrstica, `zeton_hash` je 64 hex znakov, `porabljen` je `NULL`.
7. **GET** na povezavo iz maila pokaže gumb *Prijavi me*; `porabljen` je še vedno `NULL` (mailni skener ne porabi žetona). Osveži stran dvakrat — še vedno `NULL`.
8. Klik na gumb: prijava uspe, v glavi strani je prijavljen uporabnik. V `dipxn_users` je nov uporabnik z `username` = email, `block = 0`, v skupini "Člani" (ID 10) in **samo** v njej; v `dipxn_belaunce_clani` je vrstica; `porabljen` je zdaj nastavljen.
9. Ponoven klik na isto povezavo (ali POST) → prijava **ne** uspe (žeton je porabljen).
10. **AcyMailing ostane nedotaknjen:** `SELECT COUNT(*) FROM dipxn_acym_user; SELECT COUNT(*), status FROM dipxn_acym_user_has_list WHERE list_id = 6 GROUP BY status;` pred in po testu — enako kot prej. Pri tvojem naročniku se je (po AcyMailingovi nastavitvi) smela spremeniti samo `cms_id` na ID novega uporabnika.
11. **"Zapomni si me":** zapri brskalnik (ali pobriši sejni piškotek, piškotek `joomla_remember_me_*` pa pusti) in znova odpri stran → še vedno si prijavljen. Odjava prek `index.php?option=com_users&task=user.logout&…` deluje.
12. **Druga prijava:** znova zahtevaj povezavo in se prijavi → **ne** nastane nov Joomla uporabnik (isti ID), `zadnja_prijava` se posodobi.
13. **Članstvo prek skupine** (prepisano po odločitvi A, glej D52/D53):
    a. Uporabniku odvzemi skupino "Člani" (`DELETE FROM #__belaunce_user_usergroup_map` oz. v *Users*), nato zahtevaj povezavo in se prijavi → prijava uspe, **skupina se vrne**, v dnevniku `SKUPINA_VRNJENA`.
    b. Blokado nastavi **neposredno v bazi** (`UPDATE #__users SET block=1`, da AcyMailing ne postavi `active = 0`), nato zahtevaj povezavo in jo porabi → prijava **ne** uspe, `block` ostane **1**, v dnevniku `COM_BELAUNCERUNDE_NAPAKA_BLOKIRAN`.
14. **Tuj račun:** ustvari ločen Joomla račun v skupini Registered (ne Člani) z novim emailom, **nato** tega naročnika v AcyMailingu dodaj na listo članov. Zahtevaj povezavo zanj in jo porabi → prijava **ne** uspe, račun ostane samo v svoji skupini in neblokiran, nova vrstica v `#__belaunce_clani` ne nastane, v dnevniku pa `COM_BELAUNCERUNDE_NAPAKA_EMAIL_ZASEDEN`. Vrstni red je pomemben: najprej Joomla račun, šele nato lista.
15. **Omejitev:** štirikrat zapored zahtevaj povezavo za isti email (privzeto 3/uro) → četrtič sporočilo ostane enako, novega maila ni, v `dipxn_belaunce_poskusi` so vrstice s hashiranim ključem (nikjer surovega emaila).
16. **Potek:** v bazi ročno nastavi `potece` v preteklost in porabi povezavo → prijava ne uspe z razumljivim sporočilom.
17. **ID runde:** odpri `view=prijava&runda_id=1`, zahtevaj povezavo → v `dipxn_belaunce_zetoni.runda_id` je `1`, stran povezave omenja naslov runde; prijava deluje (vpisa na rundo še ni).
18. **Navadna prijava ni pokvarjena:** prijava v administracijo z geslom in prijava člana z geslom (če obstaja) delujeta kot prej.
19. Brez PHP opozoril; administracija in frontend delujeta z **izklopljenim** vtičnikom za združljivost za nazaj.
20. Odstranitev z `ohrani_podatke = 0` pobriše tri nove tabele in predlogo maila; z `ohrani_podatke = 1` jih pusti. **Preloženo na nalogo 14**, ker test pobriše vse tabele, ki jih potrebujeta nalogi 05 in 06.

## 12. Dokumentacija
- `ARCHITECTURE.md`: razdelek **Prijava z magic linkom** — tok od obrazca do seje, vloge `ZetonService`, `ClanService`, `OmejitevHelper` in vtičnika, zakaj avtentikacijo opravi vtičnik (D48), zahteva po obeh piškotkovnih vtičnikih (D43). Dopolni tabelo razširitev in seznam tabel po nalogah.
- `NAVODILA.md`: "Prijava za člane" (kaj vidi član), "Nastavitve prijave" (omejitve, veljavnost), "Besedilo maila" (kje se ureja), "Ko član ni več član" (blokiranje, D47).
- `CHANGELOG.md`: `[0.4.0]`.

## Vprašanja
Česar ne moreš izvesti po tej nalogi, ne implementiraj po svoje: zapiši v PR pod "Vprašanja za arhitekta" in nadaljuj z ostalim.
