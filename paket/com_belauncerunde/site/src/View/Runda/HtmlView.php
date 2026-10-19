<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Runda;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Belaunce\Component\Belauncerunde\Site\Helper\TrasaHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** Pripravi javno stran runde in kanonično povezavo s trenutnim aliasom. */
class HtmlView extends BaseHtmlView
{
    /** Zaprt seznam prepreči izpis poljubnega sporočila ali razreda iz URL-ja. */
    private const OBVESTILA = [
        'prijavljen' => ['razred' => 'alert-success', 'kljuc' => 'COM_BELAUNCERUNDE_UDELEZBA_PRIJAVLJEN'],
        'odjavljen' => ['razred' => 'alert-success', 'kljuc' => 'COM_BELAUNCERUNDE_UDELEZBA_ODJAVLJEN'],
        'ni_clan' => ['razred' => 'alert-warning', 'kljuc' => 'COM_BELAUNCERUNDE_UDELEZBA_NI_CLAN'],
        'runda_zaprta' => ['razred' => 'alert-warning', 'kljuc' => 'COM_BELAUNCERUNDE_UDELEZBA_RUNDA_ZAPRTA'],
        'napaka' => ['razred' => 'alert-warning', 'kljuc' => 'COM_BELAUNCERUNDE_UDELEZBA_NAPAKA'],
        'ustvarjena' => ['razred' => 'alert-success', 'kljuc' => 'COM_BELAUNCERUNDE_OBRAZEC_USTVARJENA'],
        'shranjena' => ['razred' => 'alert-success', 'kljuc' => 'COM_BELAUNCERUNDE_OBRAZEC_SHRANJENA'],
        'odpovedana' => ['razred' => 'alert-success', 'kljuc' => 'COM_BELAUNCERUNDE_OBRAZEC_ODPOVEDANA'],
    ];

    /** @var object Javni podatki runde. */
    public object $runda;
    /** @var string Lokalni začetek za prikaz. */
    public string $zacetek;
    /** @var string Preverjena povezava na traso ali prazen niz. */
    public string $trasa = '';
    /** @var array|null Preverjen src in naslov vdelave iz zaprtega seznama ponudnikov. */
    public ?array $vdelava = null;
    /** @var bool Prijavljena identiteta ima nastavljeno člansko skupino. */
    public bool $clan = false;
    /** @var bool Obiskovalec ni prijavljen v Joomlo. */
    public bool $neprijavljen = true;
    /** @var bool Trenutna identiteta je vodja te runde. */
    public bool $vodja = false;
    /** @var bool Udeležbo je še dovoljeno spreminjati. */
    public bool $odprta = false;
    /** @var bool Član ima vrstico prijave. */
    public bool $udelezen = false;
    /** @var int Javno število udeležencev, vključno z obstoječim vodjo. */
    public int $steviloUdelezencev = 0;
    /** @var array Imena, naložena samo za člana. */
    public array $udelezenci = [];
    /** @var array|null Obvestilo iz zaprtega seznama. */
    public ?array $obvestilo = null;

    /**
     * Zavrne neobstoječo rundo s 404 in pripravi varen izpis javnih podatkov.
     *
     * @param string|null $tpl Postavitev pogleda.
     * @return void
     */
    public function display($tpl = null): void
    {
        $runda = $this->getModel()->getItem();

        if ($runda === null) {
            throw new GenericDataException(Text::_('COM_BELAUNCERUNDE_RUNDA_NI_NAJDENA'), 404);
        }

        $this->runda = $runda;
        $app = Factory::getApplication();
        $uporabnik = $app->getIdentity();
        $prijave = $app->bootComponent('com_belauncerunde')->getMVCFactory()->createModel('Prijave', 'Site', []);
        $this->clan = $prijave->jeClan($uporabnik);
        $this->neprijavljen = (bool) $uporabnik->guest;
        $this->vodja = !$this->neprijavljen && (int) $runda->vodja_id === (int) $uporabnik->id;
        $this->odprta = (int) $runda->stanje === 1 && !(bool) $runda->startala;
        $this->steviloUdelezencev = $prijave->steviloUdelezencev((int) $runda->id);
        $this->obvestilo = self::OBVESTILA[$app->getInput()->getCmd('stanje', '')] ?? null;

        // Imen ne beremo in ne vdelamo v HTML javnega obiskovalca ali prijavljenega nečlana.
        if ($this->clan) {
            $this->udelezenci = $prijave->udelezenci((int) $runda->id, $uporabnik);
            $this->udelezen = $prijave->jePrijavljen((int) $runda->id, $uporabnik);
        }

        $lokalni = new \DateTimeImmutable(CasHelper::izUtc($runda->zacetek), new \DateTimeZone('UTC'));
        $this->zacetek = $lokalni->format(Text::_('COM_BELAUNCERUNDE_DATUM_OBLIKA'));

        // URL iz baze obravnavamo kot nezaupanja vreden vhod; HTML in druge sheme odpadejo.
        $trasa = (string) ($runda->trasa_url ?? '');
        if (TrasaHelper::preveriUrl($trasa)) {
            $this->trasa = $trasa;
            $this->vdelava = TrasaHelper::vdelava($trasa);
        }

        // Router prebere sedanji alias iz baze; stara povezava ostane veljavna po ID-ju.
        $kanonicna = Route::_('index.php?option=com_belauncerunde&view=runda&id=' . (int) $runda->id, false, Route::TLS_IGNORE, true);
        $this->getDocument()->addHeadLink($kanonicna, 'canonical');
        $this->getDocument()->setTitle($runda->naslov);

        if ((bool) $runda->koncana) {
            $this->getDocument()->setMetaData('robots', 'noindex, follow');
        }

        // Jedrni MetasRenderer escapira vsebino oznak; tu podamo neescapirane vrednosti,
        // da se narekovaji in ampersandi ne kodirajo dvakrat. Opomb ali imen tu ni.
        $opis = Text::sprintf('COM_BELAUNCERUNDE_OG_OPIS', $this->zacetek,
            (string) ($runda->tip_naziv ?? ''), (string) ($runda->tezavnost_naziv ?? ''),
            Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', $runda->dolzina_km));
        $this->getDocument()->setMetaData('og:title', $runda->naslov, 'property');
        $this->getDocument()->setMetaData('og:description', $opis, 'property');
        $this->getDocument()->setMetaData('og:url', $kanonicna, 'property');
        $this->getDocument()->setMetaData('og:type', 'article', 'property');
        $this->getDocument()->setMetaData('og:site_name', (string) $app->get('sitename'), 'property');
        $this->getDocument()->getWebAssetManager()->useStyle('com_belauncerunde.site');
        if ($this->vodja && $this->odprta) {
            $this->getDocument()->getWebAssetManager()->useScript('bootstrap.modal');
        }
        parent::display($tpl);
    }
}
