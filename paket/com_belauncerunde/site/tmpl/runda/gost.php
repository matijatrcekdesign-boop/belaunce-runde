<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<section class="br-gost mb-4" aria-labelledby="br-gost-naslov">
    <h2 id="br-gost-naslov" class="br-sec-title"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_NASLOV')); ?></h2>
    <p class="br-lead"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_KONTAKT_OPIS')); ?></p>
    <form method="post" action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>">
        <?php foreach (['ime', 'telefon', 'email'] as $polje) : ?>
            <?php echo $this->gostObrazec->renderField($polje); ?>
        <?php endforeach; ?>
        <div class="br-gost-dodatek" aria-hidden="true">
            <?php echo $this->gostObrazec->renderField('spletisce'); ?>
        </div>
        <?php // Zaupan HTML ustvari izključno Joomlin captcha vtičnik, nikoli uporabnikov vnos. ?>
        <?php echo $this->gostCaptcha; ?>
        <?php echo $this->gostObrazec->renderControlFields(); ?>
        <?php echo HTMLHelper::_('form.token'); ?>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_ODGOVORNOST')); ?></p>
        <button type="submit" class="btn btn-secondary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_PRIJAVA_GUMB')); ?></button>
    </form>
</section>
