<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Odjavagosta;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

/** GET ničesar ne izbriše; surovi žeton zamenja s sejnim izzivom za POST. */
class HtmlView extends BaseHtmlView
{
    /** @var string Naključni izziv, ki ne vsebuje surovega žetona ali osebnih podatkov. */
    public string $potrditev = '';
    /** @var string Jezikovni ključ iz zaprtega seznama. */
    public string $obvestilo = 'COM_BELAUNCERUNDE_GOST_ODJAVA_KONCANO';

    /** Pripravi nevtralno stran in omeji razkrivanje žetona prek predpomnilnika ali refererja. */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Referrer-Policy', 'no-referrer', true);
        $app->setHeader('Cache-Control', 'no-store, private', true);
        $this->getDocument()->setMetaData('referrer', 'no-referrer');
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->setTitle(Text::_('COM_BELAUNCERUNDE_GOST_ODJAVA_NASLOV'));
        $stanje = $app->getInput()->getCmd('stanje', '');
        $obvestila = [
            'koncano' => 'COM_BELAUNCERUNDE_GOST_ODJAVA_KONCANO',
            'zahteva' => 'COM_BELAUNCERUNDE_GOST_ZAHTEVA',
            'napaka' => 'COM_BELAUNCERUNDE_GOST_NAPAKA',
        ];
        $this->obvestilo = $obvestila[$stanje] ?? '';
        $surovi = $app->getInput()->getString('zeton', '');

        if ($stanje === '' && preg_match('/^[a-f0-9]{64}$/D', $surovi)) {
            $model = $app->bootComponent('com_belauncerunde')->getMVCFactory()->createModel('Gost', 'Site', []);
            $hash = hash('sha256', $surovi);

            if ($model->preveriOdjavo($hash) !== null) {
                $this->potrditev = bin2hex(random_bytes(16));
                $app->setUserState('com_belauncerunde.gost.odjava.' . $this->potrditev, $hash);
            } else {
                Log::add('GOST_ODJAVA_NEVELJAVNA runda_id=0', Log::WARNING, 'com_belauncerunde');
            }
        }

        if ($stanje === '' && $this->potrditev === '') {
            $this->obvestilo = 'COM_BELAUNCERUNDE_GOST_ODJAVA_KONCANO';
        }

        parent::display($tpl);
    }
}
