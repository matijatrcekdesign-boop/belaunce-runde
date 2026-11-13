# NALOGA 24 — Push obvestila (faza 2)

Veja:    feature/NALOGA-24-push  (iz najnovejšega develop)
Verzija: 0.20.0 — uskladi VSE: paket/pkg_belauncerunde.xml, paket/com_belauncerunde/belauncerunde.xml,
         paket/plg_authentication_belauncerunde/belauncerunde.xml, paket/plg_task_belauncerunde/belauncerunde.xml,
         posodobitve.xml, CHANGELOG.md
PR v:    develop
Odvisno: D62 (PWA brez push v v1 → zdaj faza 2), D63 (zasebnost obvestila vodji), D56/D57 (sprožilci
         sprememba/odpoved, vodja dobi kopijo), D54 (vodja ni v #__belaunce_prijave), D41 (predloga se ne spreminja),
         naloga 19 (SW, PwaHelper), naloga 20 (obvestilo vodji ob prijavi), naloga 12 (plg_task)
Status:  PRIPRAVLJENO ZA IZVAJALCA (Codex). Odločitve na koncu so sprejete po predlogu arhitekta.

------------------------------------------------------------
CILJ
------------------------------------------------------------
Član, ki je na svoji napravi vklopil obvestila, prejme sistemsko obvestilo
(tudi ko aplikacija ni odprta) ob treh dogodkih:

  1. NOVA PRIJAVA  → vodji runde (član ali gost se je prijavil na njegovo rundo)
  2. ODPOVED / SPREMEMBA runde → prijavljenim članom + vodji
  3. OPOMNIK pred štartom → prijavljenim članom + vodji (Joomla Scheduler)

Dotik obvestila odpre stran runde v aplikaciji.

Push je DODATEK k emailom, ne zamenjava: vsi obstoječi maili (naloge 10, 20)
ostanejo nespremenjeni in delujejo neodvisno. Gostje push NE dobijo (nimajo
računa); zanje ostane email.

------------------------------------------------------------
OBSEG (samo to, nič drugega)
------------------------------------------------------------
1. VAPID ključi: samodejno ustvarjeni ob namestitvi, shranjeni v Možnostih.
2. Tabela naročnin `#__belaunce_push` + stolpec `opomnik_poslan` v `#__belaunce_runde`.
3. `Administrator\Service\PushService` — lastna implementacija Web Push
   (RFC 8291 aes128gcm + RFC 8292 VAPID ES256) s PHP openssl, BREZ composer knjižnic.
4. Site kontroler `PushController` (naroci, odjavi) — JSON, CSRF, samo prijavljeni člani.
5. SW: `push` + `notificationclick` v `sw-vsebina.js`.
6. UI v "Moje runde": kartica "Obvestila na tej napravi" + `media/js/obvestila.js`.
7. Sprožilci 1–3 (glej spodaj).
8. Vzdrževanje: čiščenje mrtvih/osirotelih naročnin v obstoječem opravilu.
9. Možnosti (nov zavihek "Obvestila"), jezikovni nizi sl-SI + en-GB, NAVODILA.md, CHANGELOG.

NE delaj: push za goste, push ob novi rundi (glej ODPRTE ODLOČITVE), uporabniških
nastavitev po dogodkih (en vklop na napravo), čakalne vrste/asinhronega pošiljanja,
predpomnjenja v SW, sprememb predloge (D41), sprememb obstoječih mailov.

------------------------------------------------------------
1. VAPID KLJUČI
------------------------------------------------------------
- `skript.php` (postflight install IN update): če `push_vapid_javni` ali
  `push_vapid_zasebni` v params komponente manjka, ustvari par P-256:
    openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC])
  Shrani:
    push_vapid_javni   = base64url(0x04 || X(32) || Y(32))   — 65 B nestisnjena točka
    push_vapid_zasebni = base64url(d(32))                    — 32 B
  (X, Y, d iz `openssl_pkey_get_details()['ec']`, levo podloženi z \0 na 32 B.)
  Obstoječih ključev NIKOLI ne prepiši (nova ključa = vse naročnine neveljavne).
  Shranjevanje params: preberi obstoječe, dodaj ključa, zapiši nazaj (ne povozi drugih nastavitev).
