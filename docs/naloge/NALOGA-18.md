NALOGA 18 — Cross-DB AcyMailing adapter (poddomenska postavitev)

Veja:    feature/NALOGA-18-cross-db-acymailing  (iz NAJNOVEJŠEGA develop — vsebuje 0.16.0 / PR #16, merge 6f7477d)
Verzija: 0.17.0 — uskladi VSE: paket/pkg_belauncerunde.xml, paket/com_belauncerunde/belauncerunde.xml,
         paket/plg_authentication_belauncerunde/belauncerunde.xml, paket/plg_task_belauncerunde/belauncerunde.xml,
         posodobitve.xml, CHANGELOG.md
PR v:    develop
Odvisno: D60, D61 (poddomena = LOČENA Joomla namestitev; AcyMailing na ISTI MySQL instanci, ISTI DB uporabnik, pravica SELECT)
Pred delom preberi: docs/osnova-projekta.md, docs/ARCHITECTURE.md, AGENTS.md.
Ob nejasnosti ali konfliktu NE ugibaj — zapiši pod "Vprašanja za arhitekta" in del izpusti.

------------------------------------------------------------
CILJ
------------------------------------------------------------
com_belauncerunde bo tekel na LOČENI Joomla namestitvi (poddomena, D60).
AcyMailing ostane na glavni namestitvi -> DRUGA baza na isti MySQL
instanci, verjetno z DRUGO predpono tabel. AcymailingAdapter je že danes
EDINA točka, ki bere tabele #__acym_* (preverjeno: noben drug razred jih
ne bere neposredno). Adapter mora znati brati iz druge baze BREZ spremembe
javnega vmesnika — noben klicatelj se ne spremeni.

Privzeto (obe novi nastavitvi prazni) MORA biti vedenje IDENTIČNO
današnjemu. Brez regresije.

------------------------------------------------------------
OBSEG (samo to, nič drugega)
------------------------------------------------------------
1. Dve novi možnosti v administrator/config.xml (fieldset "acymailing").
2. Spremembe SAMO v
   paket/com_belauncerunde/administrator/src/Service/AcymailingAdapter.php
   (konstruktor in vse javne metode ohranijo ENAK podpis).
3. Jezikovni nizi za novi polji v
   paket/com_belauncerunde/administrator/language/sl-SI/com_belauncerunde.ini
   in .../en-GB/com_belauncerunde.ini.
4. docs/NAVODILA.md: kratek odstavek o nastavitvi za poddomensko postavitev.
5. CHANGELOG.md + uskladitev verzij na 0.17.0.

NE spreminjaj: javnega vmesnika adapterja; klicateljev
(site/src/Controller/PrijavaController.php,
administrator/src/Service/VzdrzevanjeService.php,
administrator/src/Model/PreverjanjeModel.php,
administrator/src/Field/AcymlistaField.php,
administrator/src/View/Preverjanje/HtmlView.php,
plg_authentication_belauncerunde/src/Extension/Belauncerunde.php);
ClanService; sheme baze; predlog (D41).

------------------------------------------------------------
NASTAVITVI (paket/com_belauncerunde/administrator/config.xml)
------------------------------------------------------------
V obstoječi <fieldset name="acymailing"> dodaj PRED polje "lista_clanov"
(vrstni red je pomemben: najprej vir, nato izbira list iz tega vira):

    <field
        name="acym_baza"
        type="text"
        label="COM_BELAUNCERUNDE_CONFIG_ACYM_BAZA_LABEL"
        description="COM_BELAUNCERUNDE_CONFIG_ACYM_BAZA_DESC"
        default=""
        filter="string"
    />
    <field
        name="acym_predpona"
        type="text"
        label="COM_BELAUNCERUNDE_CONFIG_ACYM_PREDPONA_LABEL"
        description="COM_BELAUNCERUNDE_CONFIG_ACYM_PREDPONA_DESC"
        default=""
        filter="string"
        showon="acym_baza!:"
    />

Pomen:
- acym_baza prazno -> AcyMailing v ISTI bazi (današnje vedenje).
- acym_baza nastavljena -> ime DRUGE baze na isti MySQL instanci;
  acym_predpona = predpona tabel TE namestitve (npr. "jos_").
- acym_baza nastavljena, acym_predpona prazna ali katerakoli z
  nedovoljenimi znaki -> AcyMailing šteje za NEDOSEGLJIV (ne ugibaj).

