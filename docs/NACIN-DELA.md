# Način dela — pkg_belauncerunde (osnutek naloge 15)

Kompakten povzetek, kako teče projekt. Namenjen (a) novi seji arhitekta,
ki nadaljuje delo, (b) kot osnutek za nalogo 15 (povzetek metode za
prihodnje projekte).

## 1. Tri vloge

- **Matija** — lastnik projekta. Testira na DDEV, združuje PR-je, EDINI
  piše v `docs/osnova-projekta.md` (vir resnice; arhitekt pripravi
  paste-ready blok, Matija ga sam vnese in commita). Daje odgovore na
  odprta vprašanja in potrjuje odločitve.
- **Codex (ChatGPT, agent za implementacijo)** — implementira ENO
  nalogo naenkrat, v lastni veji (`feature/NALOGA-xx-kratko-ime`),
  commita, odpre PR v `develop`. Nima dostopa do Joomle/DDEV/baze; ne
  more zagnati kode. Dela STRIKTNO po `docs/osnova-projekta.md`,
  `docs/ARCHITECTURE.md`, `AGENTS.md` in prejeti specifikaciji; ob
  nejasnosti ali konfliktu ne ugiba, zapiše v PR pod "Vprašanja za
  arhitekta" in del izpusti.
- **Arhitekt (Claude)** — piše specifikacije, pregleduje PR-je, testira
  na DDEV in v brskalniku, pripravlja zaključno dokumentacijo. NIKOLI ne
  pusha v repo, NIKOLI ne piše produkcijske kode neposredno v repo
  (specifikacije in dokumentacija so edini "pisni" izdelek arhitekta
  proti repoju; produkcijsko kodo vedno pišeta Codex ali, redko,
  Matija).

## 2. Življenjski cikel ene naloge

1. **Specifikacija.** Arhitekt prebere vir resnice + obstoječo kodo,
   kjer naloga posega (ne ugiba Joomla 6 API — preveri jedro, če ni
   gotovo), napiše specifikacijo kot EN plain-text blok v code box
   (lažje kopiranje za Matijo → Codex). Specifikacija vsebuje: cilj,
   obseg (kaj NE spremeniti), natančne datoteke/metode/polja, varnost
   (CSRF/ACL/escape/bind — vedno), jezikovne nize (sl-SI + en-GB),
   merila sprejema (oštevilčena, preverljiva), ročni test za DDEV
   (korak za korakom), namestitveni ukazi, prostor za "Vprašanja za
   arhitekta/Opažanja".
2. **Predaja.** Matija pošlje specifikacijo Codexu (zunaj tega
   pogovora). Manjše vizualne/besedilne popravke (brez logike/varnosti/
   sheme) lahko Matija pošilja Codexu neposredno, brez vmesnega
   arhitektovega koraka — a vsak PR pred združitvijo gre skozi
   arhitekta.
3. **Implementacija.** Codex dela v svoji veji, odpre PR v `develop` z
   obveznimi razdelki (Kaj je narejeno, Spremenjene datoteke, Ročni
   test za Matijo, Vprašanja za arhitekta, Opažanja).
4. **Arhitektov pregled.** Vedno v tem vrstnem redu:
   a. Diff proti specifikaciji (`git diff develop...pr<N>`), preveri
      VSAKO merilo sprejema v kodi, ne samo diagonalno.
   b. `php -l` na vseh spremenjenih PHP datotekah.
   c. `xmllint` na spremenjenih XML (manifest, config, forms).
   d. `bash orodja/gradnja.sh` (preveri verzije, prepovedane vzorce,
      utf8mb4 v SQL).
   e. Pri vizualnih/runtime nalogah: test v brskalniku na DDEV (prek
      povezanega računalnika, če na voljo — Mailpit za maile, DevTools
      za DOM/computed styles, ker posnetki zaslona v tem okolju včasih
      timeoutajo; takrat uporabi `javascript_tool`/`read_page` namesto
      screenshota).
   f. Po pregledu arhitekt POSPRAVI lokalno vejo pregleda
      (`git checkout develop && git reset --hard origin/develop &&
      git branch -D pr<N>`) — nikoli ne pusha, nikoli ne pusti umazane
      veje.
