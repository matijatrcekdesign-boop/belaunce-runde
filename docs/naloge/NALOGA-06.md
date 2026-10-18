# NALOGA 06: Stran runde — udeležba, javno proti članom, Open Graph

**Veja:** `feature/NALOGA-06-udelezba` (iz `develop`) · **PR v:** `develop` · **Verzija:** 0.6.0

## Cilj
Stran runde postane uporabna: član klikne **Pridem** in je na seznamu udeležencev, lahko se tudi odjavi. Neprijavljen obiskovalec vidi samo število udeležencev in ga klik na „Pridem" pelje skozi prijavo ter ga po njej **samodejno vpiše na rundo** (D40). Končane runde se ne indeksirajo, povezave pa dobijo Open Graph oznake.

## Povezane odločitve
D6, D19, D37, D40, D42, **D54 (nova)**; `osnova-projekta.md` razdelki 5, 7, 8, 10 in 6 (točka 6); `ARCHITECTURE.md` "Pravila za Joomlo 6" **1–23**; `AGENTS.md` v celoti.

## Obseg

**V nalogi:**
1. Tabela `#__belaunce_prijave`.
2. `Site\Model\PrijaveModel` oziroma razširitev obstoječih modelov za branje in zapis udeležbe.
3. Akciji **`runda.pridem`** in **`runda.odjava`** (POST, CSRF).
4. Stran runde: javni in članski del, gumbi glede na stanje.
5. Dokončanje D40: po prijavi z magic linkom se član vpiše na rundo iz seje.
6. Končane runde: `noindex`, sporočilo „Ta runda je končana" in povezava na prihajajoče.
7. Open Graph oznake na strani runde.
8. Verzija 0.6.0; `sql/updates/mysql/0.6.0.sql`; `CHANGELOG.md`, `ARCHITECTURE.md`, `NAVODILA.md`.

**Izrecno NI v nalogi:**
- gostje in njihov obrazec (naloga 08) — a število udeležencev mora biti pripravljeno, da jih bo naloga 08 lahko prištela
- frontend obrazec za ustvarjanje in urejanje rund (naloga 07)
- transakcijski maili ob spremembi in odpovedi (naloga 10)
- gumb „Deli v Viber" (naloga 11) — Open Graph oznake pa so del te naloge
- komentarji, strukturirani podatki `Event` (backlog)

## 1. Tabela

V `administrator/sql/install.mysql.utf8.sql` **in** v novo `sql/updates/mysql/0.6.0.sql`:

```sql
CREATE TABLE IF NOT EXISTS `#__belaunce_prijave` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `runda_id` INT UNSIGNED NOT NULL,
  `uporabnik_id` INT NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_runda_uporabnik` (`runda_id`, `uporabnik_id`),
  KEY `idx_runda` (`runda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Dopolni tudi `uninstall.mysql.utf8.sql`. Čas v UTC. **Obstoj vrstice pomeni „pridem"; odjava vrstico izbriše.**

**D54:** vodja runde **nikoli** ni v tej tabeli. Šteje se posebej.

## 2. Kdo sme kaj

| | Neprijavljen | Prijavljen, ni član | Član | Vodja te runde |
|---|---|---|---|---|
| Vidi stran in število udeležencev | da | da | da | da |
| Vidi **imena** udeležencev | ne | ne | da | da |
| Gumb Pridem / Odjavi se | pelje na prijavo | ne (poziv za včlanitev) | da | ne (je samodejno udeležen) |

- **Član** = prijavljen uporabnik, ki je v skupini iz parametra `skupina_clani`. Preveri v kontrolerju **in** v pogledu, ne samo skrivanje gumba.
- Neprijavljen klik na „Pridem" vodi na `index.php?option=com_belauncerunde&view=prijava&runda_id={id}` (D40). Povratnih URL-jev ne uvajaj.
- Prijavljenemu nečlanu se izpiše poziv „Še nisi član? Pridruži se društvu" (md 8); besedilo iz jezikovnega niza, brez povezave na zunanje strani.

## 3. Kdaj udeležba ni mogoča
Gumba **ni** (in akcija je zavrnjena, tudi če kdo pošlje POST):
- runda je **odpovedana** (`stanje = 2`) — izpiše se značka in pojasnilo
- runda je **štartala ali končana** (`zacetek <= UTC_TIMESTAMP()`)