- `config.xml`, nov fieldset `obvestila`:
    push_vklop          radio  default 1   "Push obvestila"
    push_nova_prijava   radio  default 1   "Ob novi prijavi (vodji)"
    push_sprememba      radio  default 1   "Ob odpovedi/spremembi runde"
    push_opomnik        radio  default 1   "Opomnik pred štartom"
    push_opomnik_ur     list   default 2   (1, 2, 3, 6, 12, 24) "Koliko ur pred štartom"
    push_vapid_subject  text   default ""  "Kontakt (mailto: ali https:)" — prazno → "mailto:" + Global Config mailfrom
    push_vapid_javni    text   readonly="true"  (prikaz; potreben tudi za ohranitev ob shranjevanju)
    push_vapid_zasebni  hidden                  (MORA biti v obrazcu, sicer ga shranjevanje Možnosti pobriše)
  Opis pri javnem ključu: "Ustvarjen samodejno. Ne spreminjaj — sprememba izklopi obvestila na vseh napravah."
- Validacija ob uporabi: če ključ ni veljaven base64url ustrezne dolžine (65/32 B) → push tiho
  onemogočen, dnevnik `PUSH_KLJUC_NEVELJAVEN` (brez vrednosti ključa).
- Ključi NIKOLI v repozitoriju, dnevniku ali na frontendu (na frontend gre samo javni ključ).

------------------------------------------------------------
2. BAZA
------------------------------------------------------------
Nova datoteka `administrator/sql/updates/mysql/0.20.0.sql` IN dopolnjen `install.mysql.utf8.sql`
(+ `uninstall` / odstranitev po vzoru naloge 14: tabela se briše samo pri ohrani_podatke=0).

CREATE TABLE IF NOT EXISTS `#__belaunce_push` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uporabnik_id` INT NOT NULL,
  `endpoint` VARCHAR(2048) NOT NULL,
  `endpoint_hash` CHAR(64) NOT NULL,          -- hash('sha256', endpoint); edinstven ključ naprave
  `p256dh` VARCHAR(128) NOT NULL,             -- base64url, 65 B ključ naprave
  `auth` VARCHAR(48) NOT NULL,                -- base64url, 16 B
  `ustvarjeno` DATETIME NOT NULL,
  `zadnji_uspeh` DATETIME NULL,
  `napake` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_endpoint_hash` (`endpoint_hash`),
  KEY `idx_uporabnik` (`uporabnik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__belaunce_runde` ADD COLUMN `opomnik_poslan` DATETIME NULL DEFAULT NULL;
  (MariaDB 10.11: brez `IF NOT EXISTS` pri ADD COLUMN ne zanašaj se na idempotenco SQL datoteke —
   zagotovi stolpec idempotentno v `skript.php` po vzoru stolpca `barva` iz naloge 16.)

Zasebnost: NE hranimo IP-ja, user-agenta, imena naprave ali lokacije. Endpoint je naslov
push storitve (FCM/Apple/Mozilla), ne osebni podatek o napravi, a se nikoli ne izpiše in ne
gre v dnevnik (v dnevnik samo `id` naročnine in `uporabnik_id`).

------------------------------------------------------------
3. PushService — pošiljanje (Administrator\Service\PushService)
------------------------------------------------------------
Brez zunanjih knjižnic (minishlink/web-push vleče 5+ composer paketov — ne v paket).
Samo PHP openssl + hash_hkdf + Joomla HttpFactory. PHP ≥ 8.1.

Javni vmesnik (statične metode, po vzoru MailService):
  PushService::obvestiUporabnike(array $uporabnikIdji, array $sporocilo, int $izpusti = 0): int
      // vrne število uspešno poslanih; $izpusti = uporabnik, ki je dogodek sprožil (ne dobi pusha)
  $sporocilo = ['naslov' => string, 'besedilo' => string, 'url' => string (relativen, /runde/...), 'oznaka' => 'runda-{id}']

Postopek za vsako naročnino:
 a) Payload = json_encode(['title','body','url','tag']) ≤ 3000 B (skrajšaj besedilo, ne JSON).
 b) Šifriranje RFC 8291 (aes128gcm):
      ephemeral par P-256 (openssl_pkey_new), salt = random_bytes(16)
      ecdh = openssl_pkey_derive(javni_kljuc_naprave, ephemeral_zasebni, 32)
      ikm  = hash_hkdf('sha256', ecdh, 32, "WebPush: info\0" . ua_public . as_public, auth)
      cek  = hash_hkdf('sha256', ikm, 16, "Content-Encoding: aes128gcm\0", salt)
      nonce= hash_hkdf('sha256', ikm, 12, "Content-Encoding: nonce\0", salt)
      ct   = openssl_encrypt(payload . "\x02", 'aes-128-gcm', cek, OPENSSL_RAW_DATA, nonce, $tag)
      telo = salt . pack('N', 4096) . chr(65) . as_public(ephemeral) . ct . tag
    Surovi ključ (65 B) → PEM: SubjectPublicKeyInfo DER s predpono
      3059301306072a8648ce3d020106082a8648ce3d030107034200 . točka
    Zasebni VAPID (32 B d) → PEM: SEC1 "EC PRIVATE KEY" DER
      30770201010420 . d . a00a06082a8648ce3d030107a144034200 . javna_točka
 c) VAPID (RFC 8292): JWT {"typ":"JWT","alg":"ES256"}.{"aud": scheme://host endpointa,
      "exp": now+12h, "sub": push_vapid_subject}; podpis openssl_sign(SHA256) vrne DER →
      pretvori v surovih 64 B (r||s, vsak levo podložen na 32 B).
 d) POST na endpoint, glave:
      Content-Type: application/octet-stream
      Content-Encoding: aes128gcm
      TTL: 86400 (opomnik: do štarta, min 60)
      Urgency: high (odpoved, opomnik) / normal (ostalo)
      Topic: oznaka brez nedovoljenih znakov (≤32, [A-Za-z0-9_-]) — nadomesti starejše obvestilo iste runde
      Authorization: vapid t=<JWT>, k=<push_vapid_javni>
    Časovna omejitev zahteve 4 s. Joomla HttpFactory (ne curl neposredno).
 e) Odziv: 201/202 → `zadnji_uspeh`=UTC zdaj, `napake`=0.
           404 ali 410 → izbriši naročnino (naprava je odjavljena).
           drugo / izjema → `napake`+1; pri napake ≥ 5 izbriši.
    Dnevnik: `PUSH_POSLANO dogodek=.. prejemnikov=n uspesnih=m` (INFO), `PUSH_NAPAKA id=.. koda=..` (WARNING).
    Nikoli endpoint, ključi, payload ali ime v dnevnik.
 f) Vse ovito v try/catch: napaka pusha NIKOLI ne prekine prijave, shranjevanja runde ali maila.
 g) Če push_vklop=0, ključi neveljavni ali seznam prazen → takoj vrni 0 (brez poizvedb na endpoint).

