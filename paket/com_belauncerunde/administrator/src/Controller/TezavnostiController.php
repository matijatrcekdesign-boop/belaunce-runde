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
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

\defined('_JEXEC') or die;

/**
 * Kontroler seznama težavnosti.
 *
 * @since  0.1.0
 */
class TezavnostiController extends AdminController
{
    /**
     * Predpona jezikovnih nizov za sporočila.
     *
     * @var    string
     * @since  0.1.0
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
     * @since   0.1.0
     */
    public function getModel($name = 'Tezavnost', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return $this->createModel($name, $prefix, $config);
    }
    /**
     * Izbriše neuporabljene šifrante samo s potrjenim POST zahtevkom.
     *
     * @return  void
     *
     * @since   0.14.0
     */
    public function delete(): void
    {
        if ($this->input->server->getString('REQUEST_METHOD') !== 'POST') {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $this->checkToken();

        if (!Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_belauncerunde')) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
        }

        $pks = array_values(array_filter((array) $this->input->post->get('cid', [], 'int')));

        try {
            if ($pks === []) {
                $this->setMessage(Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'), 'warning');
            } elseif ($this->getModel()->delete($pks)) {
                $this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', count($pks)));
            } else {
                $this->setMessage(Text::_('COM_BELAUNCERUNDE_NAPAKA_ZAPISA'), 'error');
            }
        } catch (\Throwable $napaka) {
            // Pokažemo le pričakovani prevedeni razlog; druge izjeme ostanejo skrite.
            $sporocilo = $napaka->getMessage() === Text::_('COM_BELAUNCERUNDE_ERROR_NO_TEZAVNOSTI_DELETED')
                ? Text::_('COM_BELAUNCERUNDE_ERROR_NO_TEZAVNOSTI_DELETED') : Text::_('COM_BELAUNCERUNDE_NAPAKA_ZAPISA');
            $this->setMessage($sporocilo, 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=tezavnosti', false));
    }

}
