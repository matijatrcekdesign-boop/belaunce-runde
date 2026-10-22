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
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/** Skupni filtri seznama rund, statistike in izvoza; vzdevek tabele je vedno a. */
trait RundeFiltriTrait
{
    /** Doda iste filtre kot seznam rund, z vezanimi vhodnimi vrednostmi. */
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
                $query->where('(' . $db->quoteName('a.naslov') . ' LIKE :naslov OR '
                    . $db->quoteName('a.lokacija') . ' LIKE :lokacija)')
                    ->bind(':naslov', $search)
                    ->bind(':lokacija', $search);
            }
        }

        // Vezave morajo imeti do izvedbe poizvedbe lastne spremenljivke, ker bind sprejme referenco.
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

            $query->where('DATE_ADD(' . $db->quoteName('a.zacetek') . ', INTERVAL COALESCE('
                . $db->quoteName('a.trajanje_min') . ', :privzeto_trajanje) MINUTE) '
                . $primerjava . ' :zdaj')
                ->bind(':privzeto_trajanje', $privzetoTrajanje, ParameterType::INTEGER)
                ->bind(':zdaj', $zdaj);
        }
    }
}
