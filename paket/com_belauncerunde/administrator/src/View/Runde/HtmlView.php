<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\View\Runde;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Pogled seznama rund.
 *
 * @since  0.2.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var array Zapisi za prikaz. */
    public $items;

    /** @var object Paginacija seznama. */
    public $pagination;

    /** @var object Stanje modela. */
    public $state;

    /** @var object Obrazec filtrov. */
    public $filterForm;

    /** @var array Aktivni filtri. */
    public $activeFilters;

    /**
     * Prikaže seznam rund.
     *
     * @param   string|null  $tpl  Predloga.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    public function display($tpl = null): void
    {
        $model = $this->getModel();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        if ($this->filterForm !== null) {
            $this->filterForm->addControlField('task')->addControlField('boxchecked', '0');
        }
        $this->activeFilters = $model->getActiveFilters();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Doda gumbe orodne vrstice.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    protected function addToolbar(): void
    {
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_BELAUNCERUNDE_RUNDE'), 'calendar');

        if ($user->authorise('core.create', 'com_belauncerunde')) {
            ToolbarHelper::addNew('runda.add');
        }

        if ($user->authorise('core.edit', 'com_belauncerunde')) {
            ToolbarHelper::editList('runda.edit');
        }

        if ($user->authorise('core.edit.state', 'com_belauncerunde')) {
            ToolbarHelper::custom('runde.odpovej', 'cancel', '', 'COM_BELAUNCERUNDE_TOOLBAR_ODPOVEJ', true);
            ToolbarHelper::custom('runde.obnovi', 'refresh', '', 'COM_BELAUNCERUNDE_TOOLBAR_OBNOVI', true);
            ToolbarHelper::checkin('runde.checkin');
        }

        if ($user->authorise('core.delete', 'com_belauncerunde')) {
            ToolbarHelper::deleteList('COM_BELAUNCERUNDE_CONFIRM_DELETE', 'runde.delete', 'JTOOLBAR_DELETE');
        }

        if ($user->authorise('core.admin', 'com_belauncerunde') || $user->authorise('core.options', 'com_belauncerunde')) {
            ToolbarHelper::preferences('com_belauncerunde');
        }
    }
}
