<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Controller;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

\defined('_JEXEC') or die;

/** Izvoz filtrirane statistike v CSV. */
class StatistikaController extends BaseController
{
    /** Preveri GET žeton in pravico ter izpiše vse filtrirane runde. */
    public function izvoz(): void
    {
        $app = Factory::getApplication();
        $cilj = Route::_('index.php?option=com_belauncerunde&view=runde', false);

        if (!Session::checkToken('get') || !$app->getIdentity()->authorise('core.manage', 'com_belauncerunde')) {
            $this->setMessage(Text::_('COM_BELAUNCERUNDE_STATISTIKA_NI_DOSTOPA'), 'warning');
            $this->setRedirect($cilj);

            return;
        }

        // ListModel prebere isti shranjeni filter stanja kot pogled; izvoza ne omejimo na stran.
        $model = $this->factory->createModel('Statistika', 'Administrator', []);
        $vrstice = $model->vrsticeIzvoza();
        $datum = (new \DateTimeImmutable(CasHelper::izUtc(CasHelper::zdajUtc())))->format('Y-m-d');
        $ime = 'belaunce-statistika-' . $datum . '.csv';

        Log::add('STATISTIKA_IZVOZ vrstic=' . count($vrstice), Log::INFO, 'com_belauncerunde');

        $app->allowCache(false);
        $app->setHeader('Content-Type', 'text/csv; charset=UTF-8', true)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $ime . '"', true)
            ->setHeader('Cache-Control', 'no-store, private', true)
            ->sendHeaders();

        $tok = fopen('php://output', 'wb');
        fwrite($tok, "\xEF\xBB\xBF");
        $this->zapisiVrstico($tok, [
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_ID'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_NASLOV'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_TIP'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_TEZAVNOST'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_LOKACIJA'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_ZACETEK'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_DOLZINA'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_TRAJANJE'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_STANJE'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_VODJA'),
            Text::_('COM_BELAUNCERUNDE_STATISTIKA_CSV_UDELEZENCI'),
        ]);

        foreach ($vrstice as $vrstica) {
            $zacetek = new \DateTimeImmutable(CasHelper::izUtc((string) $vrstica->zacetek));
            $this->zapisiVrstico($tok, [
                (string) $vrstica->id,
                $this->varnoBesedilo((string) $vrstica->naslov),
                $this->varnoBesedilo((string) $vrstica->tip),
                $this->varnoBesedilo((string) $vrstica->tezavnost),
                $this->varnoBesedilo((string) $vrstica->lokacija),
                $zacetek->format('j. n. Y H:i'),
                number_format((float) $vrstica->dolzina_km, 1, ',', ''),
                $vrstica->trajanje_min === null ? '' : (string) $vrstica->trajanje_min,
                Text::_((int) $vrstica->stanje === 2
                    ? 'COM_BELAUNCERUNDE_STANJE_ODPOVEDANA' : 'COM_BELAUNCERUNDE_STANJE_OBJAVLJENA'),
                $this->varnoBesedilo((string) ($vrstica->vodja ?? '')),
                (string) $vrstica->stevilo_udelezencev,
            ]);
        }

        fclose($tok);
        $app->close();
    }

    /** fputcsv ubeži narekovaje in ločila ter uporabi predpisani CRLF. */
    private function zapisiVrstico($tok, array $polja): void
    {
        fputcsv($tok, $polja, ';', '"', '', "\r\n");
    }

    /** Excelu prepreči obravnavo besedilnega podatka kot formule. */
    private function varnoBesedilo(string $besedilo): string
    {
        return preg_match('/^[\x00-\x20]*[=+@-]/', $besedilo) ? "'" . $besedilo : $besedilo;
    }
}
