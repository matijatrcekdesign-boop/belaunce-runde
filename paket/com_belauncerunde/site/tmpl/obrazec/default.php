<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<div class="br-obrazec">
    <h1><?php echo $this->escape(Text::_($this->rundaId === 0 ? 'COM_BELAUNCERUNDE_USTVARI_RUNDO' : 'COM_BELAUNCERUNDE_UREDI_RUNDO')); ?></h1>
    <?php if ($this->obvestilo !== null) : ?>
        <p class="alert alert-warning" role="alert"><?php echo $this->escape(Text::_($this->obvestilo)); ?></p>
    <?php endif; ?>
    <?php if ($this->napake !== []) : ?>
        <ul class="alert alert-warning" role="alert">
            <?php foreach ($this->napake as $napaka) : ?>
                <li><?php echo $this->escape($napaka); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>" method="post" id="br-obrazec" class="form-validate">
        <div class="row g-3">
            <?php foreach ($this->form->getFieldset('runda') as $polje) : ?>
                <div class="col-12 col-lg-6"><?php echo $polje->renderField(); ?></div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap my-3">
            <button type="submit" class="btn btn-primary validate"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_OBRAZEC_SHRANI')); ?></button>
            <a class="btn btn-outline-secondary" href="<?php echo $this->escape(Route::_($this->rundaId > 0 ? 'index.php?option=com_belauncerunde&view=runda&id=' . $this->rundaId : 'index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_OBRAZEC_PREKLICI')); ?></a>
        </div>
        <?php echo $this->form->renderControlFields(); ?>
    </form>
</div>
