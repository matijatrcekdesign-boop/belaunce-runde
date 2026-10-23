<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<div class="br-odjava-gosta container py-4">
    <h1><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_ODJAVA_NASLOV')); ?></h1>
    <?php if ($this->potrditev !== '') : ?>
        <p class="br-lead"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_ODJAVA_OPIS')); ?></p>
        <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>">
            <input type="hidden" name="task" value="gost.odjava">
            <input type="hidden" name="potrditev" value="<?php echo $this->escape($this->potrditev); ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn btn-primary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_ODJAVA_GUMB')); ?></button>
        </form>
    <?php elseif ($this->obvestilo !== '') : ?>
        <p class="alert alert-info" role="status"><?php echo $this->escape(Text::_($this->obvestilo)); ?></p>
    <?php endif; ?>
    <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NAZAJ_NA_RUNDE')); ?></a></p>
</div>
