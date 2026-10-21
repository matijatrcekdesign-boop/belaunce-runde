<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** Omejuje zahteve brez hranjenja surovih emailov ali IP-jev. */
class OmejitevHelper
{
    public const TIP_PRIJAVA_EMAIL = 'prijava_email';
    public const TIP_PRIJAVA_IP = 'prijava_ip';

    /** @var DatabaseInterface Povezava do baze. */
    private DatabaseInterface $db;

    /** Pripravi povezavo do baze za beleženje poskusov. */
    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
    }

    /** Zabeleži vsak poskus s SHA-256 ključem in časom UTC. */
    public function zabelezi(string $tip, string $surovKljuc): void
    {
        $this->preveriTip($tip);
        $kljuc = hash('sha256', $surovKljuc);
        $zdaj = gmdate('Y-m-d H:i:s');
        $db = $this->db;
        $poizvedba = $db->createQuery()
            ->insert($db->quoteName('#__belaunce_poskusi'))
            ->columns($db->quoteName(['tip', 'kljuc', 'ustvarjeno']))
            ->values(':tip, :kljuc, :zdaj')
            ->bind(':tip', $tip, ParameterType::STRING)
            ->bind(':kljuc', $kljuc, ParameterType::STRING)
            ->bind(':zdaj', $zdaj, ParameterType::STRING);
        $db->setQuery($poizvedba)->execute();
    }

    /** Prešteje vse poskuse za ključ v drsečem oknu zadnjih 60 minut. */
    public function steviloZadnjaUra(string $tip, string $surovKljuc): int
    {
        $this->preveriTip($tip);
        $kljuc = hash('sha256', $surovKljuc);
        $od = gmdate('Y-m-d H:i:s', time() - 3600);
        $db = $this->db;
        $poizvedba = $db->createQuery()
            ->select('COUNT(*)')->from($db->quoteName('#__belaunce_poskusi'))
            ->where($db->quoteName('tip') . ' = :tip')
            ->where($db->quoteName('kljuc') . ' = :kljuc')
            ->where($db->quoteName('ustvarjeno') . ' >= :od')
            ->bind(':tip', $tip, ParameterType::STRING)
            ->bind(':kljuc', $kljuc, ParameterType::STRING)
            ->bind(':od', $od, ParameterType::STRING);

        return (int) $db->setQuery($poizvedba)->loadResult();
    }

    /** Preveri mejo po beleženju, zato je dovoljen tudi zadnji poskus do meje. */
    public function presezeno(string $tip, string $surovKljuc, int $meja): bool
    {
        $this->preveriTip($tip);

        return $meja > 0 && $this->steviloZadnjaUra($tip, $surovKljuc) > $meja;
    }

    /** Izbriše stare poskuse za vzdrževalno opravilo. */
    public function pociscenje(int $dni = 2): int
    {
        $od = gmdate('Y-m-d H:i:s', time() - max(0, $dni) * 86400);
        $db = $this->db;
        $poizvedba = $db->createQuery()->delete($db->quoteName('#__belaunce_poskusi'))
            ->where($db->quoteName('ustvarjeno') . ' < :od')
            ->bind(':od', $od, ParameterType::STRING);
        $db->setQuery($poizvedba)->execute();

        return $db->getAffectedRows();
    }

    /** Zavrne neznan tip, da se tabela ne uporablja za poljubne dogodke. */
    private function preveriTip(string $tip): void
    {
        if (!\in_array($tip, [self::TIP_PRIJAVA_EMAIL, self::TIP_PRIJAVA_IP], true)) {
            throw new \InvalidArgumentException('COM_BELAUNCERUNDE_NAPAKA_TIP_OMEJITVE');
        }
    }
}
