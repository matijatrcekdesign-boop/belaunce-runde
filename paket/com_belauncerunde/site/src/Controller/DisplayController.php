<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

/** Privzeti kontroler javnega seznama in drugih pogledov strani. */
class DisplayController extends BaseController
{
    /** @var string Privzeti pogled komponente na strani. */
    protected $default_view = 'runde';

    /** Prikaže obrazec brez predpomnjenja osebnih podatkov in žetonov. */
    public function display($cachable = false, $urlparams = [])
    {
        $this->app->setHeader('Cache-Control', 'no-store, private', true);

        return parent::display(false, $urlparams);
    }
}
