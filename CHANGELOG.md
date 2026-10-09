# Changelog

Vse pomembne spremembe paketa `pkg_belauncerunde`. Oblika po [Keep a Changelog](https://keepachangelog.com/sl/1.1.0/), verzije po semantičnem verzioniranju.

## [Neizdano]
### Dodano
- Dokumentacija projekta (`docs/`), navodila za izvajalca (`AGENTS.md`).

## [0.2.0] - 2026-10-09
### Dodano
- Administracijska tabela, seznam in obrazec za runde z odpovedjo, obnovo, brisanjem in zamenjavo vodje.
- Pretvorba časa rund med UTC in časovnim pasom iz možnosti komponente.
- Filtri seznama rund po tipu, težavnosti, stanju, vodji in obdobju.
- Polji šifrantov za tip in težavnost ter zaščita brisanja uporabljenih šifrantov.
- Nastavitve za časovni pas rund, privzeto lokacijo in privzeto trajanje.

## [0.1.0] - 2026-10-08
### Dodano
- Okostje paketa `pkg_belauncerunde` in komponente `com_belauncerunde`.
- Namestitveni SQL za šifranta tipov in težavnosti z začetnimi vrednostmi.
- Administracijska CRUD pogleda za tipe in težavnosti.
- Namestitveni skript za pripravo skupine **Člani** in možnost ohranitve podatkov ob odstranitvi.
- Gradbena skripta `orodja/gradnja.sh` in update server `posodobitve.xml`.
