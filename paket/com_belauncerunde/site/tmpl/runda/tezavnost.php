<?php

use Belaunce\Component\Belauncerunde\Site\Helper\VidezHelper;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

// Število stolpcev sledi objavljenim šifrantom; besedilo ostane tudi brez ujemanja.
$rang = VidezHelper::rangTezavnosti((int) ($runda->tezavnost_id ?? 0), $this->tezavnosti);
$oznaka = (string) ($runda->tezavnost_naziv ?? '');
$oznaka = $oznaka !== '' ? $oznaka : Text::_('COM_BELAUNCERUNDE_NI_PODATKA');
?>
<span class="br-diff" aria-label="<?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TEZAVNOST') . ': ' . $oznaka); ?>">
    <span class="br-diff__bars" aria-hidden="true">
        <?php foreach ($this->tezavnosti as $indeks => $tezavnost) : ?>
            <span class="br-diff__bar<?php echo $indeks < $rang ? ' br-diff__bar--on' : ''; ?>"></span>
        <?php endforeach; ?>
    </span>
    <span class="br-diff__label"><?php echo $this->escape($oznaka); ?></span>
</span>
