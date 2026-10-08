# SESSION.md — pkg_belauncerunde

**Zadnja posodobitev:** 2026-10-08
**Fokus seje:** pregled zahtev, odločitve D27–D43, priprava repozitorija in naloge 01
**Stanje:** pripravljeno za začetek razvoja. Kode še ni.

---

## Trenutno stanje

- Zahteve pregledane, vrzeli V1–V9 zaprte, odločitve D27–D43 zapisane v `osnova-projekta.md`.
- Repozitorij `matijatrcekdesign-boop/belaunce-runde` (javen) ima `LICENSE` in prvi sklop dokumentacije.
- DDEV (`~/joomla-dev`) je kopija belaunce.cc: Joomla 6.1.4, PHP 8.3.30, MariaDB 11.8. Na isti instanci je com_kisegonma.
- Produkcija: Joomla 6.1.4, PHP 8.3.33, MariaDB 10.11 (utf8mb3), LiteSpeed, časovni pas strani UTC, seja 15 min.

## Veje

- `main` — začetni commit
- `develop` — ustvari Matija ob prvem commitu dokumentacije
- Odprtih feature vej ni

## Naslednji korak

1. Matija commita dokumentacijo v `develop`.
2. Matija preda `docs/naloge/NALOGA-01.md` Codexu.
3. Arhitekt pregleda PR; Matija namesti ZIP na DDEV in pošlje rezultate testov.

## Odprta vprašanja

- Potrditev oblike URL-jev runde (`/runde/{id}-{alias}`, predlog v `osnova-projekta.md` 5) — pred nalogo 05.
- ID liste članov in liste "Runde" v AcyMailingu — pred nalogo 03.
