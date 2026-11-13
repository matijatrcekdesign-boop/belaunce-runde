<?php

/**
 * @package     Belaunce.Site
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Belaunce\Component\Belauncerunde\Administrator\Service\PushService;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Naročnine na push obvestila (#__belaunce_push): ena vrstica = ena naprava z enim lastnikom.
 *
 * Ne hranimo IP-ja, user-agenta, imena naprave ali lokacije. Endpoint se nikoli ne izpiše
 * in ne gre v dnevnik (samo id naročnine in uporabnik_id).
 *
 * @since  0.20.0
 */
class PushModel extends BaseDatabaseModel
{
    /** @var int Največ naročnin (naprav) na uporabnika; ob preseganju se izbriše najstarejša. */
    private const NAJVEC_NAPRAV = 10;

    /**
     * Naroči se sme prijavljen član (skupina Člani) ali vodja katerekoli runde.
     *
     * @param   User|null  $uporabnik
     *
     * @return  bool
     */
    public function smeNarociti(?User $uporabnik): bool
    {
        if ($uporabnik === null || $uporabnik->guest || (int) $uporabnik->id <= 0) {
            return false;
        }

        $prijave = $this->getMVCFactory()->createModel('Prijave', 'Site', ['ignore_request' => true]);

        if ($prijave->jeClan($uporabnik)) {
            return true;
        }

        $baza = $this->getDatabase();
        $uporabnikId = (int) $uporabnik->id;
        $poizvedba = $baza->createQuery()->select('1')->from($baza->quoteName('#__belaunce_runde'))
            ->where($baza->quoteName('vodja_id') . ' = :vodja')
            ->bind(':vodja', $uporabnikId, ParameterType::INTEGER)
            ->setLimit(1);

        return (bool) $baza->setQuery($poizvedba)->loadResult();
    }

    /**
     * Shrani ali prevzame naročnino naprave (upsert po endpoint_hash).
     *
     * @param   int     $uporabnikId
     * @param   string  $endpoint     Naslov push storitve (https).
     * @param   string  $p256dh       Javni ključ naprave (base64url, 65 B).
     * @param   string  $auth         Skrivnost naprave (base64url, 16 B).
     *
     * @return  bool  false ob neveljavnih podatkih.
     */
    public function naroci(int $uporabnikId, string $endpoint, string $p256dh, string $auth): bool
    {
        $endpoint = trim($endpoint);
        $kljuc = PushService::base64urlDekodiraj($p256dh);
        $skrivnost = PushService::base64urlDekodiraj($auth);

        if ($uporabnikId <= 0 || !self::veljavenEndpoint($endpoint)
            || $kljuc === null || \strlen($kljuc) !== PushService::DOLZINA_JAVNI || $kljuc[0] !== "\x04"
            || $skrivnost === null || \strlen($skrivnost) !== PushService::DOLZINA_AUTH) {
            return false;
        }

        // Normalizirana oblika (brez polnila) za stalne dolžine stolpcev.
        $p256dh = PushService::base64urlKodiraj($kljuc);
        $auth = PushService::base64urlKodiraj($skrivnost);
        $hash = hash('sha256', $endpoint);
        $zdaj = CasHelper::zdajUtc();
        $baza = $this->getDatabase();

        // Atomarni upsert: ista naprava pod drugim uporabnikom preide na trenutnega (en lastnik).
        $sql = 'INSERT INTO ' . $baza->quoteName('#__belaunce_push')
            . ' (uporabnik_id, endpoint, endpoint_hash, p256dh, auth, ustvarjeno, zadnji_uspeh, napake)'
            . ' VALUES (:uporabnik, :endpoint, :hash, :p256dh, :auth, :zdaj, NULL, 0)'
            . ' ON DUPLICATE KEY UPDATE uporabnik_id = VALUES(uporabnik_id), endpoint = VALUES(endpoint),'
            . ' p256dh = VALUES(p256dh), auth = VALUES(auth), napake = 0';
        $poizvedba = $baza->createQuery()->setQuery($sql)
            ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER)
            ->bind(':endpoint', $endpoint, ParameterType::STRING)
            ->bind(':hash', $hash, ParameterType::STRING)
            ->bind(':p256dh', $p256dh, ParameterType::STRING)
            ->bind(':auth', $auth, ParameterType::STRING)
            ->bind(':zdaj', $zdaj, ParameterType::STRING);
        $baza->setQuery($poizvedba)->execute();

        $id = $this->idPoHashu($hash, 0);
        $this->omejiNaprave($uporabnikId);
        $this->dnevnik('PUSH_NAROCEN id=' . $id . ' uporabnik_id=' . $uporabnikId);

