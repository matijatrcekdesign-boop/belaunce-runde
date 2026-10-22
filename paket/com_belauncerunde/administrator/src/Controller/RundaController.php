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
     * Odklep ob preklicu urejanja dovoli samo s potrjenim obrazcem POST.
     *
     * @param   string  $task  Ime opravila.
     *
     * @return  mixed
     *
     * @since   0.14.0
     */
    public function execute($task)
    {
        if (strtolower((string) $task) === 'cancel') {
            if ($this->input->server->getString('REQUEST_METHOD') !== 'POST') {
                throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
            }

            $this->checkToken();
        }

        return parent::execute($task);
    }

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
     * Ohrani obrazec in prikaže samo znane prevedene razloge neuspešnega zapisa.
     *
     * @param   string|null  $key     Ime primarnega ključa.
     * @param   string|null  $urlVar  Ime spremenljivke v URL-ju.
     *
     * @return  bool
     *
     * @since   0.14.0
     */
    public function save($key = null, $urlVar = null)
    {
        if ($this->input->server->getString('REQUEST_METHOD') !== 'POST') {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $this->checkToken();

        try {
            $uspeh = parent::save($key, $urlVar);
        } catch (\Throwable $napaka) {
            $id = $this->input->post->getInt('id');
            Factory::getApplication()->setUserState('com_belauncerunde.edit.runda.data',
                $this->input->post->get('jform', [], 'array'));
            $this->setRedirect(Route::_('index.php?option=com_belauncerunde&task=runda.edit&id=' . $id, false));
            $znane = [
                'JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED',
                'JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED',
                'JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED',
                'COM_BELAUNCERUNDE_ERROR_TIP_INVALID',
                'COM_BELAUNCERUNDE_ERROR_TEZAVNOST_INVALID',
                'COM_BELAUNCERUNDE_ERROR_VODJA_INVALID',
                'COM_BELAUNCERUNDE_ERROR_TRASA_URL_INVALID',
            ];
            $sporocilo = Text::_('COM_BELAUNCERUNDE_NAPAKA_ZAPISA');

            foreach ($znane as $koda) {
                if ($napaka->getMessage() === Text::_($koda)) {
                    $sporocilo = Text::_($koda);
                    break;
                }
            }

            $this->setMessage($sporocilo, 'error');

            return false;
        }

        if (!$uspeh) {
            // Jedrni kontroler lahko vrne surovo napako modela; prepišemo jo z varnim prevodom.
            $this->setMessage(Text::_('COM_BELAUNCERUNDE_NAPAKA_ZAPISA'), 'error');
        }

        return $uspeh;
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
        if ($this->input->server->getString('REQUEST_METHOD') !== 'POST') {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $this->checkToken();

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_belauncerunde')) {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 403);
        }

        $id = $this->input->post->getInt('id');

        try {
            if ($id > 0 && $this->getModel()->nastaviStanje([$id], $stanje)) {
                $this->setMessage(Text::_($stanje === 2 ? 'COM_BELAUNCERUNDE_RUNDA_ODPOVEDANA' : 'COM_BELAUNCERUNDE_RUNDA_OBNOVLJENA'));
            }
        } catch (\Throwable $napaka) {
            $this->setMessage(Text::_('COM_BELAUNCERUNDE_NAPAKA_ZAPISA'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_belauncerunde&task=runda.edit&id=' . $id, false));
    }
}
