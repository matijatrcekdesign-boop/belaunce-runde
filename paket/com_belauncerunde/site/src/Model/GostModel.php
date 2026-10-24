<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Belaunce\Component\Belauncerunde\Site\Helper\GostCaptchaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** Prijava gosta brez računa, zaščita obrazca in enkratna odjava. */
class GostModel extends FormModel
{
    /** Naloži samo dovoljena polja gosta z Joomlinimi vhodnimi filtri. */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_belauncerunde.gost', 'gost', ['control' => 'jform', 'load_data' => false]);
    }

    /**
     * Seja po rundah prepreči ponarejanje časa s skritim poljem.
     *
     * @param  int  $rundaId
     * @return  string
     */
    public function kljucCasa(int $rundaId): string
    {
        return 'com_belauncerunde.gost.cas.' . $rundaId;
    }

    /**
     * Preveri članstvo in odprtost po UTC uri baze; zaklep uporabi samo transakcija.
     *
     * @param  int  $rundaId
     * @param  User  $uporabnik
     * @param  bool  $zakleni
     * @return  string
     */
    public function dovoljenje(int $rundaId, User $uporabnik, bool $zakleni = false): string
    {
        $prijave = Factory::getApplication()->bootComponent('com_belauncerunde')
            ->getMVCFactory()->createModel('Prijave', 'Site', []);

        if ($prijave->jeClan($uporabnik)) {
            return 'gost_clan';
        }

        $baza = $this->getDatabase();
        $sql = 'SELECT odprto_za_goste, stanje, zacetek > UTC_TIMESTAMP() AS pred_startom'
            . ' FROM #__belaunce_runde WHERE id = :runda' . ($zakleni ? ' FOR UPDATE' : '');
        $poizvedba = $baza->createQuery()->setQuery($sql)->bind(':runda', $rundaId, ParameterType::INTEGER);
        $runda = $baza->setQuery($poizvedba)->loadObject();

        if (!$runda || (int) $runda->odprto_za_goste !== 1 || (int) $runda->stanje !== 1 || !(bool) $runda->pred_startom) {
            return 'gost_zaprto';
        }

        return '';
    }

    /**
     * Preveri zaščito in vnos ter pod zaklepom runde atomarno uveljavi kvoto.
     *
     * @param  int  $rundaId
     * @param  array  $vnos
     * @param  User  $uporabnik
     * @return  string
     */
    public function prijava(int $rundaId, array $vnos, User $uporabnik): string
    {
        $stanje = $this->dovoljenje($rundaId, $uporabnik);

        if ($stanje !== '') {
            return $stanje;
        }

        $app = Factory::getApplication();
        $moznosti = ComponentHelper::getParams('com_belauncerunde');
        $cas = (float) $app->getUserState($this->kljucCasa($rundaId), 0);
        $app->setUserState($this->kljucCasa($rundaId), null);

        // Honeypot dobi isti izpis kot uspeh, vendar brez vrstice in brez pošte.
        if (($vnos['spletisce'] ?? '') !== '') {
            Log::add('GOST_HONEYPOT runda_id=' . $rundaId, Log::WARNING, 'com_belauncerunde');

            return 'gost_prijavljen';
        }

        if ($cas <= 0 || microtime(true) - $cas < max(0, (int) $moznosti->get('gostje_min_cas', 3))) {
            return 'gost_prehitro';
        }

        $podatki = $this->getForm([], false)->filter($vnos);

        if (!\is_array($podatki)) {
            return 'gost_vnos';
        }

        foreach (['ime', 'telefon', 'email'] as $polje) {
            if (isset($podatki[$polje]) && !\is_string($podatki[$polje])) {
                return 'gost_vnos';
            }

            $podatki[$polje] = trim($podatki[$polje] ?? '');
        }

        $ime = $podatki['ime'];
        $telefon = $podatki['telefon'];
        $email = $podatki['email'];

        // Meje preverimo pred zapisom tudi ob neposrednem POST brez brskalniške validacije.
        if (mb_strlen($ime) < 2 || mb_strlen($ime) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $ime)) {
            return 'gost_ime';
        }

        if ($telefon === '' && $email === '') {
            return 'gost_kontakt';
        }

        if ($telefon !== '' && (mb_strlen($telefon) > 30 || !preg_match('~^[0-9 +/()\-]+$~D', $telefon))) {
            return 'gost_telefon';
        }

        if ($email !== '' && (mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return 'gost_email';
        }

        $captcha = GostCaptchaHelper::pridobi($rundaId);

        if ($captcha !== null) {
            try {
                if (!$captcha->checkAnswer($app->getInput()->post->getString('gost_captcha', ''))) {
                    return 'gost_captcha';
                }
            } catch (\Throwable $napaka) {
                // Napaka preverjanja obstoječe captche ne sme postati obvod zaščite.
                return 'gost_captcha';
            }
        }

        $ipHash = hash('sha256', $app->getInput()->server->getString('REMOTE_ADDR', '') . (string) $app->get('secret'));
        $omejitev = (int) $moznosti->get('gostje_omejitev', 2);
        $omejitev = \in_array($omejitev, [1, 2, 3, 5], true) ? $omejitev : 2;
        $surovi = $email !== '' ? bin2hex(random_bytes(32)) : null;
        $hash = $surovi === null ? null : hash('sha256', $surovi);
        $baza = $this->getDatabase();
        $baza->transactionStart();

        try {
            // Vsi vpisi gostov iste runde čakajo na isti InnoDB zaklep. Dva sočasna
            // POST zato ne moreta oba porabiti zadnjega prostega mesta istega hasha.
            $stanje = $this->dovoljenje($rundaId, $uporabnik, true);

            if ($stanje !== '') {
                $baza->transactionRollback();

                return $stanje;
            }

            $poizvedba = $baza->createQuery()->select('COUNT(*)')->from($baza->quoteName('#__belaunce_gostje'))
                ->where('runda_id = :runda')->where('ip = :ip')
                ->bind(':runda', $rundaId, ParameterType::INTEGER)->bind(':ip', $ipHash, ParameterType::STRING);

            if ((int) $baza->setQuery($poizvedba)->loadResult() >= $omejitev) {
                $baza->transactionRollback();

                return 'gost_omejitev';
            }

            // SQL ponovno preveri štart in odprtost tik ob zapisu; NULL ostane za
            // manjkajoči kontakt in žeton telefonskega gosta. Surovega IP-ja ni v bazi.
            $telefon = $telefon === '' ? null : $telefon;
            $email = $email === '' ? null : $email;
            $sql = 'INSERT INTO #__belaunce_gostje (runda_id, ime, telefon, email, odjava_zeton_hash, ip, ustvarjeno)'
                . ' SELECT id, :ime, :telefon, :email, :hash, :ip, UTC_TIMESTAMP() FROM #__belaunce_runde'
                . ' WHERE id = :runda AND stanje = 1 AND odprto_za_goste = 1 AND zacetek > UTC_TIMESTAMP()';
            $poizvedba = $baza->createQuery()->setQuery($sql)
                ->bind(':runda', $rundaId, ParameterType::INTEGER)->bind(':ime', $ime, ParameterType::STRING)
                ->bind(':telefon', $telefon, $telefon === null ? ParameterType::NULL : ParameterType::STRING)
                ->bind(':email', $email, $email === null ? ParameterType::NULL : ParameterType::STRING)
                ->bind(':hash', $hash, $hash === null ? ParameterType::NULL : ParameterType::STRING)
                ->bind(':ip', $ipHash, ParameterType::STRING);
            $baza->setQuery($poizvedba)->execute();
            $vstavljeno = $baza->getAffectedRows() === 1;
            $baza->transactionCommit();
        } catch (\Throwable $napaka) {
            $baza->transactionRollback();
            throw $napaka;
        }

        if (!$vstavljeno) {
            return 'gost_zaprto';
        }

        // Pošta teče šele po COMMIT; njena napaka nikoli ne razveljavi prijave.
        $stanjePoste = $surovi !== null && !$this->posljiPotrditev($rundaId, $ime, $email, $surovi)
            ? 'gost_mail' : 'gost_prijavljen';

        // Vodjo obvestimo po gostovi potrditvi; napaka ustvarjanja modela ali pošte ne vpliva na vpis.
        try {
            $prijave = $this->getMVCFactory()->createModel('Prijave', 'Site', ['ignore_request' => true]);
            \Belaunce\Component\Belauncerunde\Administrator\Service\MailService::obvestiVodjoOPrijavi(
                $rundaId, $ime, $prijave->steviloUdelezencev($rundaId), 0
            );
        } catch (\Throwable $napaka) {
        }

        return $stanjePoste;
    }

    /** Pošlje urejevalno Joomlino predlogo z lokalnim časom in absolutno SEF povezavo. */
    private function posljiPotrditev(int $rundaId, string $ime, string $email, string $surovi): bool
    {
        try {
            $app = Factory::getApplication();
            $runda = $app->bootComponent('com_belauncerunde')->getMVCFactory()->createModel('Runda', 'Site', [])->getItem($rundaId);
            $lokalni = new \DateTimeImmutable(CasHelper::izUtc($runda->zacetek), new \DateTimeZone('UTC'));
            $predloga = new MailTemplate('com_belauncerunde.gost_potrditev', $app->getLanguage()->getTag());
            $predloga->addTemplateData([
                'IME' => $ime, 'RUNDA' => $runda->naslov,
                'ZACETEK' => $lokalni->format(Text::_('COM_BELAUNCERUNDE_DATUM_OBLIKA')),
                'LOKACIJA' => $runda->lokacija, 'VODJA' => (string) ($runda->vodja_ime ?? ''),
                'POVEZAVA' => Route::_('index.php?option=com_belauncerunde&view=odjavagosta&zeton=' . $surovi, false, Route::TLS_IGNORE, true),
                'SITENAME' => (string) $app->get('sitename'),
            ]);
            $predloga->addUnsafeTags(['IME', 'RUNDA', 'ZACETEK', 'LOKACIJA', 'VODJA', 'POVEZAVA', 'SITENAME']);
            $predloga->addRecipient($email, $ime);

            if ($predloga->send()) {
                return true;
            }
        } catch (\Throwable $napaka) {
            // Besedila izjeme ne zapisujemo: vsebuje lahko naslov ali žeton.
        }

        Log::add('GOST_MAIL_NAPAKA runda_id=' . $rundaId, Log::WARNING, 'com_belauncerunde');

        return false;
    }

    /**
     * GET preveri samo hash; ne prebere imena, kontaktov ali surovega IP-ja.
     *
     * @param  string  $hash
     * @return  ?int
     */
    public function preveriOdjavo(string $hash): ?int
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $hash)) {
            return null;
        }

        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select('runda_id')->from($baza->quoteName('#__belaunce_gostje'))
            ->where('odjava_zeton_hash = :hash')->bind(':hash', $hash, ParameterType::STRING);
        $id = $baza->setQuery($poizvedba)->loadResult();

        return $id === null ? null : (int) $id;
    }

    /**
     * Samo potrjeni POST izbriše vrstico; pogojni DELETE porabi žeton natanko enkrat.
     *
     * @param  string  $hash
     * @return  void
     */
    public function odjava(string $hash): void
    {
        $rundaId = $this->preveriOdjavo($hash);

        if ($rundaId === null) {
            Log::add('GOST_ODJAVA_NEVELJAVNA runda_id=0', Log::WARNING, 'com_belauncerunde');

            return;
        }

        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->delete($baza->quoteName('#__belaunce_gostje'))
            ->where('odjava_zeton_hash = :hash')->bind(':hash', $hash, ParameterType::STRING);
        $baza->setQuery($poizvedba)->execute();
    }
}
