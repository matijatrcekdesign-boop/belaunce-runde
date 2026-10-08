<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;

\defined('_JEXEC') or die;

/**
 * Kontroler urejanja težavnosti runde.
 *
 * @since  0.1.0
 */
class TezavnostController extends FormController
{
    /**
     * Predpona jezikovnih nizov za sporočila.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $text_prefix = 'COM_BELAUNCERUNDE';

    /**
     * Pogled seznama za preusmeritve po shranjevanju.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $view_list = 'tezavnosti';
}
