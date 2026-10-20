<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Field;

use Joomla\CMS\Form\Field\CheckboxesField;

\defined('_JEXEC') or die;

/** Več izbir pošlje tudi prazno izbiro, da je mogoče izklopiti vse sprožilce. */
class MailpoljaField extends CheckboxesField
{
    /** @var string Tip polja za nastavitve pošte. */
    protected $type = 'Mailpolja';

    /** Prazen član seznama prepreči vrnitev privzetkov ob odkljukanju obeh polj. */
    protected function getInput()
    {
        return '<input type="hidden" name="' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8')
            . '" value="">' . parent::getInput();
    }
}
