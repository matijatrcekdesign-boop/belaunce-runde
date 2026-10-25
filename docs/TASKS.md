# TASKS.md — pkg_belauncerunde

**Prioritiziran seznam.** Vsaka naloga je ena veja in en PR v `develop`. Naloga se označi kot končana, ko je PR pregledan, testiran na DDEV in združen.

Oznake: ⬜ ni začeto · 🟡 v delu · 🔍 v pregledu · ✅ združeno

---

## Faza 1 (MVP)

| # | Naloga | Verzija | Odvisna od | Stanje |
|---|---|---|---|---|
| 01 | Okostje paketa, gradnja ZIP, update server, skupina "Člani", šifranti tipi in težavnosti (backend CRUD) | 0.1.0 | — | ✅ |
| 02 | Backend runde: tabela, seznam s filtri, urejanje, brisanje, zamenjava vodje, `CasHelper` (UTC ↔ `casovni_pas`) | 0.2.0 | 01 | ✅ |
| 03 | Adapter AcyMailing (samo branje, D45), izbira list v Možnostih (D46), diagnostika "Preveri člana" | 0.3.0 | 01 | ✅ |
| 04 | Prijava z magic linkom: obrazec za email, žetoni, omejitve poskusov, vtičnik za avtentikacijo, ustvarjanje uporabnika v skupini "Člani", "Zapomni si me" | 0.4.0 | 03 | ✅ |
| 05 | Javni seznam prihajajočih rund s filtri tip/težavnost, SEF router, tip menijske postavke | 0.5.0 | 02 | ✅ |
| 06 | Stran runde: javno/člani (8), "Pridem"/odjava, D40, končane runde (`noindex`), Open Graph | 0.6.0 | 04, 05 | ✅ |
| 07 | Frontend obrazec runde: ustvari, uredi, odpovej (pravila 4.1), trasa + vdelave, dnevna omejitev (D38) | 0.7.0 | 06 | ✅ |
| 08 | Gostje: obrazec, zaščita (D25), značka "gost", potrditveni mail z odjavo | 0.8.0 | 06 | ✅ |
| 09 | "Moje runde" (vodim / prijavljen sem / pretekle) | 0.9.0 | 07 | ✅ |
| 10 | Transakcijski maili ob spremembi in odpovedi (nastavljiva besedila in polja) | 0.10.0 | 07, 08 | ✅ |
| 11 | Deli v Viber: gumb, besedilo za kopiranje | 0.11.0 | 06 | ✅ |
| 12 | Scheduler opravila: sinhronizacija članov (**odvzem/vrnitev skupine "Člani", nikoli blokada** — D47, D52, D53), izbris gostov (30 dni), čiščenje žetonov in poskusov | 0.12.0 | 04, 08 | ✅ |
| 13 | Backend statistika in izvoz v CSV | 0.13.0 | 02 | ✅ |
| 14 | Zaključni pregled: varnost, ACL, GDPR (orodja za zasebnost), dostopnost, PHPDoc, `NAVODILA.md`; **test odstranitve paketa** (merilo 20 naloge 04, preloženo); zamenjava zastarelega `getError()` | 0.14.0 | vse | ✅ |
| 16 | **Vizualni pregled**: enoten videz vseh strani komponente (seznam, runda, obrazci, prijava, gostje), lasten CSS s predpono `br-`, mobilni pregled, dostopnost (kontrast, fokus, oznake). Po nalogi 11, pred izidom. | 0.15.6 | 11 | ✅ |
| 17 | **Otroška predloga za runde**: child template iz j4starter, dodeljena menijskima postavkama Runde in Prijava; vanjo doda `<jdoc:include type="message" />` (reši D41 brez posega v starša) in po potrebi lasten slog. Po izidu v1. | — | 16 | ⬜ |
| 15 | **Povzetek načina dela** (za katerikoli agent, za prihodnje projekte): brainstorming z Matijo (želje, osnutek, ideja) → pregled zahtev in vprašanja → vir resnice → postavitev repozitorija → naloge arhitekt/izvajalec → pregled → test v pravem okolju → zapis lekcij. Pripravi arhitekt z Matijo. | — | 14 | ⬜ |
| 18 | **Cross-DB AcyMailing adapter**: branje AcyMailing iz druge baze na isti MySQL instanci (D61), za ločeno namestitev na poddomeni (D60). Specifikacija: `docs/naloge/NALOGA-18.md`. | 0.17.0 | 03 | ⬜ |
| 19 | **PWA brez push**: manifest, service worker, namestitev kot samostojen pogled (D62). Specifikacija: `docs/naloge/NALOGA-19.md` (pred predajo dopolniti: SW samo za `navigate` zahteve site dela, `start_url` po poddomeni D60). | 0.18.0 | 16, 18 | ⬜ |
| 20 | Email vodji ob novem udeležencu (član ali gost), ponovna uporaba `MailService` (naloga 10). Zasebnost: ime udeleženca sme v obvestilo (D63). | 0.16.0 | 10 | ✅ |

