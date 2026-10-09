<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Polje s tipi rund iz šifranta.
 *
 * @since  0.2.0
 */
class TipField extends ListField
{
    /**
     * Vrsta polja.
     *
     * @var    string
     * @since  0.2.0
     */
    protected $type = 'Tip';

    /**
     * Pripravi možnosti iz šifranta tipov.
     *
     * @return  array
     *
     * @since   0.2.0
     */
    protected function getOptions(): array
    {
        $db      = Factory::getContainer()->get(DatabaseInterface::class);
        $izbran  = (int) $this->value;
        $query   = $db->createQuery()
            ->select($db->quoteName(['id', 'naziv', 'stanje']))
            ->from($db->quoteName('#__belaunce_tipi'))
            ->where('(' . $db->quoteName('stanje') . ' = 1' . ($izbran > 0 ? ' OR ' . $db->quoteName('id') . ' = :izbran' : '') . ')')
            ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('naziv') . ' ASC');

        if ($izbran > 0) {
            $query->bind(':izbran', $izbran, ParameterType::INTEGER);
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        $options = parent::getOptions();

        foreach ($rows as $row) {
            $text = (string) $row->naziv;

            if ((int) $row->stanje !== 1) {
                $text .= ' ' . Text::_('COM_BELAUNCERUNDE_FIELD_SKRITO');
            }

            $options[] = HTMLHelper::_('select.option', (int) $row->id, $text);
        }

        return $options;
    }
}
