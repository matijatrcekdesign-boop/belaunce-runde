<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/** Povezuje potrjenega AcyMailing člana z varnim Joomlinim članskim računom. */
class ClanService
{
    /** @var DatabaseInterface Povezava do baze. */
    private DatabaseInterface $db;

    /** @var Registry Možnosti komponente. */
    private Registry $params;

    /** @var UserFactoryInterface Tovarna Joomlinih uporabnikov. */
    private UserFactoryInterface $tovarna;

    /**
     * Pripravi odvisnosti za povezavo, ustvarjanje in zagotavljanje skupine člana.
     *
     * @param  ?DatabaseInterface  $db
     * @param  ?Registry  $params
     * @param  ?UserFactoryInterface  $tovarna
     */
    public function __construct(?DatabaseInterface $db = null, ?Registry $params = null, ?UserFactoryInterface $tovarna = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
        $this->params = $params ?: ComponentHelper::getParams('com_belauncerunde');
        $this->tovarna = $tovarna ?: Factory::getContainer()->get(UserFactoryInterface::class);
    }

    /**
     * Uskladi samo skupino Člani in vrne zaprto kodo dejanske spremembe.
     *
     * @param  int  $uporabnikId
     * @param  bool  $najBoClan
     * @return  string
     */
    public function uskladiSkupino(int $uporabnikId, bool $najBoClan): string
    {
        $skupinaClani = (int) $this->params->get('skupina_clani', 0);

        if ($skupinaClani <= 0 || $uporabnikId <= 0) {
            throw new \RuntimeException('COM_BELAUNCERUNDE_NAPAKA_SKUPINA');
        }

        $uporabnik = $this->tovarna->loadUserById($uporabnikId);

        if ((int) $uporabnik->id !== $uporabnikId) {
            throw new \RuntimeException('COM_BELAUNCERUNDE_NAPAKA_UPORABNIK');
        }

        // Adminovo blokado pustimo povsem pri miru, tudi če je skupina že napačna.
        if ((int) $uporabnik->block === 1) {
            return 'PRESKOCENO_BLOKIRAN';
        }

        $skupine = array_map('intval', (array) $uporabnik->groups);
        $jeClan = \in_array($skupinaClani, $skupine, true);

        if ($jeClan === $najBoClan) {
            return 'BREZ_SPREMEMBE';
        }

        // Joomla Table\User::store() primerja ID-je s ključi polja, zato jih ohranimo.
        $noveSkupine = $najBoClan
            ? array_values(array_unique([...$skupine, $skupinaClani]))
            : array_values(array_filter($skupine, static fn (int $id): bool => $id !== $skupinaClani));
        $uporabnik->groups = array_combine($noveSkupine, $noveSkupine) ?: [];

        if (!$uporabnik->save()) {
            throw new \RuntimeException('COM_BELAUNCERUNDE_NAPAKA_SHRANJEVANJE');
        }

        if (!$najBoClan && $noveSkupine === []) {
            // Jedro 6.1 pri praznem groups preskoči zapis mapiranja; odstranimo samo našo vrstico.
            $db = $this->db;
            $poizvedba = $db->createQuery()->delete($db->quoteName('#__user_usergroup_map'))
                ->where($db->quoteName('user_id') . ' = :uporabnik')
                ->where($db->quoteName('group_id') . ' = :skupina')
                ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER)
                ->bind(':skupina', $skupinaClani, ParameterType::INTEGER);
            $db->setQuery($poizvedba)->execute();

            if ($db->getAffectedRows() !== 1) {
                return 'BREZ_SPREMEMBE';
            }
        }

