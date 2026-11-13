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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Web Push obvestila (D64–D66): lastna implementacija RFC 8291 (aes128gcm) in RFC 8292 (VAPID ES256)
 * samo s PHP openssl in hash_hkdf, brez zunanjih knjižnic.
 *
 * Push je dodatek k mailu: nobena javna metoda ne vrže izjeme in napaka nikoli ne vpliva na prijavo,
 * shranjevanje runde ali mail. V dnevnik gredo samo kode, ID-ji in števila — nikoli endpoint, ključi,
 * vsebina sporočila ali imena.
 *
 * @since  0.20.0
 */
class PushService
{
    /** @var int Dolžina nestisnjene točke P-256 (0x04 || X || Y). */
    public const DOLZINA_JAVNI = 65;

    /** @var int Dolžina zasebnega skalarja P-256. */
    public const DOLZINA_ZASEBNI = 32;

    /** @var int Dolžina skrivnosti auth naročnine. */
    public const DOLZINA_AUTH = 16;

    /** @var int Največja dolžina JSON vsebine, da en zapis aes128gcm zanesljivo zadošča vsem storitvam. */
    private const NAJVEC_VSEBINE = 3000;

    /** @var int Velikost zapisa (rs) v glavi aes128gcm. */
    private const VELIKOST_ZAPISA = 4096;

    /** @var int Po toliko zaporednih napakah naročnino izbrišemo. */
    private const NAJVEC_NAPAK = 5;

    /** @var string DER predpona SubjectPublicKeyInfo za nestisnjeno točko P-256. */
    private const DER_JAVNI = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    /** @var string DER začetek SEC1 ECPrivateKey (verzija 1, oktetni niz 32 B). */
    private const DER_ZASEBNI_ZACETEK = '30770201010420';

    /** @var string DER nadaljevanje SEC1 z OID prime256v1 in začetkom javne točke. */
    private const DER_ZASEBNI_SREDINA = 'a00a06082a8648ce3d030107a144034200';

    /**
     * Pošlje push obvestilo vsem napravam naštetih uporabnikov.
     *
     * @param   array  $uporabnikIdji  ID-ji uporabnikov (podvojeni in neveljavni se izločijo).
     * @param   array  $sporocilo      ['naslov', 'besedilo', 'url' (relativen), 'oznaka' (runda-{id})].
     * @param   int    $izpusti        Uporabnik, ki je dogodek sprožil in obvestila ne dobi.
     * @param   array  $moznosti       Neobvezno: 'dogodek' (koda za dnevnik), 'ttl' (s), 'nujnost' (normal|high).
     *
     * @return  int  Število uspešno poslanih obvestil (naprav).
     */
    public static function obvestiUporabnike(array $uporabnikIdji, array $sporocilo, int $izpusti = 0, array $moznosti = []): int
    {
        $naprav = 0;
        $uspesnih = 0;
        $dogodek = preg_replace('/[^a-z_]/', '', (string) ($moznosti['dogodek'] ?? 'push')) ?: 'push';

        try {
            $params = ComponentHelper::getParams('com_belauncerunde');

            if ((int) $params->get('push_vklop', 1) === 0) {
                return 0;
            }

            $idji = [];

            foreach ($uporabnikIdji as $id) {
                $id = (int) $id;

                if ($id > 0 && $id !== $izpusti) {
                    $idji[$id] = $id;
                }
            }

            if ($idji === []) {
                return 0;
            }

            $kljuca = self::kljuca();

            if ($kljuca === null) {
                return 0;
            }

            $baza = Factory::getContainer()->get(DatabaseInterface::class);
            $poizvedba = $baza->createQuery()
                ->select($baza->quoteName(['id', 'uporabnik_id', 'endpoint', 'p256dh', 'auth']))
                ->from($baza->quoteName('#__belaunce_push'))
                ->whereIn($baza->quoteName('uporabnik_id'), array_values($idji))
                ->order($baza->quoteName('id') . ' ASC');
            $narocnine = $baza->setQuery($poizvedba)->loadObjectList();
            $naprav = \count($narocnine);

            if ($naprav === 0) {
                return 0;
            }

            $vsebina = self::vsebina($sporocilo);
            $ttl = max(60, (int) ($moznosti['ttl'] ?? 86400));
            $nujnost = ($moznosti['nujnost'] ?? 'normal') === 'high' ? 'high' : 'normal';
            $tema = substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($sporocilo['oznaka'] ?? '')), 0, 32);
            $subjekt = self::subjekt();
            $http = HttpFactory::getHttp();
            $jwtji = [];

