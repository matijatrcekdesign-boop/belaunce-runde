# Changelog

Vse pomembne spremembe paketa `pkg_belauncerunde`. Oblika po [Keep a Changelog](https://keepachangelog.com/sl/1.1.0/), verzije po semantičnem verzioniranju.

## [Neizdano]
### Dodano
- Dokumentacija projekta (`docs/`), navodila za izvajalca (`AGENTS.md`).

## [0.4.0] - 2026-10-14
### Dodano
- Site obrazec za prijavo člana z emailom in potrditev enkratne povezave prek POST.
- Tabele za SHA-256 žetone, povezave članov in hashirane ključe omejitev poskusov.
- `OmejitevHelper`, `ZetonService` in `ClanService` za omejitve, atomarno porabo in članske Joomla račune.
- Avtentikacijski vtičnik s ponovnim preverjanjem AcyMailing članstva, zavrnitvijo tujega računa in odblokiranjem člana.
- Nastavitve omejitev, veljavnosti povezave in Joomlinega »Zapomni si me«.
- Uredljiva Joomlina predloga maila `com_belauncerunde.prijava` v slovenščini in angleščini.
- Samodejni vklop našega vtičnika in opozorilo na potrebna jedrna piškotkovna vtičnika.

### Spremenjeno
- Paket namesti avtentikacijski vtičnik pred komponento, gradnja pa zapakira vse mape `paket/plg_*`.
- Namestitev in odstranitev upoštevata novo poštno predlogo ter možnost ohranitve podatkov.

## [0.3.0] - 2026-10-10
### Dodano
- Bralni `AcymailingAdapter` za preverjanje članstva po AcyMailing listi.
- Nastavitve AcyMailing list članov in rund v možnostih komponente.
- Administrativno orodje **Preveri člana** za diagnostiko emaila in števila članov liste.

### Spremenjeno
- Obvestila pri zaščitenem brisanju tipov in težavnosti uporabljajo množinske jezikovne nize.

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
