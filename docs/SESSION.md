# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-10
**Fokus seje:** zaključek NALOGE 02, analiza AcyMailinga, priprava NALOGE 03
**Stanje:** NALOGA 02 ✅ združena (`918c64c`). NALOGA 03 pripravljena za Codex.

---

## Kje smo ostali

- **NALOGA 02** (0.2.0): PR #2 združen, vseh 16 meril ✅ na DDEV. Paket 0.2.0 je nameščen na DDEV, v bazi sta testni rundi "Test poletje" in "Test zima".
- **AcyMailing 11.1.1** na DDEV: lista članov ID 6 (124 naročnikov, pravi člani), lista "Runde" ID 9 (prazna). Shema tabel `acym_user`, `acym_user_has_list`, `acym_list` je preverjena in zapisana v `NALOGA-03.md`.
- Nove odločitve **D44–D47** (pravilo člana, branje tabel brez API-ja, izbira list v Možnostih, člani se nikoli ne brišejo).
- **NALOGA 03** (0.3.0): `docs/naloge/NALOGA-03.md` — adapter, izbira list, diagnostika "Preveri člana", dva manjša popravka iz naloge 02.

## Naslednji korak

1. Matija preda NALOGO 03 Codexu (veja `feature/NALOGA-03-acymailing`).
2. Arhitekt pregleda PR in zažene gradnjo s PHP.
3. Test na DDEV (posodobitev 0.2.0 → 0.3.0).
4. **Pred nalogo 04:** Joomla mail na DDEV na Mailpit, AcyMailing na DDEV ne pošilja navzven, testni email na listi članov (glej kontrolni seznam v `TASKS.md`).

## Okolje

- DDEV `~/joomla-dev` (`https://joomla-dev.ddev.site`): Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8, predpona `dipxn_`. Administracija v angleščini. Na isti instanci je com_kisegonma.
- Repozitorij lokalno: `~/belaunce-runde` (WSL). Privzeta veja na GitHubu: `develop`.
- Namestitev: `cp` ZIP v `~/joomla-dev/tmp/` **pred vsako** namestitvijo, nato `ddev exec php cli/joomla.php extension:install --path=/var/www/html/tmp/…zip`.
- Skupina "Člani" na DDEV: ID 10. Matijev Joomla uporabnik na DDEV: ID 631.
- Codex (aplikacija ChatGPT) nima PHP — `php -l` preverja arhitekt.

## Odprta vprašanja

- Potrditev oblike URL-jev runde (`/runde/{id}-{alias}`) — pred nalogo 05.
- Ob zaključku v1: povzetek načina dela (naloga 15 v `TASKS.md`).