------------------------------------------------------------
SPREMEMBE V AcymailingAdapter.php
------------------------------------------------------------
a) Lastnosti + konstruktor (podpis NESPREMENJEN). Dodaj
   `use Joomla\CMS\Component\ComponentHelper;`.

   /** @var string Ime oddaljene baze AcyMailing; prazno = ista baza. */
   private string $acymBaza = '';

   /** @var string Predpona tabel oddaljene namestitve. */
   private string $acymPredpona = '';

   /** @var bool Oddaljena nastavitev je nepopolna ali neveljavna. */
   private bool $nastavitevNeveljavna = false;

   public function __construct(?DatabaseInterface $db = null)
   {
       $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);

       $parametri = ComponentHelper::getParams('com_belauncerunde');
       $baza      = trim((string) $parametri->get('acym_baza', ''));
       $predpona  = trim((string) $parametri->get('acym_predpona', ''));

       if ($baza !== '') {
           // Obrambna preverba poleg quoteName(): samo dovoljeni znaki.
           $this->nastavitevNeveljavna = !preg_match('/^[A-Za-z0-9_$]{1,64}$/', $baza)
               || !preg_match('/^[A-Za-z0-9_]{1,30}$/', $predpona);
       }

       $this->acymBaza     = $baza;
       $this->acymPredpona = $predpona;
   }

   (Prazna predpona ob nastavljeni bazi pade v drugi preg_match -> neveljavno.)

b) Nova zasebna metoda — EDINO mesto, ki sestavi ime tabele:

   /**
    * Sestavi kvalificirano in ubežano ime tabele AcyMailing. Pri lokalni
    * bazi uporabi predpono povezave (#__), pri oddaljeni bazi lastno
    * predpono, brez poseganja v povezavo.
    *
    * @param   string  $ime    Konstanta TABELA_* (z "#__").
    * @param   string  $alias  Neobvezen vzdevek.
    *
    * @return  string
    *
    * @since   0.17.0
    */
   private function tabela(string $ime, string $alias = ''): string
   {
       if ($this->acymBaza === '') {
           return $alias === ''
               ? $this->db->quoteName($ime)
               : $this->db->quoteName($ime, $alias);
       }

       $golo   = str_replace('#__', $this->acymPredpona, $ime);
       $tabela = $this->db->quoteName($this->acymBaza) . '.' . $this->db->quoteName($golo);

       return $alias === '' ? $tabela : $tabela . ' AS ' . $this->db->quoteName($alias);
   }

   (Lokalna veja vrne TOČNO isti niz kot danes — brez regresije.)

c) Zamenjaj VSAK klic, kjer je TABELA_* tarča poizvedbe (->from, ->join):
     $this->db->quoteName(self::TABELA_X)          -> $this->tabela(self::TABELA_X)
     $this->db->quoteName(self::TABELA_X, 'alias') -> $this->tabela(self::TABELA_X, 'alias')
   Mesta v trenutni datoteki (develop 6f7477d):
     seznamList()   — from TABELA_LISTE
     poisciClana()  — from TABELA_UPORABNIKI 'u', join TABELA_NAROCNINE 'l'
     preveriEmail() — from TABELA_UPORABNIKI 'u', LEFT join TABELA_NAROCNINE 'l'
     jeClan()       — from 'u', join 'l'
     poisciPoId()   — from TABELA_UPORABNIKI
     idjiClanov()   — from 'u', join 'l'
   Po zamenjavi v datoteki NE sme ostati noben quoteName(self::TABELA_...)
   razen v jeNamescen() lokalni veji (grep, naštej v PR).
   Stolpčnih referenc (quoteName('u.email'), quoteName('l.status') ...)
   NE spreminjaj.

d) jeNamescen() — obdrži obstoječi predpomnilnik $this->namescen za OBE veji:
   - acymBaza === '' -> NESPREMENJENA današnja logika (getTableList()
     + replacePrefix()).
   - nastavitevNeveljavna === true -> brez poizvedbe:
         Log::add('ACYM_NASTAVITEV_NEVELJAVNA', Log::WARNING, 'com_belauncerunde');
         return $this->namescen = false;
     (zabeleziNapako() tu NE uporabi — zahteva \Throwable. V dnevnik NE
     gre ime baze, predpona ali SQL.)
   - Sicer preveri obstoj VSEH treh tabel prek information_schema:

       $golaImena = [
           str_replace('#__', $this->acymPredpona, self::TABELA_UPORABNIKI),
           str_replace('#__', $this->acymPredpona, self::TABELA_NAROCNINE),
           str_replace('#__', $this->acymPredpona, self::TABELA_LISTE),
       ];
       $query = $this->db->createQuery()
           ->select('COUNT(*)')
           ->from($this->db->quoteName('information_schema.tables'))
           ->where($this->db->quoteName('table_schema') . ' = :baza')
           ->whereIn($this->db->quoteName('table_name'), $golaImena, ParameterType::STRING)
           ->bind(':baza', $this->acymBaza);
       $this->db->setQuery($query);

       return $this->namescen = ((int) $this->db->loadResult() === 3);

     Celoten blok v obstoječem try/catch -> zabeleziNapako(...) -> false.
     (Če whereIn + bind v isti poizvedbi povzroča težave, uporabi tri
     ločene COUNT poizvedbe — rezultat mora biti enak. Zapiši v PR.)

