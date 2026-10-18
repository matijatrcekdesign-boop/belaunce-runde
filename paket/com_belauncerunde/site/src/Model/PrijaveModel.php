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
    /** Preveri prijavo in nastavljeno člansko skupino, tudi pri neposrednem POST. */
    public function jeClan(User $uporabnik): bool
    {
        $skupina = (int) ComponentHelper::getParams('com_belauncerunde')->get('skupina_clani', 0);

        return !$uporabnik->guest && $skupina > 0
            && \in_array($skupina, array_map('intval', $uporabnik->getAuthorisedGroups()), true);
    }

    /** Potrdi udeležbo idempotentno; vodji nikoli ne vstavi vrstice (D54). */
    public function pridem(int $rundaId, User $uporabnik): string
    {
        return $this->spremeniUdelezbo($rundaId, $uporabnik, true);
    }

    /** Izbriše samo lastno prijavo; ponovljena odjava je prav tako uspešna. */
    public function odjava(int $rundaId, User $uporabnik): string
    {
        return $this->spremeniUdelezbo($rundaId, $uporabnik, false);
    }

    /** Vrne število prijav in obstoječega vodje; naloga 08 tu prišteje goste. */
    public function steviloUdelezencev(int $rundaId): int
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()
            ->select('(SELECT COUNT(*) FROM #__belaunce_prijave AS p WHERE p.runda_id = r.id)'
                . ' + CASE WHEN u.id IS NULL THEN 0 ELSE 1 END')
            ->from($baza->quoteName('#__belaunce_runde', 'r'))
            ->join('LEFT', $baza->quoteName('#__users', 'u') . ' ON u.id = r.vodja_id')
            ->where('r.id = :runda')->where('r.stanje IN (1, 2)')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);

        return (int) $baza->setQuery($poizvedba)->loadResult();
    }

    /** Imena bere samo članu; najprej vodja, nato prijave po času in ID-ju. */
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

        return $seznam;
    }

    /** Preveri samo lastno udeležbo; ID uporabnika vedno pride iz Joomline identitete. */
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

        if (!$runda || (int) $runda->stanje !== 1 || !(bool) $runda->pred_startom) {
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

            if (!$runda || (int) $runda->stanje !== 1 || !(bool) $runda->pred_startom) {
                return 'runda_zaprta';
            }

            if ((int) $runda->vodja_id === $uporabnikId) {
                return 'prijavljen';
            }
        }

        Log::add(($prihod ? 'PRIJAVA_DODANA' : 'PRIJAVA_ODSTRANJENA')
            . ' runda_id=' . $rundaId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');

        return $prihod ? 'prijavljen' : 'odjavljen';
    }
}
