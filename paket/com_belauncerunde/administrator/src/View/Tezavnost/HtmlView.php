<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\View\Tezavnost;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Pogled urejanja težavnosti runde.
 *
 * @since  0.1.0
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
     * @since   0.1.0
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
     * @since   0.1.0
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew = empty($this->item->id);

        ToolbarHelper::title($isNew ? Text::_('COM_BELAUNCERUNDE_TEZAVNOST') : Text::_('COM_BELAUNCERUNDE_TEZAVNOST'), 'pencil');
        ToolbarHelper::apply('tezavnost.apply');
        ToolbarHelper::save('tezavnost.save');
        ToolbarHelper::save2new('tezavnost.save2new');
        ToolbarHelper::cancel('tezavnost.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