e) normalizirajEmail(), jeVeljavenEmail(), zabeleziNapako() — NESPREMENJENE.
   Vse metode ob jeNamescen()===false še naprej vrnejo obstoječe varne
   vrednosti ([], null, false, 'ACYM_NI_NAMESCEN').

f) Posodobi docblock razreda: omeni možnost oddaljene baze (D61) in da
   adapter tudi tam samo bere.

------------------------------------------------------------
VARNOST (vedno)
------------------------------------------------------------
- acym_baza / acym_predpona: preverba regex v konstruktorju + SAMO skozi
  quoteName() oz. str_replace() na konstanti. NIKOLI kot surov niz v SQL.
- Vse vrednosti v WHERE prek bind()/whereIn s tipom (kot doslej).
- Adapter ostaja READ-ONLY: noben INSERT/UPDATE/DELETE/REPLACE/ALTER/CREATE.
- Dnevnik: brez imena baze, predpone, SQL izpisa, emailov, imen, IP.
- Brez prepovedanih vzorcev (JFactory, JText, getInstance( ...) — gradnja.sh.

------------------------------------------------------------
JEZIKOVNI NIZI (administrator, .ini — za COM_BELAUNCERUNDE_CONFIG_FIELDSET_ACYMAILING)
------------------------------------------------------------
sl-SI:
COM_BELAUNCERUNDE_CONFIG_ACYM_BAZA_LABEL="Zunanja baza AcyMailing (neobvezno)"
COM_BELAUNCERUNDE_CONFIG_ACYM_BAZA_DESC="Pusti prazno, če je AcyMailing v isti bazi kot ta komponenta. Sicer vpiši ime druge baze na ISTI MySQL instanci, dostopne z istim uporabnikom (pravica SELECT). Po spremembi shrani in ponovno izberi listi spodaj."
COM_BELAUNCERUNDE_CONFIG_ACYM_PREDPONA_LABEL="Predpona tabel oddaljene namestitve"
COM_BELAUNCERUNDE_CONFIG_ACYM_PREDPONA_DESC="Obvezno, če je vpisana zunanja baza (npr. jos_). Predpona tabel druge Joomla namestitve; ni nujno enaka predponi te baze."

en-GB:
COM_BELAUNCERUNDE_CONFIG_ACYM_BAZA_LABEL="Remote AcyMailing database (optional)"
COM_BELAUNCERUNDE_CONFIG_ACYM_BAZA_DESC="Leave empty if AcyMailing lives in the same database as this component. Otherwise enter the name of another database on the SAME MySQL instance, reachable with the same user (SELECT privilege). After changing it, save and re-select both lists below."
COM_BELAUNCERUNDE_CONFIG_ACYM_PREDPONA_LABEL="Remote installation table prefix"
COM_BELAUNCERUNDE_CONFIG_ACYM_PREDPONA_DESC="Required if a remote database is set (e.g. jos_). Table prefix of the other Joomla installation; not necessarily the same as this database's prefix."

------------------------------------------------------------
NAVODILA.md (kratek odstavek)
------------------------------------------------------------
- Kdaj: komponenta na ločeni namestitvi (poddomena), AcyMailing na glavni.
- Pogoj: ista MySQL instanca; DB uporabnik poddomene ima SELECT na
  AcyMailing bazi (npr. GRANT SELECT ON glavna_baza.* TO 'uporabnik'@'host').
- Postopek: vpiši bazo in predpono -> Shrani -> ponovno izberi "Lista
  članov" in "Lista rund" (ID-ji list prihajajo iz oddaljene baze) ->
  Shrani -> preveri v Preverjanje.

------------------------------------------------------------
MERILA SPREJEMA
------------------------------------------------------------
1.  Obe polji prazni -> vedenje IDENTIČNO današnjemu (magic-link prijava
    člana, AcymlistaField, Preverjanje, dnevno usklajevanje skupine "Člani").
    Lokalna veja tabela() vrne isti niz kot prej.
2.  acym_baza nastavljena, acym_predpona prazna -> jeNamescen()=false brez
    poizvedbe; odvisne metode vrnejo varne vrednosti; dnevnik:
    ACYM_NASTAVITEV_NEVELJAVNA (brez imena baze/SQL).
3.  acym_baza z nedovoljenimi znaki (npr. "x y" ali "a`b") -> enako kot 2.
4.  Obe polji nastavljeni, tabele obstajajo -> jeNamescen()=true; vse
    bralne metode in AcymlistaField berejo iz TE baze.
5.  Obe polji nastavljeni, tabel ni -> jeNamescen()=false, brez izjeme navzven.
6.  V adapterju ni zapisovalnega SQL (grep po diffu).
7.  V diffu ni nobenega klicatelja adapterja ne ClanService.
8.  V adapterju ni več quoteName(self::TABELA_...) izven lokalne veje
    tabela()/jeNamescen().
9.  Verzija 0.17.0 povsod; gradnja.sh uspe (zažene arhitekt).

------------------------------------------------------------
ROČNI TEST (DDEV)
------------------------------------------------------------
Priprava simulirane oddaljene baze na isti DDEV MariaDB instanci:
cd ~/joomla-dev
ddev mysql -uroot -proot -e "CREATE DATABASE oddaljena_test;"
ddev mysql -uroot -proot -e "CREATE TABLE oddaljena_test.test_acym_user AS SELECT * FROM db.dipxn_acym_user;"
ddev mysql -uroot -proot -e "CREATE TABLE oddaljena_test.test_acym_user_has_list AS SELECT * FROM db.dipxn_acym_user_has_list;"
ddev mysql -uroot -proot -e "CREATE TABLE oddaljena_test.test_acym_list AS SELECT * FROM db.dipxn_acym_list;"
ddev mysql -uroot -proot -e "GRANT SELECT ON oddaljena_test.* TO 'db'@'%'; FLUSH PRIVILEGES;"

1. Preverjanje za matija.trcek@gmail.com (pred spremembo) -> CLAN.
2. Nastavitve -> Zunanja baza = oddaljena_test, Predpona = test_ -> Shrani.
   Spustna seznama list se napolnita (iz kopije), izbrani listi ostaneta.
3. Preverjanje za istega člana -> CLAN.
4. Dokaz branja iz oddaljene baze:
   ddev mysql -uroot -proot -e "DELETE FROM oddaljena_test.test_acym_user_has_list WHERE user_id=238;"
   -> Preverjanje za istega člana NI več CLAN (NI_NA_LISTI).
5. Predpona na prazno (baza ostane) -> Preverjanje: ACYM_NI_NAMESCEN,
   spustna seznama: "AcyMailing ni najden".
6. Baza = "x y", predpona test_ -> ACYM_NI_NAMESCEN, brez napake na strani.
7. Baza = "neobstojeca", predpona test_ -> ACYM_NI_NAMESCEN.
8. Obe polji prazni -> Preverjanje spet CLAN (lokalne dipxn_acym_*).
9. Magic-link prijava člana 634 deluje (obe polji prazni).
10. Dnevnik: ddev exec tail -n 20 administrator/logs/com_belauncerunde.php
    -> ACYM_NASTAVITEV_NEVELJAVNA, brez imena baze, predpone, SQL, emailov.
11. Pospravi: ddev mysql -uroot -proot -e "DROP DATABASE oddaljena_test;"

------------------------------------------------------------
NAMESTITEV NA DDEV
------------------------------------------------------------
Pred združitvijo (veja PR):
cd ~/belaunce-runde && git fetch origin && git checkout feature/NALOGA-18-cross-db-acymailing && git pull && bash orodja/gradnja.sh
ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1)
cp "$ZIP" ~/joomla-dev/tmp/
cd ~/joomla-dev
ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"

Po združitvi (develop):
cd ~/belaunce-runde && git checkout develop && git pull && bash orodja/gradnja.sh
ZIP=$(ls -t dist/pkg_belauncerunde-*.zip | head -1)
cp "$ZIP" ~/joomla-dev/tmp/
cd ~/joomla-dev
ddev exec php cli/joomla.php extension:install --path="/var/www/html/tmp/$(basename "$ZIP")"

------------------------------------------------------------
PR — obvezni razdelki
------------------------------------------------------------
Kaj je narejeno / Spremenjene datoteke / Ročni test / Vprašanja za
arhitekta / Opažanja.
V "Vprašanja": če bi gostujoči MySQL omejil information_schema za tuje
baze, predlagaj alternativo (SELECT 1 FROM baza.tabela LIMIT 1 v
try/catch) — NE implementiraj je brez potrditve.
