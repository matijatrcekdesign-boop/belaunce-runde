<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Table\Usergroup;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

/**
 * Namestitveni skript komponente.
 *
 * @since  0.1.0
 */
class Com_BelauncerundeInstallerScript
{
    /**
     * Po namestitvi ali posodobitvi pripravi skupino Člani.
     *
     * @param   string            $type    Vrsta operacije.
     * @param   InstallerAdapter  $parent  Namestitveni adapter.
     *
     * @return  bool
     *
     * @since   0.1.0
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        if (!\in_array($type, ['install', 'update'], true)) {
            return true;
        }

        try {
            $this->zagotoviBarvoTipa(Factory::getContainer()->get(DatabaseInterface::class));
        } catch (\Throwable $napaka) {
            // Brez stolpca javne poizvedbe ne delujejo, zato namestitve ne potrdimo.
            Log::add('BARVA_STOLPEC_NAPAKA', Log::ERROR, 'com_belauncerunde');
            Factory::getApplication()->enqueueMessage(Text::_('COM_BELAUNCERUNDE_INSTALL_BARVA_NAPAKA'), 'error');

            return false;
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $id = $this->pripraviSkupinoClani($db);
            $this->shraniParameter($db, 'skupina_clani', $id);
        } catch (\Throwable $exception) {
            Log::add('NAMESTITEV_SKUPINA_NAPAKA', Log::ERROR, 'com_belauncerunde');
            Factory::getApplication()->enqueueMessage(Text::_('COM_BELAUNCERUNDE_INSTALL_GROUP_FAILED'), 'warning');
        }

        // Vsak korak je neodvisen: napaka skupine ne sme preskočiti priprave vtičnika ali pošte.
        try {
            $this->pripraviVticnike(Factory::getContainer()->get(DatabaseInterface::class));
        } catch (\Throwable $napaka) {
            Log::add('NAMESTITEV_VTICNIK_NAPAKA', Log::ERROR, 'com_belauncerunde');
            Factory::getApplication()->enqueueMessage(Text::_('COM_BELAUNCERUNDE_INSTALL_VTICNIK_NAPAKA'), 'warning');
        }

        try {
            $this->pripraviPostnoPredlogo();
        } catch (\Throwable $napaka) {
            Log::add('NAMESTITEV_MAIL_NAPAKA', Log::ERROR, 'com_belauncerunde');
            Factory::getApplication()->enqueueMessage(Text::_('COM_BELAUNCERUNDE_INSTALL_MAIL_NAPAKA'), 'warning');
        }

        return true;
    }

    /**
     * Ob vsaki namestitvi zagotovi stolpec barve in manjkajoče začetne barve tipov.
     *
     * @param   DatabaseInterface  $db  Povezava z bazo.
     *
     * @return  void
     */
    private function zagotoviBarvoTipa(DatabaseInterface $db): void
    {
        $tabela = $db->getPrefix() . 'belaunce_tipi';
        $stolpec = 'barva';
        $poizvedba = $db->createQuery()->select('COUNT(*)')->from('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME = :tabela')
            ->where('COLUMN_NAME = :stolpec')
            ->bind(':tabela', $tabela, ParameterType::STRING)
            ->bind(':stolpec', $stolpec, ParameterType::STRING);

        if ((int) $db->setQuery($poizvedba)->loadResult() === 0) {
            // Imena v DDL so stalna; nadomestitev #__ izvede gonilnik baze.
            $db->setQuery('ALTER TABLE `#__belaunce_tipi` ADD COLUMN `barva` CHAR(7) NULL AFTER `opis`')->execute();
            Log::add('BARVA_STOLPEC_DODAN', Log::INFO, 'com_belauncerunde');
        }

        // Ponovna namestitev ne povozi ročno nastavljenih barv.
        foreach (['mtb' => '#2a76e0', 'cestno' => '#f2b974', 'gravel' => '#8f592f'] as $alias => $barva) {
            $poizvedba = $db->createQuery()->update($db->quoteName('#__belaunce_tipi'))
                ->set($db->quoteName('barva') . ' = :barva')
                ->where($db->quoteName('alias') . ' = :alias')
                ->where($db->quoteName('barva') . ' IS NULL')
                ->bind(':barva', $barva, ParameterType::STRING)
                ->bind(':alias', $alias, ParameterType::STRING);
            $db->setQuery($poizvedba)->execute();
        }
    }