            foreach ($narocnine as $narocnina) {
                $koda = 0;

                try {
                    $ciljniKljuc = self::base64urlDekodiraj((string) $narocnina->p256dh);
                    $auth = self::base64urlDekodiraj((string) $narocnina->auth);
                    $publika = self::publika((string) $narocnina->endpoint);

                    if ($ciljniKljuc === null || $auth === null || $publika === '') {
                        throw new \RuntimeException('narocnina');
                    }

                    // En JWT na storitev (FCM, Apple, Mozilla) za ves klic; podpis je drag.
                    if (!isset($jwtji[$publika])) {
                        $jwtji[$publika] = self::vapidJwt($publika, $subjekt, time() + 12 * 3600, $kljuca[1], $kljuca[0]);
                    }

                    $glave = [
                        'Content-Type'     => 'application/octet-stream',
                        'Content-Encoding' => 'aes128gcm',
                        'TTL'              => (string) $ttl,
                        'Urgency'          => $nujnost,
                        'Authorization'    => 'vapid t=' . $jwtji[$publika] . ', k=' . self::base64urlKodiraj($kljuca[0]),
                    ];

                    if ($tema !== '') {
                        $glave['Topic'] = $tema;
                    }

                    $telo = self::sifriraj($vsebina, $ciljniKljuc, $auth);
                    $odziv = $http->post((string) $narocnina->endpoint, $telo, $glave, 4);
                    $koda = (int) $odziv->getStatusCode();
                } catch (\Throwable $napaka) {
                    // Besedilo izjeme lahko vsebuje endpoint; ne beležimo ga.
                    $koda = 0;
                }

                if ($koda === 201 || $koda === 202) {
                    $uspesnih++;
                }

                self::poOdzivu($baza, (int) $narocnina->id, $koda);
            }
        } catch (\Throwable $napaka) {
            // Napaka pusha nikoli ne prekine klicatelja (D66).
        }

        if ($naprav > 0) {
            self::dnevnik('PUSH_POSLANO dogodek=' . $dogodek . ' prejemnikov=' . $naprav . ' uspesnih=' . $uspesnih, Log::INFO);
        }

        return $uspesnih;
    }

    /**
     * Sprožilec 7.1: vodji ob novi prijavi člana ali gosta, brez imena udeleženca (D65).
     *
     * @param   int  $rundaId      ID runde.
     * @param   int  $stevilo      Skupno število udeležencev po prijavi.
     * @param   int  $uporabnikId  ID prijavljenega člana ali 0 za gosta.
     *
     * @return  int
     */
    public static function obvestiVodjoOPrijavi(int $rundaId, int $stevilo, int $uporabnikId = 0): int
    {
        try {
            if ((int) ComponentHelper::getParams('com_belauncerunde')->get('push_nova_prijava', 1) === 0) {
                return 0;
            }

            $runda = self::runda($rundaId);

            // Vodja ne dobi obvestila o lastni prijavi (enako kot mail).
            if ($runda === null || (int) $runda->stanje !== 1 || (int) $runda->vodja_id === $uporabnikId) {
                return 0;
            }

            $jezik = self::jezik();
            $cas = self::cas((string) $runda->zacetek, $jezik);
            $sporocilo = [
                'naslov'   => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_NOVA_PRIJAVA_NASLOV'), (string) $runda->naslov),
                'besedilo' => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_NOVA_PRIJAVA_BESEDILO'), $cas['datum'], $cas['ura'], $stevilo),
                'url'      => self::url($rundaId),
                'oznaka'   => 'runda-' . $rundaId,
            ];

            return self::obvestiUporabnike([(int) $runda->vodja_id], $sporocilo, $uporabnikId, ['dogodek' => 'nova_prijava']);
        } catch (\Throwable $napaka) {
            return 0;
        }
    }

    /**
     * Sprožilec 7.2: odpoved ali sprememba sprožilnih polj → prijavljeni člani in vodja (D54, D57),
     * razen uporabnika, ki je shranil (D66).
     *
     * @param   int     $rundaId   ID runde.
     * @param   bool    $odpoved   Ali je bila runda pravkar odpovedana.
     * @param   array   $polja     Spremenjena sprožilna polja (iz RundaTable).
     * @param   string  $prvotni   Prvotni UTC začetek (za besedilo odpovedi).
     *
     * @return  int
     */
    public static function poZapisu(int $rundaId, bool $odpoved, array $polja, string $prvotni = ''): int
    {
        try {
            if ((!$odpoved && $polja === [])
                || (int) ComponentHelper::getParams('com_belauncerunde')->get('push_sprememba', 1) === 0) {
                return 0;
            }

            $runda = self::runda($rundaId);

            if ($runda === null) {
                return 0;
            }

            $jezik = self::jezik();
            $termin = $odpoved && $prvotni !== '' ? $prvotni : (string) $runda->zacetek;
            $cas = self::cas($termin, $jezik);
            $naslov = (string) $runda->naslov;

            if ($odpoved) {
                $sporocilo = [
                    'naslov'   => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_ODPOVED_NASLOV'), $naslov),
                    'besedilo' => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_ODPOVED_BESEDILO'), $cas['dan'], $cas['datum'], $cas['ura']),
                ];
            } else {
                $sporocilo = [
                    'naslov'   => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_SPREMEMBA_NASLOV'), $naslov),
                    'besedilo' => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_SPREMEMBA_BESEDILO'), $cas['datum'], $cas['ura'],
                        trim((string) $runda->lokacija)),
                ];
            }

            $sporocilo['url'] = self::url($rundaId);
            $sporocilo['oznaka'] = 'runda-' . $rundaId;
            $izpusti = (int) Factory::getApplication()->getIdentity()->id;

            return self::obvestiUporabnike(self::udelezenci($rundaId, (int) $runda->vodja_id), $sporocilo, $izpusti, [
                'dogodek' => $odpoved ? 'odpoved' : 'sprememba',
                'nujnost' => $odpoved ? 'high' : 'normal',
            ]);
        } catch (\Throwable $napaka) {
            return 0;
        }
    }

    /**
     * Sprožilec 7.4: nova objavljena runda → vsi člani z vsaj eno naročnino, razen uporabnika,
     * ki je shranil. Gostje nikoli, brez osebnih podatkov (D65).
     *
     * Runda se najprej označi z UPDATE ... WHERE nova_obvescena IS NULL; samo zapis, ki vrstico
     * dejansko spremeni, pošlje push (brez dvojnega pošiljanja). Oznaka se nastavi tudi, ko je
     * sprožilec izklopljen, da kasnejši vklop ne pošlje pusha za že obstoječe runde.
     *
     * @param   int  $rundaId  ID runde.
     *
     * @return  int  Število uspešno poslanih obvestil.
     */
    public static function novaRunda(int $rundaId): int
    {
        try {
            $baza = Factory::getContainer()->get(DatabaseInterface::class);
            $zdaj = CasHelper::zdajUtc();
            // Ločena spremenljivka za vsako vezavo (pravilo 16).
            $zdajStart = $zdaj;
            $poizvedba = $baza->createQuery()->update($baza->quoteName('#__belaunce_runde'))
                ->set($baza->quoteName('nova_obvescena') . ' = :zdaj')
                ->where($baza->quoteName('id') . ' = :runda')
                ->where($baza->quoteName('nova_obvescena') . ' IS NULL')
                ->where($baza->quoteName('stanje') . ' = 1')
                ->where($baza->quoteName('zacetek') . ' > :zdajStart')
                ->bind(':zdaj', $zdaj, ParameterType::STRING)
                ->bind(':runda', $rundaId, ParameterType::INTEGER)
                ->bind(':zdajStart', $zdajStart, ParameterType::STRING);
            $baza->setQuery($poizvedba)->execute();

            if ($baza->getAffectedRows() !== 1) {
                return 0;
            }

            $params = ComponentHelper::getParams('com_belauncerunde');
            $skupina = (int) $params->get('skupina_clani', 0);

            if ((int) $params->get('push_nova_runda', 1) === 0 || $skupina <= 0) {
                return 0;
            }

            $runda = self::runda($rundaId);

            if ($runda === null) {
                return 0;
            }

            // Samo člani z vsaj eno napravo; brez nastavljene skupine nihče.
            $poizvedba = $baza->createQuery()->select('DISTINCT ' . $baza->quoteName('p.uporabnik_id'))
                ->from($baza->quoteName('#__belaunce_push', 'p'))
                ->join('INNER', $baza->quoteName('#__user_usergroup_map', 'm') . ' ON m.user_id = p.uporabnik_id')
                ->where($baza->quoteName('m.group_id') . ' = :skupina')
                ->bind(':skupina', $skupina, ParameterType::INTEGER);
            $prejemniki = array_map('intval', $baza->setQuery($poizvedba)->loadColumn());

            if ($prejemniki === []) {
                return 0;
            }

            $jezik = self::jezik();
            $cas = self::cas((string) $runda->zacetek, $jezik);
            $sporocilo = [
                'naslov'   => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_NOVA_RUNDA_NASLOV'), (string) $runda->naslov),
                'besedilo' => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_NOVA_RUNDA_BESEDILO'), $cas['dan'], $cas['datum'],
                    $cas['ura'], trim((string) $runda->lokacija)),
                'url'      => self::url($rundaId),
                'oznaka'   => 'runda-' . $rundaId,
            ];
            $izpusti = (int) Factory::getApplication()->getIdentity()->id;

            return self::obvestiUporabnike($prejemniki, $sporocilo, $izpusti, [
                'dogodek' => 'nova_runda', 'nujnost' => 'normal', 'ttl' => self::doStarta((string) $runda->zacetek),
            ]);
        } catch (\Throwable $napaka) {
            return 0;
        }
    }

    /**
     * Sprožilec 7.3: opomnik pred štartom za največ 20 rund na zagon (Scheduler).
     *
     * Runda se najprej označi z UPDATE ... WHERE opomnik_poslan IS NULL; samo zagon, ki vrstico
     * dejansko spremeni, pošlje opomnik (brez dvojnega pošiljanja ob vzporednem zagonu).
     *
     * @return  int  Število rund, za katere je bil opomnik poslan.
     */
    public static function posljiOpomnike(): int
    {
        $rund = 0;

        try {
            $params = ComponentHelper::getParams('com_belauncerunde');

            if ((int) $params->get('push_vklop', 1) === 0 || (int) $params->get('push_opomnik', 1) === 0
                || self::kljuca() === null) {
                return 0;
            }

            $ur = (int) $params->get('push_opomnik_ur', 2);
            $ur = \in_array($ur, [1, 2, 3, 6, 12, 24], true) ? $ur : 2;
            $zdaj = CasHelper::zdajUtc();
            $meja = (new \DateTimeImmutable($zdaj, new \DateTimeZone('UTC')))->modify('+' . $ur . ' hours')->format('Y-m-d H:i:s');
            $baza = Factory::getContainer()->get(DatabaseInterface::class);
            $poizvedba = $baza->createQuery()->select($baza->quoteName('id'))
                ->from($baza->quoteName('#__belaunce_runde'))
                ->where($baza->quoteName('stanje') . ' = 1')
                ->where($baza->quoteName('opomnik_poslan') . ' IS NULL')
                ->where($baza->quoteName('zacetek') . ' > :zdaj')
                ->where($baza->quoteName('zacetek') . ' <= :meja')
                ->order($baza->quoteName('zacetek') . ' ASC')
                ->bind(':zdaj', $zdaj, ParameterType::STRING)
                ->bind(':meja', $meja, ParameterType::STRING)
                ->setLimit(20);
            $idji = array_map('intval', $baza->setQuery($poizvedba)->loadColumn());

            if ($idji === []) {
                return 0;
            }

            $jezik = self::jezik();
            $danes = (new \DateTimeImmutable(CasHelper::izUtc($zdaj), new \DateTimeZone('UTC')))->format('Y-m-d');

            foreach ($idji as $rundaId) {
                try {
                    $poizvedba = $baza->createQuery()->update($baza->quoteName('#__belaunce_runde'))
                        ->set($baza->quoteName('opomnik_poslan') . ' = :zdaj')
                        ->where($baza->quoteName('id') . ' = :runda')
                        ->where($baza->quoteName('opomnik_poslan') . ' IS NULL')
                        ->bind(':zdaj', $zdaj, ParameterType::STRING)
                        ->bind(':runda', $rundaId, ParameterType::INTEGER);
                    $baza->setQuery($poizvedba)->execute();

                    if ($baza->getAffectedRows() !== 1) {
                        continue;
                    }

                    $runda = self::runda($rundaId);

                    if ($runda === null || (int) $runda->stanje !== 1) {
                        continue;
                    }

                    $cas = self::cas((string) $runda->zacetek, $jezik);
                    $kljuc = $cas['iso'] === $danes ? 'COM_BELAUNCERUNDE_PUSH_OPOMNIK_BESEDILO_DANES'
                        : 'COM_BELAUNCERUNDE_PUSH_OPOMNIK_BESEDILO';
                    $besedilo = $cas['iso'] === $danes
                        ? sprintf($jezik->_($kljuc), $cas['ura'], trim((string) $runda->lokacija))
                        : sprintf($jezik->_($kljuc), $cas['dan'], $cas['ura'], trim((string) $runda->lokacija));
                    $sporocilo = [
                        'naslov'   => sprintf($jezik->_('COM_BELAUNCERUNDE_PUSH_OPOMNIK_NASLOV'), (string) $runda->naslov),
                        'besedilo' => $besedilo,
                        'url'      => self::url($rundaId),
                        'oznaka'   => 'runda-' . $rundaId,
                    ];
                    self::obvestiUporabnike(self::udelezenci($rundaId, (int) $runda->vodja_id), $sporocilo, 0, [
                        'dogodek' => 'opomnik', 'nujnost' => 'high', 'ttl' => self::doStarta((string) $runda->zacetek),
                    ]);
                    $rund++;
                } catch (\Throwable $napaka) {
                    self::dnevnik('PUSH_OPOMNIK_NAPAKA runda_id=' . $rundaId, Log::WARNING);
                }
            }
        } catch (\Throwable $napaka) {
            self::dnevnik('PUSH_OPOMNIK_NAPAKA', Log::WARNING);
        }

        return $rund;
    }

    /**
     * Vzdrževanje: izbriše osirotele, nečlanske in dolgo neuspešne naročnine.
     * Uporabnikov in skupin nikoli ne spreminja (D52, D53).
     *
     * @return  int  Število izbrisanih naročnin.
     */
    public static function ciscenje(): int
    {
        $baza = Factory::getContainer()->get(DatabaseInterface::class);
        $izbrisanih = 0;
        $zdaj = CasHelper::zdajUtc();
        $meja = (new \DateTimeImmutable($zdaj, new \DateTimeZone('UTC')))->modify('-180 days')->format('Y-m-d H:i:s');

        // 1) Uporabnik ne obstaja več.
        $poizvedba = $baza->createQuery()->delete($baza->quoteName('#__belaunce_push'))
            ->where('NOT EXISTS (SELECT 1 FROM ' . $baza->quoteName('#__users') . ' u WHERE u.id = '
                . $baza->quoteName('#__belaunce_push.uporabnik_id') . ')');
        $baza->setQuery($poizvedba)->execute();
        $izbrisanih += $baza->getAffectedRows();

        // 2) Ni več v skupini Člani in ni vodja prihodnje runde. Brez nastavljene skupine ne brišemo nič,
        // da napačna nastavitev ne odjavi vseh naprav.
        $skupina = (int) ComponentHelper::getParams('com_belauncerunde')->get('skupina_clani', 0);

        if ($skupina > 0) {
            $poizvedba = $baza->createQuery()->delete($baza->quoteName('#__belaunce_push'))
                ->where('NOT EXISTS (SELECT 1 FROM ' . $baza->quoteName('#__user_usergroup_map') . ' m WHERE m.user_id = '
                    . $baza->quoteName('#__belaunce_push.uporabnik_id') . ' AND m.group_id = :skupina)')
                ->where('NOT EXISTS (SELECT 1 FROM ' . $baza->quoteName('#__belaunce_runde') . ' r WHERE r.vodja_id = '
                    . $baza->quoteName('#__belaunce_push.uporabnik_id') . ' AND r.zacetek > :zdaj)')
                ->bind(':skupina', $skupina, ParameterType::INTEGER)
                ->bind(':zdaj', $zdaj, ParameterType::STRING);
            $baza->setQuery($poizvedba)->execute();
            $izbrisanih += $baza->getAffectedRows();
        }

        // 3) Brez uspeha 180 dni (ločena spremenljivka za vsako vezavo, pravilo 16).
        $mejaUstvarjeno = $meja;
        $poizvedba = $baza->createQuery()->delete($baza->quoteName('#__belaunce_push'))
            ->where('((' . $baza->quoteName('zadnji_uspeh') . ' IS NOT NULL AND ' . $baza->quoteName('zadnji_uspeh') . ' < :meja1)'
                . ' OR (' . $baza->quoteName('zadnji_uspeh') . ' IS NULL AND ' . $baza->quoteName('ustvarjeno') . ' < :meja2))')
            ->bind(':meja1', $meja, ParameterType::STRING)
            ->bind(':meja2', $mejaUstvarjeno, ParameterType::STRING);
        $baza->setQuery($poizvedba)->execute();
        $izbrisanih += $baza->getAffectedRows();

        self::dnevnik('PUSH_CISCENJE izbrisanih=' . $izbrisanih, Log::INFO);

        return $izbrisanih;
    }

    /**
     * Javni ključ VAPID za frontend (applicationServerKey) ali prazen niz, če push ni na voljo.
     *
     * @return  string
     */
    public static function javniKljuc(): string
    {
        $kljuca = self::kljuca();

        return $kljuca === null ? '' : self::base64urlKodiraj($kljuca[0]);
    }

    /**
     * Šifrira vsebino po RFC 8291 (aes128gcm, en zapis).
     *
     * Neobvezna parametra $efemerniZasebni/$efemerniJavni in $sol sta namenjena SAMO testu
     * (orodja/test-webpush.php s testnim vektorjem RFC 8291, Dodatek A); v produkciji se vedno
     * ustvarita naključno.
     *
     * @param   string       $vsebina          Odprto besedilo (JSON).
     * @param   string       $ciljniJavni      Javni ključ naprave (65 B, p256dh).
     * @param   string       $auth             Skrivnost auth naprave (16 B).
     * @param   string|null  $efemerniZasebni  Test: zasebni skalar (32 B).
     * @param   string|null  $efemerniJavni    Test: javna točka (65 B).
     * @param   string|null  $sol              Test: sol (16 B).
     *
     * @return  string  Telo zahteve (glava aes128gcm + šifrirano besedilo + oznaka).
     */
    public static function sifriraj(
        string $vsebina,
        string $ciljniJavni,
        string $auth,
        ?string $efemerniZasebni = null,
        ?string $efemerniJavni = null,
        ?string $sol = null
    ): string {
        if (\strlen($ciljniJavni) !== self::DOLZINA_JAVNI || $ciljniJavni[0] !== "\x04" || \strlen($auth) !== self::DOLZINA_AUTH) {
            throw new \InvalidArgumentException('kljuc');
        }

        if ($efemerniZasebni === null || $efemerniJavni === null) {
            [$efemerniJavni, $efemerniZasebni] = self::ustvariPar();
        }

        $sol = $sol ?? random_bytes(16);
        $zasebni = openssl_pkey_get_private(self::pemZasebni($efemerniZasebni, $efemerniJavni));
        $ciljni = openssl_pkey_get_public(self::pemJavni($ciljniJavni));

        if ($zasebni === false || $ciljni === false) {
            throw new \RuntimeException('pem');
        }

        $ecdh = openssl_pkey_derive($ciljni, $zasebni, 0);

        if (!\is_string($ecdh) || \strlen($ecdh) !== 32) {
            throw new \RuntimeException('ecdh');
        }

        // RFC 8291, razdelek 3.4: IKM iz ECDH in auth, nato CEK in nonce po RFC 8188.
        $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0" . $ciljniJavni . $efemerniJavni, $auth);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $sol);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $sol);
        $oznaka = '';
        // Ločilo 0x02 označuje zadnji (edini) zapis, brez dodatnega polnila.
        $sifrirano = openssl_encrypt($vsebina . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $oznaka, '', 16);

        if (!\is_string($sifrirano) || \strlen($oznaka) !== 16) {
            throw new \RuntimeException('gcm');
        }

        return $sol . pack('N', self::VELIKOST_ZAPISA) . \chr(self::DOLZINA_JAVNI) . $efemerniJavni . $sifrirano . $oznaka;
    }

    /**
     * Sestavi in podpiše VAPID JWT (RFC 8292, ES256).
     *
     * @param   string  $publika   scheme://host[:port] endpointa.
     * @param   string  $subjekt   mailto: ali https: kontakt.
     * @param   int     $poteka    Unix čas poteka (največ 24 h).
     * @param   string  $zasebni   Zasebni skalar VAPID (32 B).
     * @param   string  $javni     Javna točka VAPID (65 B).
     *
     * @return  string
     */
    public static function vapidJwt(string $publika, string $subjekt, int $poteka, string $zasebni, string $javni): string
    {
        $glava = self::base64urlKodiraj((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $trditve = self::base64urlKodiraj((string) json_encode(['aud' => $publika, 'exp' => $poteka, 'sub' => $subjekt],
            JSON_UNESCAPED_SLASHES));
        $vhod = $glava . '.' . $trditve;
        $kljuc = openssl_pkey_get_private(self::pemZasebni($zasebni, $javni));
        $podpis = '';

        if ($kljuc === false || !openssl_sign($vhod, $podpis, $kljuc, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('podpis');
        }

        return $vhod . '.' . self::base64urlKodiraj(self::derVSurovi($podpis));
    }

    /**
     * Pretvori DER podpis ECDSA (SEQUENCE{INTEGER r, INTEGER s}) v surovih 64 B r||s.
     *
     * @param   string  $der
     *
     * @return  string
     */
    public static function derVSurovi(string $der): string
    {
        $pozicija = 0;

        if (\strlen($der) < 8 || $der[$pozicija++] !== "\x30") {
            throw new \RuntimeException('der');
        }

        $dolzina = \ord($der[$pozicija++]);

        if ($dolzina & 0x80) {
            $pozicija += $dolzina & 0x7f;
        }

        $rezultat = '';

        foreach ([0, 1] as $i) {
            if (!isset($der[$pozicija + 1]) || $der[$pozicija++] !== "\x02") {
                throw new \RuntimeException('der');
            }

            $dolzina = \ord($der[$pozicija++]);
            $stevilo = ltrim(substr($der, $pozicija, $dolzina), "\0");
            $pozicija += $dolzina;

            if (\strlen($stevilo) > 32) {
                throw new \RuntimeException('der');
            }

            $rezultat .= str_pad($stevilo, 32, "\0", STR_PAD_LEFT);
        }

        return $rezultat;
    }

    /**
     * Pretvori surovih 64 B r||s nazaj v DER (za preverjanje z openssl_verify).
     *
     * @param   string  $surovi
     *
     * @return  string
     */
    public static function surovVDer(string $surovi): string
    {
        if (\strlen($surovi) !== 64) {
            throw new \InvalidArgumentException('surovi');
        }

        $deli = '';

        foreach (str_split($surovi, 32) as $stevilo) {
            $stevilo = ltrim($stevilo, "\0");

            // Pozitivno celo število z nastavljenim najvišjim bitom potrebuje vodilno ničlo.
            if ($stevilo === '' || (\ord($stevilo[0]) & 0x80)) {
                $stevilo = "\0" . $stevilo;
            }

            $deli .= "\x02" . \chr(\strlen($stevilo)) . $stevilo;
        }

        return "\x30" . \chr(\strlen($deli)) . $deli;
    }

    /**
     * Surova javna točka (65 B) → PEM SubjectPublicKeyInfo.
     *
     * @param   string  $tocka
     *
     * @return  string
     */
    public static function pemJavni(string $tocka): string
    {
        return self::pem('PUBLIC KEY', hex2bin(self::DER_JAVNI) . $tocka);
    }

    /**
     * Surov zasebni skalar (32 B) in javna točka (65 B) → PEM SEC1 "EC PRIVATE KEY".
     *
     * @param   string  $d
     * @param   string  $tocka
     *
     * @return  string
     */
    public static function pemZasebni(string $d, string $tocka): string
    {
        if (\strlen($d) !== self::DOLZINA_ZASEBNI || \strlen($tocka) !== self::DOLZINA_JAVNI) {
            throw new \InvalidArgumentException('kljuc');
        }

        return self::pem('EC PRIVATE KEY', hex2bin(self::DER_ZASEBNI_ZACETEK) . $d
            . hex2bin(self::DER_ZASEBNI_SREDINA) . $tocka);
    }

    /**
     * Ustvari nov par P-256.
     *
     * @return  array  [javna točka 65 B, zasebni skalar 32 B]
     */
    public static function ustvariPar(): array
    {
        $kljuc = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $podrobnosti = $kljuc === false ? false : openssl_pkey_get_details($kljuc);

        if (!\is_array($podrobnosti) || !isset($podrobnosti['ec']['x'], $podrobnosti['ec']['y'], $podrobnosti['ec']['d'])) {
            throw new \RuntimeException('par');
        }

        $ec = $podrobnosti['ec'];

        return [
            "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT),
            str_pad($ec['d'], 32, "\0", STR_PAD_LEFT),
        ];
    }

    /**
     * Base64url brez polnila.
     *
     * @param   string  $podatki
     *
     * @return  string
     */
    public static function base64urlKodiraj(string $podatki): string
    {
        return rtrim(strtr(base64_encode($podatki), '+/', '-_'), '=');
    }

    /**
     * Strogo dekodira base64url (sprejme tudi polnilo); ob neveljavnem vnosu vrne null.
     *
     * @param   string  $niz
     *
     * @return  string|null
     */
    public static function base64urlDekodiraj(string $niz): ?string
    {
        $niz = rtrim(trim($niz), '=');

        if ($niz === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $niz)) {
            return null;
        }

        $rezultat = base64_decode(strtr($niz, '-_', '+/'), true);

        return $rezultat === false ? null : $rezultat;
    }

    /**
     * Prebere in preveri ključa VAPID iz Možnosti.
     *
     * @return  array|null  [javna točka 65 B, zasebni skalar 32 B] ali null (push onemogočen).
     */
    private static function kljuca(): ?array
    {
        $params = ComponentHelper::getParams('com_belauncerunde');
        $javni = self::base64urlDekodiraj((string) $params->get('push_vapid_javni', ''));
        $zasebni = self::base64urlDekodiraj((string) $params->get('push_vapid_zasebni', ''));

        if ($javni === null || $zasebni === null || \strlen($javni) !== self::DOLZINA_JAVNI || $javni[0] !== "\x04"
            || \strlen($zasebni) !== self::DOLZINA_ZASEBNI) {
            // Vrednosti ključa nikoli ne beležimo.
            self::dnevnik('PUSH_KLJUC_NEVELJAVEN', Log::WARNING);

            return null;
        }

        return [$javni, $zasebni];
    }

    /** Pripravi JSON vsebino ≤ 3000 B; skrajša se samo besedilo, nikoli JSON. */
    private static function vsebina(array $sporocilo): string
    {
        $url = (string) ($sporocilo['url'] ?? '/runde');
        $url = str_starts_with($url, '/') && !str_starts_with($url, '//') ? $url : '/runde';
        $podatki = [
            'title' => mb_substr((string) ($sporocilo['naslov'] ?? ''), 0, 200),
            'body'  => (string) ($sporocilo['besedilo'] ?? ''),
            'url'   => $url,
            'tag'   => (string) ($sporocilo['oznaka'] ?? ''),
        ];
        $json = (string) json_encode($podatki, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        while (\strlen($json) > self::NAJVEC_VSEBINE && $podatki['body'] !== '') {
            $podatki['body'] = mb_substr($podatki['body'], 0, max(0, mb_strlen($podatki['body']) - 100));
            $json = (string) json_encode($podatki, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $json;
    }

    /** Kontakt VAPID: nastavitev mailto:/https: ali "mailto:" + mailfrom iz Global Config. */
    private static function subjekt(): string
    {
        $subjekt = trim((string) ComponentHelper::getParams('com_belauncerunde')->get('push_vapid_subject', ''));

        if (preg_match('#^(mailto:\S+|https://\S+)$#i', $subjekt)) {
            return $subjekt;
        }

        return 'mailto:' . (string) Factory::getApplication()->get('mailfrom');
    }

    /** scheme://host[:port] endpointa ali prazen niz za neveljaven naslov. */
    private static function publika(string $endpoint): string
    {
        $deli = parse_url($endpoint);

        if (!\is_array($deli) || strtolower((string) ($deli['scheme'] ?? '')) !== 'https' || empty($deli['host'])) {
            return '';
        }

        return 'https://' . strtolower((string) $deli['host']) . (isset($deli['port']) ? ':' . (int) $deli['port'] : '');
    }

    /**
     * Posodobi naročnino po odzivu: uspeh ponastavi napake, 404/410 izbriše, drugo šteje napako.
     *
     * @param   DatabaseInterface  $baza
     * @param   int                $id    ID naročnine.
     * @param   int                $koda  HTTP koda ali 0 za izjemo.
     *
     * @return  void
     */
    private static function poOdzivu(DatabaseInterface $baza, int $id, int $koda): void
    {
        try {
            if ($koda === 201 || $koda === 202) {
                $zdaj = CasHelper::zdajUtc();
                $poizvedba = $baza->createQuery()->update($baza->quoteName('#__belaunce_push'))
                    ->set($baza->quoteName('zadnji_uspeh') . ' = :zdaj')->set($baza->quoteName('napake') . ' = 0')
                    ->where($baza->quoteName('id') . ' = :id')
                    ->bind(':zdaj', $zdaj, ParameterType::STRING)->bind(':id', $id, ParameterType::INTEGER);
                $baza->setQuery($poizvedba)->execute();

                return;
            }

            self::dnevnik('PUSH_NAPAKA id=' . $id . ' koda=' . $koda, Log::WARNING);

            // 404/410: naprava je odjavljena pri push storitvi.
            if ($koda !== 404 && $koda !== 410) {
                $poizvedba = $baza->createQuery()->update($baza->quoteName('#__belaunce_push'))
                    ->set($baza->quoteName('napake') . ' = LEAST(' . $baza->quoteName('napake') . ' + 1, 255)')
                    ->where($baza->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
                $baza->setQuery($poizvedba)->execute();
            }

            $najvec = self::NAJVEC_NAPAK;
            $poizvedba = $baza->createQuery()->delete($baza->quoteName('#__belaunce_push'))
                ->where($baza->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);

            if ($koda !== 404 && $koda !== 410) {
                $poizvedba->where($baza->quoteName('napake') . ' >= :najvec')->bind(':najvec', $najvec, ParameterType::INTEGER);
            }

            $baza->setQuery($poizvedba)->execute();
        } catch (\Throwable $napaka) {
        }
    }

    /** Prebere javne podatke runde, potrebne za besedilo obvestila. */
    private static function runda(int $rundaId): ?object
    {
        $baza = Factory::getContainer()->get(DatabaseInterface::class);
        $poizvedba = $baza->createQuery()
            ->select($baza->quoteName(['id', 'naslov', 'zacetek', 'lokacija', 'vodja_id', 'stanje']))
            ->from($baza->quoteName('#__belaunce_runde'))->where($baza->quoteName('id') . ' = :runda')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);

        return $baza->setQuery($poizvedba)->loadObject() ?: null;
    }

    /** Prijavljeni člani runde in vodja (D54: vodja ni v #__belaunce_prijave). Gostje nimajo računa. */
    private static function udelezenci(int $rundaId, int $vodjaId): array
    {
        $baza = Factory::getContainer()->get(DatabaseInterface::class);
        $poizvedba = $baza->createQuery()->select($baza->quoteName('uporabnik_id'))
            ->from($baza->quoteName('#__belaunce_prijave'))->where($baza->quoteName('runda_id') . ' = :runda')
            ->bind(':runda', $rundaId, ParameterType::INTEGER);
        $idji = array_map('intval', $baza->setQuery($poizvedba)->loadColumn());
        $idji[] = $vodjaId;

        return $idji;
    }

    /**
     * Jezik strani: na frontendu trenutni, sicer privzeti site jezik (kot MailService, D12).
     * Ločen objekt jezika ne spremeni nizov trenutne (npr. administratorske) strani.
     */
    private static function jezik(): Language
    {
        $app = Factory::getApplication();
        $oznaka = $app->isClient('site') ? $app->getLanguage()->getTag()
            : (string) ComponentHelper::getParams('com_languages')->get('site', 'sl-SI');
        $jezik = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($oznaka, false);
        $jezik->load('com_belauncerunde', JPATH_SITE, $oznaka, true, true);

        return $jezik;
    }

    /**
     * Lokalni dan, datum in ura začetka v časovnem pasu komponente.
     *
     * @return  array  ['dan', 'datum', 'ura', 'iso' (Y-m-d)]
     */
    private static function cas(string $utc, Language $jezik): array
    {
        $lokalni = new \DateTimeImmutable(CasHelper::izUtc($utc), new \DateTimeZone('UTC'));

        return [
            // Imena dni so v osnovni jezikovni datoteki jedra (MONDAY …).
            'dan'   => $jezik->_(strtoupper($lokalni->format('l'))),
            'datum' => $lokalni->format($jezik->_('COM_BELAUNCERUNDE_PUSH_DATUM_OBLIKA')),
            'ura'   => $lokalni->format($jezik->_('COM_BELAUNCERUNDE_PUSH_URA_OBLIKA')),
            'iso'   => $lokalni->format('Y-m-d'),
        ];
    }

    /** Relativna SEF pot do runde (tako kot v mailih, a brez domene). */
    private static function url(int $rundaId): string
    {
        return Route::link('site', 'index.php?option=com_belauncerunde&view=runda&id=' . $rundaId, false);
    }

    /** TTL opomnika: sekunde do štarta, najmanj 60 in največ 24 h. */
    private static function doStarta(string $utc): int
    {
        $start = (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))->getTimestamp();

        return max(60, min(86400, $start - time()));
    }

    /** Zapiše v dnevnik; tudi napaka dnevnika ne sme prekiniti klicatelja. */
    private static function dnevnik(string $sporocilo, int $prioriteta): void
    {
        try {
            Log::add($sporocilo, $prioriteta, 'com_belauncerunde');
        } catch (\Throwable $napaka) {
        }
    }

    /** Ovije DER v PEM. */
    private static function pem(string $vrsta, string $der): string
    {
        return '-----BEGIN ' . $vrsta . "-----\n" . chunk_split(base64_encode($der), 64, "\n") . '-----END ' . $vrsta . "-----\n";
    }
}
