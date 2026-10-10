<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Table;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

/**
 * Tabela rund.
 *
 * @since  0.2.0
 */
class RundaTable extends Table
{
    /**
     * Dovoli NULL za polja, kjer prazna vrednost pomeni odsotnost podatka.
     *
     * @var    bool
     * @since  0.2.0
     */
    protected $_supportNullValue = true;

    /**
     * Ustvari povezavo na tabelo rund.
     *
     * @param   DatabaseInterface     $db          Povezava z bazo.
     * @param   ?DispatcherInterface  $dispatcher  Dogodkovni posrednik.
     *
     * @since   0.2.0
     */
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__belaunce_runde', 'id', $db, $dispatcher);
    }

    /**
     * Preveri in dopolni podatke pred shranjevanjem.
     *
     * @return  bool
     *
     * @since   0.2.0
     */
    public function check(): bool
    {
        if (trim((string) $this->naslov) === '') {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_NASLOV_REQUIRED'));

            return false;
        }

        if (trim((string) $this->lokacija) === '') {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_LOKACIJA_REQUIRED'));

            return false;
        }

        if ((int) $this->tip_id <= 0 || (int) $this->tezavnost_id <= 0 || (int) $this->vodja_id <= 0) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_REQUIRED_RELATIONS'));

            return false;
        }

        if (trim((string) $this->zacetek) === '') {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_ZACETEK_REQUIRED'));

            return false;
        }

        if ((float) $this->dolzina_km <= 0) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_DOLZINA_REQUIRED'));

            return false;
        }

        if (!\in_array((int) $this->stanje, [1, 2], true)) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_STANJE_INVALID'));

            return false;
        }

        if (trim((string) $this->alias) === '') {
            $this->alias = ApplicationHelper::stringURLSafe((string) $this->naslov);
        } else {
            $this->alias = ApplicationHelper::stringURLSafe((string) $this->alias);
        }

        $this->alias = substr((string) $this->alias, 0, 255);

        if ($this->alias === '') {
            $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_ALIAS_REQUIRED'));

            return false;
        }

        if ($this->trasa_url !== null && trim((string) $this->trasa_url) !== '') {
            $scheme = strtolower((string) parse_url((string) $this->trasa_url, PHP_URL_SCHEME));

            if (!\in_array($scheme, ['http', 'https'], true)) {
                $this->setError(Text::_('COM_BELAUNCERUNDE_ERROR_TRASA_URL_INVALID'));

                return false;
            }
        }

        return parent::check();
    }

    /**
     * Pred shranjevanjem nastavi revizijska polja in čas odpovedi.
     *
     * @param   bool  $updateNulls  Ali naj se shranijo NULL vrednosti.
     *
     * @return  bool
     *
     * @since   0.2.0
     */
    public function store($updateNulls = true): bool
    {
        $isNew       = empty($this->id);
        $prejsnje    = $isNew ? null : $this->naloziPrejsnjeStanje((int) $this->id);
        $zdaj        = CasHelper::zdajUtc();
        $uporabnikId = (int) Factory::getApplication()->getIdentity()->id;

        if ($isNew) {
            $this->ustvarjeno  = $this->ustvarjeno ?: $zdaj;
            $this->ustvaril_id = $this->ustvaril_id ?: $uporabnikId;
        } else {
            $this->spremenjeno  = $zdaj;
            $this->spremenil_id = $uporabnikId;
        }

        // Čas odpovedi je vezan na prehod med stanjema, da običajno shranjevanje ne premakne datuma odpovedi.
        if ((int) $this->stanje === 2 && ($prejsnje === null || (int) $prejsnje->stanje !== 2)) {
            $this->odpovedano = $zdaj;
        } elseif ((int) $this->stanje === 1) {
            $this->odpovedano = null;
        }

        return parent::store($updateNulls);
    }

    /**
     * Naloži prejšnje stanje zapisa pred shranjevanjem.
     *
     * @param   int  $id  ID runde.
     *
     * @return  object|null
     *
     * @since   0.2.0
     */
    private function naloziPrejsnjeStanje(int $id): ?object
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['stanje', 'odpovedano']))
            ->from($db->quoteName('#__belaunce_runde'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        $row = $db->loadObject();

        return $row ?: null;
    }
}
