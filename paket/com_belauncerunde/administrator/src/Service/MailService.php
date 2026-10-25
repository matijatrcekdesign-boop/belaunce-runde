<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Belaunce\Component\Belauncerunde\Administrator\Table\RundaTable;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** Transakcijska obvestila po potrjenem zapisu, brez razkrivanja naslovov ali žetonov. */
class MailService
{
    /**
     * Ob dejanski novi prijavi obvesti samo vodjo aktivne runde.
     *
     * @param  int     $rundaId        ID runde.
     * @param  string  $imeUdelezenca  Prikazno ime udeleženca brez kontaktov.
     * @param  int     $stevilo        Skupno število udeležencev po prijavi.
     * @param  int     $uporabnikId    ID člana ali nič za gosta.
     * @return bool
     */
    public static function obvestiVodjoOPrijavi(int $rundaId, string $imeUdelezenca, int $stevilo, int $uporabnikId = 0): bool
    {
        try {
            if ((int) ComponentHelper::getParams('com_belauncerunde')->get('mail_nova_prijava', 1) === 0) {
                return true;
            }

            $baza = Factory::getContainer()->get(DatabaseInterface::class);
            $poizvedba = $baza->createQuery()->select($baza->quoteName(['naslov', 'zacetek', 'vodja_id', 'stanje']))
                ->from($baza->quoteName('#__belaunce_runde'))->where('id = :runda')
                ->bind(':runda', $rundaId, ParameterType::INTEGER);
            $runda = $baza->setQuery($poizvedba)->loadObject();

            if ($runda === null || (int) $runda->stanje !== 1) {
                return true;
            }

            $vodjaId = (int) $runda->vodja_id;

            if ($uporabnikId !== 0 && $uporabnikId === $vodjaId) {
                return true;
            }

            $poizvedba = $baza->createQuery()->select($baza->quoteName('email'))
                ->from($baza->quoteName('#__users'))->where('id = :vodja')
                ->bind(':vodja', $vodjaId, ParameterType::INTEGER);
            $email = trim((string) $baza->setQuery($poizvedba)->loadResult());

            if ($email === '') {
                return true;
            }

            $app = Factory::getApplication();
            // Jezik in lokalni čas sledita istemu pravilu D12 kot obvestila o spremembi runde.
            $oznaka = $app->isClient('site') ? $app->getLanguage()->getTag()
                : (string) ComponentHelper::getParams('com_languages')->get('site', 'sl-SI');
            $jezik = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($oznaka, false);
            $jezik->load('com_belauncerunde', JPATH_SITE, $oznaka, true, true);
            $lokalni = new \DateTimeImmutable(CasHelper::izUtc((string) $runda->zacetek), new \DateTimeZone('UTC'));
            $zacetek = $lokalni->format($jezik->_('COM_BELAUNCERUNDE_DATUM_OBLIKA'));
            $povezava = Route::link('site', 'index.php?option=com_belauncerunde&view=runda&id=' . $rundaId,
                false, Route::TLS_IGNORE, true);

            // En sam prejemnik; v podatkih predloge ni udeleženčevega emaila ali telefona.
            $posiljatelj = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $predloga = new PosameznaMailTemplate('com_belauncerunde.nova_prijava', $oznaka, $posiljatelj);
            $podatki = [
                'IME' => $imeUdelezenca, 'RUNDA' => (string) $runda->naslov,
                'ZACETEK' => $zacetek, 'STEVILO' => (string) $stevilo,
                'POVEZAVA' => $povezava, 'SITENAME' => (string) $app->get('sitename'),
            ];
            $predloga->addTemplateData($podatki);
            $predloga->addUnsafeTags(array_keys($podatki));
            $predloga->addRecipient($email);
            $uspeh = (bool) $predloga->send();
        } catch (\Throwable $napaka) {
            // Napaka lahko vsebuje osebne podatke; njene vsebine ne beležimo.
            $uspeh = false;
        }

        // Tudi napaka dnevnika ne sme spremeniti izida že potrjene prijave.
        try {
            Log::add(($uspeh ? 'NOVA_PRIJAVA_MAIL' : 'NOVA_PRIJAVA_MAIL_NAPAKA') . ' runda_id=' . $rundaId,
                $uspeh ? Log::INFO : Log::WARNING, 'com_belauncerunde');
        } catch (\Throwable $napaka) {
        }

        return $uspeh;
    }

