<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;
?>
<div class="br-prijava container py-4">
    <h1><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_NASLOV')); ?></h1>
    <?php if ($this->obvestilo !== null) : ?>
        <div class="alert <?php echo $this->escape($this->obvestilo['razred']); ?>" role="alert">
            <?php echo $this->escape(Text::_($this->obvestilo['kljuc'])); ?>
        </div>
    <?php endif; ?>
    <?php if ($this->prijavljen) : ?>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_ZE_PRIJAVLJEN')); ?></p>
        <a href="<?php echo $this->escape(Uri::root()); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DOMOV')); ?></a>
    <?php else : ?>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_OPIS')); ?></p>
        <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&task=prijava.poslji')); ?>" method="post">
            <?php echo $this->form->renderField('email'); ?>
            <?php echo $this->form->renderField('runda_id'); ?>
            <button class="btn btn-primary" type="submit"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_POSLJI')); ?></button>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    <?php endif; ?>
</div>
