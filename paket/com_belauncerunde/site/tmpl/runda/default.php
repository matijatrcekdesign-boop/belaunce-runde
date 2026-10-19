<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
$runda = $this->runda;
?>
<article class="br-runda">
    <h1><?php echo $this->escape($runda->naslov); ?></h1>
    <?php if ($this->obvestilo !== null) : ?>
        <p class="alert <?php echo $this->escape($this->obvestilo['razred']); ?>" role="status"><?php echo $this->escape(Text::_($this->obvestilo['kljuc'])); ?></p>
    <?php endif; ?>
    <?php if ((int) $runda->stanje === 2) : ?>
        <p><span class="badge bg-danger"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEDANO')); ?></span></p>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDA_ODPOVEDANA_OPIS')); ?></p>
    <?php endif; ?>
    <?php if ((bool) $runda->koncana) : ?>
        <p class="alert alert-info"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDA_KONCANA')); ?></p>
    <?php elseif ((bool) $runda->startala) : ?>
        <p class="alert alert-info"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDA_STARTALA')); ?></p>
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
    <h2 class="h4"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_UDELEZENCI')); ?></h2>
    <p><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_STEVILO_UDELEZENCEV', $this->steviloUdelezencev)); ?></p>
    <?php if ($this->clan && $this->udelezenci !== []) : ?>
        <ul>
            <?php foreach ($this->udelezenci as $udelezenec) : ?>
                <li>
                    <?php echo $this->escape($udelezenec->ime); ?>
                    <?php if ($udelezenec->vodja) : ?>
                        <span class="badge bg-secondary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA')); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($this->vodja) : ?>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA_UDELEZEN')); ?></p>
    <?php elseif ($this->odprta && $this->clan) : ?>
        <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>" method="post" class="mb-3">
            <input type="hidden" name="task" value="<?php echo $this->escape($this->udelezen ? 'runda.odjava' : 'runda.pridem'); ?>">
            <input type="hidden" name="id" value="<?php echo $this->escape((string) $runda->id); ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn <?php echo $this->escape($this->udelezen ? 'btn-outline-primary' : 'btn-primary'); ?>"><?php echo $this->escape(Text::_($this->udelezen ? 'COM_BELAUNCERUNDE_ODJAVA' : 'COM_BELAUNCERUNDE_PRIDEM')); ?></button>
        </form>
    <?php elseif ($this->odprta && $this->neprijavljen) : ?>
        <p><a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=prijava&runda_id=' . (int) $runda->id, false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIDEM')); ?></a></p>
    <?php endif; ?>
    <?php if (!$this->clan) : ?>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POZIV_CLANSTVO')); ?></p>
    <?php endif; ?>
    <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NAZAJ_NA_RUNDE')); ?></a></p>
</article>
