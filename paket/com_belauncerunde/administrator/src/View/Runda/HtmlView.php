<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\View\Runda;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Pogled urejanja runde.
 *
 * @since  0.2.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var object Zapis za urejanje. */
    public $item;

    /** @var object Obrazec. */
    public $form;

    /** @var object Stanje modela. */
    public $state;

    /**
     * Prikaže obrazec.
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
        $this->item  = $model->getItem();
        $this->form  = $model->getForm();
        $this->state = $model->getState();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Doda gumbe obrazca.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $user  = Factory::getApplication()->getIdentity();
        $isNew = empty($this->item->id);

        ToolbarHelper::title(Text::_('COM_BELAUNCERUNDE_RUNDA'), 'pencil');

        if ($user->authorise($isNew ? 'core.create' : 'core.edit', 'com_belauncerunde')) {
            ToolbarHelper::apply('runda.apply');
            ToolbarHelper::save('runda.save');
            ToolbarHelper::save2new('runda.save2new');
        }

        if (!$isNew && $user->authorise('core.edit.state', 'com_belauncerunde')) {
            if ((int) $this->item->stanje === 2) {
                ToolbarHelper::custom('runda.obnovi', 'refresh', '', 'COM_BELAUNCERUNDE_TOOLBAR_OBNOVI', false);
            } else {
                ToolbarHelper::custom('runda.odpovej', 'cancel', '', 'COM_BELAUNCERUNDE_TOOLBAR_ODPOVEJ', false);
            }
        }

        ToolbarHelper::cancel('runda.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
