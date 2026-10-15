<?php

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Belaunce\Component\Belauncerunde\Administrator\Helper\OmejitevHelper;
use Belaunce\Component\Belauncerunde\Administrator\Service\AcymailingAdapter;
use Belaunce\Component\Belauncerunde\Administrator\Service\ZetonService;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** Sprejema zahteve za povezavo brez razkrivanja obstoja člana. */
class PrijavaController extends BaseController
{
    /** Zabeleži poskus, preveri omejitve in članu pošlje enkratno povezavo. */
    public function poslji(): void
    {
        // 1. Akcija sprejema izključno POST z veljavnim Joomlinim CSRF žetonom.
        if (!$this->checkToken('post', false) || $this->input->server->getString('REQUEST_METHOD') !== 'POST') {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        // 2. Že prijavljenega uporabnika ne pošiljamo skozi tok za goste.
        if (!$this->app->getIdentity()->guest) {
            $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=prijava&stanje=ze_prijavljen', false), Text::_('COM_BELAUNCERUNDE_PRIJAVA_ZE_PRIJAVLJEN'));

            return;
        }

        try {
            // 3. Filter obrazca in validacija modela dopolnjujeta normalizacijo emaila.
            $model = $this->factory->createModel('Prijava', 'Site', []);
            $podatki = (array) $this->input->post->get('jform', [], 'array');
            $podatki = $model->getForm([], false)->filter($podatki);
            $email = mb_strtolower(trim(\is_string($podatki['email'] ?? null) ? $podatki['email'] : ''));
            $podatki['email'] = $email;
            $veljaven = MailHelper::isEmailAddress($email) && $model->preveriVnos($podatki);
            $params = ComponentHelper::getParams('com_belauncerunde');
            $ip = $this->app->getInput()->server->getString('REMOTE_ADDR', '');

            // 4. Vsak poskus beležimo pred AcyMailing poizvedbami, tudi neveljavnega.
            $omejitev = new OmejitevHelper();
            $omejitev->zabelezi(OmejitevHelper::TIP_PRIJAVA_IP, $ip);

            if ($veljaven) {
                $omejitev->zabelezi(OmejitevHelper::TIP_PRIJAVA_EMAIL, $email);
            }

            $ipPresezen = $omejitev->presezeno(OmejitevHelper::TIP_PRIJAVA_IP, $ip, (int) $params->get('omejitev_ip_uro', 20));
            $emailPresezen = $veljaven && $omejitev->presezeno(OmejitevHelper::TIP_PRIJAVA_EMAIL, $email, (int) $params->get('omejitev_email_uro', 3));

            if ($ipPresezen || $emailPresezen) {
                Log::add($ipPresezen ? 'OMEJITEV_PRIJAVA_IP' : 'OMEJITEV_PRIJAVA_EMAIL', Log::WARNING, 'com_belauncerunde');

                return;
            }

            // 5. Neveljaven email dobi isti odgovor in ne sproži iskanja ali pošiljanja.
            if (!$veljaven) {
                return;
            }

            // 6. Manjkajoča lista ali adapter pomenita enak odgovor ter opozorilo adminu.
            $adapter = new AcymailingAdapter();
            $listaId = (int) $params->get('lista_clanov', 0);
            $clan = $adapter->poisciClana($email, $listaId);

            if (!$clan) {
                if ($listaId <= 0 || !$adapter->jeNamescen()) {
                    Log::add($listaId <= 0 ? 'LISTA_NI_NASTAVLJENA' : 'ACYM_NI_NAMESCEN', Log::WARNING, 'com_belauncerunde');
                }

                return;
            }

            // 7. Šele potrjen član dobi nov žeton in pošto; ID runde je le informativen.
            $rundaId = (int) ($podatki['runda_id'] ?? 0);
            $surovi = (new ZetonService())->ustvari((int) $clan->acym_id, $rundaId > 0 ? $rundaId : null, $ip);
            $model->posljiPovezavo($clan, $surovi);
        } catch (\Throwable $napaka) {
            // Napake ne smejo razkriti emaila, SQL-a oziroma žetona.
            Log::add('ZAHTEVA_PRIJAVA_NAPAKA', Log::ERROR, 'com_belauncerunde');
        } finally {
            // 8. Tudi zgodnji return ali napaka ohranita enako sporočilo in preusmeritev.
            $this->setRedirect(Route::_('index.php?option=com_belauncerunde&view=prijava&stanje=poslano', false), Text::_('COM_BELAUNCERUNDE_PRIJAVA_POSLANO'));
        }
    }
}
