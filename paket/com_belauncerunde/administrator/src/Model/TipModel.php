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
use Joomla\CMS\MVC\Model\AdminModel;

\defined('_JEXEC') or die;

/**
 * Model za urejanje tipa runde.
 *
 * @since  0.1.0
 */
class TipModel extends AdminModel
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
        return $this->loadForm('com_belauncerunde.tip', 'tip', ['control' => 'jform', 'load_data' => $loadData]);
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
        $data = Factory::getApplication()->getUserState('com_belauncerunde.edit.tip.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Novemu tipu dodeli naslednji vrstni red.
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
                ->from($db->quoteName('#__belaunce_tipi'));
            $db->setQuery($query);
            $table->ordering = (int) $db->loadResult() + 1;
        }
    }
}
