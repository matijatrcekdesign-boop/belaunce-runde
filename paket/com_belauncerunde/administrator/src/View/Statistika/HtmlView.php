<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\View\Statistika;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/** Pogled osnovnih kazalnikov in razčlenitev rund. */
class HtmlView extends BaseHtmlView
{
    /** @var object Skupni kazalniki. */
    public $povzetek;

    /** @var array Razčlenitev po tipu. */
    public $tipi;

    /** @var array Razčlenitev po težavnosti. */
    public $tezavnosti;

    /** @var object Obrazec filtrov. */
    public $filterForm;

    /** @var object Stanje filtrov. */
    public $state;

    /** @var array Aktivni filtri. */
    public $activeFilters;

    /** Prikaže agregate filtriranih rund. */
    public function display($tpl = null): void
    {
        $model = $this->getModel();
        $this->povzetek = $model->povzetek();
        $this->tipi = $model->poTipu();
        $this->tezavnosti = $model->poTezavnosti();
        $this->state = $model->getState();
        $this->filterForm = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if ($this->filterForm !== null) {
            $this->filterForm->addControlField('task');
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /** Doda naslov, povezavo za izvoz in standardne možnosti. */
    protected function addToolbar(): void
    {
        $user = Factory::getApplication()->getIdentity();
        ToolbarHelper::title(Text::_('COM_BELAUNCERUNDE_STATISTIKA'), 'chart-bar');

        if ($user->authorise('core.manage', 'com_belauncerunde')) {
            $url = Route::_('index.php?option=com_belauncerunde&task=statistika.izvoz&'
                . Session::getFormToken() . '=1', false);
            ToolbarHelper::link($url, 'COM_BELAUNCERUNDE_STATISTIKA_IZVOZI', 'download');
        }

        if ($user->authorise('core.admin', 'com_belauncerunde')
            || $user->authorise('core.options', 'com_belauncerunde')) {
            ToolbarHelper::preferences('com_belauncerunde');
        }
    }
}
