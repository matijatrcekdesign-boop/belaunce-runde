<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Model;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Model za urejanje runde.
 *
 * @since  0.2.0
 */
class RundaModel extends AdminModel
{
    /**
     * Vrne obrazec urejanja.
     *
     * @param   array  $data      Podatki za obrazec.
     * @param   bool   $loadData  Ali naj obrazec naloži shranjene podatke.
     *
     * @return  Form|false
     *
     * @since   0.2.0
     */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_belauncerunde.runda', 'runda', ['control' => 'jform', 'load_data' => $loadData]);
    }

    /**
     * Naloži zapis in UTC čas pretvori za prikaz v obrazcu.
     *
     * @param   int|null  $pk  Primarni ključ.
     *
     * @return  object|false
     *
     * @since   0.2.0
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item === false) {
            return false;
        }

        if (!empty($item->id) && !empty($item->zacetek)) {
            $item->zacetek = CasHelper::izUtc((string) $item->zacetek);
        }

        if (empty($item->id)) {
            $params = ComponentHelper::getParams('com_belauncerunde');
            $item->lokacija = (string) $params->get('privzeta_lokacija', 'Gorenja vas');
            $item->vodja_id = (int) Factory::getApplication()->getIdentity()->id;
            $item->stanje   = 1;
        }

        return $item;
    }

    /**
     * Naloži podatke za obrazec iz seje ali tabele.
     *
     * @return  mixed
     *
     * @since   0.2.0
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_belauncerunde.edit.runda.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Shrani rundo po validaciji povezav in pretvorbi lokalnega časa v UTC.
     *
     * @param   array  $data  Podatki obrazca.
     *
     * @return  bool
     *
     * @since   0.2.0
     */
    public function save($data): bool
    {
        $user  = Factory::getApplication()->getIdentity();
        $id    = (int) ($data['id'] ?? 0);
        $isNew = $id === 0;

        if ($isNew && !$user->authorise('core.create', 'com_belauncerunde')) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'));

            return false;
        }

        if (!$isNew && !$user->authorise('core.edit', 'com_belauncerunde')) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));

            return false;
        }

        if (isset($data['stanje']) && !$user->authorise('core.edit.state', 'com_belauncerunde')) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));

            return false;
        }

        if (!$this->obstajaSifrant('#__belaunce_tipi', (int) ($data['tip_id'] ?? 0))) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_TIP_INVALID'));

            return false;
        }

        if (!$this->obstajaSifrant('#__belaunce_tezavnosti', (int) ($data['tezavnost_id'] ?? 0))) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_TEZAVNOST_INVALID'));

            return false;
        }

        if (!$this->obstajaVodja((int) ($data['vodja_id'] ?? 0))) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_VODJA_INVALID'));

            return false;
        }

        if (!empty($data['trasa_url'])) {
            $scheme = strtolower((string) parse_url((string) $data['trasa_url'], PHP_URL_SCHEME));

            if (!\in_array($scheme, ['http', 'https'], true)) {
                $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_TRASA_URL_INVALID'));

                return false;
            }
        }

        if (!empty($data['zacetek'])) {
            $data['zacetek'] = CasHelper::vUtc((string) $data['zacetek']);
        }

        foreach (['trajanje_min', 'trasa_url', 'opombe'] as $polje) {
            if (!isset($data[$polje]) || trim((string) $data[$polje]) === '') {
                $data[$polje] = null;
            }
        }

        return parent::save($data);
    }

    /**
     * Nastavi stanje izbranih rund.
     *
     * @param   array  $pks     ID-ji rund.
     * @param   int    $stanje  Novo stanje.
     *
     * @return  bool
     *
     * @since   0.2.0
     */
    public function nastaviStanje(array $pks, int $stanje): bool
    {
        if (!\in_array($stanje, [1, 2], true)) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_STANJE_INVALID'));

            return false;
        }

        $pks = array_values(array_filter(array_map('intval', $pks)));

        if ($pks === []) {
            $this->setError(Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));

            return false;
        }

        $table = $this->getTable();

        foreach ($pks as $pk) {
            if (!$table->load($pk)) {
                $this->setError($table->getError());

                return false;
            }

            $table->stanje = $stanje;

            if (!$table->check() || !$table->store()) {
                $this->setError($table->getError());

                return false;
            }
        }

        return true;
    }

    /**
     * Preveri obstoj šifrantnega zapisa.
     *
     * @param   string  $tabela  Ime tabele.
     * @param   int     $id      ID zapisa.
     *
     * @return  bool
     *
     * @since   0.2.0
     */
    private function obstajaSifrant(string $tabela, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName($tabela))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        return (int) $db->loadResult() > 0;
    }

    /**
     * Preveri, da je vodja obstoječ in neblokiran Joomla uporabnik.
     *
     * @param   int  $id  ID uporabnika.
     *
     * @return  bool
     *
     * @since   0.2.0
     */
    private function obstajaVodja(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $blokiran = 0;
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('block') . ' = :blokiran')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->bind(':blokiran', $blokiran, ParameterType::INTEGER);
        $db->setQuery($query);

        return (int) $db->loadResult() > 0;
    }
}
