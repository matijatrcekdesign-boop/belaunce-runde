<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Extension;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Log\Log;
use Psr\Container\ContainerInterface;

\defined('_JEXEC') or die;

/**
 * Glavni razred komponente.
 *
 * @since  0.1.0
 */
class BelauncerundeComponent extends MVCComponent implements BootableExtensionInterface
{
    use HTMLRegistryAwareTrait;

    /** @var bool Ali je zapisovalnik v trenutni zahtevi že registriran. */
    private static bool $dnevnikRegistriran = false;

    /**
     * Ob zagonu komponente za oba odjemalca pripravi lasten dnevnik.
     *
     * @param   ContainerInterface  $container  Vsebnik storitev razširitve.
     *
     * @return  void
     *
     * @since   0.4.0
     */
    public function boot(ContainerInterface $container): void
    {
        if (self::$dnevnikRegistriran) {
            return;
        }

        // Kategorija mora imeti zapisovalnik tudi brez nastavitve Log Almost Everything.
        Log::addLogger(['text_file' => 'com_belauncerunde.php'], Log::ALL, ['com_belauncerunde']);
        self::$dnevnikRegistriran = true;
    }
}
