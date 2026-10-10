<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Field;

use Belaunce\Component\Belauncerunde\Administrator\Service\AcymailingAdapter;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

/**
 * Polje za izbiro AcyMailing liste.
 *
 * @since  0.3.0
 */
class AcymlistaField extends ListField
{
    /**
     * Vrsta polja.
     *
     * @var    string
     * @since  0.3.0
     */
    protected $type = 'Acymlista';

    /**
     * Pripravi možnosti iz AcyMailing list.
     *
     * @return  array
     *
     * @since   0.3.0
     */
    protected function getOptions(): array
    {
        $adapter = new AcymailingAdapter();
        $options = [
            HTMLHelper::_('select.option', 0, Text::_('COM_BELAUNCERUNDE_ACYM_LISTA_IZBERI')),
        ];

        if (!$adapter->jeNamescen()) {
            $this->description = Text::_('COM_BELAUNCERUNDE_ACYM_LISTA_NI_NAMESCEN_DESC');

            return array_merge(parent::getOptions(), $options);
        }

        foreach ($adapter->seznamList() as $id => $ime) {
            $options[] = HTMLHelper::_('select.option', $id, Text::sprintf('COM_BELAUNCERUNDE_ACYM_LISTA_OPTION', $ime, $id));
        }

        return array_merge(parent::getOptions(), $options);
    }
}
