<?php

namespace Belaunce\Plugin\Authentication\Belauncerunde\Extension;

use Belaunce\Component\Belauncerunde\Administrator\Service\AcymailingAdapter;
use Belaunce\Component\Belauncerunde\Administrator\Service\ClanService;
use Belaunce\Component\Belauncerunde\Administrator\Service\ZetonService;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/** Avtenticira izključno prijavo z žetonom komponente na strani. */
final class Belauncerunde extends CMSPlugin implements SubscriberInterface
{
    /** Prijavi samo dogodek avtentikacije. */
    public static function getSubscribedEvents(): array
    {
        return ['onUserAuthenticate' => 'onUserAuthenticate'];
    }

    /** Porabi žeton, ponovno preveri članstvo in pripravi odgovor Joomlini prijavi. */
    public function onUserAuthenticate(AuthenticationEvent $event): void
    {
        // 1. Administratorskih prijav ne spreminjamo.
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        // 2. Brez lastnega žetona prepustimo običajne prijave drugim vtičnikom.
        $credentials = $event->getCredentials();
        $zeton = $credentials['zeton'] ?? '';

        if ($zeton === '') {
            return;
        }

        // 3. Od tu naprej tudi ob napaki preprečimo prepis odgovora z drugim računom.
        try {
            $this->loadLanguage();
            $response = $event->getAuthenticationResponse();
            $response->status = Authentication::STATUS_FAILURE;
            $response->type = 'Belauncerunde';
            $response->username = '';
            $response->email = '';
            $response->fullname = '';
            $response->error_message = Text::_('PLG_AUTHENTICATION_BELAUNCERUNDE_ZETON_NEVELJAVEN');

            // 4. Oblika je strogo omejena na 64 malih šestnajstiških znakov.
            if (!\is_string($zeton) || !preg_match('/^[0-9a-f]{64}$/D', $zeton)) {
                return;
            }

            // 5. Žeton porabi samo atomarni UPDATE, še pred ponovnim preverjanjem člana.
            $zapis = (new ZetonService())->porabi($zeton);

            if (!$zapis) {
                Log::add('ZETON_NEVELJAVEN', Log::WARNING, 'com_belauncerunde');

                return;
            }

            // 6. Članstvo ob izdaji ne zadošča: preverimo trenutno stanje liste.
            $params = ComponentHelper::getParams('com_belauncerunde');
            $adapter = new AcymailingAdapter();
            $acymId = (int) $zapis->acym_uporabnik_id;
            $clan = $adapter->poisciPoId($acymId);

            if (!$clan || !$adapter->jeClan($acymId, (int) $params->get('lista_clanov', 0))) {
                $response->error_message = Text::_('PLG_AUTHENTICATION_BELAUNCERUNDE_NI_CLAN');
                Log::add('NI_CLAN acym_id=' . $acymId, Log::WARNING, 'com_belauncerunde');

                return;
            }

            // 7. Uporabnik mora obstajati pred onUserLogin, brez samodejne registracije jedra.
            $uporabnikId = (new ClanService())->zagotoviUporabnika($clan);
            $uporabnik = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($uporabnikId);

            // 8. Nastavimo vsa polja, tudi morebitno napako predhodnega vtičnika.
            $response->status = Authentication::STATUS_SUCCESS;
            $response->type = 'Belauncerunde';
            $response->error_message = '';
            $response->username = $uporabnik->username;
            $response->email = $uporabnik->email;
            $response->fullname = $uporabnik->name;

            // 9. Naloga 06 bo ID prebrala in pobrisala; tu se udeležba še ne vpisuje.
            if ($zapis->runda_id !== null) {
                $app->getSession()->set('com_belauncerunde.runda_po_prijavi', (int) $zapis->runda_id);
            }
        } catch (\Throwable $napaka) {
            // 10. Razred in mesto napake omogočata diagnostiko; sporočilo lahko razkrije email ali SQL.
            $response = $event->getAuthenticationResponse();
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('PLG_AUTHENTICATION_BELAUNCERUNDE_PRIJAVA_NI_USPELA');
            Log::add(
                'PRIJAVA_NI_USPELA razred=' . \get_class($napaka)
                . ' datoteka=' . $napaka->getFile() . ' vrstica=' . $napaka->getLine(),
                Log::ERROR,
                'com_belauncerunde'
            );
        } finally {
            $event->stopPropagation();
        }
    }
}
