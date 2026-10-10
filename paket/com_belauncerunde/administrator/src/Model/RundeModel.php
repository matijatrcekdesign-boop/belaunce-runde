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
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/**
 * Model seznama rund.
 *
 * @since  0.2.0
 */
class RundeModel extends ListModel
{
    /**
     * Nastavi dovoljena polja za razvrščanje.
     *
     * @param   array                 $config   Nastavitve modela.
     * @param   ?MVCFactoryInterface  $factory  MVC tovarna.
     *
     * @since   0.2.0
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'naslov', 'a.naslov',
                'zacetek', 'a.zacetek',
                'stanje', 'a.stanje',
                'tip_id', 'a.tip_id',
                'tezavnost_id', 'a.tezavnost_id',
                'vodja_id', 'a.vodja_id',
            ];
        }

        parent::__construct($config, $factory);
    }

    /**
     * Napolni stanje seznama iz zahteve.
     *
     * @param   string  $ordering   Privzeto polje razvrščanja.
     * @param   string  $direction  Privzeta smer razvrščanja.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    protected function populateState($ordering = 'a.zacetek', $direction = 'desc'): void
    {
        parent::populateState($ordering, $direction);
    }

    /**
     * Ustvari ključ za predpomnjenje seznama.
     *
     * @param   string  $id  Obstoječi ključ.
     *
     * @return  string
     *
     * @since   0.2.0
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.tip_id');
        $id .= ':' . $this->getState('filter.tezavnost_id');
        $id .= ':' . $this->getState('filter.stanje');
        $id .= ':' . $this->getState('filter.vodja_id');
        $id .= ':' . $this->getState('filter.obdobje');

        return parent::getStoreId($id);
    }

    /**
     * Zgradi poizvedbo za seznam.
     *
     * @return  QueryInterface
     *
     * @since   0.2.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select(
            [
                $db->quoteName('a.id'),
                $db->quoteName('a.naslov'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.lokacija'),
                $db->quoteName('a.zacetek'),
                $db->quoteName('a.dolzina_km'),
                $db->quoteName('a.trajanje_min'),
                $db->quoteName('a.stanje'),
                $db->quoteName('a.odpovedano'),
                $db->quoteName('a.checked_out'),
                $db->quoteName('a.checked_out_time'),
                $db->quoteName('t.naziv', 'tip_naziv'),
                $db->quoteName('tz.naziv', 'tezavnost_naziv'),
                $db->quoteName('u.name', 'vodja_ime'),
                $db->quoteName('uc.name', 'editor'),
            ]
        )
            ->from($db->quoteName('#__belaunce_runde', 'a'))
            ->join('INNER', $db->quoteName('#__belaunce_tipi', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('a.tip_id'))
            ->join('INNER', $db->quoteName('#__belaunce_tezavnosti', 'tz') . ' ON ' . $db->quoteName('tz.id') . ' = ' . $db->quoteName('a.tezavnost_id'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.vodja_id'))
            ->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        $this->dodajFiltre($query);

        $orderCol  = $this->state->get('list.ordering', 'a.zacetek');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Seznamu doda filtre iz stanja modela.
     *
     * @param   QueryInterface  $query  Poizvedba seznama.
     *
     * @return  void
     *
     * @since   0.2.0
     */
    private function dodajFiltre(QueryInterface $query): void
    {
        $db = $this->getDatabase();

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $search = '%' . trim($search) . '%';
                $query->where(
                    '(' . $db->quoteName('a.naslov') . ' LIKE :naslov OR ' . $db->quoteName('a.lokacija') . ' LIKE :lokacija)'
                )
                    ->bind(':naslov', $search)
                    ->bind(':lokacija', $search);
            }
        }

        $vrednosti = [];

        foreach (['tip_id', 'tezavnost_id', 'stanje', 'vodja_id'] as $filter) {
            $value = (string) $this->getState('filter.' . $filter);

            if ($value !== '' && is_numeric($value)) {
                $placeholder = ':' . $filter;
                $vrednosti[$filter] = (int) $value;
                $query->where($db->quoteName('a.' . $filter) . ' = ' . $placeholder)
                    ->bind($placeholder, $vrednosti[$filter], ParameterType::INTEGER);
            }
        }

        $obdobje = (string) $this->getState('filter.obdobje');

        if (\in_array($obdobje, ['prihajajoce', 'pretekle'], true)) {
            $params = ComponentHelper::getParams('com_belauncerunde');
            $privzetoTrajanje = (int) $params->get('privzeto_trajanje_min', 180);
            $zdaj = CasHelper::zdajUtc();
            $primerjava = $obdobje === 'pretekle' ? '<' : '>=';

            $query->where(
                'DATE_ADD(' . $db->quoteName('a.zacetek') . ', INTERVAL COALESCE(' . $db->quoteName('a.trajanje_min') . ', :privzeto_trajanje) MINUTE) ' . $primerjava . ' :zdaj'
            )
                ->bind(':privzeto_trajanje', $privzetoTrajanje, ParameterType::INTEGER)
                ->bind(':zdaj', $zdaj);
        }
    }
}
