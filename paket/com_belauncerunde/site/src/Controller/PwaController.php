<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

/** Javna pot za service worker s korenskim dovoljenjem dosega. */
class PwaController extends BaseController
{
    /**
     * Vrne statično skripto z glavo, ki dovoli doseg celotne poddomene.
     *
     * @return  void
     */
    public function sw(): void
    {
        // Vir je pod nadzorom paketa; zahteva nikoli ne izbira imena datoteke.
        $vsebina = file_get_contents(JPATH_ROOT . '/media/com_belauncerunde/js/sw-vsebina.js');

        if ($vsebina === false) {
            throw new \RuntimeException('Service worker ni na voljo.', 404);
        }

        $app = Factory::getApplication();
        $app->allowCache(false);
        $app->setHeader('Content-Type', 'application/javascript; charset=utf-8', true)
            ->setHeader('Service-Worker-Allowed', '/', true)
            ->setHeader('Cache-Control', 'no-cache', true)
            ->sendHeaders();

        echo $vsebina;
        $app->close();
    }
}
