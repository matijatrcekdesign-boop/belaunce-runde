# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-13
**Fokus seje:** zaključek NALOGE 03, priprava NALOGE 04 (prijava z magic linkom)
**Stanje:** NALOGA 03 ✅ združena (`6fdf9ff`). NALOGA 04 pripravljena za Codex.

---

## Kje smo ostali

- **NALOGA 03** (0.3.0): PR #3 združen, vseh 12 meril ✅ na DDEV. Adapter AcyMailinga, zavihek AcyMailing v Možnostih (lista članov 6, lista Runde 9), pogled "Preveri člana" (124 članov). Pred in po testih je bilo stanje tabel `acym_*` nespremenjeno — adapter res ne piše.
- Med testom naloge 03 odpravljeni: prednastavljena dolžina `0,1` v obrazcu runde (jedrna postavitev `number`, → pravilo 17) in manjkajoč odmik na obrazcih urejanja (prehod na jedrni vzorec `com_users/tmpl/group/edit.php`, → dopolnjeno pravilo 14).
- **NALOGA 04** (0.4.0): `docs/naloge/NALOGA-04.md` — prvi del za obiskovalce (site), tri nove tabele, vtičnik za avtentikacijo, predloga maila, omejitve poskusov.
- Nove odločitve **D48–D51** in nova pravila **18–21** v `ARCHITECTURE.md`; vsa so preverjena v izvorni kodi Joomle 6.1 (navedene datoteke in vrstice).

## Naslednji korak

1. **Matija pred testom naloge 04 na DDEV:**
   - Joomla mail na Mailpit: *Global Configuration → Server → Mail*: Mailer **SMTP**, Host `localhost`, Port `1025`, brez avtentikacije in šifriranja; pošiljatelj `info@belaunce.cc`. Mailpit na DDEV: `ddev launch -m` (ali `https://joomla-dev.ddev.site:8026`).
   - AcyMailing na DDEV ne sme pošiljati navzven (isti SMTP ali izklopljeno pošiljanje). **Na listi 6 so pravi člani.**
   - Dodaj svoj testni email kot naročnika na listo članov (ID 6), `active = 1`, `status = 1`.
   - Vklopi vtičnika *Authentication – Cookie* in *System – Remember Me* (D43).
2. Matija preda NALOGO 04 Codexu (veja `feature/NALOGA-04-prijava`).
3. Arhitekt pregleda PR in zažene gradnjo s PHP.
4. Test na DDEV (posodobitev 0.3.0 → 0.4.0).

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, predpona `dipxn_`. Administracija v angleščini. Na isti instanci je com_kisegonma.
- Repozitorij lokalno: `~/belaunce-runde` (WSL). Privzeta veja na GitHubu: `develop`.
- Namestitev: `cp` ZIP v `~/joomla-dev/tmp/` **pred vsako** namestitvijo, nato `ddev exec php cli/joomla.php extension:install --path=/var/www/html/tmp/…zip`.
- Skupina "Člani" na DDEV: ID 10. Matijev Joomla uporabnik na DDEV: ID 631.
- AcyMailing Enterprise 11.1.1: lista članov ID 6 (124 naročnikov), lista "Runde" ID 9 (prazna).
- Codex (aplikacija ChatGPT) nima PHP — `php -l` preverja arhitekt.

## Odprta vprašanja

- Potrditev oblike URL-jev runde (`/runde/{id}-{alias}`) — pred nalogo 05.
- Ob zaključku v1: povzetek načina dela (naloga 15 v `TASKS.md`).
