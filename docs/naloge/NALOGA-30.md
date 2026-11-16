# NALOGA 30 — Oblikovani (HTML) maili

Veja:    feature/NALOGA-30-html-maili  (iz najnovejšega develop)
Verzija: 0.21.0 — vsi manifesti, `joomla.asset.json` (vrhnja IN vse lokalne datoteke, preveri `gradnja.sh`),
         `posodobitve.xml` (verzija + pot na vejo `izdaje`), CHANGELOG
PR v:    develop
Odvisno: D63 (zasebnost obvestila vodji), D50/D56/D57 (sprememba/odpoved), naloge 08, 10, 20
Status:  POTRJENO (Matija 2026-11-16): videz po predogledu `docs/naloge/NALOGA-30-predogled.png`

------------------------------------------------------------
CILJ
------------------------------------------------------------
Vseh 5 mailov komponente dobi oblikovano HTML različico v enotnem videzu Belaunce. Besedilna
različica (obstoječi `body`) ostane in se pošilja skupaj s HTML (multipart/alternative).
Videz mora biti čist v **Gmailu (splet, Android, iOS)**, **Apple Mail (iOS/macOS)** IN **Outlooku**:
Outlook za Windows (2016/2019/2021/365 — pogon Word), novi Outlook / Outlook.com in Outlook za iOS/Android.

Maili:
1. `com_belauncerunde.prijava`          — povezava za prijavo člana (IME, POVEZAVA, VELJAVNOST)
2. `com_belauncerunde.gost_potrditev`   — potrdilo gostu z odjavo
3. `com_belauncerunde.nova_prijava`     — nova prijava vodji (D63: ime udeleženca ostane, kot v besedilu)
4. `com_belauncerunde.runda_sprememba`  — sprememba (začetek/lokacija)
5. `com_belauncerunde.runda_odpoved`    — odpoved

NE delaj: spreminjanja logike pošiljanja/prejemnikov, novih mailov, sprememb besedilnih različic
(razen novih oznak, ki jih rabi HTML), push obvestil, sprememb predloge strani.

