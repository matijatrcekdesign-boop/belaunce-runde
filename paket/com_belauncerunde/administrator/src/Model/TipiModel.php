<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Model;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/**
 * Model seznama tipov rund.
 *
 * @since  0.1.0
 */
class TipiModel extends ListModel
{
    /**
     * Nastavi dovoljena polja za razvrščanje.
     *
     * @param   array                 $config   Nastavitve modela.
     * @param   ?MVCFactoryInterface  $factory  MVC tovarna.
     *
     * @since   0.1.0
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'naziv', 'a.naziv',
                'alias', 'a.alias',
                'published', 'a.stanje',
                'stanje', 'a.stanje',
                'ordering', 'a.ordering',
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
     * @since   0.1.0
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'asc'): void
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
     * @since   0.1.0
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    /**
     * Zgradi poizvedbo za seznam.
     *
     * @return  QueryInterface
     *
     * @since   0.1.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select(
            [
                $db->quoteName('a.id'),
                $db->quoteName('a.naziv'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.opis'),
                $db->quoteName('a.stanje'),
                $db->quoteName('a.stanje', 'published'),
                $db->quoteName('a.ordering'),
                $db->quoteName('a.checked_out'),
                $db->quoteName('a.checked_out_time'),
                $db->quoteName('uc.name', 'editor'),
            ]
        )
            ->from($db->quoteName('#__belaunce_tipi', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
            );

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.stanje') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $search = '%' . trim($search) . '%';
                $query->where(
                    '(' . $db->quoteName('a.naziv') . ' LIKE :naziv OR ' . $db->quoteName('a.alias') . ' LIKE :alias)'
                )
                    ->bind(':naziv', $search)
                    ->bind(':alias', $search);
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
}
