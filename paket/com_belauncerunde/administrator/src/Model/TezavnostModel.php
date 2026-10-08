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
 * Model za urejanje težavnosti runde.
 *
 * @since  0.1.0
 */
class TezavnostModel extends AdminModel
{
    /**
     * Predpona za dogodke modela.
     *
     * @var    string
     * @since  0.1.0
     */
    protected $event_after_save = 'onBelauncerundeAfterSave';

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
}