        return true;
    }

    /**
     * Izbriše naročnino z ujemajočim endpointom, samo če pripada trenutnemu uporabniku.
     *
     * @param   int     $uporabnikId
     * @param   string  $endpoint
     *
     * @return  void
     */
    public function odjavi(int $uporabnikId, string $endpoint): void
    {
        $endpoint = trim($endpoint);

        if ($uporabnikId <= 0 || $endpoint === '' || \strlen($endpoint) > 2048) {
            return;
        }

        $hash = hash('sha256', $endpoint);
        $id = $this->idPoHashu($hash, $uporabnikId);

        if ($id === 0) {
            return;
        }

        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->delete($baza->quoteName('#__belaunce_push'))
            ->where($baza->quoteName('id') . ' = :id')
            ->where($baza->quoteName('uporabnik_id') . ' = :uporabnik')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);
        $baza->setQuery($poizvedba)->execute();
        $this->dnevnik('PUSH_ODJAVLJEN id=' . $id . ' uporabnik_id=' . $uporabnikId);
    }

    /** @var string[] Gostitelji push storitev, ki se ujemajo natanko. */
    private const STORITVE_NATANKO = ['fcm.googleapis.com'];

    /** @var string[] Pripone gostiteljev push storitev (z vodilno piko, torej samo poddomene). */
    private const STORITVE_PRIPONE = ['.push.apple.com', '.push.services.mozilla.com', '.notify.windows.com'];

    /**
     * Preveri endpoint pred shranjevanjem: strežnik bo nanj pošiljal POST, zato sprejmemo samo
     * https naslov znane push storitve (SSRF): Google FCM (natanko fcm.googleapis.com), Apple
     * (*.push.apple.com), Mozilla (*.push.services.mozilla.com) in Microsoft WNS (*.notify.windows.com).
     * IP naslovi, localhost, imena brez domene in vsi drugi gostitelji so zavrnjeni.
     *
     * @param   string  $endpoint
     *
     * @return  bool
     */
    public static function veljavenEndpoint(string $endpoint): bool
    {
        if ($endpoint === '' || \strlen($endpoint) > 2048 || preg_match('/[\s\x00-\x1f]/', $endpoint)) {
            return false;
        }

        $deli = parse_url($endpoint);

        if (!\is_array($deli) || strtolower((string) ($deli['scheme'] ?? '')) !== 'https' || empty($deli['host'])
            || isset($deli['user']) || isset($deli['pass'])) {
            return false;
        }

        $gostitelj = strtolower(rtrim(trim((string) $deli['host'], '[]'), '.'));

        if (filter_var($gostitelj, FILTER_VALIDATE_IP) !== false || $gostitelj === 'localhost'
            || str_ends_with($gostitelj, '.localhost') || !str_contains($gostitelj, '.')) {
            return false;
        }

        if (!preg_match('/^[a-z0-9.-]+$/', $gostitelj)) {
            return false;
        }

        // Seznam dovoljenih storitev: brez njega bi član lahko strežnik usmeril na poljuben javni naslov.
        if (\in_array($gostitelj, self::STORITVE_NATANKO, true)) {
            return true;
        }

        foreach (self::STORITVE_PRIPONE as $pripona) {
            if (str_ends_with($gostitelj, $pripona)) {
                return true;
            }
        }

        return false;
    }

    /** ID naročnine po hashu (po želji samo za danega lastnika) ali 0. */
    private function idPoHashu(string $hash, int $uporabnikId): int
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select($baza->quoteName('id'))
            ->from($baza->quoteName('#__belaunce_push'))
            ->where($baza->quoteName('endpoint_hash') . ' = :hash')
            ->bind(':hash', $hash, ParameterType::STRING);

        if ($uporabnikId > 0) {
            $poizvedba->where($baza->quoteName('uporabnik_id') . ' = :uporabnik')
                ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);
        }

        return (int) $baza->setQuery($poizvedba)->loadResult();
    }

    /** Ohrani največ 10 naročnin uporabnika; najstarejše se izbrišejo. */
    private function omejiNaprave(int $uporabnikId): void
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select($baza->quoteName('id'))
            ->from($baza->quoteName('#__belaunce_push'))
            ->where($baza->quoteName('uporabnik_id') . ' = :uporabnik')
            ->order($baza->quoteName('ustvarjeno') . ' DESC, ' . $baza->quoteName('id') . ' DESC')
            ->bind(':uporabnik', $uporabnikId, ParameterType::INTEGER);
        $odvecni = \array_slice(array_map('intval', $baza->setQuery($poizvedba)->loadColumn()), self::NAJVEC_NAPRAV);

        if ($odvecni === []) {
            return;
        }

        $poizvedba = $baza->createQuery()->delete($baza->quoteName('#__belaunce_push'))
            ->whereIn($baza->quoteName('id'), $odvecni);
        $baza->setQuery($poizvedba)->execute();
    }

    /** Zapis v dnevnik brez osebnih vrednosti; napaka dnevnika ne prekine zahteve. */
    private function dnevnik(string $sporocilo): void
    {
        try {
            Log::add($sporocilo, Log::INFO, 'com_belauncerunde');
        } catch (\Throwable $napaka) {
        }
    }
}