    /**
     * Ob odstranitvi po želji izbriše tabele; skupina Člani ostane.
     *
     * @param   InstallerAdapter  $parent  Namestitveni adapter.
     *
     * @return  bool
     *
     * @since   0.1.0
     */
    public function uninstall(InstallerAdapter $parent): bool
    {
        $params = ComponentHelper::getParams('com_belauncerunde');

        if ((int) $params->get('ohrani_podatke', 1) === 1) {
            return true;
        }

        try {
            $db      = Factory::getContainer()->get(DatabaseInterface::class);
            $sqlPath = __DIR__ . '/sql/uninstall.mysql.utf8.sql';

            if (!is_file($sqlPath)) {
                Log::add('ODSTRANITEV_SQL_MANJKA', Log::ERROR, 'com_belauncerunde');

                return true;
            }

            $queries = $this->razdeliSql((string) file_get_contents($sqlPath));

            foreach ($queries as $query) {
                $db->setQuery($query);
                if (!$db->execute()) {
                    throw new \RuntimeException('ODSTRANITEV_SQL_NAPAKA');
                }
            }

            // Poštne predloge odstranimo šele, ko je potrjena pot do odstranitvenega SQL.
            foreach (['com_belauncerunde.prijava', 'com_belauncerunde.gost_potrditev',
                'com_belauncerunde.runda_sprememba', 'com_belauncerunde.runda_odpoved',
                'com_belauncerunde.nova_prijava'] as $predloga) {
                if (!MailTemplate::deleteTemplate($predloga)) {
                    Log::add('ODSTRANITEV_MAIL_NAPAKA', Log::ERROR, 'com_belauncerunde');
                }
            }
        } catch (\Throwable $exception) {
            Log::add('ODSTRANITEV_PODATKI_NAPAKA', Log::ERROR, 'com_belauncerunde');
        }

        return true;
    }

    /**
     * Poišče ali ustvari skupino Člani pod skupino Registered.
     *
     * @param   DatabaseInterface  $db  Povezava z bazo.
     *
     * @return  int
     *
     * @since   0.1.0
     */
    private function pripraviSkupinoClani(DatabaseInterface $db): int
    {
        $params = ComponentHelper::getParams('com_belauncerunde');
        $id     = (int) $params->get('skupina_clani', 0);

        if ($id > 0 && $this->skupinaObstaja($db, $id)) {
            return $id;
        }

        $registeredId = $this->najdiRegisteredId($db);
        $claniId      = $this->najdiSkupino($db, 'Člani', $registeredId);

        if ($claniId > 0) {
            return $claniId;
        }

        // Skupina uporabnikov uporablja gnezdeno drevo, zato jo shranimo prek Joomline tabele.
        $table = new Usergroup($db);
        $table->bind(
            [
                'title'     => 'Člani',
                'parent_id' => $registeredId,
            ]
        );

        if (!$table->check() || !$table->store()) {
            throw new \RuntimeException(Text::_('COM_BELAUNCERUNDE_INSTALL_GROUP_FAILED'));
        }

        return (int) $table->id;
    }

    /**
     * Preveri obstoj skupine po ID.
     *
     * @param   DatabaseInterface  $db  Povezava z bazo.
     * @param   int                $id  ID skupine.
     *
     * @return  bool
     *
     * @since   0.1.0
     */
    private function skupinaObstaja(DatabaseInterface $db, int $id): bool
    {
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        return (int) $db->loadResult() > 0;
    }

    /**
     * Poišče skupino Registered, sicer uporabi standardni ID 2.
     *
     * @param   DatabaseInterface  $db  Povezava z bazo.
     *
     * @return  int
     *
     * @since   0.1.0
     */
    private function najdiRegisteredId(DatabaseInterface $db): int
    {
        $id = $this->najdiSkupino($db, 'Registered', null);

        if ($id > 0) {
            return $id;
        }

        Log::add(Text::_('COM_BELAUNCERUNDE_INSTALL_REGISTERED_FALLBACK'), Log::WARNING, 'com_belauncerunde');

        return 2;
    }

    /**
     * Poišče skupino po naslovu in po želji po staršu.
     *
     * @param   DatabaseInterface  $db        Povezava z bazo.
     * @param   string             $title     Naslov skupine.
     * @param   int|null           $parentId  ID starša ali null.
     *
     * @return  int
     *
     * @since   0.1.0
     */
    private function najdiSkupino(DatabaseInterface $db, string $title, ?int $parentId): int
    {
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = :title')
            ->bind(':title', $title);

        if ($parentId !== null) {
            $query->where($db->quoteName('parent_id') . ' = :parent_id')
                ->bind(':parent_id', $parentId, ParameterType::INTEGER);
        }

        $db->setQuery($query, 0, 1);

        return (int) $db->loadResult();
    }

