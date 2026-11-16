<?php

/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Podatki za nove oznake HTML mailov (naloga 30). Samo javni podatki runde (kot na strani runde);
 * oznake se pri pošiljanju vedno dodajo med addUnsafeTags, da Joomla vrednosti ubeži v HTML.
 *
 * @since  0.21.0
 */
final class MailPodatki
{
    /** @var string Barva ob manjkajoči ali neveljavni barvi tipa. */
    public const PRIVZETA_BARVA = '#6ab7e3';

    /** @var string Ozadje oznake težavnosti (težavnosti nimajo barve v šifrantu; kot na strani). */
    public const TEZAVNOST_OZADJE = '#e8eefb';

    /** @var string Besedilo oznake težavnosti (kot `--br-diff` na strani). */
    public const TEZAVNOST_BESEDILO = '#2560c4';

    /** @var string[] Nove oznake, ki jih uporabljajo HTML predloge (tudi seznam v urejevalniku com_mails). */
    public const OZNAKE = [
        'SITEURL', 'NASLOV', 'DAN', 'DATUM', 'URA', 'LOKACIJA', 'DOLZINA', 'VODJA', 'TIP', 'TIP_BARVA',
        'TIP_BARVA_BESEDILA', 'TEZAVNOST', 'TEZAVNOST_BARVA', 'TEZAVNOST_BARVA_BESEDILA', 'PREDGLAVA',
    ];

    /** @var string[] Notranje oznake blokov {SPREMEMBE}…{/SPREMEMBE} in {ODJAVA}…{/ODJAVA}. */
    public const OZNAKE_BLOKOV = ['NAZIV', 'VREDNOST', 'URL'];

    /**
     * Absolutni naslov strani s končnim / (za logotip in povezave v nogi).
     *
     * @return  string
     */
    public static function siteUrl(): string
    {
        return rtrim(Uri::root(), '/') . '/';
    }

    /**
     * Oznake runde za HTML predlogo.
     *
     * @param   int       $rundaId  ID runde.
     * @param   Language  $jezik    Jezik maila (naloženi site nizi com_belauncerunde).
     * @param   string    $termin   UTC termin, ki naj se izpiše (npr. prvotni pri odpovedi); prazno = trenutni.
     *
     * @return  array  Oznake (prazen niz, če runde ni).
     */
    public static function runda(int $rundaId, Language $jezik, string $termin = ''): array
    {
        $baza = Factory::getContainer()->get(DatabaseInterface::class);
        $poizvedba = $baza->createQuery()
            ->select($baza->quoteName(['r.naslov', 'r.zacetek', 'r.lokacija', 'r.dolzina_km']))
            ->select($baza->quoteName('t.naziv', 'tip_naziv'))
            ->select($baza->quoteName('t.barva', 'tip_barva'))
            ->select($baza->quoteName('tz.naziv', 'tezavnost_naziv'))
            ->select($baza->quoteName('u.name', 'vodja_ime'))
            ->from($baza->quoteName('#__belaunce_runde', 'r'))
            ->join('LEFT', $baza->quoteName('#__belaunce_tipi', 't') . ' ON t.id = r.tip_id')
            ->join('LEFT', $baza->quoteName('#__belaunce_tezavnosti', 'tz') . ' ON tz.id = r.tezavnost_id')
            ->join('LEFT', $baza->quoteName('#__users', 'u') . ' ON u.id = r.vodja_id')
            ->where($baza->quoteName('r.id') . ' = :runda')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);
        $runda = $baza->setQuery($poizvedba)->loadObject();

        if ($runda === null) {
            return array_fill_keys(array_diff(self::OZNAKE, ['SITEURL']), '') + ['SITEURL' => self::siteUrl()];
        }

        $cas = self::cas($termin !== '' ? $termin : (string) $runda->zacetek, $jezik);
        $barva = self::barva($runda->tip_barva ?? null);
        $naslov = (string) $runda->naslov;

