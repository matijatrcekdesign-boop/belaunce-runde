<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** Sprejema samo zaščiteni POST za udeležbo trenutno prijavljenega člana. */
class RundaController extends BaseController
{
    /** Potrdi udeležbo prek iste metode, ki jo po prijavi uporabi tok D40. */
    public function pridem(): void
    {
        $this->spremeniUdelezbo(true);
    }

    /** Odjavi člana samo z njegove prijave, nikoli drugega uporabnika. */
    public function odjava(): void
    {
        $this->spremeniUdelezbo(false);
    }

    /** Preveri metodo, CSRF in članstvo ter preusmeri z zaprto kodo stanja. */
    private function spremeniUdelezbo(bool $prihod): void
    {
        if (!$this->checkToken('post', false) || $this->input->server->getString('REQUEST_METHOD') !== 'POST') {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $rundaId = $this->input->post->getInt('id', 0);
        $uporabnik = $this->app->getIdentity();

        try {
            $model = $this->factory->createModel('Prijave', 'Site', []);

            // Pravico preverimo v kontrolerju, model pa jo ponovno varuje tudi za D40.
            if (!$model->jeClan($uporabnik)) {
                $stanje = 'ni_clan';
            } else {
                $stanje = $prihod ? $model->pridem($rundaId, $uporabnik) : $model->odjava($rundaId, $uporabnik);
            }
        } catch (\Throwable $napaka) {
            Log::add('UDELEZBA_NAPAKA runda_id=' . $rundaId . ' uporabnik_id=' . (int) $uporabnik->id,
                Log::ERROR, 'com_belauncerunde');
            $stanje = 'napaka';
        }

        $url = 'index.php?option=com_belauncerunde&view=runda&id=' . $rundaId . '&stanje=' . $stanje;
        $this->setRedirect(Route::_($url, false), Text::_('COM_BELAUNCERUNDE_UDELEZBA_' . strtoupper($stanje)),
            \in_array($stanje, ['prijavljen', 'odjavljen'], true) ? 'message' : 'warning');
    }
}
