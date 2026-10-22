<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Povezava;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

/** Prikaže potrditveni gumb in zaščiti povezavo pred posredovanjem prek referrerja. */
class HtmlView extends BaseHtmlView
{
    /** @var string Surovi žeton samo za skrito polje potrditvenega POST. */
    public string $zeton = '';

    /** @var object|null Informativni podatki povezave. */
    public ?object $podatki = null;

    /** @var bool Ali je uporabnik že prijavljen. */
    public bool $prijavljen = false;

    /**
     * Preveri veljavnost brez porabe; GET zato ne sproži avtentikacije.
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Referrer-Policy', 'no-referrer', true);
        $app->setHeader('Cache-Control', 'no-store, private', true);
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->prijavljen = !$app->getIdentity()->guest;

        if (!$this->prijavljen) {
            $this->zeton = $app->getInput()->getString('zeton', '');
            $this->podatki = $this->getModel()->podatkiPovezave($this->zeton);
        }

        parent::display($tpl);
    }
}