Vrstni red se lahko po dogovoru spremeni (npr. 05 pred 03), odvisnosti pa morajo ostati izpolnjene.

## Kontrolni seznam pred zagonom na produkciji (ni naloga za Codex)

- [ ] **Pred nalogo 04 (DDEV):** Joomla mail na DDEV preusmerjen na Mailpit (SMTP `localhost:1025`, brez avtentikacije in šifriranja), AcyMailing na DDEV ne pošilja navzven; testni email dodan na listo članov (ID 6). Na DDEV so pravi člani!
- [ ] AcyMailing na produkciji: "Automatically subscribe to" prazno; razmisliti o izklopu "Delete the subscriber on site account deletion" (D47)
- [ ] **Članov nikoli ne blokiraj v Joomli** — dostop odvzemi z odvzemom skupine "Člani" (D52, D53). Blokada bi jim ustavila vso klubsko pošto.
- [ ] Pred nalogo 12 preveriti, ali AcyMailing kaj spremeni tudi ob spremembi **skupine** uporabnika (ne samo ob blokadi)
- [ ] Možnosti na produkciji: izbrani pravi listi članov in "Runde" (ID-ja sta drugačna kot na DDEV)

- [ ] **Poddomenska namestitev (D60, D61):** ločena Joomla na poddomeni (npr. `runde.belaunce.cc`); DB uporabnik poddomene ima `SELECT` na AcyMailing bazi glavne namestitve; v Možnostih vpisani *Zunanja baza AcyMailing* in *Predpona*, nato ponovno izbrani listi (naloga 18); preusmeritev starih povezav `belaunce.cc/runde/...` na poddomeno (Viber zgodovina)
- [ ] **Dnevnik pred zagonom:** pobriši `administrator/logs/com_belauncerunde.php`, ker stare vrstice (pred 0.8.0) vsebujejo IP naslove
- [ ] **Predloga j4starter:** `<jdoc:include type="message" />` tudi v veji brez modula `sidebar` (sicer uporabniki ne vidijo sporočil o prijavi) — D41
- [ ] AcyMailing: javni obrazec vpisuje samo na listo "Runde"; ID liste članov in liste "Runde" vpisana v možnosti
- [ ] Vtičnika *Authentication – Cookie* in *System – Remember Me* vklopljena (D43); `cookie_lifetime` v vtičniku Cookie nastavljen po dogovoru
- [ ] SMTP: testni mail z info@belaunce.cc; SPF/DKIM preverjena (npr. mail-tester.com)
- [ ] Scheduler: pravi strežniški cron za Joomlina opravila
- [ ] Test namestitve paketa na **kopiji produkcije** (MariaDB 10.11, utf8mb3 privzeto) — preveri, da so tabele utf8mb4
- [ ] Akeeba backup pred namestitvijo, načrt povratka
- [ ] Menijska postavka **Seznam rund** z aliasom `runde` (brez nje so URL-ji `/component/…`)
- [ ] Menijska postavka **Prijava za člane** z aliasom `prijava` (čist naslov za povezavo „Prijava"; dolga pot `index.php?option=…&view=prijava` mora ostati delujoča zaradi starih mailov)
- [ ] **Naloga 11, merilo 14:** na kopiji produkcije (prava domena) preveri, da „Deli v Viber" na telefonu odpre Viber s pripravljenim besedilom in da se pokaže Open Graph predogled (na DDEV ni izvedljivo — `ddev share` prek `trycloudflare.com` vrne 421)

## Backlog (izven v1)

- Predlogi zadnjih lokacij (`datalist`) pri vnosu lokacije (D36)
- ~~Poddomena kot preusmeritev / domain mapping nad isto namestitvijo~~ — nadomeščeno z D60 (ločena Joomla namestitev na poddomeni, v1) in nalogo 18.
- Tedenski povzetek, kode za prijavo, .ics, komentarji, hitra runda (faza 2)
- **Push obvestila** (faza 2, po nalogi 19): ločena naloga, šele po izidu in testiranju PWA brez push (D62). VAPID ključi, tabela naročnin, SW push handler, UI za naročnino, `minishlink/web-push`. Sprožilci najverjetneje nova prijava (kot naloga 20) in nova runda — obseg določiti, ko pride na vrsto.

## Končano

- **20** — PR #16, združen 2026-10-25 (`6f7477d`, verzija 0.16.0). Vodja runde prejme nastavljivo email obvestilo (ime udeleženca, trenutno število udeležencev, povezava) ob novi prijavi člana (`PrijaveModel::spremeniUdelezbo`, samo `$prihod===true` in `$spremenjeno>0`) ali gosta (`GostModel::prijava`, samo ob uspešnem vstavku). Nova `MailService::obvestiVodjoOPrijavi()`: izklopljiva (`mail_nova_prijava`, privzeto Da), brez emaila/telefona udeleženca v telesu, brez imena/emaila v dnevniku, vodja se ne obvesti o lastni prijavi, ovito v `try/catch` (napaka pošte ne razveljavi prijave). Nova predloga `com_belauncerunde.nova_prijava` (sl-SI + en-GB), idempotentna registracija/brisanje v `skript.php`. Testirano na DDEV z Mailpitom (arhitekt, prek povezanega računalnika): nova prijava → en mail vodji; ponovitev in odjava → brez maila; gost → vodja obveščen, gost prejme svoje potrdilo; stikalo izklopljeno → brez obvestila. Dodan D63.
- **16** — PR #15, združen 2026-10-24 (verzija 0.15.6; iterativno 0.15.0→0.15.6 po vizualnih dodelavah z Matijo). Videz smeri C, usklajen s komponento kisegonma: pisavi Lexend/Archivo, čipi, kvadratne kartice z barvnim pasom, merilnik težavnosti (modra), ploščice na podstrani, poenoteni gumbi in filtri. **Barva tipa iz backenda** (nov stolpec `barva` v `#__belaunce_tipi`, izbirnik v urejanju tipa, seed MTB/Cestno/Gravel; idempotentna zagotovitev stolpca v `skript.php`). Frontend izpiše `--br-type` iz validiranega hex-a + **samodejni kontrast #000/#fff** (AA pri vseh barvah). Dodano: gumb „Odpri rundo" na kartici, „Nazaj na runde" zgoraj, povezava po prijavi na seznam rund, možnost `clanstvo_url` z varno validacijo in gumbom „Postani član". Dolžina po slovensko. Mobilni offcanvas filtri (z namiznim resetom, ker j4starter nima `.offcanvas-md`). Dostopnost (fokus, 44px, barva ni edini nosilec), potrjeno v brskalniku. Dolg iterativni PR; vse frontend + barva tipa, brez posega v logiko/predlogo.
- **14** — PR #14, združen 2026-10-22. Vsa merila ✅ na DDEV. Zastareli `getError()/setError()` odstranjen iz naše kode: `RundaTable::check()` in admin `RundaModel::save()`/`nastaviStanje()` mečejo `\RuntimeException` s prevedenimi razlogi, frontend `ObrazecModel` vrača zaprto kodo `napaka`, kontrolerji ujamejo in prikažejo varna sporočila (surova validacija ne gre več v sejo; admin urejanje ob napaki vrne na obrazec s shranjenim vnosom). Zasebnost: `ZetonService` shrani HMAC namesto surovega IP (stolpec se nikjer ne bere). Odstranitev paketa potrjena: `ohrani_podatke=1` tabele ohrani, `ohrani_podatke=0` pobriše 8 tabel in mail predloge, skupina „Člani" ostane. Dodatek (Q2, GDPR): vzdrževalno opravilo z `NOT EXISTS` pobriše osirotele goste in prijave po izbrisu runde (`SIROTE_GOSTJE`, `SIROTE_PRIJAVE`). GDPR lahka pot dokumentirana v NAVODILA.md; dostopnost prestavljena na nalogo 16. Širok PR (PHPDoc povsod + CSRF utrjevanje), regresija nalog 1–13 potrjena.
- **13** — PR #13, združen 2026-10-22. Vsa merila ✅ na DDEV. Nov backend pogled Statistika: skupni kazalniki + razčlenitev po tipu in težavnosti, z istimi filtri kot seznam rund (tip, težavnost, stanje, vodja, obdobje, iskanje). Filtri izločeni v `RundeFiltriTrait` (en vir za `RundeModel` in `StatistikaModel`); število udeležencev bere isti D54 izraz iz Site modela `Prijave` prek MVCFactory. Izvoz CSV z gumbom v pogledu (core.manage + CSRF žeton): podpičje, UTF-8 z BOM, decimalna vejica za dolžino, RFC-4180 ubežanje prek `fputcsv` + CRLF, obramba pred vrivanjem formul; brez imen prijavljenih in kontaktov gostov (samo število in javno ime vodje). Povprečno trajanje izpušča runde brez trajanja (AVG ignorira NULL).
- **12** — PR #12, združen 2026-10-22. Vsa merila ✅ na DDEV. Eno dnevno opravilo `plg_task_belauncerunde` (`belaunce_vzdrzevanje`) izvede tri neodvisne dele v svojih `try/catch`: sinhronizacija članov, izbris gostov (zacetek + 30 dni, ne glede na stanje), čiščenje žetonov in poskusov. Potrjeno: odvzem in vrnitev skupine „Člani" brez spremembe `block` (D53), `acym_user.active` ostane 1 ob spremembi skupine (zaprto staro vprašanje D52), blokiran član preskočen (`PRESKOCENO_BLOKIRAN`), nepovezan uporabnik se ne obdeluje, varnostni pogoj ob prazni/nenastavljeni listi (`SINHRONIZACIJA_PRESKOCENA`, `idjiClanov(..., strogo=true)` razlikuje napako branja od prazne liste), dnevnik samo kode/ID (pravili 22, 24). D53 centraliziran v `ClanService::uskladiSkupino()`; odvzem edine skupine reši vezan izbris mapiranja (jedro 6.1 `Table\User::store()` ob praznem `groups` preskoči zapis). Vtičnik vklopi namestitveni skript (pravilo 18, naveden pred komponento).
- **11** — PR #11, združen 2026-10-21. Vsa preverljiva merila ✅ na DDEV brez popravkov kode (PHP gradnja; deljivost glede na stanje/štart; besedilo brez osebnih podatkov; escapiranje naslova; kopiranje in sistemsko deljenje; prazna/lastna predloga; Moje runde na obeh listah). Merilo 14 (pravi telefon z Viberjem) preloženo na test na kopiji produkcije (DDEV tunel vrne 421). Dodani D58, D59. `DeljenjeHelper` sestavi besedilo iz zaprtega seznama oznak, `viber://` prek `rawurlencode` in nikoli skozi `Route`.
- **10** — PR #10, združen 2026-10-21. Vseh 17 meril ✅ brez popravkov. Zaznava sprememb v `RundaTable::store()` (interne javne lastnosti s `_`, niso DB stolpci), pošiljanje po commitu prek `MailService`; frontend in admin gresta skozi isto točko (D56), vodja vedno dobi kopijo (D57). Vsak naslov dobi svoj mail (brez Cc/Bcc), gostu se žeton ob mailu zamenja, dnevnik beleži samo kodo+ID+število. Potrjeno z Mailpitom: ločeni maili, pravilni čas/lokacija, odpoved s prvotnim terminom, neprožilno polje brez maila, pokvarjen SMTP ne razveljavi zapisa.
- **09** — PR #9, združen 2026-10-20. Vseh 19 meril ✅ brez popravkov. Izraz za število udeležencev prestavljen v `PrijaveModel::izrazStevilaUdelezencev()` (eno mesto, D54). Router aliase predpomni iz že prebranega seznama (brez N+1), kar je potrdil dnevnik poizvedb: 2 poizvedbi seznama na zavihek. Cilj preusmeritve po odjavi je zaprt seznam (zunanji `vrni` zavrnjen).
- **08** — PR #8, združen 2026-10-20. Vseh 21 meril ✅. Med testiranjem najdeno, da Joomlin privzeti zapisovalnik v lasten dnevnik zapisuje IP obiskovalca (`{CLIENTIP}`); popravljeno z izrecno obliko zapisa → pravilo 24 in merilo 21. Prijava gosta teče v transakciji z `FOR UPDATE`, odjava uporablja izziv v seji namesto surovega žetona v POST.
- **07** — PR #7, združen 2026-10-19. Vseh 22 meril ✅. Med pregledom dodana dnevna omejitev rund (D38, razdelek 6.1), ki je naloga ni zajela; med testiranjem najdena napaka branja seje: po uspešnem shranjevanju se je obrazec odprl prazen in bi ob ponovnem urejanju tiho pobrisal neobvezna polja. Popravljeno tudi v treh administratorskih modelih. Obliki vdelav za Strava in Komoot potrjeni na pravih javnih trasah.
- **06** — PR #6, združen 2026-10-19. Vseh 17 meril ✅ na DDEV, en popravek po vprašanju za arhitekta (izbrisana runda v toku D40 → `runda_ni_najdena` in preusmeritev na seznam z opozorilom). Testiranje je prvič opravil arhitekt v brskalniku (Claude in Chrome), Matija je potrjeval stanje v bazi.
- **05** — PR #5, združen 2026-10-18 (`92b7ca9`). Vseh 17 meril ✅ na DDEV, brez popravkov po pregledu. Potrjena oblika URL-jev `/runde` in `/runde/{id}-{alias}`; prehod poletni/zimski čas preverjen na treh rundah.
- **04** — PR #4, združen 2026-10-16 (`8fe6a00`). 19 od 20 meril ✅ na DDEV; merilo 20 (odstranitev) preloženo na nalogo 14, ker pobriše tabele. Med testom najdena blokerska napaka `User::bind()` po referenci (→ razširjeno pravilo 16), nevidnost lastnega dnevnika (→ pravilo 22) in konflikt med Joomlino blokado in AcyMailingom (→ D52, D53, popravljen D47).
- **03** — PR #3, združen 2026-10-13 (`6fdf9ff`). Vseh 12 meril ✅ na DDEV; AcyMailing ostal nespremenjen (124/124). Med testom najdeni napaki prednastavljene dolžine in manjkajočega odmika na obrazcih → pravili 17 in dopolnjeno 14 v `ARCHITECTURE.md`.
- **02** — PR #2, združen 2026-10-10 (`918c64c`). Vseh 16 meril ✅ na DDEV (posodobitev 0.1.0 → 0.2.0, čas poleti/pozimi, brez dvojne pretvorbe). Med pregledom najdena napaka `bind()` po referenci → pravilo 16.
- **01** — PR #1, združen 2026-10-09 (`f3e74ec`). Vseh 13 meril ✅ na DDEV. Med testom najdene in odpravljene napake → pravila 13–15 v `ARCHITECTURE.md`.
