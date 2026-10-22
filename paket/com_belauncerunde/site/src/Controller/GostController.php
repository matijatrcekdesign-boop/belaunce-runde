<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** POST vstopni točki za prijavo gosta in potrditev odjave brez računa. */
class GostController extends BaseController
{
    /**
     * Preveri POST, CSRF in pravila runde še pred modelom, ki jih ponovi ob zapisu.
     *
     * @return  void
     */
    public function prijava(): void
    {
        $rundaId = $this->input->post->getUint('id', 0);
        $stanje = 'gost_zahteva';

        try {
            if ($this->input->server->getString('REQUEST_METHOD') === 'POST' && $this->checkToken('post', false)) {
                $model = $this->factory->createModel('Gost', 'Site', []);
                $uporabnik = $this->app->getIdentity();
                $stanje = $model->dovoljenje($rundaId, $uporabnik);

                if ($stanje === '') {
                    $stanje = $model->prijava($rundaId, $this->input->post->get('jform', [], 'array'), $uporabnik);
                }
            }
        } catch (\Throwable $napaka) {
            $stanje = 'gost_napaka';
        }

        if (!\in_array($stanje, ['gost_prijavljen', 'gost_mail'], true)) {
            Log::add(strtoupper($stanje) . ' runda_id=' . $rundaId, Log::WARNING, 'com_belauncerunde');
        }

        // Izbrisana runda ne sme po zavrnitvi voditi na 404. Tudi seznam ima zaprte kode.
        $runda = $this->factory->createModel('Runda', 'Site', [])->getItem($rundaId);
        $pot = $runda === null ? 'view=runde' : 'view=runda&id=' . $rundaId;
        $this->setRedirect(Route::_('index.php?option=com_belauncerunde&' . $pot . '&stanje=' . $stanje, false));
    }

    /**
     * Porabi samo strežniško shranjen hash za izziv potrditvene strani.
     *
     * @return  void
     */
    public function odjava(): void
    {
        $stanje = 'koncano';

        try {
            if ($this->input->server->getString('REQUEST_METHOD') !== 'POST' || !$this->checkToken('post', false)) {
                $stanje = 'zahteva';
                Log::add('GOST_ODJAVA_ZAHTEVA runda_id=0', Log::WARNING, 'com_belauncerunde');
            } else {
                $potrditev = $this->input->post->getString('potrditev', '');

                if (preg_match('/^[a-f0-9]{32}$/D', $potrditev)) {
                    $kljuc = 'com_belauncerunde.gost.odjava.' . $potrditev;
                    $hash = (string) $this->app->getUserState($kljuc, '');
                    $this->app->setUserState($kljuc, null);
                    $this->factory->createModel('Gost', 'Site', [])->odjava($hash);
                } else {
                    Log::add('GOST_ODJAVA_NEVELJAVNA runda_id=0', Log::WARNING, 'com_belauncerunde');
                }
            }
        } catch (\Throwable $napaka) {
            $stanje = 'napaka';
            Log::add('GOST_ODJAVA_NAPAKA runda_id=0', Log::ERROR, 'com_belauncerunde');
        }

        $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=odjavagosta&stanje=' . $stanje, false));
    }
}