Meja za udeležbo je **štart runde**, ne njen konec — po štartu se ni več smiselno prijavljati. (Na seznamu iz naloge 05 pa runda ostane do konca; to je namerna razlika.)

## 4. Akciji

### `runda.pridem`
1. `$this->checkToken()`; samo POST.
2. ID runde iz POST (`getInt`), runda mora obstajati in biti `stanje IN (1,2)`.
3. Uporabnik mora biti prijavljen in član → sicer zavrni z jezikovnim nizom.
4. Runda ne sme biti odpovedana ali štartala (razdelek 3).
5. Uporabnik ne sme biti vodja te runde (D54) → sporočilo, da je kot vodja že udeležen.
6. `INSERT IGNORE` oziroma `INSERT` z lovljenjem podvojenega ključa — dvojni klik ne sme vreči napake; rezultat je enak kot ena prijava.
7. Preusmeritev nazaj na stran runde s kodo stanja (glej 6).

### `runda.odjava`
Enako preverjanje 1–4, nato `DELETE` po `runda_id` + `uporabnik_id`. Odjava neobstoječe prijave ni napaka — rezultat je isti.

Obe akciji zapišeta v dnevnik samo kodo in ID-je (`PRIJAVA_DODANA runda_id=.. uporabnik_id=..`), nikoli emailov.

## 5. Prikaz udeležencev

- **Število** = vrstice v `#__belaunce_prijave` za to rundo **+ 1 za vodjo** (D54). Metodo za štetje napiši tako, da bo naloga 08 lahko prištela goste na enem mestu (npr. `steviloUdelezencev(int $rundaId): int`).
- **Imena** (samo član): vodja **prvi**, z oznako „vodja", nato ostali po `ustvarjeno` naraščajoče. Imena iz `#__users.name` prek `LEFT JOIN`.
- Neprijavljenemu in nečlanu se izpiše samo število, nikoli imena.
- Če vodja ne obstaja več (izbrisan uporabnik), ga ne izpiši in ga ne štej.

## 6. Povratna informacija
Isti vzorec kot pri prijavi (naloga 04), ker predloga strani ne izpisuje sistemskih sporočil (D41): preusmeritev s kodo stanja v URL-ju in izris v predlogi iz **zaprtega seznama**. Dodaj kode: `prijavljen`, `odjavljen`, `ni_clan`, `runda_zaprta`, `napaka`. Neznana koda ne izriše ničesar. `enqueueMessage` pusti, kjer je.

## 7. Dokončanje D40
Vtičnik iz naloge 04 po uspešni prijavi shrani `com_belauncerunde.runda_po_prijavi` v sejo. Zdaj ga nekdo mora prebrati:

