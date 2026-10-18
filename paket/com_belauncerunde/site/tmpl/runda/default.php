<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
$runda = $this->runda;
?>
<article class="br-runda">
    <h1><?php echo $this->escape($runda->naslov); ?></h1>
    <?php if ((int) $runda->stanje === 2) : ?>
        <p><span class="badge bg-danger"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEDANO')); ?></span></p>
    <?php endif; ?>
    <dl class="row">
        <dt class="col-sm-4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ZACETEK')); ?></dt>
        <dd class="col-sm-8"><?php echo $this->escape($this->zacetek); ?></dd>
        <dt class="col-sm-4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TIP')); ?></dt>
        <dd class="col-sm-8"><?php echo $this->escape((string) ($runda->tip_naziv ?? '')); ?></dd>
        <dt class="col-sm-4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TEZAVNOST')); ?></dt>
        <dd class="col-sm-8"><?php echo $this->escape((string) ($runda->tezavnost_naziv ?? '')); ?></dd>
        <dt class="col-sm-4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_LOKACIJA')); ?></dt>
        <dd class="col-sm-8"><?php echo $this->escape($runda->lokacija); ?></dd>
        <dt class="col-sm-4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DOLZINA')); ?></dt>
        <dd class="col-sm-8"><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', $runda->dolzina_km)); ?></dd>
        <dt class="col-sm-4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TRAJANJE')); ?></dt>
        <dd class="col-sm-8"><?php echo $runda->trajanje_min === null ? $this->escape(Text::_('COM_BELAUNCERUNDE_NI_PODATKA')) : $this->escape(Text::sprintf('COM_BELAUNCERUNDE_TRAJANJE_MIN', $runda->trajanje_min)); ?></dd>
        <dt class="col-sm-4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA')); ?></dt>
        <dd class="col-sm-8"><?php echo $this->escape((string) ($runda->vodja_ime ?? '')); ?></dd>
    </dl>
    <?php if ((string) ($runda->opombe ?? '') !== '') : ?>
        <h2 class="h4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_OPOMBE')); ?></h2>
        <p class="br-opombe"><?php echo $this->escape($runda->opombe); ?></p>
    <?php endif; ?>
    <?php if ($this->trasa !== '') : ?>
        <p><a href="<?php echo $this->escape(Route::_($this->trasa, false)); ?>" rel="noopener noreferrer" target="_blank"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TRASA')); ?></a></p>
    <?php endif; ?>
    <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NAZAJ_NA_RUNDE')); ?></a></p>
</article>
