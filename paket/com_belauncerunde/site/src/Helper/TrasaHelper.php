<?php

namespace Belaunce\Component\Belauncerunde\Site\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/** Preveri povezavo in sestavi samo opis vdelave, nikoli uporabnikovega HTML-ja. */
abstract class TrasaHelper
{
    /**
     * Dovoli absolutni HTTP/HTTPS URL brez prijavnih podatkov in kontrolnih znakov.
     *
     * @param  string  $url
     * @return  bool
     */
    public static function preveriUrl(string $url): bool
    {
        $deli = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) !== false && $deli !== false
            && !preg_match('/[\x00-\x20\x7f]/', $url)
            && !isset($deli['user']) && !isset($deli['pass'])
            && \in_array(strtolower($deli['scheme'] ?? ''), ['http', 'https'], true);
    }

    /**
     * Iz zaprtega seznama gostiteljev in poti vrne varen src ter ključ naslova.
     *
     * @param  string  $url
     * @param  ?Registry  $parametri
     * @return  ?array
     */
    public static function vdelava(string $url, ?Registry $parametri = null): ?array
    {
        if (!self::preveriUrl($url)) {
            return null;
        }

        $deli = parse_url($url);

        // Vrata, shema, domena ali parametri uporabnika ne smejo določati izvora iframe.
        if (strtolower($deli['scheme']) !== 'https' || isset($deli['port'])) {
            return null;
        }

        $gostitelj = strtolower($deli['host']);
        $pot = $deli['path'] ?? '';
        $parametri ??= ComponentHelper::getParams('com_belauncerunde');
        $ponudnik = '';
        $src = '';

        if (\in_array($gostitelj, ['strava.com', 'www.strava.com'], true)
            && preg_match('~^/routes/([1-9][0-9]*)/?$~D', $pot, $ujemanje)) {
            $ponudnik = 'strava';
            // Oblika je preverjena v uradnem generatorju strava-embeds.com/embed.js.
            $src = 'https://strava-embeds.com/route/' . $ujemanje[1] . '?style=standard';
        } elseif (\in_array($gostitelj, ['komoot.com', 'www.komoot.com', 'komoot.de', 'www.komoot.de'], true)
            && preg_match('~^/(?:[a-z]{2}-[a-z]{2}/)?tour/([1-9][0-9]*)/?$~D', $pot, $ujemanje)) {
            $ponudnik = 'komoot';
            $src = 'https://www.komoot.com/tour/' . $ujemanje[1] . '/embed?layout=classic&profile=1';
        } elseif (\in_array($gostitelj, ['bikemap.net', 'www.bikemap.net'], true)
            && preg_match('~^/(?:[a-z]{2}/)?r/([1-9][0-9]*)(?:-[a-zA-Z0-9-]+)?/?$~D', $pot, $ujemanje)) {
            $ponudnik = 'bikemap';
            $src = 'https://widgets.bikemap.net/r/' . $ujemanje[1] . '?unit=metric&extended=1&distanceMarkers=1';
        }

        if ($ponudnik === '' || (int) $parametri->get('trasa_' . $ponudnik, 1) !== 1) {
            return null;
        }

        return ['src' => $src, 'naslov' => 'COM_BELAUNCERUNDE_VDELAVA_' . strtoupper($ponudnik)];
    }
}
