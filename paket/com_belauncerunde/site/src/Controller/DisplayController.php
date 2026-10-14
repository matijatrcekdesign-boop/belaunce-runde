<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

/** Privzeti kontroler strani; seznam rund pride v nalogi 05. */
class DisplayController extends BaseController
{
    /** @var string Privzeti pogled komponente na strani. */
    protected $default_view = 'prijava';

    /** Prikaže obrazec brez predpomnjenja osebnih podatkov in žetonov. */
    public function display($cachable = false, $urlparams = [])
    {
        $this->app->setHeader('Cache-Control', 'no-store, private', true);

        return parent::display(false, $urlparams);
    }
}