Razvojni test (NI del paketa): `orodja/test-webpush.php` — šifriranje z injiciranim
ephemeral ključem in soljo mora dati natanko testni vektor iz RFC 8291, Dodatek A
(ciphertext del "…_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN").
Zato naj šifrirna metoda sprejme neobvezna parametra (ephemeral ključ, salt) samo za test.
Skripta preveri še VAPID podpis (DER→surovi→DER + openssl_verify).
Arhitekt je ta postopek že preveril s prototipom (PHP 8.3) — ujema se.

------------------------------------------------------------
4. NAROČNINA — Site\Controller\PushController
------------------------------------------------------------
Naloge (samo POST kot navaden obrazec — `URLSearchParams`, NE JSON telo; odgovor JSON,
`$app->close()` po vzoru PwaController):
  task=push.naroci   polja: endpoint, p256dh, auth, <žeton>=1
  task=push.odjavi   polja: endpoint, <žeton>=1
Pravila:
- CSRF: `$this->checkToken()` (standardno, žeton iz `Joomla.getOptions('csrf.token')` kot ime polja
  z vrednostjo 1). Neveljaven žeton → JSON `{ok:false}` s kodo 403, ne preusmeritev.
- naroci: samo prijavljen uporabnik v skupini "Člani" (skupina_clani) ALI vodja katerekoli runde.
  Sicer 403 `{ok:false}`.
- Validacija: endpoint `https://`, ≤ 2048 znakov, host ni IP in ni localhost (preprečimo SSRF:
  strežnik bo POSTal na ta naslov); p256dh dekodiran = 65 B in se začne z 0x04; auth = 16 B.
- Upsert po `endpoint_hash`: če naprava že obstaja pod drugim uporabnikom → prevzame jo trenutni
  (ena naprava = en lastnik). Največ 10 naročnin na uporabnika (najstarejša se izbriše).
- odjavi: izbriše samo naročnino trenutnega uporabnika z ujemajočim hash-om.
- Dnevnik: `PUSH_NAROCEN id=.. uporabnik_id=..`, `PUSH_ODJAVLJEN ...`.