    /**
     * Porabi zaznavo iste tabele enkrat; odpoved ima prednost pred spremembo.
     *
     * @param  RundaTable  $tabela
     * @return  bool
     */
    public static function poZapisu(RundaTable $tabela): bool
    {
        $odpoved = $tabela->_pravkarOdpovedana;
        $polja = $tabela->_sprozilnaSprememba;
        $prvotni = $tabela->_prvotniZacetek;
        $tabela->_pravkarOdpovedana = false;
        $tabela->_sprozilnaSprememba = [];
        $tabela->_prvotniZacetek = '';

        if (!$odpoved && $polja === []) {
            return true;
        }

        return (new self())->poslji((int) $tabela->id, $odpoved ? 'odpoved' : 'sprememba', $polja, $prvotni);
    }

    /**
     * Pošlje vsakemu naslovu svojo predlogo; neuspeh ne prekine drugih prejemnikov.
     *
     * @param  int  $rundaId
     * @param  string  $dogodek
     * @param  array  $polja
     * @param  string  $prvotniZacetek
     * @return  bool
     */
    public function poslji(int $rundaId, string $dogodek, array $polja, string $prvotniZacetek = ''): bool
    {
        $stevilo = 0;
        $uspeh = true;

        try {
            if (!\in_array($dogodek, ['sprememba', 'odpoved'], true)) {
                throw new \InvalidArgumentException();
            }

            $baza = Factory::getContainer()->get(DatabaseInterface::class);
            $poizvedba = $baza->createQuery()->select($baza->quoteName(['naslov', 'zacetek', 'lokacija', 'vodja_id']))
                ->from($baza->quoteName('#__belaunce_runde'))->where('id = :runda')
                ->bind(':runda', $rundaId, ParameterType::INTEGER);
            $runda = $baza->setQuery($poizvedba)->loadObject();

            if ($runda === null) {
                throw new \RuntimeException();
            }

            $app = Factory::getApplication();
            // Backendov jezik urejevalca ni nujno jezik strani (D12).
            $oznaka = $app->isClient('site') ? $app->getLanguage()->getTag()
                : (string) ComponentHelper::getParams('com_languages')->get('site', 'sl-SI');
            $jezik = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($oznaka, false);
            $jezik->load('com_belauncerunde', JPATH_SITE, $oznaka, true, true);
            $termin = $dogodek === 'odpoved' && $prvotniZacetek !== '' ? $prvotniZacetek : (string) $runda->zacetek;
            $lokalni = new \DateTimeImmutable(CasHelper::izUtc($termin), new \DateTimeZone('UTC'));
            $zacetek = $lokalni->format($jezik->_('COM_BELAUNCERUNDE_DATUM_OBLIKA'));
            $spremembe = [];

            // Sestavljamo samo dejansko spremenjena vključena polja, ne celotnega povzetka.
            if (\in_array('zacetek', $polja, true)) {
                $spremembe[] = sprintf($jezik->_('COM_BELAUNCERUNDE_MAIL_NOV_ZACETEK'), $zacetek);
            }

            if (\in_array('lokacija', $polja, true)) {
                $spremembe[] = sprintf($jezik->_('COM_BELAUNCERUNDE_MAIL_NOVA_LOKACIJA'), trim((string) $runda->lokacija));
            }

            $povezava = Route::link('site', 'index.php?option=com_belauncerunde&view='
                . ($dogodek === 'odpoved' ? 'runde' : 'runda&id=' . $rundaId), false, Route::TLS_IGNORE, true);
            $prejemniki = $this->prejemniki($baza, $rundaId, (int) $runda->vodja_id);
            $stevilo = \count($prejemniki);

            foreach ($prejemniki as $email => $gostje) {
                try {
                    $odjave = [];

                    // En naslov lahko pripada več gostom ali tudi vodji: pošljemo en mail
                    // z odjavnimi povezavami vseh pripadajočih gostov, brez njihovih imen.
                    foreach ($gostje as $gostId) {
                        $surovi = bin2hex(random_bytes(32));
                        $hash = hash('sha256', $surovi);
                        $poizvedba = $baza->createQuery()->update($baza->quoteName('#__belaunce_gostje'))
                            ->set('odjava_zeton_hash = :hash')->where('id = :gost')->where('runda_id = :runda')
                            ->bind(':hash', $hash, ParameterType::STRING)->bind(':gost', $gostId, ParameterType::INTEGER)
                            ->bind(':runda', $rundaId, ParameterType::INTEGER);
                        $baza->setQuery($poizvedba)->execute();

                        if ($baza->getAffectedRows() === 1) {
                            $url = Route::link('site', 'index.php?option=com_belauncerunde&view=odjavagosta&zeton='
                                . $surovi, false, Route::TLS_IGNORE, true);
                            $odjave[] = sprintf($jezik->_('COM_BELAUNCERUNDE_MAIL_ODJAVA_GOSTA'), $url);
                        }
                    }

                    // Nov objekt za vsak naslov prepreči kopičenje To/Cc/Bcc med iteracijami.
                    $posiljatelj = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
                    $predloga = new PosameznaMailTemplate('com_belauncerunde.runda_' . $dogodek, $oznaka, $posiljatelj);
                    $podatki = [
                        'RUNDA' => (string) $runda->naslov, 'ZACETEK' => $zacetek,
                        'SPREMEMBE' => implode("\n", $spremembe), 'POVEZAVA' => $povezava,
                        'ODJAVA' => implode("\n\n", $odjave), 'SITENAME' => (string) $app->get('sitename'),
                    ];
                    $predloga->addTemplateData($podatki);
                    $predloga->addUnsafeTags(array_keys($podatki));
                    $predloga->addRecipient($email);

                    if (!$predloga->send()) {
                        $uspeh = false;
                    }
                } catch (\Throwable $napaka) {
                    // Izjema lahko vsebuje naslov ali žeton; niti besedila niti sledi ne beležimo.
                    $uspeh = false;
                }
            }
        } catch (\Throwable $napaka) {
            $uspeh = false;
        }

        // Tudi nedosegljiv dnevnik ne sme spremeniti uspešnega zapisa v napako obrazca.
        try {
            Log::add(($uspeh ? 'RUNDA_MAIL_POSLAN' : 'RUNDA_MAIL_NAPAKA') . ' runda_id=' . $rundaId
                . ' prejemnikov=' . $stevilo, $uspeh ? Log::INFO : Log::WARNING, 'com_belauncerunde');
        } catch (\Throwable $napaka) {
        }

        return $uspeh;
    }