------------------------------------------------------------
1. VIDEZ (po predogledu)
------------------------------------------------------------
- Ozadje #f2f4f6; vsebinska tabela 600 px, bela, zgoraj logotip levo + "RUNDE" desno
  (Lexend/Arial 13 px, 800, razmik 3 px, #6ab7e3), pod glavo modra črta 4 px #6ab7e3.
- Oznaka dogodka (samo 4 in 5): SPREMEMBA = jantarna #FFE3A5 / temno besedilo; ODPOVEDANO = #212125 / belo.
- Uvodni stavek 16 px.
- **Kartica runde**: obroba 1 px #e3e7ea + leva obroba 4 px (#6ab7e3; sprememba #FFE3A5; odpoved #212125),
  naslov runde 19 px krepko, vrstice "oznaka — vrednost" (Kdaj, Zbirno mesto, Dolžina, Vodja),
  pod njimi oznaki tipa in težavnosti (barve iz šifrantov, kot na strani). Pri odpovedi je vsebina kartice siva in prečrtana.
- Sprememba: nad kartico rumeno polje #fff8e6 s seznamom sprememb (npr. "Nov začetek: …", "Nova lokacija: …").
- **Gumb** (#6ab7e3, belo besedilo, 15 px krepko): 1 "Prijava", 2/3/4 "Odpri rundo", 5 "Prihajajoče runde".
- Noga: "Vožnja je na lastno odgovornost." (1, ne; 2–5 da), pri gostih odjava (2 vedno, 4/5 kjer je ODJAVA),
  spodaj temna vrstica #212125: "Kolesarsko društvo Belaunce · runde.belaunce.cc".
- Pisave: `font-family: Lexend, Arial, sans-serif` (naslovi, gumb) in `Archivo, Arial, sans-serif` (besedilo).
  Brez spletnih pisav v <head> — v mailu se ne zanašamo na njih; Arial je pravi videz za Outlook.

------------------------------------------------------------
2. PRAVILA ZA OUTLOOK IN OSTALE (obvezno)
------------------------------------------------------------
 1. Postavitev SAMO s tabelami (`role="presentation"`, `cellpadding="0" cellspacing="0" border="0"`).
    Brez div-postavitve, flex, grid, float, position, max-width na div.
 2. Vsi slogi INLINE (`style=""`). V <head> sme biti samo kratek <style> za mobilni prikaz in temni način
    (Gmail ga delno upošteva, Outlook ga ignorira) — mail mora brez njega izgledati pravilno.
 3. Širina: zunanja tabela 100 %, notranja `width="600"` atribut + `style="width:100%;max-width:600px"`;
    za Outlook Windows ovij v "ghost table":
    `<!--[if mso]><table role="presentation" width="600" align="center"><tr><td><![endif]--> … <!--[if mso]></td></tr></table><![endif]-->`
 4. Barve ozadja z atributom `bgcolor` IN v slogu (`background-color`), nikoli samo `background:` kratica.
 5. Razmiki s `padding` na <td>, ne z `margin` (Outlook margin ignorira). <p> z `margin:0` in razmik s padding.
 6. `line-height` v px (npr. 24px) + `mso-line-height-rule:exactly`.
 7. Gumb "bulletproof": VML `v:roundrect` v `<!--[if mso]>` (arcsize ≈ 8 %, fillcolor #6ab7e3, stroke f)
    + za ostale `<a>` z `display:inline-block;padding` v <td bgcolor>. Href mora delovati v obeh.
 8. Zaobljeni robovi (`border-radius`) so dovoljeni kot okras — Outlook Windows jih ignorira in mail
    mora biti še vedno lep (oglati robovi). Brez senc, gradientov, ozadij-slik, SVG, CSS spremenljivk, rem/em.
 9. Logotip: PNG `media/com_belauncerunde/images/logo-mail.png` (širina 600 px, prikaz `width="200"` atribut
    + `style="display:block;width:200px;height:auto;border:0"`), **bela podlaga zapečena v sliko**
    (ne prozorna — temni način v Outlooku/Gmailu bi temni logotip na prozorni podlagi skril), `alt="Belaunce"`.
    Pripravi ga iz `orodja/ikone/logo.svg` (zapiši ukaz v `orodja/ikone/README.md`). URL absoluten:
    nova oznaka `{SITEURL}` (= `Uri::root()`, s končnim /) → `{SITEURL}media/com_belauncerunde/images/logo-mail.png`.
10. Temni način: `<meta name="color-scheme" content="light">` + `<meta name="supported-color-schemes" content="light">`;
    besedilo nikoli čisto belo na prozornem; temna noga ima `bgcolor`.
11. Predglavno besedilo (preheader): skrit <div> na začetku (display:none; max-height:0; overflow:hidden; mso-hide:all)
    s kratkim povzetkom (npr. "Sobotni krog · sobota, 21. 11. ob 9:00").
12. `<html lang="sl">`, `<meta charset="utf-8">`, `<meta name="viewport" …>`, `<meta name="x-apple-disable-message-reformatting">`,
    v <head> `<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->`
    in xmlns za v/o na <html>.
13. Velikost: HTML posameznega maila < 60 KB (Gmail obreže nad ~102 KB). Brez base64 slik.
14. Vse povezave absolutne (https://runde.belaunce.cc/...), `target="_blank"` ni potreben.
15. Dostopnost: kontrast besedila ≥ 4.5:1 (sivina oznak #6b6b72 na beli je OK), gumb ima besedilo, ne slike.

Preveri pravila na referenčnih virih in navedi v PR, na kaj si se oprl (npr. caniemail.com, goodemailcode.com).

------------------------------------------------------------
3. JOOMLA: HTML PREDLOGE (preveri v jedru 6.x, ne ugibaj)
------------------------------------------------------------
- `MailTemplate::createTemplate($key, $subject, $body, $tags, $htmlbody = '')` sprejme htmlbody.
  Preberi `libraries/src/Mail/MailTemplate.php` (6.x) in v PR navedi:
  a) kako Joomla odloči, ali pošlje HTML (nastavitve com_mails: oblika pošte / "mail_htmllayout" / per-template params),
  b) ali Joomla HTML ovije v svojo postavitev (`layouts/joomla/mail/mailtemplate.php`) in z logotipom iz com_mails —
     **naš htmlbody je celoten dokument**, zato ovitje izklopi za naše predloge (per-template params, npr. `disable_htmllayout`)
     ali, če to ni mogoče, napiši htmlbody kot fragment, ki se pravilno ugnezdi; izberi in utemelji,
  c) kako se oznake v htmlbody ubežijo (htmlspecialchars) in kako delujejo bloki `{OZNAKA}…{/OZNAKA}` za seznam vrednosti.
- Ponavljajoči/pogojni deli prek blokov (seznam vrstic): `{SPREMEMBE}…{/SPREMEMBE}` (elementi NAZIV, VREDNOST),
  `{ODJAVA}…{/ODJAVA}` (0 ali 1 element z URL) — tako vrstica izgine, ko podatka ni. Če bloki v 6.x ne delujejo,
  zapiši v "Vprašanja za arhitekta".
- **Nove oznake** (dodaj v `addTemplateData` na mestih pošiljanja IN v seznam `$tags` predloge):
  SITEURL, NASLOV (= RUNDA), DAN, DATUM, URA, LOKACIJA, DOLZINA, VODJA, TIP, TIP_BARVA, TEZAVNOST, TEZAVNOST_BARVA,
  PREDGLAVA. Datum/ura v Europe/Ljubljana (CasHelper, kot na strani). Barve iz šifrantov; neveljavna → #6ab7e3.
  Obstoječe oznake ostanejo (besedilne različice jih uporabljajo).
- HTML vsebina predlog v jezikovnih datotekah? NE — HTML ni primeren za .ini. Shrani jo kot datoteke
  `administrator/mail/<kljuc>.html` (sl) in `administrator/mail/en-GB/<kljuc>.html` (en), ki jih `skript.php` prebere.
  (Statična besedila v HTML so v jeziku predloge; ločeni datoteki za sl-SI in en-GB.)
- **Namestitev/posodobitev (`skript.php`)**:
  - nova namestitev: createTemplate z htmlbody (+ jezikovni vrstici sl-SI/en-GB kot zdaj, z ustreznim htmlbody),
  - posodobitev: za vsako od 5 predlog in vsak jezik (`''`, `sl-SI`, `en-GB`) nastavi `htmlbody` **samo, če je prazen**
    (admin, ki je HTML uredil sam, ga obdrži), in dopolni seznam oznak v `params`/tags, da se nove oznake pokažejo v
    urejevalniku com_mails. Besedilnega `body` ne spreminjaj. Idempotentno.
- Nič trdo kodiranih emailov ali skrivnosti.

------------------------------------------------------------
4. TESTNO POŠILJANJE (razvoj)
------------------------------------------------------------
- `orodja/maili/predogled.php` (ni v paketu): iz 5 HTML datotek in primernih vrednosti oznak (izmišljena runda)
  ustvari `dist/maili/<kljuc>.html` za ogled v brskalniku IN validacijo velikosti (< 60 KB).
- V PR priloži posnetke predogledov (desktop 600 px in mobilno 375 px).

------------------------------------------------------------
MERILA SPREJEMA
------------------------------------------------------------
 1. `bash orodja/gradnja.sh` uspe (verzije usklajene, tudi joomla.asset.json).
 2. Sveža namestitev: vseh 5 predlog ima htmlbody v `''`, `sl-SI`, `en-GB`.
 3. Posodobitev 0.20.4 → 0.21.0: prazni htmlbody se napolnijo; ročno spremenjen htmlbody ostane; `body` nespremenjen.
 4. Ponovna namestitev 0.21.0 ničesar ne podvoji in ne prepiše.
 5. Vsak od 5 mailov pride kot multipart (HTML + besedilo); besedilna različica enaka kot prej (+ morebitne nove oznake niso vidne kot {OZNAKA}).
 6. V nobenem mailu ni neobdelane oznake `{...}`; prazni bloki (ODJAVA pri članu, SPREMEMBE brez sprememb) izginejo.
 7. Logotip se prikaže (absolutni URL), z belo podlago; v temnem načinu Gmail/Outlook ostane berljiv.
 8. **Outlook za Windows** (365/2019): postavitev 600 px, gumb je moder s povezavo (VML), brez razpadlih tabel ali
    belih presledkov med vrsticami; kartica z levo obrobo; noga temna.
 9. Outlook.com / novi Outlook, Outlook iOS/Android, Gmail splet/Android/iOS, Apple Mail iOS: videz kot predogled.
10. Mobilno (375 px): brez vodoravnega drsenja, gumb dovolj velik za prst (≥ 44 px visok).
11. Gost dobi povezavo za odjavo (2 in 4/5), član ne; nova prijava vodji ostane po D63.
12. Datumi in ure v Europe/Ljubljana, slovenski dnevi; en-GB različica angleško.
13. HTML posameznega maila < 60 KB, brez base64 slik in zunanjih CSS/pisav.
14. Brez sprememb logike prejemnikov in sprožilcev (regresija nalog 08, 10, 20).

Merila 8–9 preveri Matija/arhitekt na produkciji s pravimi mailboxi (testna runda + gost z Outlook naslovom);
v PR jih označi "test na produkciji".

------------------------------------------------------------
ROČNI TEST ZA MATIJO (produkcija, po namestitvi)
------------------------------------------------------------
- Povezava za prijavo: na /prijava vpiši email (Gmail in Outlook naslov) → preveri mail v obeh.
- Testna runda (arhitekt): prijava gosta z Outlook naslovom → potrdilo; vodja dobi "nova prijava";
  sprememba ure → mail spremembe; odpoved → mail odpovedi. Na koncu testno rundo izbriše arhitekt.
