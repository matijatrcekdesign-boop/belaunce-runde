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
    /** Porabi zaznavo iste tabele enkrat; odpoved ima prednost pred spremembo. */
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

    /** Pošlje vsakemu naslovu svojo predlogo; neuspeh ne prekine drugih prejemnikov. */
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
