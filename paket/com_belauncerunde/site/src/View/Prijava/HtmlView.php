<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Prijava;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

/** Prikaže obrazec samo gostu, prijavljenemu uporabniku pa obvestilo. */
class HtmlView extends BaseHtmlView
{
    /** @var \Joomla\CMS\Form\Form|null Obrazec za zahtevo povezave. */
    public $form = null;

    /** @var bool Ali je obiskovalec že prijavljen. */
    public bool $prijavljen = false;

    /** Pripravi obrazec z informativnim ID runde. */
    public function display($tpl = null): void
    {
        $this->prijavljen = !Factory::getApplication()->getIdentity()->guest;

        if (!$this->prijavljen) {
            $this->form = $this->getModel()->getForm();
        }

        parent::display($tpl);
    }
}
