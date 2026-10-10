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
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Kontroler diagnostike AcyMailing članstva.
 *
 * @since  0.3.0
 */
class PreverjanjeController extends BaseController
{
    /**
     * Preveri email iz administratorskega obrazca.
     *
     * @return  void
     *
     * @since   0.3.0
     */
    public function preveri(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();

        if (!$app->getIdentity()->authorise('core.admin', 'com_belauncerunde')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $data  = (array) $this->input->post->get('jform', [], 'array');
        $email = (string) ($data['email'] ?? '');
        $model = $this->createModel('Preverjanje', 'Administrator', []);

        $app->getSession()->set('com_belauncerunde.preverjanje.rezultat', $model->preveri($email));
        $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=preverjanje', false));
    }
}