    /** Združi prijavljene člane, vodjo in goste po normaliziranem naslovu. */
    private function prejemniki(DatabaseInterface $baza, int $rundaId, int $vodjaId): array
    {
        $poizvedba = $baza->createQuery()->select($baza->quoteName('u.email'))
            ->from($baza->quoteName('#__belaunce_prijave', 'p'))
            ->join('INNER', $baza->quoteName('#__users', 'u') . ' ON u.id = p.uporabnik_id')
            ->where('p.runda_id = :runda')->bind(':runda', $rundaId, ParameterType::INTEGER);
        $naslovi = $baza->setQuery($poizvedba)->loadColumn();
        $poizvedba = $baza->createQuery()->select($baza->quoteName('email'))
            ->from($baza->quoteName('#__users'))->where('id = :vodja')
            ->bind(':vodja', $vodjaId, ParameterType::INTEGER);
        $naslovi[] = (string) $baza->setQuery($poizvedba)->loadResult();
        $prejemniki = [];

        foreach ($naslovi as $naslov) {
            $email = strtolower(trim((string) $naslov));

            if ($email !== '') {
                $prejemniki[$email] = [];
            }
        }

        $poizvedba = $baza->createQuery()->select($baza->quoteName(['id', 'email']))
            ->from($baza->quoteName('#__belaunce_gostje'))->where('runda_id = :runda')->where('email IS NOT NULL')
            ->order('id ASC')->bind(':runda', $rundaId, ParameterType::INTEGER);

        foreach ($baza->setQuery($poizvedba)->loadObjectList() as $gost) {
            $email = strtolower(trim((string) $gost->email));

            if ($email !== '') {
                $prejemniki[$email][] = (int) $gost->id;
            }
        }

        return $prejemniki;
    }
}
