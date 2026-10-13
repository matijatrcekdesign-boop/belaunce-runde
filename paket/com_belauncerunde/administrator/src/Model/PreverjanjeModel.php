<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Model;

use Belaunce\Component\Belauncerunde\Administrator\Service\AcymailingAdapter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;

\defined('_JEXEC') or die;

/**
 * Model diagnostike AcyMailing članstva.
 *
 * @since  0.3.0
 */
class PreverjanjeModel extends FormModel
{
    /**
     * Vrne obrazec za preverjanje emaila.
     *
     * @param   array  $data      Podatki za obrazec.
     * @param   bool   $loadData  Ali naj obrazec naloži shranjene podatke.
     *
     * @return  Form|false
     *
     * @since   0.3.0
     */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_belauncerunde.preverjanje', 'preverjanje', ['control' => 'jform', 'load_data' => $loadData]);
    }

    /**
     * Preveri email in pripravi podatke za prikaz po preusmeritvi.
     *
     * @param   string  $email  Email za preverjanje.
     *
     * @return  array
     *
     * @since   0.3.0
     */
    public function preveri(string $email): array
    {
        $params   = ComponentHelper::getParams('com_belauncerunde');
        $listaId  = (int) $params->get('lista_clanov', 0);
        $adapter  = new AcymailingAdapter($this->getDatabase());
        $koda     = $adapter->preveriEmail($email, $listaId);
        $rezultat = [
            'email' => trim($email),
            'koda'  => $koda,
            'clan'  => null,
        ];

        if ($koda === 'CLAN') {
            $rezultat['clan'] = $adapter->poisciClana($email, $listaId);
        }

        return $rezultat;
    }

    /**
     * Prešteje trenutne člane nastavljene liste.
     *
     * @return  int
     *
     * @since   0.3.0
     */
    public function prestejClane(): int
    {
        $params  = ComponentHelper::getParams('com_belauncerunde');
        $listaId = (int) $params->get('lista_clanov', 0);

        if ($listaId <= 0) {
            return 0;
        }

        $adapter = new AcymailingAdapter($this->getDatabase());

        return \count($adapter->idjiClanov($listaId));
    }
}