        return [
            'SITEURL'                  => self::siteUrl(),
            'NASLOV'                   => $naslov,
            'DAN'                      => $cas['dan'],
            'DATUM'                    => $cas['datum'],
            'URA'                      => $cas['ura'],
            'LOKACIJA'                 => trim((string) $runda->lokacija),
            'DOLZINA'                  => sprintf($jezik->_('COM_BELAUNCERUNDE_DOLZINA_KM'), self::dolzina($runda->dolzina_km)),
            'VODJA'                    => (string) ($runda->vodja_ime ?? ''),
            'TIP'                      => (string) ($runda->tip_naziv ?? ''),
            'TIP_BARVA'                => $barva,
            'TIP_BARVA_BESEDILA'       => self::kontrast($barva),
            'TEZAVNOST'                => (string) ($runda->tezavnost_naziv ?? ''),
            'TEZAVNOST_BARVA'          => self::TEZAVNOST_OZADJE,
            'TEZAVNOST_BARVA_BESEDILA' => self::TEZAVNOST_BESEDILO,
            'PREDGLAVA'                => sprintf($jezik->_('COM_BELAUNCERUNDE_MAIL_PREDGLAVA'), $naslov, $cas['dan'], $cas['datum'], $cas['ura']),
        ];
    }

    /**
     * "dan, datum ob ura" v jeziku maila (za seznam sprememb).
     *
     * @param   string    $utc
     * @param   Language  $jezik
     *
     * @return  string
     */
    public static function kdaj(string $utc, Language $jezik): string
    {
        $cas = self::cas($utc, $jezik);

        return sprintf($jezik->_('COM_BELAUNCERUNDE_MAIL_KDAJ'), $cas['dan'], $cas['datum'], $cas['ura']);
    }

    /**
     * Dan, datum in ura v časovnem pasu komponente (Europe/Ljubljana, CasHelper).
     *
     * @return  array  ['dan', 'datum', 'ura']
     */
    private static function cas(string $utc, Language $jezik): array
    {
        $lokalni = new \DateTimeImmutable(CasHelper::izUtc($utc), new \DateTimeZone('UTC'));

        return [
            // Lastni nizi dni (sl: male začetnice), neodvisni od osnovne jezikovne datoteke jedra.
            'dan'   => $jezik->_('COM_BELAUNCERUNDE_MAIL_DAN_' . $lokalni->format('N')),
            'datum' => $lokalni->format($jezik->_('COM_BELAUNCERUNDE_MAIL_DATUM_OBLIKA')),
            'ura'   => $lokalni->format($jezik->_('COM_BELAUNCERUNDE_MAIL_URA_OBLIKA')),
        ];
    }

    /** Preverjena barva tipa (#rrggbb) ali privzeta modra. */
    private static function barva(?string $hex): string
    {
        $hex = trim((string) $hex);

        if (!preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $hex)) {
            return self::PRIVZETA_BARVA;
        }

        if (\strlen($hex) === 4) {
            $hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        }

        return strtolower($hex);
    }

    /** Črno ali belo besedilo z višjim kontrastom po WCAG (kot VidezHelper::kontrastBesedila na strani). */
    private static function kontrast(string $hex): string
    {
        $kanali = [];

        foreach ([1, 3, 5] as $zacetek) {
            $c = hexdec(substr($hex, $zacetek, 2)) / 255;
            $kanali[] = $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        $svetlost = 0.2126 * $kanali[0] + 0.7152 * $kanali[1] + 0.0722 * $kanali[2];

        return ($svetlost + 0.05) / 0.05 > 1.05 / ($svetlost + 0.05) ? '#000000' : '#ffffff';
    }

    /** Dolžina z decimalno vejico brez končne ničle (kot VidezHelper::dolzina). */
    private static function dolzina($kilometri): string
    {
        $vrednost = number_format((float) $kilometri, 1, ',', '');

        return str_ends_with($vrednost, ',0') ? substr($vrednost, 0, -2) : $vrednost;
    }
}
