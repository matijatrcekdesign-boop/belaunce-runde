<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Prijava;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

/** Prikaže obrazec samo gostu, prijavljenemu uporabniku pa obvestilo. */
class HtmlView extends BaseHtmlView
{
    /** Dovoljene kode določajo besedilo in videz brez izpisovanja uporabnikovega vnosa. */
    private const OBVESTILA = [
        'moje_prijava' => ['razred' => 'alert-info', 'kljuc' => 'COM_BELAUNCERUNDE_MOJE_PRIJAVA'],
        'poslano' => ['razred' => 'alert-success', 'kljuc' => 'COM_BELAUNCERUNDE_PRIJAVA_POSLANO'],
        'uspeh' => ['razred' => 'alert-success', 'kljuc' => 'COM_BELAUNCERUNDE_PRIJAVA_USPEH'],
        'napaka' => ['razred' => 'alert-warning', 'kljuc' => 'COM_BELAUNCERUNDE_PRIJAVA_NAPAKA'],
        'povezava_neveljavna' => ['razred' => 'alert-warning', 'kljuc' => 'COM_BELAUNCERUNDE_POVEZAVA_NEVELJAVNA'],
        'ze_prijavljen' => ['razred' => 'alert-info', 'kljuc' => 'COM_BELAUNCERUNDE_PRIJAVA_ZE_PRIJAVLJEN'],
    ];

    /** @var array|null Obvestilo iz zaprtega seznama; neznana koda nima izpisa. */
    public ?array $obvestilo = null;

    /** @var \Joomla\CMS\Form\Form|null Obrazec za zahtevo povezave. */
    public $form = null;

    /** @var bool Ali je obiskovalec že prijavljen. */
    public bool $prijavljen = false;

    /**
     * Pripravi obrazec z informativnim ID runde.
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->prijavljen = !$app->getIdentity()->guest;

        // Komponenta pokaže stanje tudi, kadar predloga strani ne izriše sistemskih sporočil (D41).
        $stanje = $app->getInput()->getCmd('stanje', '');
        $this->obvestilo = self::OBVESTILA[$stanje] ?? null;

        if (!$this->prijavljen) {
            $this->form = $this->getModel()->getForm();
        }

        parent::display($tpl);
    }
}
