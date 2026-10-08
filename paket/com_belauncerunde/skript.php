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
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $id = $this->pripraviSkupinoClani($db);
            $this->shraniParameter($db, 'skupina_clani', $id);
        } catch (\Throwable $exception) {
            Log::add($exception->getMessage(), Log::ERROR, 'com_belauncerunde');
            Factory::getApplication()->enqueueMessage(Text::_('COM_BELAUNCERUNDE_INSTALL_GROUP_FAILED'), 'warning');
        }

        return true;
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
            $sqlPath = __DIR__ . '/administrator/sql/uninstall.mysql.utf8.sql';
            $queries = $this->razdeliSql((string) file_get_contents($sqlPath));

            foreach ($queries as $query) {
                $db->setQuery($query);
                $db->execute();
            }
        } catch (\Throwable $exception) {
            Log::add($exception->getMessage(), Log::ERROR, 'com_belauncerunde');
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
            throw new \RuntimeException((string) $table->getError());
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
