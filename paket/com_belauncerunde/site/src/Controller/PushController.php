<?php

/**
 * @package     Belaunce.Site
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

/**
 * Naročnina na push obvestila (naloga 24): task=push.naroci in task=push.odjavi.
 *
 * Sprejema samo POST kot navaden obrazec (URLSearchParams) in vedno odgovori z JSON;
 * neveljaven žeton ali pravica vrne 403 {ok:false}, nikoli preusmeritve.
 *
 * @since  0.20.0
 */
class PushController extends BaseController
{
    /**
     * Shrani ali osveži naročnino naprave trenutnega člana ali vodje.
     *
     * @return  void
     */
    public function naroci(): void
    {
        if (!$this->veljavnaZahteva()) {
            $this->odgovor(false, 403);
        }

        $uporabnik = $this->app->getIdentity();

        try {
            $model = $this->factory->createModel('Push', 'Site', ['ignore_request' => true]);

            // Pravica se preveri na strežniku pri vsaki zahtevi, ne samo s skrivanjem kartice.
            if (!$model->smeNarociti($uporabnik)) {
                $this->odgovor(false, 403);
            }

            $ok = $model->naroci(
                (int) $uporabnik->id,
                $this->input->post->getString('endpoint', ''),
                $this->input->post->getString('p256dh', ''),
                $this->input->post->getString('auth', '')
            );
        } catch (\Throwable $napaka) {
            $ok = false;
        }

        $this->odgovor($ok, $ok ? 200 : 400);
    }

    /**
     * Izbriše naročnino te naprave, samo če pripada trenutnemu uporabniku.
     *
     * @return  void
     */
    public function odjavi(): void
    {
        $uporabnik = $this->app->getIdentity();

        if (!$this->veljavnaZahteva() || $uporabnik === null || $uporabnik->guest) {
            $this->odgovor(false, 403);
        }

        try {
            $model = $this->factory->createModel('Push', 'Site', ['ignore_request' => true]);
            $model->odjavi((int) $uporabnik->id, $this->input->post->getString('endpoint', ''));
            $ok = true;
        } catch (\Throwable $napaka) {
            $ok = false;
        }

        $this->odgovor($ok, $ok ? 200 : 400);
    }

    /** POST z veljavnim CSRF žetonom (brez preusmeritve ob napaki). */
    private function veljavnaZahteva(): bool
    {
        return $this->input->server->getString('REQUEST_METHOD') === 'POST' && $this->checkToken('post', false);
    }

    /**
     * Pošlje JSON odgovor in zaključi zahtevo.
     *
     * @param   bool  $ok    Izid.
     * @param   int   $koda  HTTP status.
     *
     * @return  never
     */
    private function odgovor(bool $ok, int $koda): never
    {
        $this->app->allowCache(false);
        $this->app->setHeader('status', (string) $koda, true)
            ->setHeader('Content-Type', 'application/json; charset=utf-8', true)
            ->setHeader('Cache-Control', 'no-store', true)
            ->sendHeaders();

        echo json_encode(['ok' => $ok]);
        $this->app->close();
    }
}
