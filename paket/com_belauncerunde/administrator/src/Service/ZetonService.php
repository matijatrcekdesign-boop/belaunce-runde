<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/** Izdaja in enkratno porabi žetone; v bazo zapiše samo SHA-256. */
class ZetonService
{
    /** @var DatabaseInterface Povezava do baze. */
    private DatabaseInterface $db;

    /** @var Registry Možnosti komponente. */
    private Registry $params;

    /** Pripravi povezavo in nastavitve veljavnosti. */
    public function __construct(?DatabaseInterface $db = null, ?Registry $params = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
        $this->params = $params ?: ComponentHelper::getParams('com_belauncerunde');
    }

    /** Razveljavi stare povezave člana in vrne nov 256-bitni surovi žeton. */
    public function ustvari(int $acymId, ?int $rundaId, string $ip): string
    {
        $db = $this->db;
        $zdaj = gmdate('Y-m-d H:i:s');
        $poizvedba = $db->createQuery()->update($db->quoteName('#__belaunce_zetoni'))
            ->set($db->quoteName('porabljen') . ' = :zdaj')
            ->where($db->quoteName('acym_uporabnik_id') . ' = :acym')
            ->where($db->quoteName('porabljen') . ' IS NULL')
            ->bind(':zdaj', $zdaj, ParameterType::STRING)
            ->bind(':acym', $acymId, ParameterType::INTEGER);
        $db->setQuery($poizvedba)->execute();

        // Naključnost varuje povezavo, hash pa omogoča indeksirano iskanje brez surovega žetona.
        $surovi = bin2hex(random_bytes(32));
        $hash = hash('sha256', $surovi);
        $potece = gmdate('Y-m-d H:i:s', time() + (int) $this->params->get('veljavnost_min', 15) * 60);
        $poizvedba = $db->createQuery()->insert($db->quoteName('#__belaunce_zetoni'))
            ->columns($db->quoteName(['acym_uporabnik_id', 'zeton_hash', 'runda_id', 'potece', 'ip', 'ustvarjeno']))
            ->values(':acym, :hash, :runda, :potece, :ip, :zdaj')
            ->bind(':acym', $acymId, ParameterType::INTEGER)
            ->bind(':hash', $hash, ParameterType::STRING)
            ->bind(':runda', $rundaId, $rundaId === null ? ParameterType::NULL : ParameterType::INTEGER)
            ->bind(':potece', $potece, ParameterType::STRING)
            ->bind(':ip', $ip, ParameterType::STRING)
            ->bind(':zdaj', $zdaj, ParameterType::STRING);
        $db->setQuery($poizvedba)->execute();

        return $surovi;
    }

    /** Prebere veljaven žeton za GET, ne da bi ga porabil. */
    public function preveri(string $surovi): ?object
    {
        if (!preg_match('/^[0-9a-f]{64}$/D', $surovi)) {
            return null;
        }

        $hash = hash('sha256', $surovi);
        $zdaj = gmdate('Y-m-d H:i:s');
        $db = $this->db;
        $poizvedba = $db->createQuery()->select('*')->from($db->quoteName('#__belaunce_zetoni'))
            ->where($db->quoteName('zeton_hash') . ' = :hash')
            ->where($db->quoteName('porabljen') . ' IS NULL')
            ->where($db->quoteName('potece') . ' > :zdaj')
            ->bind(':hash', $hash, ParameterType::STRING)
            ->bind(':zdaj', $zdaj, ParameterType::STRING);

        return $db->setQuery($poizvedba)->loadObject() ?: null;
    }

    /** Atomarno porabi žeton; samo ena sočasna zahteva lahko spremeni eno vrstico. */
    public function porabi(string $surovi): ?object
    {
        if (!preg_match('/^[0-9a-f]{64}$/D', $surovi)) {
            return null;
        }

        $hash = hash('sha256', $surovi);
        $porabljen = gmdate('Y-m-d H:i:s');
        $zdaj = $porabljen;
        $db = $this->db;
        $poizvedba = $db->createQuery()->update($db->quoteName('#__belaunce_zetoni'))
            ->set($db->quoteName('porabljen') . ' = :porabljen')
            ->where($db->quoteName('zeton_hash') . ' = :hash')
            ->where($db->quoteName('porabljen') . ' IS NULL')
            ->where($db->quoteName('potece') . ' > :zdaj')
            ->bind(':porabljen', $porabljen, ParameterType::STRING)
            ->bind(':hash', $hash, ParameterType::STRING)
            ->bind(':zdaj', $zdaj, ParameterType::STRING);
        $db->setQuery($poizvedba)->execute();

        if ($db->getAffectedRows() !== 1) {
            return null;
        }

        // Vrstico beremo šele po uspešnem UPDATE, zato preverjanje ne odpira dirke.
        $poizvedba = $db->createQuery()->select('*')->from($db->quoteName('#__belaunce_zetoni'))
            ->where($db->quoteName('zeton_hash') . ' = :hash')
            ->bind(':hash', $hash, ParameterType::STRING);

        return $db->setQuery($poizvedba)->loadObject() ?: null;
    }

    /** Izbriše dovolj stare porabljene ali potekle žetone za vzdrževalno opravilo. */
    public function pociscenje(int $dni = 7): int
    {
        $od = gmdate('Y-m-d H:i:s', time() - max(0, $dni) * 86400);
        $zdaj = gmdate('Y-m-d H:i:s');
        $db = $this->db;
        $poizvedba = $db->createQuery()->delete($db->quoteName('#__belaunce_zetoni'))
            ->where($db->quoteName('ustvarjeno') . ' < :od')
            ->where('(' . $db->quoteName('porabljen') . ' IS NOT NULL OR ' . $db->quoteName('potece') . ' <= :zdaj)')
            ->bind(':od', $od, ParameterType::STRING)
            ->bind(':zdaj', $zdaj, ParameterType::STRING);
        $db->setQuery($poizvedba)->execute();

        return $db->getAffectedRows();
    }
}
