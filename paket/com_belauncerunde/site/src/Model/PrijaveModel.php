<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** Enotna pravila članske udeležbe za gumba na rundi in tok D40. */
class PrijaveModel extends BaseDatabaseModel
{
    /**
     * Preveri prijavo in nastavljeno člansko skupino, tudi pri neposrednem POST.
     *
     * @param  User  $uporabnik
     * @return  bool
     */
    public function jeClan(User $uporabnik): bool
    {
        $skupina = (int) ComponentHelper::getParams('com_belauncerunde')->get('skupina_clani', 0);

        return !$uporabnik->guest && $skupina > 0
            && \in_array($skupina, array_map('intval', $uporabnik->getAuthorisedGroups()), true);
    }

    /**
     * Potrdi udeležbo idempotentno; vodji nikoli ne vstavi vrstice (D54).
     *
     * @param  int  $rundaId
     * @param  User  $uporabnik
     * @return  string
     */
    public function pridem(int $rundaId, User $uporabnik): string
    {
        return $this->spremeniUdelezbo($rundaId, $uporabnik, true);
    }

    /**
     * Izbriše samo lastno prijavo; ponovljena odjava je prav tako uspešna.
     *
     * @param  int  $rundaId
     * @param  User  $uporabnik
     * @return  string
     */
    public function odjava(int $rundaId, User $uporabnik): string
    {
        return $this->spremeniUdelezbo($rundaId, $uporabnik, false);
    }

