<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\FormModel;

\defined('_JEXEC') or die;

/** Obrazec in validacija zahteve za prijavno povezavo. */
class PrijavaModel extends FormModel
{
    /** Naloži obrazec z email filtrom in skritim ID runde. */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_belauncerunde.prijava', 'prijava', ['control' => 'jform', 'load_data' => $loadData]);
    }

    /** Za začetni GET prenese samo nepredznačen ID runde, emaila ne hrani v seji. */
    protected function loadFormData()
    {
        return ['runda_id' => Factory::getApplication()->getInput()->getUint('runda_id', 0)];
    }

    /** Validira na strežniku, ne da bi napake obrazca spreminjale enoten odgovor. */
    public function preveriVnos(array $podatki): bool
    {
        return $this->validate($this->getForm([], false), $podatki) !== false;
    }
}
