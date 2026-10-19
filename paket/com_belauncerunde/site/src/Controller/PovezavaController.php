<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** Prijavo z žetonom v celoti prepusti Joomli in avtentikacijskemu vtičniku. */
class PovezavaController extends BaseController
{
    /** Preveri POST in pokliče Joomlino prijavo z možnostjo daljše seje. */
    public function prijavi(): void
    {
        // 1. Žeton se lahko porabi samo prek POST z veljavnim CSRF žetonom.
        if (!$this->checkToken('post', false) || $this->input->server->getString('REQUEST_METHOD') !== 'POST') {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $url = 'index.php?option=com_belauncerunde&view=prijava';

        // 2. Pri že prijavljenem uporabniku se žeton ne porabi.
        if (!$this->app->getIdentity()->guest) {
            $this->setRedirect(Route::_($url . '&stanje=ze_prijavljen', false), Text::_('COM_BELAUNCERUNDE_PRIJAVA_ZE_PRIJAVLJEN'));

            return;
        }

        // 3. Žeton preberemo samo iz POST in preverimo celotno obliko.
        $surovZeton = $this->input->post->getString('zeton', '');

        if (!preg_match('/^[0-9a-f]{64}$/D', $surovZeton)) {
            $this->setRedirect(Route::_($url . '&stanje=povezava_neveljavna', false), Text::_('COM_BELAUNCERUNDE_POVEZAVA_NEVELJAVNA'), 'warning');

            return;
        }

        // 4. username mora obstajati; autoregister=false ohrani skupino, ki jo določi vtičnik.
        $params = ComponentHelper::getParams('com_belauncerunde');

        // Ostanka prejšnje neuspele prijave ne sme porabiti kasnejša povezava brez runde.
        $this->app->getSession()->remove('com_belauncerunde.runda_po_prijavi');

        try {
            $uspeh = $this->app->login(
                ['username' => '', 'zeton' => $surovZeton],
                ['remember' => (bool) $params->get('zapomni_si_me', 1), 'autoregister' => false]
            );
        } catch (\Throwable $napaka) {
            Log::add('JOOMLA_PRIJAVA_NAPAKA', Log::ERROR, 'com_belauncerunde');
            $this->setRedirect(Route::_($url . '&stanje=napaka', false), Text::_('COM_BELAUNCERUNDE_PRIJAVA_NAPAKA'), 'warning');

            return;
        }

        // 5. Po uspehu ni žetona v URL-ju; prikaz prijave pove, da je uporabnik prijavljen.
        if ($uspeh === true) {
            $seja = $this->app->getSession();
            $rundaId = (int) $seja->get('com_belauncerunde.runda_po_prijavi', 0);
            $seja->remove('com_belauncerunde.runda_po_prijavi');

            if ($rundaId > 0) {
                try {
                    $prijave = $this->factory->createModel('Prijave', 'Site', []);
                    $uporabnik = $this->app->getIdentity();
                    $stanje = $prijave->jeClan($uporabnik) ? $prijave->pridem($rundaId, $uporabnik) : 'ni_clan';
                } catch (\Throwable $napaka) {
                    // Uspešne prijave ne razveljavimo, če zapis udeležbe ne uspe.
                    Log::add('D40_NAPAKA runda_id=' . $rundaId . ' uporabnik_id=' . (int) $this->app->getIdentity()->id,
                        Log::ERROR, 'com_belauncerunde');
                    $stanje = 'napaka';
                }

                // Za izbrisano ali nejavno rundo pokažemo obvestilo na seznamu, da uspešni prijavi ne sledi 404.
                $cilj = $stanje === 'runda_ni_najdena'
                    ? 'index.php?option=com_belauncerunde&view=runde'
                    : 'index.php?option=com_belauncerunde&view=runda&id=' . $rundaId;
                $this->setRedirect(Route::_($cilj . '&stanje=' . $stanje, false),
                    Text::_('COM_BELAUNCERUNDE_UDELEZBA_' . strtoupper($stanje)),
                    $stanje === 'prijavljen' ? 'message' : 'warning');

                return;
            }

            $this->setRedirect(Route::_($url . '&stanje=uspeh', false), Text::_('COM_BELAUNCERUNDE_PRIJAVA_USPEH'));

            return;
        }

        // 6. Jedro že doda sporočilo vtičnika; preusmeritev nikoli ne prenaša surovega žetona.
        $this->setRedirect(Route::_($url . '&stanje=napaka', false));
    }
}
