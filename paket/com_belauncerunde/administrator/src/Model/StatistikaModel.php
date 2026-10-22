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
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/** Agregati in vrstice izvoza nad enako filtrirano množico rund. */
class StatistikaModel extends ListModel
{
    use RundeFiltriTrait;

    /**
     * Dovoli istih šest filtrov kot seznam rund; razvrščanja tu ni.
     *
     * @param  ?MVCFactoryInterface  $factory
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] = ['search', 'tip_id', 'tezavnost_id', 'stanje', 'vodja_id', 'obdobje'];
        parent::__construct($config, $factory);
    }

    /**
     * Prebere skupne kazalnike z eno poizvedbo.
     *
     * @return  object
     */
    public function povzetek(): object
    {
        $db = $this->getDatabase();
        $udelezenci = $this->izrazUdelezencev();
        $query = $this->osnovnaPoizvedba()
            ->select([
                'COUNT(*) AS stevilo_rund',
                'SUM(CASE WHEN a.stanje = 1 THEN 1 ELSE 0 END) AS objavljene',
                'SUM(CASE WHEN a.stanje = 2 THEN 1 ELSE 0 END) AS odpovedane',
                'SUM(a.dolzina_km) AS skupna_dolzina',
                'AVG(a.dolzina_km) AS povprecna_dolzina',
                'AVG(a.trajanje_min) AS povprecno_trajanje',
                'SUM(' . $udelezenci . ') AS skupno_udelezencev',
                'AVG(' . $udelezenci . ') AS povprecno_udelezencev',
            ]);

        return $db->setQuery($query)->loadObject();
    }

    /**
     * Združi filtrirane runde po tipu brez poizvedbe za vsako vrstico.
     *
     * @return  array
     */
    public function poTipu(): array
    {
        return $this->poSifrantu('tipi', 'tip_id');
    }

    /**
     * Združi filtrirane runde po težavnosti brez poizvedbe za vsako vrstico.
     *
     * @return  array
     */
    public function poTezavnosti(): array
    {
        return $this->poSifrantu('tezavnosti', 'tezavnost_id');
    }

    /**
     * Vrne vse filtrirane runde za CSV, brez paginacije in zasebnih podatkov.
     *
     * @return  array
     */
    public function vrsticeIzvoza(): array
    {
        $db = $this->getDatabase();
        $query = $this->osnovnaPoizvedba()
            ->select([
                $db->quoteName('a.id'), $db->quoteName('a.naslov'),
                $db->quoteName('t.naziv', 'tip'), $db->quoteName('tz.naziv', 'tezavnost'),
                $db->quoteName('a.lokacija'), $db->quoteName('a.zacetek'),
                $db->quoteName('a.dolzina_km'), $db->quoteName('a.trajanje_min'),
                $db->quoteName('a.stanje'), $db->quoteName('u.name', 'vodja'),
                '(' . $this->izrazUdelezencev() . ') AS stevilo_udelezencev',
            ])
            ->join('INNER', $db->quoteName('#__belaunce_tipi', 't') . ' ON t.id = a.tip_id')
            ->join('INNER', $db->quoteName('#__belaunce_tezavnosti', 'tz') . ' ON tz.id = a.tezavnost_id')
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = a.vodja_id')
            ->order($db->quoteName('a.zacetek') . ' DESC, ' . $db->quoteName('a.id') . ' DESC');

        return $db->setQuery($query)->loadObjectList();
    }

    /** Sestavi osnovo z istimi filtri kot administratorski seznam rund. */
    private function osnovnaPoizvedba(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()->from($db->quoteName('#__belaunce_runde', 'a'));
        $this->dodajFiltre($query);

        return $query;
    }

    /** Zgradi razčlenitev za vnaprej določen šifrant. */
    private function poSifrantu(string $tabela, string $stolpec): array
    {
        $db = $this->getDatabase();
        $udelezenci = $this->izrazUdelezencev();
        $query = $this->osnovnaPoizvedba()
            ->select([
                $db->quoteName('s.naziv', 'naziv'),
                'COUNT(*) AS stevilo_rund',
                'AVG(a.dolzina_km) AS povprecna_dolzina',
                'AVG(' . $udelezenci . ') AS povprecno_udelezencev',
            ])
            ->join('INNER', $db->quoteName('#__belaunce_' . $tabela, 's') . ' ON s.id = a.' . $stolpec)
            ->group([$db->quoteName('s.id'), $db->quoteName('s.naziv'), $db->quoteName('s.ordering')])
            ->order($db->quoteName('s.ordering') . ' ASC, ' . $db->quoteName('s.id') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /** Uporabi edini D54 izraz iz site modela tudi v administraciji. */
    private function izrazUdelezencev(): string
    {
        $prijave = Factory::getApplication()->bootComponent('com_belauncerunde')
            ->getMVCFactory()->createModel('Prijave', 'Site', ['ignore_request' => true]);

        return $prijave->izrazStevilaUdelezencev('a');
    }
}
