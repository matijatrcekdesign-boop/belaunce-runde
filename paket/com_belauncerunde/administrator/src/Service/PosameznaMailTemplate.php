<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Predloga za posameznika prepreči dodatne prejemnike iz nastavitev kopiranja.
 * Od 0.21.0 razširja HtmlMailTemplate (HTML + besedilo, brez Joomline postavitve).
 */
class PosameznaMailTemplate extends HtmlMailTemplate
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
