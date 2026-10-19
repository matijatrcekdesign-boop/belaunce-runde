# NALOGA 07: Frontend obrazec runde (ustvari, uredi, odpovej) in vdelava trase

Veja: feature/NALOGA-07-obrazec (iz develop) · PR v: develop · Verzija: 0.7.0

CILJ
Član ustvari rundo s strani in s tem postane njen vodja. Vodja svojo rundo ureja
in jo odpove, oboje samo do štarta. Polje trasa dobi vdelavo za znane ponudnike.

POVEZANE ODLOCITVE
D3, D7, D15, D23, D36, D41, D42, D54; osnova-projekta.md razdelki 4, 4.1, 7, 15;
ARCHITECTURE.md "Pravila za Joomlo 6" 1-23 in razdelka "Frontend seznam in router"
ter "Udelezba na rundi"; AGENTS.md v celoti.

OBSEG

V nalogi:
1. Site pogled "obrazec" za ustvarjanje in urejanje runde (en pogled, dve stanji).
2. Site kontroler z akcijami runda.shrani in runda.odpovej.
3. Razsiritev routerja: /runde/nova in /runde/uredi-{id}-{alias}.
4. Gumbi na strani runde in na seznamu: "Ustvari rundo" (clan), "Uredi" in
   "Odpovej" (samo vodja, samo do startu).
5. TrasaHelper: vdelava trase za Strava, Komoot, Bikemap; drugje navaden link.
6. Moznosti komponente, zavihek "Trasa": vklop/izklop posameznega ponudnika.
7. Verzija 0.7.0 v vseh manifestih in posodobitve.xml; sql/updates/mysql/0.7.0.sql
   brez sprememb sheme (samo komentar); CHANGELOG.md, ARCHITECTURE.md, NAVODILA.md.

Izrecno NI v nalogi:
- gostje (naloga 08), "Moje runde" (naloga 09), transakcijski maili (naloga 10)
- brisanje runde in zamenjava vodje (ostaneta samo v backendu, D15 in razdelek 7)
- vrnitev odpovedane runde v objavljeno stanje (samo backend)
- kakrsnekoli spremembe administracije in predloge strani (D41)

1. PRAVICE IN ZAKLEPANJE (razdelek 7 in 4.1)
- Ustvari rundo: vsak clan (ista metoda PrijaveModel::jeClan(), ne lastna razlicica).
- Uredi in odpovej: samo uporabnik, ki je vodja te runde (runda.vodja_id === id
  identitete), in samo dokler zacetek ni mimo. Po startu je runda zaklenjena tudi
  za vodjo; admin v backendu lahko vse.
- Meja je START (zacetek), ne konec runde. Primerjava vedno v UTC z uro baze.
- Odpovedane runde (stanje = 2) ni mogoce urejati ne ponovno odpovedati.
- Pravice se preverijo v kontrolerju in v modelu pred zapisom, ne samo s skrivanjem
  gumbov. Neavtoriziran poskus se konca s preusmeritvijo in kodo stanja, nikoli z
  belo stranjo ali PHP napako.

2. POGLED "obrazec"
- Lastna site datoteka forms/runda.xml (administratorske ne uporabljaj, ker vsebuje
  vodja_id, stanje in alias).
- Polja: naslov, tip_id, tezavnost_id, zacetek (datum in ura), lokacija, dolzina_km,
  trajanje_min, opombe, trasa_url, odprto_za_goste.
- Obvezna: naslov, tip_id, tezavnost_id, zacetek, lokacija, dolzina_km.
- Privzetki ob ustvarjanju: lokacija iz moznosti (privzeta_lokacija, D36),
  odprto_za_goste = 0, zacetek prazen.
- Sifranta tip in tezavnost ponudita samo objavljene vnose, po ordering.
- trajanje_min je neobvezno: ce mora biti prazno, atributa min in max izpusti
  (pravilo 17) in meje preveri v modelu.
- Cas: uporabnik vpisuje lokalni cas v casovni_pas (D42). Pretvorbo v UTC in nazaj
  opravi izkljucno CasHelper; v bazo gre UTC, v obrazec lokalni cas.
