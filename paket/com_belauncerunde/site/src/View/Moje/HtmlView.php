<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Moje;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Belaunce\Component\Belauncerunde\Site\Helper\DeljenjeHelper;
use Belaunce\Component\Belauncerunde\Site\Helper\PwaHelper;
use Belaunce\Component\Belauncerunde\Site\Service\Router;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** Članski pregled z zavihkoma, zasebnimi seznami in varnimi povratnimi obvestili. */
class HtmlView extends BaseHtmlView
{
    /** Zaprti seznam stanj odjave; neznana koda se nikoli ne izpiše. */
    private const OBVESTILA = [
        'odjavljen' => 'COM_BELAUNCERUNDE_UDELEZBA_ODJAVLJEN',
        'prijavljen' => 'COM_BELAUNCERUNDE_UDELEZBA_PRIJAVLJEN',
        'runda_zaprta' => 'COM_BELAUNCERUNDE_UDELEZBA_RUNDA_ZAPRTA',
        'runda_ni_najdena' => 'COM_BELAUNCERUNDE_MOJE_RUNDA_NI_NAJDENA',
        'napaka' => 'COM_BELAUNCERUNDE_UDELEZBA_NAPAKA',
    ];

    /** @var string Izbrani zavihek iz zaprtega seznama. */
    public string $pogled = 'prihajajoce';
    /** @var bool Ali menijska postavka omogoča arhiv. */
    public bool $prikaziPretekle = true;
    /** @var array Sklopi trenutnega zavihka, pripravljeni za prikaz. */
    public array $sklopi = [];
    /** @var array Objavljene težavnosti za merilnike na karticah. */
    public array $tezavnosti = [];
    /** @var Pagination|null Paginacija samo za pretekle runde. */
    public ?Pagination $paginacija = null;
    /** @var array|null Prevedeno obvestilo in varen Bootstrap razred. */
    public ?array $obvestilo = null;

    /**
     * Preveri dostop pred branjem seznamov ter pripravi čas, gumbe in povezave brez N+1.
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $uporabnik = $app->getIdentity();
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');

        if ($uporabnik->guest) {
            $app->redirect(Route::_('index.php?option=com_belauncerunde&view=prijava&stanje=moje_prijava', false));

            return;
        }

        $prijave = $app->bootComponent('com_belauncerunde')->getMVCFactory()->createModel('Prijave', 'Site', []);

        if (!$prijave->jeClan($uporabnik)) {
            $app->redirect(Route::_('index.php?option=com_belauncerunde&view=runde&stanje=ni_clan', false));

            return;
        }

        $this->prikaziPretekle = (int) $app->getParams('com_belauncerunde')->get('prikazi_pretekle', 1) === 1;
        $this->pogled = $this->prikaziPretekle && $app->getInput()->getCmd('pogled', 'prihajajoce') === 'pretekle'
            ? 'pretekle' : 'prihajajoce';
        $podatki = $this->getModel()->podatki($this->pogled);
        $this->tezavnosti = $app->bootComponent('com_belauncerunde')->getMVCFactory()
            ->createModel('Runde', 'Site', [])->tezavnosti();
        $this->paginacija = $podatki['paginacija'];

        if ($this->paginacija !== null) {
            $this->paginacija->setAdditionalUrlParam('option', 'com_belauncerunde');
            $this->paginacija->setAdditionalUrlParam('view', 'moje');
            $this->paginacija->setAdditionalUrlParam('pogled', 'pretekle');
            $this->paginacija->setAdditionalUrlParam('stanje', null);
        }

        $sklopi = $this->pogled === 'pretekle' ? ['pretekle'] : ['vodim', 'prijavljen'];
        foreach ($sklopi as $sklop) {
            // Router dobi že prebrane aliase pred katerimkoli klicem Route v predlogi.
            Router::predpomniAliase($podatki[$sklop]);
            foreach ($podatki[$sklop] as $runda) {
                $runda->je_vodja = (int) $runda->vodja_id === (int) $uporabnik->id;
                $odprta = $this->pogled !== 'pretekle' && (int) $runda->stanje === 1 && !(bool) $runda->startala;
                $runda->urejanje = $odprta && $runda->je_vodja;
                $runda->odjava = $odprta && $sklop === 'prijavljen';
                $runda->deljiva = $odprta;
                $runda->povezava = Route::_('index.php?option=com_belauncerunde&view=runda&id=' . (int) $runda->id, false, Route::TLS_IGNORE, true);
                if ($runda->deljiva) {
                    // Alias je že v predpomnilniku; deljenje ne doda poizvedb v zanki.
                    $runda->deli_viber = DeljenjeHelper::viberPovezava(DeljenjeHelper::besedilo($runda, $runda->povezava));
                }
                $lokalni = new \DateTimeImmutable(CasHelper::izUtc($runda->zacetek), new \DateTimeZone('UTC'));
                $runda->zacetek_prikaz = $lokalni->format(Text::_('COM_BELAUNCERUNDE_DATUM_OBLIKA'));

                if ($runda->urejanje) {
                    $this->getDocument()->getWebAssetManager()->useScript('bootstrap.modal');
                }
            }
            $this->sklopi[$sklop] = $podatki[$sklop];
        }

        $stanje = $app->getInput()->getCmd('stanje', '');
        if (isset(self::OBVESTILA[$stanje])) {
            $this->obvestilo = [
                'besedilo' => Text::_(self::OBVESTILA[$stanje]),
                'razred' => \in_array($stanje, ['odjavljen', 'prijavljen'], true) ? 'alert-success' : 'alert-warning',
            ];
        }

        $this->getDocument()->setTitle(Text::_('COM_BELAUNCERUNDE_MOJE_NASLOV'));
        $this->getDocument()->getWebAssetManager()->useStyle('com_belauncerunde.pisave')
            ->useStyle('com_belauncerunde.site');
        PwaHelper::pripni($this->getDocument());
        parent::display($tpl);
    }
}