    /**
     * Vrne skupni SQL izraz brez poizvedbe; vzdevek je izključno SQL identifikator.
     *
     * @param  string  $vzdevek
     * @return  string
     */
    public function izrazStevilaUdelezencev(string $vzdevek): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $vzdevek)) {
            throw new \InvalidArgumentException('COM_BELAUNCERUNDE_MOJE_NAPAKA_VZDEVEK');
        }

        // D54: štejejo vrstice prijav, obstoječi vodja in gostje, brez branja imen ali kontaktov.
        $id = $this->getDatabase()->quoteName($vzdevek . '.id');
        $vodja = $this->getDatabase()->quoteName($vzdevek . '.vodja_id');

        return '(SELECT COUNT(*) FROM #__belaunce_prijave AS br_p WHERE br_p.runda_id = ' . $id . ')'
            . ' + CASE WHEN EXISTS (SELECT 1 FROM #__users AS br_u WHERE br_u.id = ' . $vodja . ') THEN 1 ELSE 0 END'
            . ' + (SELECT COUNT(*) FROM #__belaunce_gostje AS br_g WHERE br_g.runda_id = ' . $id . ')';
    }

    /**
     * Prebere skupno število z istim SQL izrazom kot članski seznam.
     *
     * @param  int  $rundaId
     * @return  int
     */
    public function steviloUdelezencev(int $rundaId): int
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()
            ->select($this->izrazStevilaUdelezencev('r'))
            ->from($baza->quoteName('#__belaunce_runde', 'r'))
            ->where('r.id = :runda')->where('r.stanje IN (1, 2)')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);

        return (int) $baza->setQuery($poizvedba)->loadResult();
    }

    /**
     * Imena bere samo članu; najprej vodja, nato prijave po času in ID-ju.
     *
     * @param  int  $rundaId
     * @param  User  $uporabnik
     * @return  array
     */
    public function udelezenci(int $rundaId, User $uporabnik): array
    {
        if (!$this->jeClan($uporabnik)) {
            return [];
        }

        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select($baza->quoteName('u.name', 'ime'))
            ->from($baza->quoteName('#__belaunce_runde', 'r'))
            ->join('LEFT', $baza->quoteName('#__users', 'u') . ' ON u.id = r.vodja_id')
            ->where('r.id = :runda')->where('r.stanje IN (1, 2)')->where('u.id IS NOT NULL')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);
        $vodja = $baza->setQuery($poizvedba)->loadObject();
        $seznam = [];

        if ($vodja !== null) {
            $seznam[] = (object) ['ime' => $vodja->ime, 'vodja' => true];
        }

        // LEFT JOIN ohrani prijavo tudi ob izbrisanem uporabniku, brez branja kontaktov.
        $poizvedba = $baza->createQuery()->select($baza->quoteName('u.name', 'ime'))
            ->from($baza->quoteName('#__belaunce_prijave', 'p'))
            ->join('LEFT', $baza->quoteName('#__users', 'u') . ' ON u.id = p.uporabnik_id')
            ->join('INNER', $baza->quoteName('#__belaunce_runde', 'r') . ' ON r.id = p.runda_id')
            ->where('p.runda_id = :runda')->where('r.stanje IN (1, 2)')
            ->order('p.ustvarjeno ASC, p.id ASC')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);

        foreach ($baza->setQuery($poizvedba)->loadObjectList() as $prijava) {
            $seznam[] = (object) ['ime' => (string) ($prijava->ime ?? ''), 'vodja' => false];
        }

        // Kontakta sploh ne beremo za navadnega člana; frontend nima izjeme za admina.
        $kontakt = !$uporabnik->guest && (int) $uporabnik->id > 0;
        $uporabnikId = (int) $uporabnik->id;
        $poizvedba = $baza->createQuery()->select('COUNT(*)')->from($baza->quoteName('#__belaunce_runde'))
            ->where('id = :runda')->where('vodja_id = :uporabnik')
            ->bind(':runda', $rundaId, ParameterType::INTEGER)->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);
        $kontakt = $kontakt && (int) $baza->setQuery($poizvedba)->loadResult() === 1;
        $poizvedba = $baza->createQuery()->select('g.ime')
            ->from($baza->quoteName('#__belaunce_gostje', 'g'))
            ->join('INNER', $baza->quoteName('#__belaunce_runde', 'r') . ' ON r.id = g.runda_id')
            ->where('g.runda_id = :runda')->where('r.stanje IN (1, 2)')
            ->order('g.ustvarjeno ASC, g.id ASC')->bind(':runda', $rundaId, ParameterType::INTEGER);

        if ($kontakt) {
            // Tudi zamenjava vodje med obema branjema ne sme razkriti kontakta prejšnjemu vodji.
            $vodjaTelefon = $uporabnikId;
            $vodjaEmail = $uporabnikId;
            $poizvedba->select('CASE WHEN r.vodja_id = :vodja_telefon THEN g.telefon ELSE NULL END AS telefon')
                ->select('CASE WHEN r.vodja_id = :vodja_email THEN g.email ELSE NULL END AS email')
                ->bind(':vodja_telefon', $vodjaTelefon, ParameterType::INTEGER)
                ->bind(':vodja_email', $vodjaEmail, ParameterType::INTEGER);
        }

        foreach ($baza->setQuery($poizvedba)->loadObjectList() as $gost) {
            $seznam[] = (object) [
                'ime' => $gost->ime, 'vodja' => false, 'gost' => true,
                'telefon' => $kontakt ? (string) ($gost->telefon ?? '') : '',
                'email' => $kontakt ? (string) ($gost->email ?? '') : '',
            ];
        }

        return $seznam;
    }

    /**
     * Preveri samo lastno udeležbo; ID uporabnika vedno pride iz Joomline identitete.
     *
     * @param  int  $rundaId
     * @param  User  $uporabnik
     * @return  bool
     */
    public function jePrijavljen(int $rundaId, User $uporabnik): bool
    {
        if (!$this->jeClan($uporabnik)) {
            return false;
        }

        $uporabnikId = (int) $uporabnik->id;
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select('COUNT(*)')
            ->from($baza->quoteName('#__belaunce_prijave'))
            ->where('runda_id = :runda')->where('uporabnik_id = :uporabnik')
            ->bind(':runda', $rundaId, ParameterType::INTEGER)
            ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);

        return (int) $baza->setQuery($poizvedba)->loadResult() > 0;
    }

    /** Prebere javno rundo in mejo štarta po isti UTC uri, kot jo uporablja zapis. */
    private function rundaZaUdelezbo(int $rundaId): ?object
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select('id, vodja_id, stanje, zacetek > UTC_TIMESTAMP() AS pred_startom')
            ->from($baza->quoteName('#__belaunce_runde'))
            ->where('id = :runda')->where('stanje IN (1, 2)')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);

        return $baza->setQuery($poizvedba)->loadObject() ?: null;
    }

    /** Preveri skupna pravila in vrne samo kodo iz zaprtega seznama stanj. */
    private function spremeniUdelezbo(int $rundaId, User $uporabnik, bool $prihod): string
    {
        if (!$this->jeClan($uporabnik)) {
            return 'ni_clan';
        }

        $runda = $this->rundaZaUdelezbo($rundaId);

        if ($runda === null) {
            return 'runda_ni_najdena';
        }

        if ((int) $runda->stanje !== 1 || !(bool) $runda->pred_startom) {
            return 'runda_zaprta';
        }

        // Vodja ostane udeležen brez prijave in se s svoje runde ne more odjaviti.
        $uporabnikId = (int) $uporabnik->id;

        if ((int) $runda->vodja_id === $uporabnikId) {
            return 'prijavljen';
        }

        $baza = $this->getDatabase();

        // Pravila preverimo tudi v samem zapisu: štart ali odpoved med branjem in pisanjem
        // ne smeta ustvariti prijave. UNIQUE in INSERT IGNORE rešita tudi sočasna klika.
        if ($prihod) {
            $sql = 'INSERT IGNORE INTO #__belaunce_prijave (runda_id, uporabnik_id, ustvarjeno)'
                . ' SELECT r.id, :uporabnik, UTC_TIMESTAMP() FROM #__belaunce_runde AS r'
                . ' WHERE r.id = :runda AND r.stanje = 1 AND r.zacetek > UTC_TIMESTAMP()'
                . ' AND r.vodja_id <> :vodja';
        } else {
            $sql = 'DELETE p FROM #__belaunce_prijave AS p'
                . ' INNER JOIN #__belaunce_runde AS r ON r.id = p.runda_id'
                . ' WHERE r.id = :runda AND p.uporabnik_id = :uporabnik'
                . ' AND r.stanje = 1 AND r.zacetek > UTC_TIMESTAMP() AND r.vodja_id <> :vodja';
        }

        $vodjaId = $uporabnikId;
        $poizvedba = $baza->createQuery()->setQuery($sql)
            ->bind(':runda', $rundaId, ParameterType::INTEGER)
            ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER)
            ->bind(':vodja', $vodjaId, ParameterType::INTEGER);
        $baza->setQuery($poizvedba)->execute();
        $spremenjeno = $baza->getAffectedRows();

        // Nič vrstic lahko pomeni ponovitev ali medtem zaprto rundo; ločimo obe možnosti.
        if ($spremenjeno === 0) {
            $runda = $this->rundaZaUdelezbo($rundaId);

            if ($runda === null) {
                return 'runda_ni_najdena';
            }

            if ((int) $runda->stanje !== 1 || !(bool) $runda->pred_startom) {
                return 'runda_zaprta';
            }

            if ((int) $runda->vodja_id === $uporabnikId) {
                return 'prijavljen';
            }
        }

        // Ponovitev iste zahteve je uspešna, vendar brez spremembe ne beležimo dodajanja ali odstranitve.
        if ($spremenjeno > 0) {
            Log::add(($prihod ? 'PRIJAVA_DODANA' : 'PRIJAVA_ODSTRANJENA')
                . ' runda_id=' . $rundaId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');

            if ($prihod) {
                // Pošlji šele po novi vrstici; napaka štetja ali pošte ne razveljavi prijave.
                try {
                    \Belaunce\Component\Belauncerunde\Administrator\Service\MailService::obvestiVodjoOPrijavi(
                        $rundaId, (string) $uporabnik->name, $this->steviloUdelezencev($rundaId), $uporabnikId
                    );
                } catch (\Throwable $napaka) {
                }
            }
        }

        return $prihod ? 'prijavljen' : 'odjavljen';
    }
}