------------------------------------------------------------
5. SERVICE WORKER — media/js/sw-vsebina.js
------------------------------------------------------------
Obstoječa `fetch` logika ostane NESPREMENJENA. `CACHE` → 'br-pwa-v2' (sprememba bajtov sproži posodobitev SW).
Dodaj:
  self.addEventListener('push', (e) => {
    let p = {};
    try { p = e.data ? e.data.json() : {}; } catch (x) {}
    const naslov = p.title || 'Runde Belaunce';
    e.waitUntil(self.registration.showNotification(naslov, {
      body: p.body || '',
      tag: p.tag || undefined,
      renotify: !!p.tag,
      icon: '/media/com_belauncerunde/images/icon-192.png',
      badge: '/media/com_belauncerunde/images/icon-192.png',
      data: { url: p.url || '/runde' }
    }));
  });
  self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    // Odpremo samo naslov na istem izvoru (obvestilo nikoli ne preusmeri drugam).
    const cilj = new URL((e.notification.data && e.notification.data.url) || '/runde', self.location.origin);
    if (cilj.origin !== self.location.origin) { return; }
    e.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((okna) => {
      for (const o of okna) { if ('focus' in o) { o.navigate(cilj.href); return o.focus(); } }
      return clients.openWindow(cilj.href);
    }));
  });
  (Opomba: iOS zahteva, da vsak `push` dogodek prikaže obvestilo — brez tihih pushov.)

------------------------------------------------------------
6. UI — "Moje runde" (site/tmpl/moje/default.php) + media/js/obvestila.js
------------------------------------------------------------
Kartica `.br-obvestila` na vrhu pogleda Moje runde (samo prijavljen član/vodja; ista pravica kot naroci).
Stanja (JS določi ob nalaganju; brez JS kartica ostane skrita):
  A) Ni podpore in iOS v brskalniku (ne standalone):
     "Obvestila na iPhonu delujejo, ko aplikacijo dodaš na začetni zaslon: Deli → Dodaj na
      začetni zaslon. Nato jo odpri z ikone in tukaj vklopi obvestila." (iOS 16.4+)
  B) Ni podpore drugje: kartica skrita.
  C) Notification.permission === 'denied': "Obvestila so v nastavitvah brskalnika/telefona
     zavrnjena. Omogoči jih tam." (brez gumba)
  D) Podprto, ni naročnine: gumb [Vklopi obvestila] (btn-secondary).
  E) Naročen: "Obvestila so vklopljena na tej napravi." + gumb [Izklopi] (btn-outline).
Vklop (SAMO na klik — iOS/Chrome zahtevata uporabniško gesto):
  Notification.requestPermission() → registration.pushManager.subscribe({userVisibleOnly:true,
  applicationServerKey: base64url→Uint8Array(javni ključ)}) → POST push.naroci → stanje E.
  Napaka strežnika → subscription.unsubscribe() in sporočilo "Vklop ni uspel, poskusi znova."
Izklop: POST push.odjavi → subscription.unsubscribe() → stanje D.
Ob vsakem nalaganju: če obstaja naročnina v brskalniku, jo tiho ponovno pošlji na push.naroci
(osveži ključe po menjavi naprave/uporabnika; idempotentno).
Podatki v JS prek `addScriptOptions('com_belauncerunde.push', ['kljuc' => javni, 'naroci' => Route URL,
'odjavi' => Route URL])`, nizi prek `Text::script` (ne trdo kodirano — lekcija naloge 22).
Registracija `com_belauncerunde.obvestila` v joomla.asset.json (odvisnost: core). Slog v site.css, predpona `br-`.
SW registrira že `namesti.js` — obvestila.js počaka `navigator.serviceWorker.ready`.

------------------------------------------------------------
7. SPROŽILCI
------------------------------------------------------------
Besedila v jezikovnih datotekah SITE (sl-SI, en-GB). Pri pošiljanju iz administracije
naloži site jezik: `Factory::getLanguage()->load('com_belauncerunde', JPATH_SITE)`.
URL: `Route::link('site', 'index.php?option=com_belauncerunde&view=runda&id=' . $id, false)`
→ relativna pot (SEF), tako kot v mailih.