- Alias se ne vpisuje. Ustvari ga RundaTable::check() iz naslova.
- Obrazec uporablja Joomlino validacijo (class="form-validate") in Bootstrap 5
  razporeditev; pogled doda addControlField('task'), predloga izpise
  renderControlFields() (pravilo 14).

3. ZAPIS
- Uporabi obstojeco RundaTable (check(), store(), alias, revizijska polja,
  odpovedano, odstranitev morebitne vrstice vodje iz prijav). Ne pisi nove logike
  za polja, ki jih tabela ze obvladuje.
- Vrednosti, ki jih uporabnik NE sme dolociti in jih nastavi model:
  vodja_id = id identitete (ob ustvarjanju), stanje = 1, ustvaril_id, ustvarjeno.
- Pri urejanju se vodja_id, stanje, ustvaril_id in ustvarjeno ne spreminjajo; ID
  runde se vzame iz POST, lastnistvo pa se vsakic preveri iz baze, nikoli iz
  skritega polja ali povratnega URL-ja.
- D54: ob ustvarjanju se v #__belaunce_prijave ne vstavi nic. Vodja je udelezenec
  po definiciji (PrijaveModel::steviloUdelezencev()).
- Po uspesnem zapisu preusmeritev na stran runde s kodo stanja (ustvarjena /
  shranjena / odpovedana). Pri napaki se podatki obrazca ohranijo v seji
  (setUserState), da uporabnik ne izgubi vpisanega.

4. ODPOVED
- runda.odpovej je POST s CSRF, brez GET razlicice.
- Dovoljena samo vodji, samo za objavljeno rundo pred startom.
- Nastavi stanje = 2; cas odpovedi nastavi RundaTable::store() sam.
- Potrditev pred odpovedjo: Bootstrap modal ali onsubmit confirm, nalozen prek
  WebAssetManagerja (bootstrap.modal), brez jQueryja.
- Prijavljeni clani ostanejo v #__belaunce_prijave; obvescanje je naloga 10.

5. ROUTER
- Pogled obrazec registriraj kot otroka pogleda runde, s kljucem id.
- Segmenti: "nova" za id = 0 in "uredi-{id}-{alias}" za urejanje.
- getObrazecId() vzame prvo stevilko za predpono "uredi-"; "nova" vrne 0.
- Preveri, da razclenjevanje ne trci z obstojecim segmentom runde "{id}-{alias}"
  (ta se vedno zacne s stevilko). Ce se pri tem pokaze dvoumnost, je NE resuj po
  svoje: zapisi v "Vprasanja za arhitekta".
- Stare dolge poti (index.php?option=...&view=obrazec) morajo ostati delujoce.

6. VDELAVA TRASE (D23, razdelek 15)
- Nov razred Site\Helper\TrasaHelper z dvema metodama: preveri URL in vrni opis
  vdelave, ali pa null.
- Shrani se SAMO URL, nikoli HTML od uporabnika.
- Pogoji za vdelavo: shema https, gostitelj iz zaprtega seznama (strava.com,
  komoot.com, komoot.de, bikemap.net, vkljucno z www), pot se ujema s pricakovanim
  vzorcem ponudnika, ponudnik je v moznostih vklopljen.
- Vse drugo se izrise kot navaden link z rel="noopener noreferrer" target="_blank".
- Iframe: v Bootstrap ovoju ratio, z loading="lazy", referrerpolicy="no-referrer",
  title iz jezikovnega niza. Brez inline skript.
- Oblike vdelav preveri v AKTUALNI dokumentaciji vsakega ponudnika in v PR navedi
  vir za vsakega (povezava in datum). Ne ugibaj po spominu; arhitekt bo preveril.
  Ce za ponudnika uradne oblike vdelave ni, ga pusti na navadnem linku in to zapisi.
- V NAVODILA.md zapisi, da je pri vklopljenem CSP treba dovoliti frame-src domene.

