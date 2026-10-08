<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\View\Tezavnosti;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Pogled seznama težavnosti rund.
 *
 * @since  0.1.0
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
     * Prikaže seznam težavnosti.
     *
     * @param   string|null  $tpl  Predloga.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function display($tpl = null): void
    {
        $model = $this->getModel();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
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
     * @since   0.1.0
     */
    protected function addToolbar(): void
    {
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_BELAUNCERUNDE_TEZAVNOSTI'), 'list');

        if ($user->authorise('core.create', 'com_belauncerunde')) {
            ToolbarHelper::addNew('tezavnost.add');
        }

        if ($user->authorise('core.edit', 'com_belauncerunde')) {
            ToolbarHelper::editList('tezavnost.edit');
        }

        if ($user->authorise('core.edit.state', 'com_belauncerunde')) {
            ToolbarHelper::publish('tezavnosti.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('tezavnosti.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            ToolbarHelper::checkin('tezavnosti.checkin');
        }

        if ($user->authorise('core.delete', 'com_belauncerunde')) {
            ToolbarHelper::deleteList('COM_BELAUNCERUNDE_CONFIRM_DELETE', 'tezavnosti.delete', 'JTOOLBAR_DELETE');
        }

        if ($user->authorise('core.admin', 'com_belauncerunde') || $user->authorise('core.options', 'com_belauncerunde')) {
            ToolbarHelper::preferences('com_belauncerunde');
        }
    }
}
