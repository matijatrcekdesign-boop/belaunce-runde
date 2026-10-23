<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<div class="br-povezava container py-4">
    <h1><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_NASLOV')); ?></h1>
    <?php if ($this->prijavljen) : ?>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_ZE_PRIJAVLJEN')); ?></p>
        <a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NA_SEZNAM_RUND')); ?></a>
    <?php elseif (!$this->podatki) : ?>
        <p class="alert alert-warning" role="alert"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POVEZAVA_NEVELJAVNA')); ?></p>
        <a href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=prijava')); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POVEZAVA_NOVA')); ?></a>
    <?php else : ?>
        <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&task=povezava.prijavi')); ?>" method="post">
            <input type="hidden" name="zeton" value="<?php echo $this->escape($this->zeton); ?>">
            <button class="btn btn-primary" type="submit">
                <?php echo $this->escape($this->podatki->naslov !== ''
                    ? Text::sprintf('COM_BELAUNCERUNDE_POVEZAVA_PRIJAVI_RUNDA', $this->podatki->naslov)
                    : Text::_('COM_BELAUNCERUNDE_POVEZAVA_PRIJAVI')); ?>
            </button>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    <?php endif; ?>
</div>