7. VARNOSTNE ZAHTEVE
- Vsaka spreminjajoca akcija: POST + checkToken, pravice preverjene v kontrolerju.
- Vse poizvedbe z bind() in ParameterType (pravilo 16: vrednost po referenci).
- Vsak izpis skozi escape(); trasa_url dodatno skozi TrasaHelper.
- V dnevnik samo kode in ID-ji, nikoli emaili, surovi zetoni, SQL ali IP.
- V URL-jih ni osebnih podatkov; stanja so zaprti seznam kod, neznana koda se ne
  izpise (enako kot v nalogi 06).
- opombe so navadno besedilo brez HTML-ja (filter string, izpis z escape()).

8. MERILA SPREJEMA
Matija preveri na DDEV (ZIP pred namestitvijo kopiraj v tmp/):

1.  bash orodja/gradnja.sh uspe; vse verzije 0.7.0. Posodobitev z 0.6.0 uspe,
    verzija sheme je 0.7.0, obstojeci podatki (runde, prijave, clani) nespremenjeni.
2.  Neprijavljen na seznamu in na strani runde NE vidi gumba "Ustvari rundo".
3.  Clan vidi "Ustvari rundo"; /runde/nova odpre prazen obrazec z lokacijo
    "Gorenja vas" in privzeto odprto_za_goste = ne.
4.  Oddaja obrazca brez obveznih polj pokaze napake in ne ustvari vrstice.
5.  Uspesno ustvarjanje: runda je v bazi, vodja_id je ID clana, stanje = 1,
    ustvaril_id in ustvarjeno izpolnjena, alias zgenerirani iz naslova, v
    #__belaunce_prijave ni nove vrstice (D54).
6.  Po ustvarjanju se odpre stran nove runde; clan je stet kot udelezenec (1).
7.  Cas: runda, vpisana ob 17:00, je v bazi v UTC in na strani prikazana ob 17:00;
    preveri z eno poletno in eno zimsko rundo (D42).
8.  Vodja na svoji rundi vidi "Uredi" in "Odpovej"; drug clan ju NE vidi.
9.  Urejanje: sprememba lokacije in casa se shrani, spremenil_id in spremenjeno se
    posodobita, vodja_id in ustvarjeno ostaneta nespremenjena.
10. Rocno poslan POST runda.shrani z ID-jem tuje runde je zavrnjen in tuja runda
    ostane nespremenjena.
11. Odpoved: potrditev, nato stanje = 2, odpovedano izpolnjeno, na seznamu in na
    strani znacka "Odpovedano", prijave clanov ostanejo v bazi.
12. Odpovedane runde ni mogoce urejati ne znova odpovedati (gumbov ni, rocni POST
    zavrnjen).
13. Startana runda (zacetek v preteklosti): vodja nima ne "Uredi" ne "Odpovej",
    rocni POST obeh akcij je zavrnjen (4.1).
14. Trasa: veljavna Strava povezava se izrise kot vdelava; izklop ponudnika v
    moznostih jo spremeni v navaden link.
15. Trasa: povezava na nepodprto domeno se NIKOLI ne vdela, izrise se kot link z
    rel="noopener noreferrer"; vpis javascript: ali data: URL-ja je zavrnjen.
16. Cisti URL-ji delujejo: /runde/nova in /runde/uredi-{id}-{alias}; stara dolga
    pot z index.php?option=... prav tako.
17. Brez PHP opozoril; vse deluje z izklopljenim vticnikom za zdruzljivost nazaj.
18. Z omejitvijo 3: clan ustvari tri runde; cetrti poskus je zavrnjen s
    sporocilom o dovoljeni meji, v bazi cetrte runde ni. Urejanje obstojece
    runde se vedno dela.
19. Odpoved ene od treh rund NE sprosti kvote; po odpovedi je cetrti poskus
    se vedno zavrnjen.