    /**
     * Shrani parameter komponente brez prepisovanja ostalih nastavitev.
     *
     * @param   DatabaseInterface  $db     Povezava z bazo.
     * @param   string             $kljuc  Ključ parametra.
     * @param   mixed              $vrednost  Vrednost parametra.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    private function shraniParameter(DatabaseInterface $db, string $kljuc, $vrednost): void
    {
        $query = $db->createQuery()
            ->select($db->quoteName(['extension_id', 'params']))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_belauncerunde'));
        $db->setQuery($query);

        $extension = $db->loadObject();

        if (!$extension) {
            return;
        }

        $params = new Registry($extension->params ?: '{}');
        $params->set($kljuc, $vrednost);
        $extensionId = (int) $extension->extension_id;
        $paramsJson  = $params->toString();

        $query = $db->createQuery()
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = :params')
            ->where($db->quoteName('extension_id') . ' = :extension_id')
            ->bind(':params', $paramsJson)
            ->bind(':extension_id', $extensionId, ParameterType::INTEGER);
        $db->setQuery($query);
        $db->execute();
    }

    /** Registrira privzeto predlogo enkrat; adminovih popravkov ne prepisuje. */
    private function pripraviPostnoPredlogo(): void
    {
        $this->pripraviPredlogiRunde();
        $this->pripraviPredlogoNovePrijave();

        if (MailTemplate::getTemplate('com_belauncerunde.gost_potrditev', '') === null) {
            if (!MailTemplate::createTemplate(
                'com_belauncerunde.gost_potrditev',
                'COM_BELAUNCERUNDE_MAIL_GOST_POTRDITEV_SUBJECT',
                'COM_BELAUNCERUNDE_MAIL_GOST_POTRDITEV_BODY',
                ['IME', 'RUNDA', 'ZACETEK', 'LOKACIJA', 'VODJA', 'POVEZAVA', 'SITENAME']
            )) {
                throw new \RuntimeException('COM_BELAUNCERUNDE_INSTALL_MAIL_NAPAKA');
            }
        }

        if (MailTemplate::getTemplate('com_belauncerunde.prijava', '') === null) {
            if (!MailTemplate::createTemplate(
                'com_belauncerunde.prijava',
                'COM_BELAUNCERUNDE_MAIL_PRIJAVA_SUBJECT',
                'COM_BELAUNCERUNDE_MAIL_PRIJAVA_BODY',
                ['IME', 'POVEZAVA', 'VELJAVNOST', 'SITENAME']
            )) {
                throw new \RuntimeException('COM_BELAUNCERUNDE_INSTALL_MAIL_NAPAKA');
            }
        }
    }

    /** Registrira predlogo nove prijave in manjkajoča jezika brez prepisovanja sprememb. */
    private function pripraviPredlogoNovePrijave(): void
    {
        $kljuc = 'com_belauncerunde.nova_prijava';

        if (MailTemplate::getTemplate($kljuc, '') === null
            && !MailTemplate::createTemplate($kljuc,
                'COM_BELAUNCERUNDE_MAIL_NOVA_PRIJAVA_SUBJECT',
                'COM_BELAUNCERUNDE_MAIL_NOVA_PRIJAVA_BODY',
                ['IME', 'RUNDA', 'ZACETEK', 'STEVILO', 'POVEZAVA', 'SITENAME'])) {
            throw new \RuntimeException('COM_BELAUNCERUNDE_INSTALL_MAIL_NAPAKA');
        }

        $baza = Factory::getContainer()->get(DatabaseInterface::class);

        // Rezervna vrstica language='' ni dokaz, da obstaja tudi prevod posameznega jezika.
        foreach (['sl-SI', 'en-GB'] as $jezik) {
            $poizvedba = $baza->createQuery()->select('COUNT(*)')->from($baza->quoteName('#__mail_templates'))
                ->where('template_id = :kljuc')->where('language = :jezik')
                ->bind(':kljuc', $kljuc, ParameterType::STRING)->bind(':jezik', $jezik, ParameterType::STRING);

            if ((int) $baza->setQuery($poizvedba)->loadResult() > 0) {
                continue;
            }

            $sql = 'INSERT INTO #__mail_templates'
                . ' (template_id, language, subject, body, htmlbody, attachments, params, extension)'
                . ' SELECT template_id, :jezik, subject, body, htmlbody, attachments, params, extension'
                . " FROM #__mail_templates WHERE template_id = :kljuc AND language = ''";
            $poizvedba = $baza->createQuery()->setQuery($sql)
                ->bind(':jezik', $jezik, ParameterType::STRING)->bind(':kljuc', $kljuc, ParameterType::STRING);
            $baza->setQuery($poizvedba)->execute();
        }
    }

