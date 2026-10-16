<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Runda;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** Pripravi javno stran runde in kanonično povezavo s trenutnim aliasom. */
class HtmlView extends BaseHtmlView
{
    /** @var object Javni podatki runde. */
    public object $runda;
    /** @var string Lokalni začetek za prikaz. */
    public string $zacetek;
    /** @var string Preverjena povezava na traso ali prazen niz. */
    public string $trasa = '';

    /**
     * Zavrne neobstoječo rundo s 404 in pripravi varen izpis javnih podatkov.
     *
     * @param string|null $tpl Postavitev pogleda.
     * @return void
     */
    public function display($tpl = null): void
    {
        $runda = $this->getModel()->getItem();

        if ($runda === null) {
            throw new GenericDataException(Text::_('COM_BELAUNCERUNDE_RUNDA_NI_NAJDENA'), 404);
        }

        $this->runda = $runda;
        $lokalni = new \DateTimeImmutable(CasHelper::izUtc($runda->zacetek), new \DateTimeZone('UTC'));
        $this->zacetek = $lokalni->format(Text::_('COM_BELAUNCERUNDE_DATUM_OBLIKA'));

        // URL iz baze obravnavamo kot nezaupanja vreden vhod; HTML in druge sheme odpadejo.
        $trasa = (string) ($runda->trasa_url ?? '');
        $shema = strtolower((string) parse_url($trasa, PHP_URL_SCHEME));

        if (filter_var($trasa, FILTER_VALIDATE_URL) && \in_array($shema, ['http', 'https'], true)) {
            $this->trasa = $trasa;
        }

        // Router prebere sedanji alias iz baze; stara povezava ostane veljavna po ID-ju.
        $kanonicna = Route::_('index.php?option=com_belauncerunde&view=runda&id=' . (int) $runda->id, false, Route::TLS_IGNORE, true);
        $this->getDocument()->addHeadLink($kanonicna, 'canonical');
        $this->getDocument()->setTitle($runda->naslov);
        $this->getDocument()->getWebAssetManager()->useStyle('com_belauncerunde.site');
        parent::display($tpl);
    }
}