20. Clan ustvari rundo, nato znova odpre /runde/nova: polje Lokacija je ponovno
    prednastavljeno na vrednost iz moznosti, polja Naslov in Datum pa so prazna.
    Po neuspeli oddaji se vpisane vrednosti se vedno ohranijo. Ponovno odpiranje
    obrazca s pociscenim seznamom napak ne povzroci PHP opozoril.
21. Clan uredi rundo in shrani, nato znova odpre isto rundo v urejanju: vsa polja
    so napolnjena iz baze, vkljucno z opombami, trajanjem in traso. Po shranjevanju
    brez sprememb ostanejo neobvezna polja v bazi nespremenjena.
22. Administracija (Runde, Tipi, Tezavnosti, Preveri clana), prijava z magic linkom
    in udelezba iz naloge 06 delujejo nespremenjeno.

9. DOKUMENTACIJA
- ARCHITECTURE.md: razdelek "Ustvarjanje in urejanje rund" (pravice, zaklepanje ob
  startu, kaj nastavi model in cesa uporabnik ne sme dolociti, segmenti routerja)
  in "Vdelava trase" (zaprti seznam gostiteljev, vzorci poti, viri dokumentacije).
- NAVODILA.md: "Kako clan ustvari rundo", "Kako vodja odpove rundo", "Trasa in
  vdelave" z opombo o CSP frame-src.
- CHANGELOG.md: [0.7.0].

VPRASANJA
Cesar ne mores izvesti po tej nalogi, ne implementiraj po svoje: zapisi v PR pod
"Vprasanja za arhitekta" in nadaljuj z ostalim.
## Dopolnitev: dnevna omejitev novih rund (D38, razdelek 6.1)

Isti PR #7 in veja `feature/NALOGA-07-obrazec`, različica ostane 0.7.0.

1. V zavihku možnosti z ostalimi omejitvami dodaj `omejitev_runde_dan`,
   `type="list"`, vrednosti 3/5/10, privzeto 5, `filter="integer"`.
   Oznaka in opis sta v administratorjevih sl-SI in en-GB `.ini`.
2. V `ObrazecModel::dovoljenje()` preveri omejitev samo za `$id === 0`, takoj po
   članstvu, ter ponovno v `shrani()` tik pred `store()`. Koda je `omejitev`.
3. Štetje uporablja `SELECT COUNT(*) FROM #__belaunce_runde WHERE ustvaril_id =
   :uporabnik AND ustvarjeno >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)`.
   ID identitete veži z `bind()` in `ParameterType::INTEGER`. Gre za drsečih 24 ur
   po UTC uri baze; štejejo tudi odpovedane runde. Urejanje ni omejeno.
4. Gumb **Ustvari rundo** ostane članu viden. Ob doseženi meji obrazec preusmeri
   na seznam s kodo `omejitev`, ki je v zaprtem seznamu stanj in ima jasno
   prevedeno sporočilo z dejansko nastavljeno dovoljeno mejo.
5. V dnevnik samo koda in ID uporabnika, brez emaila.
6. V `ARCHITECTURE.md` opiši, kaj se šteje, zakaj štejejo tudi odpovedane in zakaj
   ne uporabljamo `#__belaunce_poskusi`. V `NAVODILA.md` navedi mesto nastavitve.
   V `CHANGELOG.md` dodaj vrstico pod 0.7.0.
7. Dodatni merili sta 18 in 19 zgoraj. Prvotno merilo regresije administracije,
   prijave in udeležbe je po dopolnitvi s popravkom seje ohranjeno kot 22.

## Popravek: nalaganje obrazca po uspešnem shranjevanju

`loadFormData()` obravnava vrednost iz seje kot `(array) (... ?: [])`, tudi ko jo
kontroler ali kdo drug počisti z `null`. Neprazen neuspešni vnos ima še naprej
prednost. Pri prazni seji nova runda dobi privzetke, obstoječa pa vsa vnosna polja
iz baze. Enaka normalizacija velja za seznam `.napake` v pogledu Obrazec in za
ostala branja podatkov obrazcev iz seje. Popravek ni v kontrolerju. Poleg privzetkov
je treba po merilu 21 preveriti ohranitev neobveznih podatkov runde.