        return $najBoClan ? 'VRNJENA' : 'ODVZETA';
    }

    /**
     * Vrne ID računa člana; klicatelj mora predhodno ponovno preveriti članstvo.
     *
     * @param  object  $acymZapis
     * @return  int
     */
    public function zagotoviUporabnika(object $acymZapis): int
    {
        $db = $this->db;
        $acymId = (int) $acymZapis->acym_id;
        $email = mb_strtolower(trim((string) $acymZapis->email));
        $skupinaClani = (int) $this->params->get('skupina_clani', 0);
        $poizvedba = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('id') . ' = :skupina')
            ->bind(':skupina', $skupinaClani, ParameterType::INTEGER);

        if ($skupinaClani <= 0 || !(int) $db->setQuery($poizvedba)->loadResult()) {
            $this->napaka('COM_BELAUNCERUNDE_NAPAKA_SKUPINA', $acymId);
        }

        // 1. Naši povezavi po AcyMailing ID zaupamo tudi po odvzemu skupine Člani.
        $poizvedba = $db->createQuery()->select('*')->from($db->quoteName('#__belaunce_clani'))
            ->where($db->quoteName('acym_uporabnik_id') . ' = :acym')
            ->bind(':acym', $acymId, ParameterType::INTEGER);
        $povezava = $db->setQuery($poizvedba)->loadObject();

        if ($povezava) {
            $uporabnikId = (int) $povezava->uporabnik_id;
            $uporabnik = $this->tovarna->loadUserById($uporabnikId);

            if ((int) $uporabnik->id > 0) {
                return $this->preveriUporabnika($uporabnik, $skupinaClani, $acymId, true);
            }

            $poizvedba = $db->createQuery()->delete($db->quoteName('#__belaunce_clani'))
                ->where($db->quoteName('uporabnik_id') . ' = :uporabnik')
                ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);
            $db->setQuery($poizvedba)->execute();
            Log::add('POVEZAVA_ZASTARELA acym_id=' . $acymId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');
        }

        // 2. Ob spremenjenem AcyMailing ID se po emailu išče izključno med člani.
        $poizvedba = $db->createQuery()->select($db->quoteName('u.id'))
            ->from($db->quoteName('#__users', 'u'))
            ->join('INNER', $db->quoteName('#__user_usergroup_map', 'm') . ' ON ' . $db->quoteName('m.user_id') . ' = ' . $db->quoteName('u.id'))
            ->where($db->quoteName('m.group_id') . ' = :skupina')
            ->where('LOWER(' . $db->quoteName('u.email') . ') = :email')
            ->bind(':skupina', $skupinaClani, ParameterType::INTEGER)
            ->bind(':email', $email, ParameterType::STRING);
        $uporabnikId = (int) $db->setQuery($poizvedba, 0, 1)->loadResult();

        if ($uporabnikId > 0) {
            $this->shraniPovezavo($uporabnikId, $acymId);
            Log::add('POVEZAVA_PO_EMAILU acym_id=' . $acymId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');

            return $this->preveriUporabnika($this->tovarna->loadUserById($uporabnikId), $skupinaClani, $acymId);
        }

        // 3. Zaseden email ali username zunaj skupine ne sme ustvariti druge identitete.
        $uporabniskoIme = $email;
        $poizvedba = $db->createQuery()->select($db->quoteName('u.id'))
            ->from($db->quoteName('#__users', 'u'))
            ->where('(LOWER(' . $db->quoteName('u.email') . ') = :email OR LOWER(' . $db->quoteName('u.username') . ') = :ime)')
            ->where('NOT EXISTS (SELECT 1 FROM ' . $db->quoteName('#__user_usergroup_map', 'm')
                . ' WHERE ' . $db->quoteName('m.user_id') . ' = ' . $db->quoteName('u.id')
                . ' AND ' . $db->quoteName('m.group_id') . ' = :skupina)')
            ->bind(':email', $email, ParameterType::STRING)
            ->bind(':ime', $uporabniskoIme, ParameterType::STRING)
            ->bind(':skupina', $skupinaClani, ParameterType::INTEGER);

        if ((int) $db->setQuery($poizvedba, 0, 1)->loadResult() > 0) {
            $this->napaka('COM_BELAUNCERUNDE_NAPAKA_EMAIL_ZASEDEN', $acymId);
        }

        // 4. User::bind() ob praznem geslu ustvari neznano naključno geslo (D49).
        $ime = trim((string) $acymZapis->ime);
        $ime = $ime !== '' ? $ime : explode('@', $email, 2)[0];
        $uporabnik = $this->tovarna->loadUserById(0);

        // bind() sprejme polje po referenci, zato mora biti vhod poimenovana spremenljivka.
        $podatkiUporabnika = [
            'name' => $ime, 'username' => $email, 'email' => $email,
            'password' => '', 'password2' => '', 'groups' => [$skupinaClani],
            'activation' => '', 'sendEmail' => 0, 'requireReset' => 0,
        ];

        if (!$uporabnik->bind($podatkiUporabnika) || !$uporabnik->save()) {
            // Napaka računa lahko vsebuje osebne podatke, zato uporabimo zaprt prevodni ključ.
            $koda = 'COM_BELAUNCERUNDE_NAPAKA_SHRANJEVANJE';
            $this->napaka($koda, $acymId);
        }

        $uporabnikId = (int) $uporabnik->id;
        $this->shraniPovezavo($uporabnikId, $acymId);
        Log::add('UPORABNIK_USTVARJEN acym_id=' . $acymId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');

        // 5. Tudi prva uspešna prijava posodobi zadnja_prijava v UTC.
        return $this->preveriUporabnika($uporabnik, $skupinaClani, $acymId);
    }

    /**
     * Zavrne blokado in zagotovi skupino samo na podlagi naše obstoječe povezave.
     *
     * @param   User  $uporabnik        Joomla uporabnik.
     * @param   int   $skupinaClani     ID skupine Člani.
     * @param   int   $acymId           ID potrjenega AcyMailing člana.
     * @param   bool  $zaupanaPovezava  Ali je račun najden prek naše povezovalne tabele.
     *
     * @return  int  ID uporabnika po posodobitvi časa prijave.
     */
    private function preveriUporabnika(User $uporabnik, int $skupinaClani, int $acymId, bool $zaupanaPovezava = false): int
    {
        $uporabnikId = (int) $uporabnik->id;

        // Adminove namerne blokade ne spreminjamo in pred zavrnitvijo ne vračamo skupine.
        if ((int) $uporabnik->block === 1) {
            $this->napaka('COM_BELAUNCERUNDE_NAPAKA_BLOKIRAN', $acymId, $uporabnikId);
        }

        if (!\in_array($skupinaClani, array_map('intval', $uporabnik->groups), true)) {
            // Iskanje po emailu samo po sebi nikoli ne sme podeliti članstva tujemu računu.
            if (!$zaupanaPovezava) {
                $this->napaka('COM_BELAUNCERUNDE_NAPAKA_EMAIL_ZASEDEN', $acymId, $uporabnikId);
            }

            // Prijava in vzdrževanje uporabljata isto pot; napaka prijave ostane ista.
            try {
                $rezultat = $this->uskladiSkupino($uporabnikId, true);
            } catch (\Throwable $napaka) {
                $this->napaka('COM_BELAUNCERUNDE_NAPAKA_SHRANJEVANJE', $acymId, $uporabnikId);
            }

            if ($rezultat !== 'VRNJENA') {
                $this->napaka('COM_BELAUNCERUNDE_NAPAKA_SHRANJEVANJE', $acymId, $uporabnikId);
            }

            Log::add('SKUPINA_VRNJENA acym_id=' . $acymId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');
        }

        $zdaj = gmdate('Y-m-d H:i:s');
        $db = $this->db;
        $poizvedba = $db->createQuery()->update($db->quoteName('#__belaunce_clani'))
            ->set($db->quoteName('zadnja_prijava') . ' = :zdaj')
            ->where($db->quoteName('uporabnik_id') . ' = :uporabnik')
            ->bind(':zdaj', $zdaj, ParameterType::STRING)
            ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);
        $db->setQuery($poizvedba)->execute();

        return $uporabnikId;
    }

    /** Ustvari povezavo ali posodobi AcyMailing ID obstoječega članskega računa. */
    private function shraniPovezavo(int $uporabnikId, int $acymId): void
    {
        $db = $this->db;
        $poizvedba = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__belaunce_clani'))
            ->where($db->quoteName('uporabnik_id') . ' = :uporabnik')
            ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);
        $obstaja = (int) $db->setQuery($poizvedba)->loadResult() > 0;
        $zdaj = gmdate('Y-m-d H:i:s');
        $poizvedba = $db->createQuery();

        if ($obstaja) {
            $poizvedba->update($db->quoteName('#__belaunce_clani'))
                ->set($db->quoteName('acym_uporabnik_id') . ' = :acym')
                ->where($db->quoteName('uporabnik_id') . ' = :uporabnik');
        } else {
            $poizvedba->insert($db->quoteName('#__belaunce_clani'))
                ->columns($db->quoteName(['uporabnik_id', 'acym_uporabnik_id', 'ustvarjeno']))
                ->values(':uporabnik, :acym, :zdaj')
                ->bind(':zdaj', $zdaj, ParameterType::STRING);
        }

        $poizvedba->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER)
            ->bind(':acym', $acymId, ParameterType::INTEGER);
        $db->setQuery($poizvedba)->execute();
    }

    /** Zabeleži samo kodo in ID-je ter vrže izjemo z jezikovnim ključem. */
    private function napaka(string $koda, int $acymId, int $uporabnikId = 0): void
    {
        Log::add($koda . ' acym_id=' . $acymId . ' uporabnik_id=' . $uporabnikId, Log::WARNING, 'com_belauncerunde');
        throw new \RuntimeException($koda);
    }
}
