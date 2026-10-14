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

    /** Pripravi odvisnosti za povezavo, ustvarjanje in odblokiranje člana. */
    public function __construct(?DatabaseInterface $db = null, ?Registry $params = null, ?UserFactoryInterface $tovarna = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
        $this->params = $params ?: ComponentHelper::getParams('com_belauncerunde');
        $this->tovarna = $tovarna ?: Factory::getContainer()->get(UserFactoryInterface::class);
    }

    /** Vrne ID računa člana; klicatelj mora predhodno ponovno preveriti članstvo. */
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

        // 1. Povezava po AcyMailing ID ima prednost; tuji račun se vedno zavrne.
        $poizvedba = $db->createQuery()->select('*')->from($db->quoteName('#__belaunce_clani'))
            ->where($db->quoteName('acym_uporabnik_id') . ' = :acym')
            ->bind(':acym', $acymId, ParameterType::INTEGER);
        $povezava = $db->setQuery($poizvedba)->loadObject();

        if ($povezava) {
            $uporabnikId = (int) $povezava->uporabnik_id;
            $uporabnik = $this->tovarna->loadUserById($uporabnikId);

            if ((int) $uporabnik->id > 0) {
                return $this->preveriUporabnika($uporabnik, $skupinaClani, $acymId);
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
            'block' => 0, 'activation' => '', 'sendEmail' => 0, 'requireReset' => 0,
        ];

        if (!$uporabnik->bind($podatkiUporabnika) || !$uporabnik->save()) {
            // getError() lahko vsebuje email ali SQL; surovega besedila zato ne beležimo.
            $koda = $uporabnik->getError() ? 'COM_BELAUNCERUNDE_NAPAKA_SHRANJEVANJE' : 'COM_BELAUNCERUNDE_NAPAKA_UPORABNIK';
            $this->napaka($koda, $acymId);
        }

        $uporabnikId = (int) $uporabnik->id;
        $this->shraniPovezavo($uporabnikId, $acymId);
        Log::add('UPORABNIK_USTVARJEN acym_id=' . $acymId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');

        // 5. Tudi prva uspešna prijava posodobi zadnja_prijava v UTC.
        return $this->preveriUporabnika($uporabnik, $skupinaClani, $acymId);
    }

    /** Zavrne tujo skupino, odblokira člana in zabeleži čas zadnje prijave. */
    private function preveriUporabnika(User $uporabnik, int $skupinaClani, int $acymId): int
    {
        $uporabnikId = (int) $uporabnik->id;

        if (!\in_array($skupinaClani, array_map('intval', $uporabnik->groups), true)) {
            $this->napaka('COM_BELAUNCERUNDE_NAPAKA_NI_CLANSKI_RACUN', $acymId, $uporabnikId);
        }

        if ((int) $uporabnik->block === 1) {
            $uporabnik->block = 0;

            if (!$uporabnik->save()) {
                $this->napaka('COM_BELAUNCERUNDE_NAPAKA_SHRANJEVANJE', $acymId, $uporabnikId);
            }

            Log::add('UPORABNIK_ODBLOKIRAN acym_id=' . $acymId . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');
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
