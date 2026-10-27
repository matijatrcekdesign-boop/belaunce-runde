NALOGA 21 — Izbira AcyMailing liste se ne izgubi, ko AcyMailing ni dosegljiv

Veja:    feature/NALOGA-21-ohrani-listo  (iz NAJNOVEJŠEGA develop — vsebuje 0.17.0 / PR #17, merge 7538ba8)
Verzija: 0.17.1 — uskladi VSE: paket/pkg_belauncerunde.xml, paket/com_belauncerunde/belauncerunde.xml,
         paket/plg_authentication_belauncerunde/belauncerunde.xml, paket/plg_task_belauncerunde/belauncerunde.xml,
         posodobitve.xml, CHANGELOG.md
PR v:    develop
Odvisno: naloga 18 (D61). Najdeno med testom naloge 18 na DDEV.
Pred delom preberi: docs/osnova-projekta.md, docs/ARCHITECTURE.md, AGENTS.md.
Ob nejasnosti ali konfliktu NE ugibaj — zapiši pod "Vprašanja za arhitekta" in del izpusti.

------------------------------------------------------------
TEŽAVA (potrjeno na DDEV)
------------------------------------------------------------
AcymlistaField (polji "lista_clanov" in "lista_runde" v Možnostih) ob
nedosegljivem AcyMailingu ponudi SAMO možnost 0 "AcyMailing ni najden".
Če admin v tem stanju shrani Možnosti (npr. po tipkarski napaki v
"Zunanja baza AcyMailing"), se obe izbrani listi tiho ponastavita na 0.
Enako se zgodi, če shranjeni ID liste ni med prebranimi listami (npr. po
menjavi baze). Posledica: prijava članov ne deluje, dokler admin list ne
izbere znova (sinhronizacija se varno preskoči — ni uničujoče, a je past).

------------------------------------------------------------
CILJ
------------------------------------------------------------
Shranjena vrednost (ID > 0) se NIKOLI ne izgubi samo zato, ker je
trenutno ni med možnostmi. Polje jo prikaže kot izbrano možnost z jasno
oznako, da trenutno ni na voljo; shranjevanje jo ohrani nespremenjeno.

------------------------------------------------------------
OBSEG (samo to, nič drugega)
------------------------------------------------------------
1. paket/com_belauncerunde/administrator/src/Field/AcymlistaField.php — samo getOptions().
2. En nov jezikovni niz (administrator, sl-SI + en-GB).
3. CHANGELOG.md + verzija 0.17.1.

NE spreminjaj: AcymailingAdapter, config.xml, drugih klicateljev, sheme,
predlog (D41).

------------------------------------------------------------
SPREMEMBA — AcymlistaField::getOptions()
------------------------------------------------------------
Na koncu (v OBEH vejah — AcyMailing nedosegljiv IN dosegljiv) preveri
trenutno vrednost polja in jo po potrebi dodaj:

    $trenutna = (int) $this->value;

    if ($trenutna > 0) {
        $obstaja = false;

        foreach ($options as $option) {
            if ((int) $option->value === $trenutna) {
                $obstaja = true;
                break;
            }
        }

        if (!$obstaja) {
            // Shranjena lista ostane izbrana, tudi ko je trenutno ni mogoče prebrati.
            $options[] = HTMLHelper::_(
                'select.option',
                $trenutna,
                Text::sprintf('COM_BELAUNCERUNDE_ACYM_LISTA_NEDOSEGLJIVA', $trenutna)
            );
        }
    }

    return array_merge(parent::getOptions(), $options);

Za to restrukturiraj metodo tako, da obe veji gradita isti $options in
imata EN return na koncu (nedosegljiva veja: $options = [0 => "AcyMailing
ni najden"]; dosegljiva: obstoječa logika). Obstoječe besedilo in vrstni
red možnosti ostaneta enaka. Vrednost primerjaj kot int (ne kot niz).

Opomba: $this->value je že prebrana shranjena vrednost (Joomla jo nastavi
pred getOptions()); ID se izpiše samo kot celo število v Text::sprintf
s %d — brez dodatnega escape-a ni tveganja, ListField sam escapira besedilo.

------------------------------------------------------------
JEZIKOVNI NIZ (administrator/language/*/com_belauncerunde.ini)
------------------------------------------------------------
sl-SI:
COM_BELAUNCERUNDE_ACYM_LISTA_NEDOSEGLJIVA="ID %d – trenutno ni na voljo (ohranjeno)"

en-GB:
COM_BELAUNCERUNDE_ACYM_LISTA_NEDOSEGLJIVA="ID %d – currently unavailable (kept)"

------------------------------------------------------------
MERILA SPREJEMA
------------------------------------------------------------
1.  AcyMailing dosegljiv, shranjena lista med listami → polje IDENTIČNO kot
    prej (iste možnosti, isti vrstni red, nič dodatnega).
2.  AcyMailing NEdosegljiv, shranjena lista 6 → možnosti: "AcyMailing ni
    najden" (0) + "ID 6 – trenutno ni na voljo (ohranjeno)", izbrana je 6.
    Shrani → v nastavitvah ostane 6.
3.  AcyMailing dosegljiv, shranjeni ID ne obstaja med listami (npr. 999) →
    dodana možnost "ID 999 – trenutno ni na voljo (ohranjeno)", izbrana.
4.  Shranjena vrednost 0 → brez dodatne možnosti (vedenje kot prej).
5.  Admin lahko kadarkoli ročno izbere 0 ali drugo listo in shrani — izbira
    se upošteva.
6.  En sam return v getOptions(); v diffu le AcymlistaField, jezikovni
    datoteki, manifesti, posodobitve.xml, CHANGELOG.
7.  gradnja.sh uspe (zažene arhitekt).

------------------------------------------------------------
ROČNI TEST (DDEV)
------------------------------------------------------------
1. Možnosti → AcyMailing: Lista članov = Člani (ID 6), Lista Runde = ID 9,
   obe polji zunanje baze prazni → spustna seznama kot prej (merilo 1).
2. Zunanja baza = "neobstojeca", Predpona = "test_" → Shrani.
   Spustna seznama: "AcyMailing ni najden" + "ID 6 – trenutno ni na voljo
   (ohranjeno)" (izbrana) oz. "ID 9 – …" (izbrana) (merilo 2).
3. Še enkrat Shrani (brez spremembe) → obe izbiri ostaneta 6 in 9.
4. Zunanja baza in Predpona nazaj na prazno → Shrani → listi sta spet
   "Člani (ID 6)" in "Runde - lista za app (ID 9)", Preveri člana za
   matija.trcek@gmail.com → Član (merilo 1, brez izgube).
5. Merilo 3 (neobvezno, prek baze):
   ddev mysql -uroot -proot -e "UPDATE db.dipxn_extensions SET params=JSON_SET(params,'$.lista_runde','999') WHERE element='com_belauncerunde';"
   → Možnosti → Lista Runde: "ID 999 – trenutno ni na voljo (ohranjeno)".
   Nato v Možnostih ročno izberi "Runde - lista za app (ID 9)" → Shrani (merilo 5).

------------------------------------------------------------
NAMESTITEV NA DDEV
------------------------------------------------------------
Pred združitvijo (veja PR):
cd ~/belaunce-runde && git fetch origin && git checkout feature/NALOGA-21-ohrani-listo && git pull && bash orodja/gradnja.sh
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
