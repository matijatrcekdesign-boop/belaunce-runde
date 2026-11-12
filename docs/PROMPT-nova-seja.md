# Prompt za novo sejo (implementacija manjkajočega)

Kopiraj spodnji blok v novo sejo (Claude Code ali Claude z orodji za brskalnik).

```
Nadaljuješ projekt Belaunce Runde (Joomla 6.2: komponenta com_belauncerunde + predloga belauncetemplate) na https://runde.belaunce.cc.
Repozitorij: https://github.com/matijatrcekdesign-boop/belaunce-runde, veja develop (verzija 0.19.0, zadnji commit z docs in ikono).
Jezik komunikacije: slovenščina. Strogo korak za korakom, pred večjimi koraki potrdi z uporabnikom. Dokazi iz pravega brskalnika pred ugibanjem.

NAJPREJ PREBERI (v tem vrstnem redu):
1. docs/SESSION.md (stanje), docs/TASKS.md (naloge 23-29), docs/PRODUKCIJA-runde.md (razdelek "App videz 0.19.0"), docs/ARCHITECTURE.md (pravila za Joomlo 6), AGENTS.md.
2. docs/predloga-belauncetemplate/ - kopija sprememb predloge (index.php, style-app.css); predloga NI v gitu.

STANJE:
- 0.19.0 je v živo in na GitHubu; paket pkg_belauncerunde-0.19.0.zip se zgradi z: bash orodja/gradnja.sh (potrebuje php, zip, xmllint). SHA-256 gre v posodobitve.xml.
- Videz je narejen po canvasu (artefakt "Belaunce Runde - app predogled", https://claude.ai/artifact/39LoRzyyCz6MGkAk8z7Zqk): modra #6ab7e3, bela pisava na modrem, jantarna #FFE3A5, temna #212125, radij 5/3 px, Lexend + Archivo.
- Uporabnik (Matija/Sandi) je delovanje kot član preveril na telefonu in potrdil.
- Odločitev uporabnika: push obvestila so NASLEDNJA FAZA.

NALOGE (po prioriteti; ena naloga = ena veja/en commit sklop; ne združuj brez potrditve):
A) Naloga 29: GitHub Release v0.19.0 s ZIP-om (preveri, ali že obstaja; sicer ga ustvari prek spletnega vmesnika; ZIP naloži iz dist/ po gradnji; preveri, da sha256 v posodobitve.xml ustreza).
B) Naloga 24: Push obvestila. Najprej napiši specifikacijo docs/naloge/NALOGA-24.md in jo pokaži uporabniku; šele po potrditvi implementiraj. Obseg predlog: VAPID ključi (v Možnostih komponente, ne v kodi), tabela naročnin (uporabnik/gost, endpoint, ključi, uporabniški agent, ustvarjeno), service worker push + notificationclick handler, UI naročnine (iOS samo če je PWA nameščena na začetni zaslon; pokaži namig), sprožilci: nova prijava (vodji), odpoved/sprememba runde (prijavljenim), opomnik pred rundo (scheduler opravilo). Zasebnost po D63: brez emaila/telefona v obvestilu. Knjižnica: minishlink/web-push (ali brez odvisnosti, če je izvedljivo - utemelji). Testiraj v pravem brskalniku in na telefonu.
C) Naloga 26: og-runde.png v paket (media/images) in HtmlView naj ga bere iz Uri::root().'media/com_belauncerunde/images/og-runde.png'; če uporabnik priskrbi vektorski logotip (SVG/PDF), ponovno generiraj ikone (192/512/maskable/apple-touch) in OG sliko ostreje. Navodilo za generiranje ikon: črka B (balanca) iz logotipa, bela podlaga, #212125 črka, modri #6ab7e3 rob 28/512 (any), maskable: polna bela podlaga + obroč, črka v varnem območju 80 %.
D) Naloga 25 (+17): predloga v repozitorij. Predlog: otroška predloga ali mapa templates/belauncetemplate-runde v repozitoriju z index.php in style-app.css; opiši postopek namestitve. Ne spreminjaj starša brez potrditve.
E) Naloga 27: QA - sestavi preverjalni seznam za iOS Safari (lepljive vrstice, izbirnik datuma, PWA), Viber predogled, člani (ustvari/uredi/odpovej/odjavi), in ga izvedi z uporabnikom na napravi.
F) Naloga 28: preusmeritve starih belaunce.cc/runde/... povezav na poddomeno (predlagaj pravila .htaccess/Joomla redirect, počakaj na potrditev).

PRAVILA IN ZANKE (iz prejšnje seje):
- Dostop: v Chrome (Claude in Chrome) je uporabnik že prijavljen v admin in GitHub; gesel ne vpisuj. Admin: FF Explorer (index.php?option=com_ffexplorer): window.FF_EXPLORER_DATA {path.ajax, params[0]=žeton}; POST (URLSearchParams, ne FormData, razen pri uploadu): task=explorer.openFile|saveContent|deleteFile|upload (poti z vodilnim /), žeton kot <ključ>=1. .webmanifest se ne da odpreti, saveContent deluje. Binarne datoteke: explorer.upload (FormData: task, path, žeton, file) ali Joomla Media API (administrator/index.php?option=com_media&format=json&task=api.files&path=local-images:/ z JSON {name, content(base64)} in glavo X-CSRF-Token).
- Izpis Chrome orodja zamolči nize z ? & = (BLOCKED: query string) in skrajša dolge izpise (~1000 znakov): v rezultatu zamenjaj te znake in razdeli v kose. Top-level return v javascript_tool ne deluje, uporabi končni izraz.
- Direktno izvajanje .php prek URL-ja v /media in /templates je blokirano (403); ne poskušaj nalaganja skript.
- Predpomnjenje: golo /media/com_belauncerunde/css/site.css brskalnik predpomni; vedno preveri z ?x=<čas>. Template style.css ima ?v=filemtime.
- GitHub: strežniški git push NI mogoč (GH_TOKEN neveljaven). Delovalo je: (1) spletni urejevalnik: github.com/.../edit/develop/<pot>, CodeMirror 6: document.querySelector('.cm-content').cmTile.view; vstavljanje prek view.dispatch; sporočilo commita nastavi prek native value setterja + input event v dialogu; (2) stran Upload (github.com/.../upload/develop/<mapa>) + orodje file_upload na <input type=file> (ref iz find, potem počakaj 3 s pred potrditvijo); (3) kopiranje besedila prek pravega ctrl+c/ctrl+v (computer key) med zavihkoma. Po vsakem commitu preveri z git fetch + git log origin/develop.
- Lokalni klon sinhroniziraj z git reset --hard origin/develop preden gradiš.
- Ne ustvarjaj/objavljaj javnih stvari (release, objave) brez potrditve uporabnika. Ne spreminjaj podatkov članov. Članov nikoli ne blokiraj (D52, D53).
- Vsak commit: sporočilo oblike "0.xx.y: kaj - zakaj".
- Po končani nalogi posodobi docs/SESSION.md, docs/TASKS.md, CHANGELOG.md.
```
