<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Privzeti kontroler administracije.
 *
 * @since  0.1.0
 */
class DisplayController extends BaseController
{
    /**
     * Privzeti pogled administracije.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $default_view = 'runde';

    /**
     * Prikaže pogled in varuje neposreden dostop do obrazcev brez zaklepa.
     *
     * @param   bool   $cachable   Ali je pogled predpomnjen.
     * @param   array  $urlparams  Dovoljeni URL parametri.
     *
     * @return  static|bool
     *
     * @since   0.1.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        $view   = $this->input->getCmd('view', $this->default_view);
        $layout = $this->input->getCmd('layout', 'default');
        $id     = $this->input->getInt('id');

        if ($view === 'runda' && $layout === 'edit' && !$this->checkEditId('com_belauncerunde.edit.runda', $id)) {
            if (!\count($this->app->getMessageQueue())) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            }

            $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=runde', false));

            return false;
        }

        if ($view === 'tip' && $layout === 'edit' && !$this->checkEditId('com_belauncerunde.edit.tip', $id)) {
            if (!\count($this->app->getMessageQueue())) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            }

            $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=tipi', false));

            return false;
        }

        if ($view === 'tezavnost' && $layout === 'edit' && !$this->checkEditId('com_belauncerunde.edit.tezavnost', $id)) {
            if (!\count($this->app->getMessageQueue())) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
            }

            $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=tezavnosti', false));

            return false;
        }

        return parent::display();
    }
}
