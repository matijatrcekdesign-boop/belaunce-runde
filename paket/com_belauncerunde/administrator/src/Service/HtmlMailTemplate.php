<?php

/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Mail\MailTemplate;

\defined('_JEXEC') or die;

/**
 * Joomlina predloga za pošto, ki maile komponente vedno pošlje kot HTML + besedilo
 * (multipart/alternative) in našega htmlbody NE ovije v Joomlino postavitev (naloga 30).
 *
 * Jedro 6.x (`libraries/src/Mail/MailTemplate.php::send()`) bere obliko pošte samo iz globalnih
 * nastavitev com_mails: `mail_style` (plaintext|html|both, privzeto plaintext) in
 * `disable_htmllayout` (1 = ovij v `layouts/joomla/mail/mailtemplate.php`, privzeto 1). Nastavitev
 * posamezne predloge (`params.disable_htmllayout`) jedro upošteva samo ob globalnem
 * `alternative_mailconfig = 1`. Naš htmlbody je celoten dokument, zato bi ga postavitev ovila dvakrat.
 *
 * Zato send() za čas enega pošiljanja v predpomnjenih nastavitvah com_mails (isti objekt Registry,
 * ki ga vrne ComponentHelper::getParams) nastavi `mail_style = both` in `disable_htmllayout = 0`
 * ter ju v finally vrne v prejšnje stanje. Nastavitve v bazi in drugi maili strani se ne spremenijo.
 *
 * @since  0.21.0
 */
class HtmlMailTemplate extends MailTemplate
{
    /**
     * Prebere predlogo in ji izklopi Joomlino postavitev tudi za primer alternative_mailconfig = 1.
     *
     * @param   string  $key       Ključ predloge.
     * @param   string  $language  Jezik predloge.
     *
     * @return  object|null
     */
    public static function getTemplate($key, $language)
    {
        $predloga = parent::getTemplate($key, $language);

        if ($predloga !== null) {
            $predloga->params->set('disable_htmllayout', 0);
        }

        return $predloga;
    }

    /**
     * Pošlje mail kot HTML + besedilo brez Joomline postavitve; nastavitve com_mails povrne.
     *
     * @return  bool
     */
    public function send()
    {
        $nastavitve = ComponentHelper::getParams('com_mails');
        $prej = [];

        foreach (['mail_style' => 'both', 'disable_htmllayout' => 0] as $kljuc => $vrednost) {
            $prej[$kljuc] = [$nastavitve->exists($kljuc), $nastavitve->get($kljuc)];
            $nastavitve->set($kljuc, $vrednost);
        }

        try {
            return parent::send();
        } finally {
            // Povrnemo tudi ob izjemi, da drugi maili v isti zahtevi ostanejo po nastavitvah strani.
            foreach ($prej as $kljuc => [$obstaja, $vrednost]) {
                if ($obstaja) {
                    $nastavitve->set($kljuc, $vrednost);
                } else {
                    $nastavitve->remove($kljuc);
                }
            }
        }
    }
}
