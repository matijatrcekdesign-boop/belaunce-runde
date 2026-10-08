# DECISIONS.md — pkg_belauncerunde

Zakaj so bile sprejete ključne tehnične odločitve. **Seznam vseh odločitev (D1–D…) je v `osnova-projekta.md` razdelek 3**; tukaj so samo razlogi za tiste, pri katerih "zakaj" ni očiten. Ne briši zgodovine; preklicane odločitve označi.

---

## D28 — Koda v slovenščini, brez šumnikov
**Zakaj:** Matijina izbira, skladno s com_kisegonma (`jezik`, `naziv`, `stanje`). ASCII zato, ker šumniki v identifikatorjih povzročajo težave v orodjih, SQL in samodejnem nalaganju razredov. Kar zahteva Joomla, ostane angleško, sicer ogrodje ne deluje.
**Stanje:** aktivno.

## D39 — Namestitev samo prek paketa, tudi na DDEV
**Zakaj:** pri com_kisegonma je ročna namestitev na DDEV skrila 6 napak, ki so se pokazale šele ob namestitvi paketa na produkciji. Paket se testira na enak način, kot se bo namestil.
**Cena:** vsak test na DDEV zahteva gradnjo ZIP in namestitev (ena ukazna vrstica).
**Stanje:** aktivno.

## D40 — "Pridem" za neprijavljene: ID runde pri žetonu
**Zakaj ne samodejna prijava prek povratnega URL-ja:** povezava z akcijo v URL-ju bi lahko člana vpisala na rundo brez njegove volje. Seja ne preživi, ker se magic link pogosto odpre v drugem brskalniku (poštna aplikacija, Viber).
**Rešitev:** ID runde je del zahteve za povezavo (POST s strani runde) in je shranjen k žetonu v bazi. Namen je viden na gumbu in potrjen s POST. Deluje v vsakem brskalniku.
**Stanje:** aktivno.

## D42 — Lasten časovni pas namesto časovnega pasu strani
**Zakaj:** strani ima nastavljen časovni pas UTC in Matija ga ne želi spreminjati (vpliva na ostale razširitve). Brez lastnega parametra bi se runda ob 17:00 poleti prikazala kot 15:00.
**Kako:** v bazi UTC; vnos in prikaz prek `casovni_pas` (privzeto Europe/Ljubljana), ena pomožna metoda za pretvorbo.
**Stanje:** aktivno.

## Tabele izrecno utf8mb4
**Zakaj:** produkcijska baza ima privzeto `utf8mb3_general_ci`, ki ne shrani emojijev (opombe vodij, Viber besedila). DDEV ima utf8mb4, zato napake lokalno ne bi videli.
**Stanje:** aktivno, pravilo v `AGENTS.md`.

## MariaDB na DDEV ostane 11.8
**Zakaj:** isti DDEV projekt uporablja tudi com_kisegonma; prehod na nižjo verzijo je tvegan. Tveganje razlik se pokrije s pravilom "SQL za 10.11" in testom namestitve na kopiji produkcije pred zagonom.
**Stanje:** aktivno, na kontrolnem seznamu pred zagonom.

## Postopek dela: Claude arhitekt, Codex izvajalec, Matija tester
**Zakaj:** preizkušeno pri com_kisegonma. Claude lahko bere javni repozitorij, ne more pa vanj pisati ali zagnati Joomle. Codex piše in commita, ne more zagnati Joomle. Vsako preverjanje teče na Matijevem DDEV, z izpisi in posnetki zaslona nazaj k arhitektu.
**Stanje:** trajno.