7.1 NOVA PRIJAVA → vodja   (push_nova_prijava)
  Mesto: tik ob obstoječih klicih `MailService::obvestiVodjoOPrijavi()` v
  `PrijaveModel::spremeniUdelezbo` (samo $prihod && $spremenjeno>0) in `GostModel::prijava`
  (samo ob uspešnem vstavku). Ločen try/catch, NEODVISEN od mail_nova_prijava.
  Vodja ne dobi pusha o lastni prijavi (enako kot mail).
  Besedilo:  naslov "Nova prijava: {NASLOV}"
             besedilo "{DATUM} ob {URA} · prijavljenih: {N}"
  BREZ imena udeleženca (glej ODPRTE ODLOČITVE, točka 2 — obvestilo je vidno na zaklenjenem zaslonu).

7.2 ODPOVED / SPREMEMBA → prijavljeni člani + vodja   (push_sprememba)
  Mesto: `MailService::poZapisu()` — PO tem, ko prebere in ponastavi zastavice, pred/ob `poslji()`
  pokliči `PushService::poZapisu($rundaId, $odpoved, $polja, $prvotni)`. Ista točka za frontend
  in admin (D56). Sprožilna polja = ista kot za mail (`mail_sprozilna_polja`).
  Prejemniki: uporabnik_id iz `#__belaunce_prijave` + vodja_id (D54, D57); $izpusti = uporabnik,
  ki je shranil (vodja ne dobi pusha o lastni spremembi — mail kopijo po D57 še vedno dobi).
  Besedilo odpoved:  "Odpovedano: {NASLOV}" / "Runda {DAN} {DATUM} ob {URA} je odpovedana."
  Besedilo sprememba: "Sprememba: {NASLOV}" / "Novo: {DATUM} ob {URA}, {LOKACIJA}"
  Ob spremembi `zacetek` ponastavi `opomnik_poslan = NULL` (v RundaTable::store, kjer se že zaznava
  sprememba), da opomnik velja za novi čas.

7.3 OPOMNIK → prijavljeni člani + vodja   (push_opomnik, push_opomnik_ur)
  Nova rutina v `plg_task_belauncerunde`: `belaunce_opomnik` (ločena od belaunce_vzdrzevanje,
  ker teče pogosteje; admin jo nastavi npr. na vsakih 15 min).
  Izbor: stanje=1 AND opomnik_poslan IS NULL AND zacetek > UTC_NOW
         AND zacetek <= UTC_NOW + push_opomnik_ur ur.
  Za vsako rundo NAJPREJ označi `opomnik_poslan = UTC zdaj` (UPDATE ... WHERE opomnik_poslan IS NULL,
  preveri affectedRows=1 — prepreči dvojno pošiljanje ob vzporednem zagonu), nato pošlji.
  Največ 20 rund na zagon. TTL = sekunde do štarta (min 60).
  Besedilo: "Kmalu štart: {NASLOV}" / "Danes ob {URA}, {LOKACIJA}"  (če ni isti dan: "{DAN} ob {URA}, …")
  Runda, ustvarjena že znotraj okna (npr. 1 h pred štartom), dobi opomnik ob naslednjem zagonu — sprejemljivo.
  Opomba za produkcijo: Scheduler mora teči s pravim cronom (že na kontrolnem seznamu v TASKS.md);
  "lazy scheduler" ob obiskih je za opomnik nezanesljiv.

