<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<div class="br-prijava">
    <div class="br-prijava__brand">
        <img src="/templates/belauncetemplate/img/logo100.png" alt="Belaunce">
        <span>Runde</span>
    </div>
    <h1><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_NASLOV')); ?></h1>
    <?php if ($this->obvestilo !== null) : ?>
        <div class="alert <?php echo $this->escape($this->obvestilo['razred']); ?>" role="alert">
            <?php echo $this->escape(Text::_($this->obvestilo['kljuc'])); ?>
        </div>
    <?php endif; ?>
    <?php if ($this->prijavljen) : ?>
        <p class="br-lead"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_ZE_PRIJAVLJEN')); ?></p>
        <a class="btn btn-secondary br-prijava__gumb" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NA_SEZNAM_RUND')); ?></a>
    <?php else : ?>
        <p class="br-lead"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_OPIS')); ?></p>
        <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&task=prijava.poslji')); ?>" method="post">
            <?php echo $this->form->renderField('email'); ?>
            <?php echo $this->form->renderField('runda_id'); ?>
            <button class="btn btn-secondary br-prijava__gumb" type="submit"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M4 7l8 6 8-6"></path></svg><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_POSLJI_POVEZAVO')); ?></button>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
        <p class="br-prijava__namig"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"></circle><path d="M12 11v5M12 8h.01"></path></svg><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVA_SAMO_CLANI')); ?></p>
        <div class="br-prijava__ali"><span></span><em><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ALI')); ?></em><span></span></div>
        <div class="br-prijava__gost">
            <h2><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NISI_CLAN')); ?></h2>
            <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NISI_CLAN_OPIS')); ?></p>
            <a class="br-prijava__gost-gumb" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POGLEJ_RUNDE')); ?> →</a>
        </div>
    <?php endif; ?>
</div>
