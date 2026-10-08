<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

\defined('_JEXEC') or die;

/**
 * Kontroler seznama tipov.
 *
 * @since  0.1.0
 */
class TipiController extends AdminController
{
    /**
     * Predpona jezikovnih nizov za sporočila.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $text_prefix = 'COM_BELAUNCERUNDE';

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
    public function getModel($name = 'Tip', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return $this->createModel($name, $prefix, $config);
    }
}
