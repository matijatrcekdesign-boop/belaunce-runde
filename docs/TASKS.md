# TASKS.md — pkg_belauncerunde

**Prioritiziran seznam.** Vsaka naloga je ena veja in en PR v `develop`. Naloga se označi kot končana, ko je PR pregledan, testiran na DDEV in združen.

Oznake: ⬜ ni začeto · 🟡 v delu · 🔍 v pregledu · ✅ združeno

---

## Faza 1 (MVP)

| # | Naloga | Verzija | Odvisna od | Stanje |
|---|---|---|---|---|
| 01 | Okostje paketa, gradnja ZIP, update server, skupina "Člani", šifranti tipi in težavnosti (backend CRUD) | 0.1.0 | — | ✅ |
| 02 | Backend runde: tabela, seznam s filtri, urejanje, brisanje, zamenjava vodje, `CasHelper` (UTC ↔ `casovni_pas`) | 0.2.0 | 01 | ✅ |
| 03 | Adapter AcyMailing (samo branje, D45), izbira list v Možnostih (D46), diagnostika "Preveri člana" | 0.3.0 | 01 | 🟡 |
| 04 | Prijava z magic linkom: obrazec za email, žetoni, omejitve poskusov, vtičnik za avtentikacijo, ustvarjanje uporabnika v skupini "Člani", "Zapomni si me" | 0.4.0 | 03 | ⬜ |
| 05 | Javni seznam prihajajočih rund s filtri tip/težavnost, SEF router, tip menijske postavke | 0.5.0 | 02 | ⬜ |
| 06 | Stran runde: javno/člani (8), "Pridem"/odjava, D40, končane runde (`noindex`), Open Graph | 0.6.0 | 04, 05 | ⬜ |
| 07 | Frontend obrazec runde: ustvari, uredi, odpovej (pravila 4.1), trasa + vdelave | 0.7.0 | 06 | ⬜ |
| 08 | Gostje: obrazec, zaščita (D25), značka "gost", potrditveni mail z odjavo | 0.8.0 | 06 | ⬜ |
| 09 | "Moje runde" (vodim / prijavljen sem / pretekle) | 0.9.0 | 07 | ⬜ |
| 10 | Transakcijski maili ob spremembi in odpovedi (nastavljiva besedila in polja) | 0.10.0 | 07, 08 | ⬜ |
| 11 | Deli v Viber: gumb, besedilo za kopiranje | 0.11.0 | 06 | ⬜ |
| 12 | Scheduler opravila: sinhronizacija članov, izbris gostov (30 dni), čiščenje žetonov in poskusov | 0.12.0 | 04, 08 | ⬜ |
| 13 | Backend statistika in izvoz v CSV | 0.13.0 | 02 | ⬜ |
| 14 | Zaključni pregled: varnost, ACL, GDPR (orodja za zasebnost), dostopnost, PHPDoc, `NAVODILA.md` | 0.14.0 | vse | ⬜ |
| 15 | **Povzetek načina dela** (za katerikoli agent, za prihodnje projekte): brainstorming z Matijo (želje, osnutek, ideja) → pregled zahtev in vprašanja → vir resnice → postavitev repozitorija → naloge arhitekt/izvajalec → pregled → test v pravem okolju → zapis lekcij. Pripravi arhitekt z Matijo. | — | 14 | ⬜ |

Vrstni red se lahko po dogovoru spremeni (npr. 05 pred 03), odvisnosti pa morajo ostati izpolnjene.

## Kontrolni seznam pred zagonom na produkciji (ni naloga za Codex)

- [ ] **Pred nalogo 04 (DDEV):** Joomla mail na DDEV preusmerjen na Mailpit (SMTP `localhost:1025`, brez avtentikacije in šifriranja), AcyMailing na DDEV ne pošilja navzven; testni email dodan na listo članov (ID 6). Na DDEV so pravi člani!
- [ ] AcyMailing na produkciji: "Automatically subscribe to" prazno; razmisliti o izklopu "Delete the subscriber on site account deletion" (D47)
- [ ] Možnosti na produkciji: izbrani pravi listi članov in "Runde" (ID-ja sta drugačna kot na DDEV)

- [ ] **Predloga j4starter:** `<jdoc:include type="message" />` tudi v veji brez modula `sidebar` (sicer uporabniki ne vidijo sporočil o prijavi) — D41
- [ ] AcyMailing: javni obrazec vpisuje samo na listo "Runde"; ID liste članov in liste "Runde" vpisana v možnosti
- [ ] Vtičnik *System – Remember Me* vklopljen (D43)
- [ ] SMTP: testni mail z info@belaunce.cc; SPF/DKIM preverjena (npr. mail-tester.com)
- [ ] Scheduler: pravi strežniški cron za Joomlina opravila
- [ ] Test namestitve paketa na **kopiji produkcije** (MariaDB 10.11, utf8mb3 privzeto) — preveri, da so tabele utf8mb4
- [ ] Akeeba backup pred namestitvijo, načrt povratka
- [ ] Menijska postavka za seznam rund (čisti SEF URL-ji brez `/component/…`)

## Backlog (izven v1)

- Predlogi zadnjih lokacij (`datalist`) pri vnosu lokacije (D36)
- Tedenski povzetek, kode za prijavo, PWA, .ics, komentarji, hitra runda (faza 2)

## Končano

- **02** — PR #2, združen 2026-10-10 (`918c64c`). Vseh 16 meril ✅ na DDEV (posodobitev 0.1.0 → 0.2.0, čas poleti/pozimi, brez dvojne pretvorbe). Med pregledom najdena napaka `bind()` po referenci → pravilo 16.
- **01** — PR #1, združen 2026-10-09 (`f3e74ec`). Vseh 13 meril ✅ na DDEV. Med testom najdene in odpravljene napake → pravila 13–15 v `ARCHITECTURE.md`.