------------------------------------------------------------
8. VZDRŽEVANJE (obstoječe opravilo belaunce_vzdrzevanje, nov neodvisen del v svojem try/catch)
------------------------------------------------------------
- Izbriši naročnine uporabnikov, ki ne obstajajo več (NOT EXISTS #__users).
- Izbriši naročnine uporabnikov, ki niso več v skupini "Člani" IN niso vodja prihodnje runde.
- Izbriši naročnine brez uspeha 180 dni (zadnji_uspeh < zdaj-180d ali NULL in ustvarjeno < zdaj-180d).
- Dnevnik: `PUSH_CISCENJE izbrisanih=n`.
Članov se pri tem NIKOLI ne blokira in ne spreminja skupin (D52, D53) — briše se samo vrstice naročnin.

------------------------------------------------------------
MERILA SPREJEMA
------------------------------------------------------------
 1. `bash orodja/gradnja.sh` uspe; `php orodja/test-webpush.php` izpiše UJEMA za RFC 8291 in OK za VAPID.
 2. Sveža namestitev IN posodobitev 0.19.0→0.20.0: ključa ustvarjena, tabela in stolpec obstajata, utf8mb4.
 3. Ponovna posodobitev (0.20.0→0.20.0) ključev NE spremeni.
 4. Shranjevanje Možnosti (katerikoli zavihek) ključev NE pobriše.
 5. Moje runde, desktop Chrome (DDEV https): stanje D → Vklopi → dovoljenje → stanje E; vrstica v tabeli.
 6. Izklopi → vrstica izbrisana, stanje D. Ponoven obisk po vklopu ne podvoji vrstice.
 7. naroci kot gost / brez žetona / z http:// ali localhost endpointom → zavrnjeno, brez vrstice.
 8. Nova prijava člana na rundo → vodja dobi push; ponovitev/odjava → brez pusha; vodjev lasten vpis → brez.
 9. Prijava gosta → vodja dobi push; gost nič.
10. Sprememba sprožilnega polja → prijavljeni + vodja (razen tistega, ki je shranil); nesprožilno polje → nič.
11. Odpoved → prijavljeni + vodja; mail se pošlje enako kot prej (regresija naloge 10).
12. Dotik obvestila odpre pravo rundo (odprta aplikacija se fokusira in preusmeri; zaprta se odpre).
13. Opomnik: runda čez <2 h → en push ob zagonu opravila; drugi zagon → nič; sprememba začetka → ponovno.
14. Odpovedana runda ne dobi opomnika.
15. Mrtva naročnina (ročno pokvarjen endpoint → 404/410) se izbriše; napaka ne prekine ostalih prejemnikov
    ne shranjevanja runde.
16. push_vklop=0 → nobenega pusha, maili delujejo.
17. Dnevnik: brez endpointov, ključev, imen in emailov.
18. iOS (produkcija, iPhone, iOS ≥16.4): v Safariju stanje A; kot nameščena PWA vklop in prejem obvestila.
    (Na DDEV ni izvedljivo — preverita arhitekt in Matija na produkciji, kot merilo 14 naloge 11.)
19. Android Chrome (produkcija): vklop, prejem, dotik.
20. NAVODILA.md: "Obvestila" (član: kako vklopi, iPhone pogoj; admin: Možnosti, Scheduler rutina opomnika, cron).

------------------------------------------------------------
ROČNI TEST ZA MATIJO / ARHITEKTA (DDEV)
------------------------------------------------------------
- Desktop Chrome na https://joomla-dev.ddev.site (mkcert certifikat mora biti zaupan, sicer SW ne deluje).
- Dva brskalniška profila: profil A = vodja (naročen), profil B = član, ki se prijavi.
- Opomnik: System → Scheduled Tasks → nova rutina "Belaunce – opomnik" → Run Test.
- Mrtva naročnina: v bazi spremeni endpoint naročnine na `https://fcm.googleapis.com/fcm/send/neveljaven` → sproži dogodek.

------------------------------------------------------------
SPREJETE ODLOČITVE (veljajo za to nalogo)
------------------------------------------------------------
1. Brez zunanje knjižnice: lasten PushService (openssl + hash_hkdf), preverjen s testnim vektorjem
   RFC 8291. minishlink/web-push in drugi composer paketi NE gredo v paket.
2. Push vodji ob novi prijavi NE vsebuje imena udeleženca (vidno na zaklenjenem zaslonu);
   samo naslov runde, datum/uro in število prijavljenih.
3. Kdor rundo spremeni ali odpove sam (vodja ali admin), push o tem NE dobi (mail kopijo po D57 še vedno).
4. En opomnik, privzeto 2 uri pred štartom (nastavljivo 1/2/3/6/12/24 ur).
5. Push ob NOVI RUNDI NI v 0.20.0 (kasneje, z ločenim vklopom na napravi).
6. Implementira Codex prek PR v develop; arhitekt pregleda, zažene gradnjo in
   `php orodja/test-webpush.php` ter testira na DDEV; Matija združi.

Predlog novih odločitev za `osnova-projekta.md` (vpiše Matija, izvajalec jih NE vpisuje):
  D64 — Push brez zunanjih knjižnic; ključi VAPID samodejno ob namestitvi, nikoli v gitu, dnevniku ali na frontendu
        (na frontend gre samo javni ključ).
  D65 — Push nikoli ne vsebuje osebnih podatkov udeležencev (imena, kontakti); samo javni podatki runde
        in število prijavljenih.
  D66 — Push je dodatek k mailu; napaka pusha nikoli ne vpliva na prijavo, shranjevanje runde ali mail.
