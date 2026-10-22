<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** Javni podatki posamezne runde brez kontaktov in podatkov prijavljenih. */
class RundaModel extends ItemModel
{
    /** Prebere ID iz zahteve; alias ne vpliva na iskanje. @return void */
    protected function populateState(): void
    {
        $this->setState('runda.id', Factory::getApplication()->getInput()->getInt('id', 0));
    }

    /**
     * Vrne objavljeno ali odpovedano rundo, tudi ko je že končana.
     *
     * @param int|null $pk Neobvezen ID runde.
     * @return object|null
     */
    public function getItem($pk = null): ?object
    {
        $id = $pk === null ? (int) $this->getState('runda.id') : (int) $pk;
        $baza = $this->getDatabase();
        $privzetoTrajanje = (int) ComponentHelper::getParams('com_belauncerunde')->get('privzeto_trajanje_min', 180);
        $poizvedba = $baza->createQuery()
            ->select($baza->quoteName(['a.id', 'a.naslov', 'a.alias', 'a.zacetek', 'a.lokacija', 'a.dolzina_km', 'a.trajanje_min', 'a.opombe', 'a.trasa_url', 'a.stanje']))
            ->select($baza->quoteName('t.naziv', 'tip_naziv'))
            ->select($baza->quoteName('t.alias', 'tip_alias'))
            ->select($baza->quoteName('tz.naziv', 'tezavnost_naziv'))
            ->select($baza->quoteName('tz.id', 'tezavnost_id'))
            ->select($baza->quoteName('u.name', 'vodja_ime'))
            ->select($baza->quoteName('a.vodja_id'))
            ->select($baza->quoteName('a.odprto_za_goste'))
            ->select('a.zacetek <= UTC_TIMESTAMP() AS startala')
            ->select('DATE_ADD(a.zacetek, INTERVAL COALESCE(a.trajanje_min, :trajanje) MINUTE) < UTC_TIMESTAMP() AS koncana')
            ->from($baza->quoteName('#__belaunce_runde', 'a'))
            ->join('LEFT', $baza->quoteName('#__belaunce_tipi', 't') . ' ON t.id = a.tip_id')
            ->join('LEFT', $baza->quoteName('#__belaunce_tezavnosti', 'tz') . ' ON tz.id = a.tezavnost_id')
            ->join('LEFT', $baza->quoteName('#__users', 'u') . ' ON u.id = a.vodja_id')
            ->where($baza->quoteName('a.id') . ' = :id')
            ->where($baza->quoteName('a.stanje') . ' IN (1, 2)')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->bind(':trajanje', $privzetoTrajanje, ParameterType::INTEGER);
        $baza->setQuery($poizvedba);

        return $baza->loadObject() ?: null;
    }
}
