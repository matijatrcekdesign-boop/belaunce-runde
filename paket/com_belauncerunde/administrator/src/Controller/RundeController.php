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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Kontroler seznama rund.
 *
 * @since  0.2.0
 */
class RundeController extends AdminController
{
    /**
     * Predpona jezikovnih nizov za sporočila.
     *
     * @var    string
     * @since  0.2.0
     */
    protected $text_prefix = 'COM_BELAUNCERUNDE';

    /**
     * Za jedrne akcije seznama zahteva POST, žeton in pravico komponente.
     *
     * @param   string  $task  Ime opravila.
     *
     * @return  mixed
     *
     * @since   0.14.0
     */
    public function execute($task)
    {
        $dejanje = strtolower((string) $task);
        $stanja = ['publish', 'unpublish', 'archive', 'trash', 'orderup', 'orderdown',
            'saveorder', 'saveorderajax', 'runtransition'];
        $pravica = $dejanje === 'delete' ? 'core.delete'
            : (in_array($dejanje, $stanja, true) ? 'core.edit.state'
                : ($dejanje === 'checkin' ? 'core.manage' : null));

        if ($pravica !== null) {
            if ($this->input->server->getString('REQUEST_METHOD') !== 'POST') {
                throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
            }

            $this->checkToken();

            if (!Factory::getApplication()->getIdentity()->authorise($pravica, 'com_belauncerunde')) {
                throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
            }
        }

        return parent::execute($task);
    }

    /**
     * Vrne singularni model, ki ga potrebujejo standardne akcije seznama.
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
     * Označi izbrane runde kot odpovedane.
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
     * Obnovi izbrane odpovedane runde.
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
     * Skupna izvedba paketne spremembe stanja.
     *
     * @param   int  $stanje  Novo stanje runde.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    private function spremeniStanje(int $stanje): void
    {
        if ($this->input->server->getString('REQUEST_METHOD') !== 'POST') {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $this->checkToken();

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_belauncerunde')) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 403);
        }

        $pks = array_map('intval', (array) $this->input->post->get('cid', [], 'array'));

        try {
            if ($this->getModel()->nastaviStanje($pks, $stanje)) {
                $this->setMessage(Text::_($stanje === 2 ? 'COM_BELAUNCERUNDE_RUNDE_ODPOVEDANE' : 'COM_BELAUNCERUNDE_RUNDE_OBNOVLJENE'));
            }
        } catch (\Throwable $napaka) {
            $this->setMessage(Text::_('COM_BELAUNCERUNDE_NAPAKA_ZAPISA'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=runde', false));
    }
}
