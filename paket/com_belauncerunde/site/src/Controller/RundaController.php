<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** Sprejema zaščitene POST akcije udeležbe in ustvarjanja, urejanja ter odpovedi. */
class RundaController extends BaseController
{
    /** Shrani dovoljena polja in ob napaki ohrani lokalni vnos v seji. */
    public function shrani(): void
    {
        $this->spremeniRundo(false);
    }

    /** Odpove rundo šele po POST potrditvi in ponovnem preverjanju lastništva. */
    public function odpovej(): void
    {
        $this->spremeniRundo(true);
    }

    /** Preveri POST, CSRF in pravice v kontrolerju; model jih preveri tudi pred zapisom. */
    private function spremeniRundo(bool $odpoved): void
    {
        $id = $this->input->post->getUint('id', 0);
        $uporabnik = $this->app->getIdentity();
        $stanje = 'napaka_zapisa';
        $obrazec = false;

        // Neveljavna zahteva se prav tako konča z varno preusmeritvijo in zaprto kodo.
        if ($this->input->server->getString('REQUEST_METHOD') !== 'POST' || !$this->checkToken('post', false)) {
            $stanje = 'neveljavna_zahteva';
        } else {
            try {
                $model = $this->factory->createModel('Obrazec', 'Site', []);
                $stanje = $odpoved && $id === 0 ? 'ni_najdena' : $model->dovoljenje($id, $uporabnik);

                if ($stanje === '') {
                    if ($odpoved) {
                        $stanje = $model->odpovej($id, $uporabnik);
                    } else {
                        $vnos = $model->podatkiObrazca($this->input->post->get('jform', [], 'array'));
                        $this->app->setUserState($model->kljucSeje($id), $vnos);
                        $obrazec = true;
                        $rezultat = $model->shrani($id, $vnos, $uporabnik);
                        $stanje = $rezultat['stanje'];

                        if (\in_array($stanje, ['ustvarjena', 'shranjena', 'shranjena_mail'], true)) {
                            $this->app->setUserState($model->kljucSeje($id), null);
                            $this->app->setUserState($model->kljucSeje($id) . '.napake', null);
                            $id = $rezultat['id'];
                            $obrazec = false;
                        } else {
                            $napake = array_map(static function ($napaka): string {
                                return $napaka instanceof \Throwable ? $napaka->getMessage() : (string) $napaka;
                            }, $model->getErrors());
                            $this->app->setUserState($model->kljucSeje($id) . '.napake', $napake);
                        }
                    }
                }
            } catch (\Throwable $napaka) {
                Log::add('RUNDA_ZAPIS_NAPAKA runda_id=' . $id . ' uporabnik_id=' . (int) $uporabnik->id,
                    Log::ERROR, 'com_belauncerunde');
                $stanje = 'napaka_zapisa';
            }
        }

        // Zavrnitev lastništva ali zaklepa gre na seznam, da tudi izbris ne povzroči 404.
        if ($obrazec && \in_array($stanje, ['neveljavno', 'napaka_zapisa'], true)) {
            $url = 'index.php?option=com_belauncerunde&view=obrazec&id=' . $id;
        } elseif (\in_array($stanje, ['ustvarjena', 'shranjena', 'odpovedana', 'shranjena_mail', 'odpovedana_mail'], true)) {
            $url = 'index.php?option=com_belauncerunde&view=runda&id=' . $id;
        } else {
            $url = 'index.php?option=com_belauncerunde&view=runde';
        }

        $sporocilo = $stanje === 'omejitev'
            ? Text::sprintf('COM_BELAUNCERUNDE_OBRAZEC_OMEJITEV', $model->omejitevRundNaDan())
            : Text::_('COM_BELAUNCERUNDE_OBRAZEC_' . strtoupper($stanje));
        $this->setRedirect(Route::_($url . '&stanje=' . $stanje, false), $sporocilo,
            \in_array($stanje, ['ustvarjena', 'shranjena', 'odpovedana'], true) ? 'message' : 'warning');
    }

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

        // Star obrazec lahko kaže na medtem izbrisano rundo; obvestilo zato prikaže seznam.
        // Zaprt seznam ciljev: samo odjava s točno vrednostjo moje sme spremeniti cilj.
        // Poljubni URL, referer in polje vrni pri prijavi nikoli niso cilj preusmeritve.
        if (!$prihod && $this->input->post->getString('vrni', '') === 'moje') {
            $url = 'index.php?option=com_belauncerunde&view=moje';
        } else {
            $url = $stanje === 'runda_ni_najdena'
                ? 'index.php?option=com_belauncerunde&view=runde'
                : 'index.php?option=com_belauncerunde&view=runda&id=' . $rundaId;
        }
        $this->setRedirect(Route::_($url . '&stanje=' . $stanje, false), Text::_('COM_BELAUNCERUNDE_UDELEZBA_' . strtoupper($stanje)),
            \in_array($stanje, ['prijavljen', 'odjavljen'], true) ? 'message' : 'warning');
    }
}
