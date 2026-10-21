<?php

namespace Belaunce\Component\Belauncerunde\Site\Helper;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

/** Sestavi navadno besedilo za deljenje izključno iz dovoljenih javnih polj. */
abstract class DeljenjeHelper
{
    /**
     * Zamenja znane oznake v nastavljivi predlogi brez ponovnega razčlenjevanja vrednosti.
     *
     * @param object $runda Javni podatki runde z začetkom v UTC.
     * @param string $povezava Absolutna kanonična povezava na rundo.
     * @return string Neescapirano besedilo; za varen izpis poskrbi predloga pogleda.
     */
    public static function besedilo(object $runda, string $povezava): string
    {
        $predloga = (string) ComponentHelper::getParams('com_belauncerunde')->get('viber_predloga', '');

        if (trim($predloga) === '') {
            $predloga = Text::_('COM_BELAUNCERUNDE_VIBER_PRIVZETO');
        }

        $predloga = str_replace(["\r\n", "\r"], "\n", $predloga);
        // CasHelper je edina pretvorba časovnega pasu; Date nato samo prevede in oblikuje.
        $lokalni = CasHelper::izUtc((string) $runda->zacetek);
        $datum = new Date($lokalni, 'UTC');

        // Zaprt seznam prepreči vključitev udeležencev, gostov, kontaktov, opomb ali trase.
        // strtr zamenja oznake v enem prehodu; oznak znotraj naslova ali drugih vrednosti ne razširi.
        return strtr($predloga, [
            '{NASLOV}' => (string) $runda->naslov,
            '{DAN}' => $datum->format('l', false),
            '{DATUM}' => $datum->format(Text::_('COM_BELAUNCERUNDE_VIBER_DATUM_OBLIKA'), false),
            '{URA}' => $datum->format(Text::_('COM_BELAUNCERUNDE_VIBER_URA_OBLIKA'), false),
            '{LOKACIJA}' => (string) $runda->lokacija,
            '{TIP}' => (string) ($runda->tip_naziv ?? ''),
            '{TEZAVNOST}' => (string) ($runda->tezavnost_naziv ?? ''),
            '{DOLZINA}' => Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', $runda->dolzina_km),
            '{TRAJANJE}' => isset($runda->trajanje_min)
                ? Text::sprintf('COM_BELAUNCERUNDE_TRAJANJE_MIN', $runda->trajanje_min) : '',
            '{VODJA}' => (string) ($runda->vodja_ime ?? ''),
            '{POVEZAVA}' => $povezava,
        ]);
    }

    /**
     * Kodira besedilo po RFC 3986; nestandardna shema ne sme skozi Joomla router ali Uri.
     *
     * @param string $besedilo Navadno besedilo za pošiljanje.
     * @return string Povezava za izbiro pogovora v Viberju.
     */
    public static function viberPovezava(string $besedilo): string
    {
        return 'viber://forward?text=' . rawurlencode($besedilo);
    }
}
