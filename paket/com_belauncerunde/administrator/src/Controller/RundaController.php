<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Kontroler urejanja runde.
 *
 * @since  0.2.0
 */
class RundaController extends FormController
{
    /**
     * Predpona jezikovnih nizov za sporočila.
     *
     * @var    string
     * @since  0.2.0
     */
    protected $text_prefix = 'COM_BELAUNCERUNDE';

    /**
     * Pogled seznama za preusmeritve po shranjevanju.
     *
     * @var    string
     * @since  0.2.0
     */
    protected $view_list = 'runde';

    /**
     * Vrne model runde prek MVC tovarne.
     *
     * @param   string  $name    Ime modela.
     * @param   string  $prefix  Odjemalec modela.
     * @param   array   $config  Nastavitve modela.
     *
     * @return  BaseDatabaseModel|bool
     *
     * @since   0.2.0
     */
    public function getModel($name = 'Runda', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return $this->createModel($name, $prefix, $config);
    }

    /**
     * Označi rundo kot odpovedano iz obrazca.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    public function odpovej(): void
    {
        $this->spremeniStanje(2);
    }

    /**
     * Obnovi odpovedano rundo iz obrazca.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    public function obnovi(): void
    {
        $this->spremeniStanje(1);
    }

    /**
     * Skupna izvedba spremembe stanja iz obrazca.
     *
     * @param   int  $stanje  Novo stanje runde.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    private function spremeniStanje(int $stanje): void
    {
        $this->checkToken();

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_belauncerunde')) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 403);
        }

        $id = $this->input->getInt('id');

        if ($id > 0 && $this->getModel()->nastaviStanje([$id], $stanje)) {
            $this->setMessage(Text::_($stanje === 2 ? 'COM_BELAUNCERUNDE_RUNDA_ODPOVEDANA' : 'COM_BELAUNCERUNDE_RUNDA_OBNOVLJENA'));
        }

        $this->setRedirect(Route::_('index.php?option=com_belauncerunde&task=runda.edit&id=' . $id, false));
    }
}
