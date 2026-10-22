<?php

namespace Belaunce\Component\Belauncerunde\Site\Helper;

use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;

\defined('_JEXEC') or die;

/** Izbirna captcha uporablja izključno globalno nastavljen Joomlin vtičnik. */
abstract class GostCaptchaHelper
{
    /**
     * Manjkajoča nastavitev ali vtičnik ne smeta onemogočiti obrazca za goste.
     *
     * @param  int  $rundaId
     * @return  ?Captcha
     */
    public static function pridobi(int $rundaId): ?Captcha
    {
        if (!(bool) ComponentHelper::getParams('com_belauncerunde')->get('gostje_captcha', 0)) {
            return null;
        }

        $ime = (string) Factory::getApplication()->get('captcha', '');

        try {
            if ($ime === '' || $ime === '0' || !PluginHelper::isEnabled('captcha', $ime)) {
                throw new \RuntimeException('CAPTCHA_NI_NASTAVLJENA');
            }

            return Captcha::getInstance($ime, ['namespace' => 'com_belauncerunde.gost']);
        } catch (\Throwable $napaka) {
            Log::add('GOST_CAPTCHA_MANJKA runda_id=' . $rundaId, Log::WARNING, 'com_belauncerunde');

            return null;
        }
    }
}