    /** Registrira D50 predlogi in jezikovni različici brez prepisovanja adminovih sprememb. */
    private function pripraviPredlogiRunde(): void
    {
        $baza = Factory::getContainer()->get(DatabaseInterface::class);

        foreach (['sprememba', 'odpoved'] as $dogodek) {
            $kljuc = 'com_belauncerunde.runda_' . $dogodek;
            $predpona = 'COM_BELAUNCERUNDE_MAIL_RUNDA_' . strtoupper($dogodek);

            if (MailTemplate::getTemplate($kljuc, '') === null
                && !MailTemplate::createTemplate($kljuc, $predpona . '_SUBJECT', $predpona . '_BODY',
                    ['RUNDA', 'ZACETEK', 'SPREMEMBE', 'POVEZAVA', 'ODJAVA', 'SITENAME'])) {
                throw new \RuntimeException('COM_BELAUNCERUNDE_INSTALL_MAIL_NAPAKA');
            }

            // createTemplate ustvari samo language=''; getTemplate sicer vrne tudi
            // ta rezervni zapis, zato obstoj jezikovne vrstice preverimo izrecno.
            foreach (['sl-SI', 'en-GB'] as $jezik) {
                $poizvedba = $baza->createQuery()->select('COUNT(*)')->from($baza->quoteName('#__mail_templates'))
                    ->where('template_id = :kljuc')->where('language = :jezik')
                    ->bind(':kljuc', $kljuc, ParameterType::STRING)->bind(':jezik', $jezik, ParameterType::STRING);

                if ((int) $baza->setQuery($poizvedba)->loadResult() > 0) {
                    continue;
                }

                $sql = 'INSERT INTO #__mail_templates'
                    . ' (template_id, language, subject, body, htmlbody, attachments, params, extension)'
                    . ' SELECT template_id, :jezik, subject, body, htmlbody, attachments, params, extension'
                    . " FROM #__mail_templates WHERE template_id = :kljuc AND language = ''";
                $poizvedba = $baza->createQuery()->setQuery($sql)
                    ->bind(':jezik', $jezik, ParameterType::STRING)->bind(':kljuc', $kljuc, ParameterType::STRING);
                $baza->setQuery($poizvedba)->execute();
            }
        }
    }

    /** Vklopi naša vtičnika in opozori na manjkajoča vtičnika za daljšo prijavo. */
    private function pripraviVticnike(DatabaseInterface $db): void
    {
        $tip = 'plugin';
        $element = 'belauncerunde';
        $izklopljen = 0;
        $vklopljen = 1;

        // Paket namesti oba vtičnika pred komponento; ob posodobitvi vklopimo le izklopljena.
        foreach (['authentication', 'task'] as $mapa) {
            $poizvedba = $db->createQuery()->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = :vklopljen')
                ->where($db->quoteName('type') . ' = :tip')
                ->where($db->quoteName('folder') . ' = :mapa')
                ->where($db->quoteName('element') . ' = :element')
                ->where($db->quoteName('enabled') . ' = :izklopljen')
                ->bind(':vklopljen', $vklopljen, ParameterType::INTEGER)
                ->bind(':tip', $tip, ParameterType::STRING)
                ->bind(':mapa', $mapa, ParameterType::STRING)
                ->bind(':element', $element, ParameterType::STRING)
                ->bind(':izklopljen', $izklopljen, ParameterType::INTEGER);
            $db->setQuery($poizvedba)->execute();
            Log::add('VTICNIK_VKLOP mapa=' . $mapa . ' spremenjeno=' . $db->getAffectedRows(), Log::INFO, 'com_belauncerunde');
        }

        // Jedrnih vtičnikov ne vklapljamo samodejno, ampak admina opozorimo na oba pogoja D43.
        foreach ([['authentication', 'cookie'], ['system', 'remember']] as [$mapaJedra, $elementJedra]) {
            $poizvedba = $db->createQuery()->select($db->quoteName('enabled'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = :tip')
                ->where($db->quoteName('folder') . ' = :mapa')
                ->where($db->quoteName('element') . ' = :element')
                ->bind(':tip', $tip, ParameterType::STRING)
                ->bind(':mapa', $mapaJedra, ParameterType::STRING)
                ->bind(':element', $elementJedra, ParameterType::STRING);

            if ((int) $db->setQuery($poizvedba)->loadResult() !== 1) {
                Factory::getApplication()->enqueueMessage(Text::_('COM_BELAUNCERUNDE_INSTALL_ZAPOMNI_OPOZORILO'), 'warning');
                break;
            }
        }
    }

    /**
     * Razdeli preprosto SQL datoteko na posamezne stavke.
     *
     * @param   string  $sql  Vsebina SQL datoteke.
     *
     * @return  array
     *
     * @since   0.1.0
     */
    private function razdeliSql(string $sql): array
    {
        $stavki = array_filter(array_map('trim', explode(';', $sql)));

        return array_values($stavki);
    }
}