5. **Test na DDEV.** Matija (ali arhitekt, če je povezan z računalnikom
   prek vgrajenega mostu) namesti novo verzijo in preveri ročni test iz
   specifikacije. Testni podatki in njihovo stanje po testu se
   zapišejo v SESSION.md (kaj je bilo spremenjeno, kaj NI bilo
   povrnjeno) — naslednja seja se na to lahko zanese.
6. **Merge.** Matija združi PR v `develop`, ko je zadovoljen. Arhitekt
   ne združuje PR-jev.
7. **Zaključna dokumentacija.** Arhitekt pripravi posodobljena
   `SESSION.md` (kje smo, kaj je odprto, naslednji korak) in `TASKS.md`
   (tabela nalog, vnos v "Končano" z datumom in povzetkom, backlog) kot
   **ZIP, ki se razširi v `docs/`** (vzpostavljen vzorec — Matija ZIP
   sam razširi in commita prek terminala; arhitekt ne pusha).

## 3. Vir resnice in odločitve (D-oznake)

`docs/osnova-projekta.md` je EDINI vir resnice. Vsaka nova ali
spremenjena odločitev dobi zaporedno oznako `D<n>` in se NAJPREJ zapiše
tja (ali pripravi kot paste-ready dodatek, ki ga Matija sam vnese), šele
nato v naloge in kodo. Arhitekt odločitev ne spreminja brez potrditve;
Codex jih ne spreminja nikoli.

Opaženo tveganje: odločitve, dogovorjene sredi pogovora (npr. D58, D59
pri nalogi 11), se lahko izgubijo, če jih Matija ne vnese takoj — zato
jih je smiselno vedno zbrati v enem paste-ready bloku ob zaključku
naloge, ne šele ob koncu projekta.

## 4. Jezik in osnovna pravila kode

Glej `AGENTS.md` za popoln seznam (slovenski ASCII identifikatorji,
brez zastarelih Joomla aliasov, brez jQuery, CSRF/ACL/escape/bind
povsod, `br-` predpona CSS, predloge se ne spreminjajo — D41). Ta
pravila se med sejami ne spreminjajo; arhitekt jih ob vsakem pregledu
preverja, ne samo ob prvi nalogi.

## 5. Kaj se je v praksi obneslo (lekcije)

- **Testiranje z resničnim izpisom, ne izolirano predpostavko.** Pri
  vizualnih/runtime nalogah (16, 20) je bil test v pravem brskalniku na
  DDEV (DOM, computed styles, Mailpit) bolj zanesljiv kot branje kode
  same — ujel je npr. manjkajoč CSS reset (naloga 16) in potrdil pravi
  tok maila (naloga 20).
- **Posnetki zaslona v tem okolju včasih timeoutajo** — nadomestek je
  `javascript_tool`/`read_page` (DOM, computed CSS, Mailpit REST API
  prek `fetch()`), ki je tudi hitrejši.
- **Vsaka sprememba sheme potrebuje idempotentno "ensure" logiko v
  `skript.php`** (naloga 16: Joomla ne zažene update SQL ponovno ob isti
  verziji) — preveriti z `information_schema`, ne samo z install SQL.
- **Zaznava spremembe prek internih `_`-lastnosti na Table objektu**
  (naloga 10) je čistejša kot primerjava stanja v kontrolerju — eno
  mesto za frontend in admin pot (D56).
- **Dnevnik nikoli ne vsebuje osebnih podatkov** (ime, email, telefon,
  IP) — ta disciplina se je izkazala kritična že pri nalogi 04 (najden
  privzeti zapisovalnik, ki je beležil IP) in je zdaj preverjena
  postavka pri VSAKEM pregledu, ki dotika `Log::add()`.
- **Cross-DB/multi-install odločitve (D60, D61) je treba potrditi
  EKSPLICITNO z Matijo**, preden se pišejo specifikacije, ki na njih
  temeljijo — tehnične predpostavke o infrastrukturi (ista MySQL
  instanca, isti uporabnik) niso nekaj, kar arhitekt lahko ugane.
- **Zaključna dokumentacija kot ZIP v `docs/`** je uveljavljen in dober
  vzorec: Matija ohrani nadzor nad commiti, arhitekt pa vedno pusti
  natančen, strukturiran zapis za naslednjo sejo (ne zanaša se na
  spomin med sejami).