- Takoj po prijavi (v kontrolerju `povezava.prijavi`, po uspešnem `login()`) preberi vrednost, **takoj jo pobriši iz seje** in jo uporabi.
- Če runda obstaja, ni odpovedana in še ni štartala → vpiši udeležbo in preusmeri na stran runde s kodo `prijavljen`.
- Če runda medtem ne ustreza pogojem → samo prijava, preusmeritev na stran runde s kodo `runda_zaprta` (D40: „izvede se samo prijava z obvestilom").
- Če ključa v seji ni → kot doslej, preusmeritev na `view=prijava&stanje=uspeh`.

Vpis udeležbe uporabi **isto metodo** kot `runda.pridem`, da pravila ne obstajajo na dveh mestih.

## 8. Končane runde, `noindex` in Open Graph
- Runda, katere **konec** je v preteklosti (`zacetek + COALESCE(trajanje_min, privzeto)`), se prikaže z obvestilom „Ta runda je končana" in povezavo na seznam prihajajočih; stran **ne** vrne 404 (md 8).
- Za take runde dodaj `<meta name="robots" content="noindex, follow">`. Prihajajoče runde ostanejo indeksirane.
- Open Graph na vsaki strani runde: `og:title` (naslov runde), `og:description` (datum, ura, tip, težavnost, dolžina — sestavljeno iz jezikovnega niza, brez opomb), `og:url` (kanonični URL), `og:type` = `article`, `og:site_name`. Vse vrednosti escapirane; slike v v1 ni.

## 9. Varnostne zahteve
- CSRF in POST na obeh akcijah; preverjanje članstva v kontrolerju, ne samo v pogledu.
- Vse poizvedbe z `bind()` in `ParameterType` (pravilo 16, velja tudi za `User::bind()`).
- Imena udeležencev se izpišejo samo članom; v HTML nikoli emailov, telefonov ali IP-jev — tudi ne v `og:` oznakah ali v `data-` atributih.
- Vsak izpis skozi `$this->escape()`.
- V URL-jih samo ID runde in koda stanja z zaprtega seznama.

## 10. Merila sprejema
Matija preveri na DDEV (ZIP pred namestitvijo kopiraj v `tmp/`):

1. `bash orodja/gradnja.sh` uspe; vse verzije 0.6.0. Posodobitev z 0.5.0 uspe, verzija sheme `0.6.0`, tabela `#__belaunce_prijave` obstaja in je `utf8mb4`; obstoječi podatki nespremenjeni.
2. **Neprijavljen** na strani runde vidi število udeležencev, **ne** imen; gumb „Pridem" ga pelje na obrazec za prijavo z `runda_id` v URL-ju.
3. **Član** vidi imena udeležencev in gumb **Pridem**. Klik → ime se pojavi na seznamu, število se poveča za 1, v `dipxn_belaunce_prijave` je ena vrstica.
4. Gumb se zamenja v **Odjavi se**. Klik → vrstica izgine, število se zmanjša.
5. Dvojni klik na Pridem (ali ponovna oddaja z gumbom Nazaj) ne povzroči napake in ne ustvari druge vrstice.
6. **Vodja** runde je na seznamu udeležencev prvi, z oznako „vodja", in **nima** gumba Pridem. V `dipxn_belaunce_prijave` zanj ni vrstice (D54).
7. **Odpovedana runda:** gumba ni, POST na `runda.pridem` (npr. s ponovno oddajo starega obrazca) je zavrnjen.
8. **Štartana runda:** enako — gumba ni, akcija zavrnjena. Runda ostane na seznamu do svojega konca (naloga 05).
9. **Končana runda:** stran se odpre z obvestilom „Ta runda je končana" in povezavo na prihajajoče; v izvorni kodi je `robots: noindex, follow`.
10. **Prihajajoča runda:** v izvorni kodi `noindex` **ni**.
11. **Open Graph:** v izvorni kodi so `og:title`, `og:description`, `og:url`, `og:type`, `og:site_name`; `og:url` je enak kanoničnemu.
12. **D40 od konca do konca:** odjavljen v anonimnem oknu odpri prihajajočo rundo → klikni „Pridem" → vpiši članski email → odpri povezavo iz Mailpita → klikni „Prijavi me" → po prijavi si **na strani runde in vpisan nanjo**, v `dipxn_belaunce_prijave` je vrstica.
13. **D40 z zaprto rundo:** ponovi 12, a rundo pred klikom na „Prijavi me" v administraciji odpovej → prijava uspe, udeležba **ne** nastane, izpiše se obvestilo.
14. **Nečlan:** prijavljen uporabnik, ki ni v skupini Člani (testni `testtujec`, ID 635), ne vidi imen ne gumba; ročno poslan POST na `runda.pridem` je zavrnjen in v `dipxn_belaunce_prijave` ni vrstice.
15. Seja: po odjavi iz strani član ne vidi več imen udeležencev.
16. Brez PHP opozoril; vse deluje z **izklopljenim** vtičnikom za združljivost za nazaj.
17. Administracija (Runde, Tipi, Težavnosti, Preveri člana) in prijava z magic linkom delujeta nespremenjeno.

## 11. Dokumentacija
- `ARCHITECTURE.md`: razdelek **Udeležba na rundi** — tabela, pravilo D54, kje se preverja članstvo, kako se zaključi tok D40, od kod število udeležencev (in da bo naloga 08 dodala goste na istem mestu).
- `NAVODILA.md`: „Prijava na rundo" (kaj vidi član, kaj neprijavljen), „Odpovedana in končana runda".
- `CHANGELOG.md`: `[0.6.0]`.

## Vprašanja
Česar ne moreš izvesti po tej nalogi, ne implementiraj po svoje: zapiši v PR pod "Vprašanja za arhitekta" in nadaljuj z ostalim.
