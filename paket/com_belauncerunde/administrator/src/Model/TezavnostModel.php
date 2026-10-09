<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Model za urejanje težavnosti runde.
 *
 * @since  0.1.0
 */
class TezavnostModel extends AdminModel
{
    /**
     * Vrne obrazec urejanja.
     *
     * @param   array  $data      Podatki za obrazec.
     * @param   bool   $loadData  Ali naj obrazec naloži shranjene podatke.
     *
     * @return  Form|false
     *
     * @since   0.1.0
     */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_belauncerunde.tezavnost', 'tezavnost', ['control' => 'jform', 'load_data' => $loadData]);
    }

    /**
     * Naloži podatke za obrazec iz seje ali tabele.
     *
     * @return  mixed
     *
     * @since   0.1.0
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_belauncerunde.edit.tezavnost.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Novi težavnosti dodeli naslednji vrstni red.
     *
     * @param   \Joomla\CMS\Table\Table  $table  Zapis za shranjevanje.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    protected function prepareTable($table)
    {
        if (empty($table->id) && empty($table->ordering)) {
            $db = $this->getDatabase();
            $query = $db->createQuery()
                ->select('MAX(' . $db->quoteName('ordering') . ')')
                ->from($db->quoteName('#__belaunce_tezavnosti'));
            $db->setQuery($query);
            $table->ordering = (int) $db->loadResult() + 1;
        }
    }

    /**
     * Izbriše samo težavnosti, ki jih ne uporablja nobena runda.
     *
     * @param   array  &$pks  ID-ji za brisanje.
     *
     * @return  bool
     *
     * @since   0.2.0
     */
    public function delete(&$pks): bool
    {
        $brisanje = [];
        $app      = Factory::getApplication();

        foreach ((array) $pks as $pk) {
            $pk = (int) $pk;
            $uporabe = $this->prestejRunde($pk);

            if ($uporabe > 0) {
                $app->enqueueMessage(Text::sprintf('COM_BELAUNCERUNDE_ERROR_TEZAVNOST_IN_USE', $uporabe), 'warning');
                continue;
            }

            $brisanje[] = $pk;
        }

        if ($brisanje === []) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_NO_TEZAVNOSTI_DELETED'));

            return false;
        }

        $pks = $brisanje;

        return parent::delete($brisanje);
    }

    /**
     * Prešteje runde, ki uporabljajo težavnost.
     *
     * @param   int  $id  ID težavnosti.
     *
     * @return  int
     *
     * @since   0.2.0
     */
    private function prestejRunde(int $id): int
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__belaunce_runde'))
            ->where($db->quoteName('tezavnost_id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        return (int) $db->loadResult();
    }
}
