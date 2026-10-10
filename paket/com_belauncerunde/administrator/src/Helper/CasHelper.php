<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;

\defined('_JEXEC') or die;

/**
 * Pomočnik za pretvorbo časa med UTC in časovnim pasom rund.
 *
 * @since  0.2.0
 */
abstract class CasHelper
{
    /**
     * Privzeti časovni pas rund.
     *
     * @var    string
     * @since  0.2.0
     */
    private const PRIVZETI_CASOVNI_PAS = 'Europe/Ljubljana';

    /**
     * Pretvori lokalni čas runde v UTC za shranjevanje v bazo.
     *
     * @param   string  $lokalni  Čas v časovnem pasu komponente.
     *
     * @return  string
     *
     * @since   0.2.0
     */
    public static function vUtc(string $lokalni): string
    {
        $datum = new \DateTimeImmutable($lokalni, self::casovniPas());

        return $datum->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * Pretvori UTC čas iz baze v lokalni čas runde.
     *
     * @param   string  $utc  Čas v UTC.
     *
     * @return  string
     *
     * @since   0.2.0
     */
    public static function izUtc(string $utc): string
    {
        $datum = new \DateTimeImmutable($utc, new \DateTimeZone('UTC'));

        return $datum->setTimezone(self::casovniPas())->format('Y-m-d H:i:s');
    }

    /**
     * Vrne trenutni UTC čas za dosledne primerjave in shranjevanje.
     *
     * @return  string
     *
     * @since   0.2.0
     */
    public static function zdajUtc(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    /**
     * Vrne nastavljen časovni pas ali varno privzeto vrednost.
     *
     * @return  \DateTimeZone
     *
     * @since   0.2.0
     */
    private static function casovniPas(): \DateTimeZone
    {
        $params = ComponentHelper::getParams('com_belauncerunde');
        $ime    = (string) $params->get('casovni_pas', self::PRIVZETI_CASOVNI_PAS);

        try {
            return new \DateTimeZone($ime);
        } catch (\Throwable $exception) {
            Log::add('Neveljaven časovni pas za com_belauncerunde: ' . $ime, Log::WARNING, 'com_belauncerunde');

            return new \DateTimeZone(self::PRIVZETI_CASOVNI_PAS);
        }
    }
}
