<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/** Javni seznam objavljenih in odpovedanih rund, ki še niso končane. */
class RundeModel extends ListModel
{
    /**
     * Prebere filtre samo iz GET, brez ohranjanja prejšnje izbire v seji.
     *
     * @param string|null $ordering  Neuporabljeno; vrstni red je vedno po začetku.
     * @param string|null $direction Neuporabljeno; smer je vedno naraščajoča.
     * @return void
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app = Factory::getApplication();
        $vhod = $app->getInput()->get;
        $parametri = $app->getParams('com_belauncerunde');
        $omejitev = max(1, (int) $parametri->get('list_limit', 20));

        // Starševska metoda uporablja sejo; tukaj mora isti URL vedno pomeniti isto izbiro.
        $this->setState('params', $parametri);
        $this->setState('list.limit', $omejitev);
        // SiteRouter pretvori SEF parameter start v limitstart v glavnem vhodu aplikacije.
        $odmik = $app->getInput()->getUint('limitstart', 0);
        $this->setState('list.start', (int) (floor($odmik / $omejitev) * $omejitev));
        $this->setState('list.ordering', 'a.zacetek');
        $this->setState('list.direction', 'ASC');

        // Neveljaven niz ali negativna vrednost pomeni »vse«, ne delno izluščene številke.
        foreach (['tip', 'tezavnost'] as $filter) {
            $ime = 'filter_' . $filter;
            $vrednost = $vhod->getUint($ime, 0);
            $this->setState('filter.' . $filter, ctype_digit($vhod->getString($ime, '0')) ? $vrednost : 0);
        }
    }

    /**
     * Loči rezultate predpomnilnika glede na oba filtra.
     *
     * @param string $id Osnovni ključ.
     * @return string
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.tip') . ':' . $this->getState('filter.tezavnost');

        return parent::getStoreId($id);
    }

    /**
     * Bere samo javna polja in uporablja UTC mejo konca runde.
     *
     * @return QueryInterface
     */
    protected function getListQuery(): QueryInterface
    {
        $baza = $this->getDatabase();
        $privzetoTrajanje = (int) $this->getState('params')->get('privzeto_trajanje_min', 180);
        $tip = (int) $this->getState('filter.tip');
        $tezavnost = (int) $this->getState('filter.tezavnost');
        $prijave = Factory::getApplication()->bootComponent('com_belauncerunde')
            ->getMVCFactory()->createModel('Prijave', 'Site', []);
        $poizvedba = $baza->createQuery()
            ->select($baza->quoteName(['a.id', 'a.naslov', 'a.alias', 'a.zacetek', 'a.lokacija', 'a.dolzina_km', 'a.stanje']))
            ->select($baza->quoteName('t.naziv', 'tip_naziv'))
            ->select($baza->quoteName('t.alias', 'tip_alias'))
            ->select($baza->quoteName('t.barva', 'tip_barva'))
            ->select($baza->quoteName('tz.naziv', 'tezavnost_naziv'))
            ->select($baza->quoteName('tz.id', 'tezavnost_id'))
            ->select($baza->quoteName('a.trajanje_min'))
            ->select($baza->quoteName('u.name', 'vodja_ime'))
            ->select($baza->quoteName('a.vodja_id'))
            ->select('(' . $prijave->izrazStevilaUdelezencev('a') . ') AS stevilo_udelezencev')
            ->select('a.zacetek <= UTC_TIMESTAMP() AS startala')
            ->from($baza->quoteName('#__belaunce_runde', 'a'))
            ->join('LEFT', $baza->quoteName('#__belaunce_tipi', 't') . ' ON t.id = a.tip_id')
            ->join('LEFT', $baza->quoteName('#__belaunce_tezavnosti', 'tz') . ' ON tz.id = a.tezavnost_id')
            ->join('LEFT', $baza->quoteName('#__users', 'u') . ' ON u.id = a.vodja_id')
            ->where($baza->quoteName('a.stanje') . ' IN (1, 2)')
            ->where('DATE_ADD(a.zacetek, INTERVAL COALESCE(a.trajanje_min, :privzeto_trajanje) MINUTE) >= UTC_TIMESTAMP()')
            ->bind(':privzeto_trajanje', $privzetoTrajanje, ParameterType::INTEGER)
            ->order($baza->quoteName('a.zacetek') . ' ASC, ' . $baza->quoteName('a.id') . ' ASC');

        // Vezavi imata ločeni spremenljivki, ker bind() vrednosti hrani po referenci.
        if ($tip > 0) {
            $poizvedba->where($baza->quoteName('a.tip_id') . ' = :tip')->bind(':tip', $tip, ParameterType::INTEGER);
        }

        if ($tezavnost > 0) {
            $poizvedba->where($baza->quoteName('a.tezavnost_id') . ' = :tezavnost')->bind(':tezavnost', $tezavnost, ParameterType::INTEGER);
        }

        return $poizvedba;
    }

    /** Vrne objavljene tipe v vrstnem redu šifranta. @return array */
    public function tipi(): array
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select($baza->quoteName(['id', 'naziv']))
            ->from($baza->quoteName('#__belaunce_tipi'))->where($baza->quoteName('stanje') . ' = 1')
            ->order($baza->quoteName('ordering') . ' ASC, ' . $baza->quoteName('id') . ' ASC');
        $baza->setQuery($poizvedba);

        return $baza->loadObjectList();
    }

    /** Vrne objavljene težavnosti v vrstnem redu šifranta. @return array */
    public function tezavnosti(): array
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select($baza->quoteName(['id', 'naziv']))
            ->from($baza->quoteName('#__belaunce_tezavnosti'))->where($baza->quoteName('stanje') . ' = 1')
            ->order($baza->quoteName('ordering') . ' ASC, ' . $baza->quoteName('id') . ' ASC');
        $baza->setQuery($poizvedba);

        return $baza->loadObjectList();
    }
}
