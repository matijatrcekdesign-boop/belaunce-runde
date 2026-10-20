<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Joomla\CMS\Mail\MailTemplate;

\defined('_JEXEC') or die;

/** Predloga za posameznika prepreči dodatne prejemnike iz nastavitev kopiranja. */
class PosameznaMailTemplate extends MailTemplate
{
    /** Izključi Joomlin samodejni Bcc samo v prebranem objektu te predloge. */
    public static function getTemplate($key, $language)
    {
        $predloga = parent::getTemplate($key, $language);

        if ($predloga !== null) {
            $predloga->params->set('copyto', '');
        }

        return $predloga;
    }
}
