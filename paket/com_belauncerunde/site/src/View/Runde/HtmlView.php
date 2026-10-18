<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Runde;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/** Pripravi javni seznam, filtre in Joomlino paginacijo. */
class HtmlView extends BaseHtmlView
{
    /** @var array Javne runde za trenutno stran. */
    public array $runde = [];
    /** @var array Objavljeni tipi. */
    public array $tipi = [];
    /** @var array Objavljene težavnosti. */
    public array $tezavnosti = [];
    /** @var int Izbrani tip. */
    public int $tip = 0;
    /** @var int Izbrana težavnost. */
    public int $tezavnost = 0;
    /** @var Registry Parametri komponente in menijske postavke. */
    public Registry $parametri;
    /** @var Pagination Paginacija seznama. */
    public Pagination $paginacija;
    /** @var string Čista povezava za obrazec in ponastavitev. */
    public string $povezavaSeznama;
    /** @var int Menijska postavka za oddajo GET z izklopljenim SEF. */
    public int $menijskaPostavka = 0;

    /**
     * Naloži podatke in slog ter pretvori čas iz UTC po D42.
     *
     * @param string|null $tpl Postavitev pogleda.
     * @return void
     */
    public function display($tpl = null): void
    {
        $model = $this->getModel();
        $this->runde = $model->getItems();
        $this->tipi = $model->tipi();
        $this->tezavnosti = $model->tezavnosti();
        $this->tip = (int) $model->getState('filter.tip');
        $this->tezavnost = (int) $model->getState('filter.tezavnost');
        $this->parametri = $model->getState('params');
        $this->paginacija = $model->getPagination();
        $this->povezavaSeznama = Route::_('index.php?option=com_belauncerunde&view=runde', false);
        $this->menijskaPostavka = (int) (new Uri($this->povezavaSeznama))->getVar('Itemid', 0);

        // Paginacija mora ohraniti oba filtra tudi pri povezavi brez menijske postavke.
        $this->paginacija->setAdditionalUrlParam('option', 'com_belauncerunde');
        $this->paginacija->setAdditionalUrlParam('view', 'runde');
        $this->paginacija->setAdditionalUrlParam('filter_tip', $this->tip ?: null);
        $this->paginacija->setAdditionalUrlParam('filter_tezavnost', $this->tezavnost ?: null);

        foreach ($this->runde as $runda) {
            // CasHelper že opravi pretvorbo; oblikovanje ne sme znova premakniti ure.
            $lokalni = new \DateTimeImmutable(CasHelper::izUtc($runda->zacetek), new \DateTimeZone('UTC'));
            $runda->zacetek_prikaz = $lokalni->format(Text::_('COM_BELAUNCERUNDE_DATUM_OBLIKA'));
        }

        $this->getDocument()->setTitle(Text::_('COM_BELAUNCERUNDE_RUNDE_NASLOV'));
        $this->getDocument()->getWebAssetManager()->useStyle('com_belauncerunde.site');
        parent::display($tpl);
    }
}
