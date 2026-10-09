<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\String\StringHelper;

\defined('_JEXEC') or die;

/**
 * Tabela tipov rund.
 *
 * @since  0.1.0
 */
class TipTable extends Table
{
    /**
     * Dovoli NULL za polji zaklepa ob odklepanju zapisa.
     *
     * @var    bool
     * @since  0.1.0
     */
    protected $_supportNullValue = true;

    /**
     * Ustvari povezavo na tabelo in alias za standardno objavljanje.
     *
     * @param   DatabaseInterface     $db          Povezava z bazo.
     * @param   ?DispatcherInterface  $dispatcher  Dogodkovni posrednik.
     *
     * @since   0.1.0
     */
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__belaunce_tipi', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'stanje');
    }

    /**
     * Preveri in dopolni podatke pred shranjevanjem.
     *
     * @return  bool
     *
     * @since   0.1.0
     */
    public function check(): bool
    {
        if (trim((string) $this->naziv) === '') {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_NAZIV_REQUIRED'));

            return false;
        }

        // Alias je obvezen za stabilne povezave kasnejših pogledov; prazen se ustvari iz naziva.
        if (trim((string) $this->alias) === '') {
            $this->alias = ApplicationHelper::stringURLSafe((string) $this->naziv);
        } else {
            $this->alias = ApplicationHelper::stringURLSafe((string) $this->alias);
        }

        $this->alias = substr((string) $this->alias, 0, 100);

        if ($this->alias === '') {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_ALIAS_REQUIRED'));

            return false;
        }

        $this->alias = $this->ustvariUnikatenAlias($this->alias);

        return parent::check();
    }

    /**
     * Po potrebi doda številko, da alias ostane unikaten.
     *
     * @param   string  $alias  Predlagani alias.
     *
     * @return  string
     *
     * @since   0.1.0
     */
    private function ustvariUnikatenAlias(string $alias): string
    {
        $db      = $this->getDatabase();
        $osnova  = $alias;
        $id      = (int) $this->id;
        $poskusi = 0;

        do {
            $query = $db->createQuery()
                ->select('COUNT(*)')
                ->from($db->quoteName($this->_tbl))
                ->where($db->quoteName('alias') . ' = :alias')
                ->where($db->quoteName('id') . ' <> :id')
                ->bind(':alias', $alias)
                ->bind(':id', $id, ParameterType::INTEGER);
            $db->setQuery($query);

            if ((int) $db->loadResult() === 0) {
                return $alias;
            }

            $poskusi++;
            $alias = substr(StringHelper::increment($osnova, 'dash'), 0, 100);
            $osnova = $alias;
        } while ($poskusi < 100);

        return substr($alias . '-' . time(), 0, 100);
    }
}
