<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Belaunce\Component\Belauncerunde\Site\Helper\VidezHelper;

\defined('_JEXEC') or die;
$runda = $this->runda;
?>
<article class="br-runda <?php echo $this->escape(VidezHelper::razredTipa($runda->tip_alias ?? null)); ?>">
    <h1><?php echo $this->escape($runda->naslov); ?></h1>
    <div class="br-runda__badges">
        <span class="br-tag"><?php echo $this->escape((string) ($runda->tip_naziv ?: Text::_('COM_BELAUNCERUNDE_NI_PODATKA'))); ?></span>
        <?php require __DIR__ . '/tezavnost.php'; ?>
    </div>
    <div class="d-flex gap-2 flex-wrap mb-3">
        <?php if ($this->clan) : ?>
            <a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=obrazec&id=0', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_USTVARI_RUNDO')); ?></a>
        <?php endif; ?>
        <?php if ($this->vodja && $this->odprta) : ?>
            <?php require __DIR__ . '/upravljanje.php'; ?>
        <?php endif; ?>
    </div>
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
    <div class="br-stat-grid">
        <div class="br-stat"><span class="v"><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', VidezHelper::dolzina($runda->dolzina_km))); ?></span><span class="l"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DOLZINA')); ?></span></div>
        <div class="br-stat"><span class="v"><?php echo $runda->trajanje_min === null ? $this->escape(Text::_('COM_BELAUNCERUNDE_NI_PODATKA')) : $this->escape(Text::sprintf('COM_BELAUNCERUNDE_TRAJANJE_MIN', $runda->trajanje_min)); ?></span><span class="l"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TRAJANJE')); ?></span></div>
        <div class="br-stat"><span class="v"><?php echo $this->escape((string) ($runda->tezavnost_naziv ?? '')); ?></span><span class="l"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TEZAVNOST')); ?></span></div>
    </div>
    <dl class="br-runda__facts">
        <div><dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ZACETEK')); ?></dt><dd><?php echo $this->escape($this->zacetek); ?></dd></div>
        <div><dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_LOKACIJA')); ?></dt><dd><?php echo $this->escape($runda->lokacija); ?></dd></div>
        <div><dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA')); ?></dt><dd><?php echo $this->escape((string) ($runda->vodja_ime ?? '')); ?></dd></div>
    </dl>
    <?php if ((string) ($runda->opombe ?? '') !== '') : ?>
        <h2 class="br-sec-title"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_OPOMBE')); ?></h2>
        <p class="br-opombe br-prose"><?php echo $this->escape($runda->opombe); ?></p>
    <?php endif; ?>
    <?php if ($this->trasa !== '') : ?>
        <h2 class="br-sec-title"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TRASA')); ?></h2>
        <?php if ($this->vdelava !== null) : ?>
            <div class="ratio ratio-4x3 mb-3">
                <iframe src="<?php echo $this->escape(Route::_($this->vdelava['src'], false)); ?>" loading="lazy" referrerpolicy="no-referrer" title="<?php echo $this->escape(Text::_($this->vdelava['naslov'])); ?>" allowfullscreen></iframe>
            </div>
        <?php endif; ?>
        <p><a href="<?php echo $this->escape(Route::_($this->trasa, false)); ?>" rel="noopener noreferrer" target="_blank"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TRASA')); ?></a></p>
    <?php endif; ?>
    <h2 class="br-sec-title"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_UDELEZENCI')); ?></h2>
    <p><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_STEVILO_UDELEZENCEV', $this->steviloUdelezencev)); ?></p>
    <?php if ($this->clan && $this->udelezenci !== []) : ?>
        <ul>
            <?php foreach ($this->udelezenci as $udelezenec) : ?>
                <li>
                    <?php echo $this->escape($udelezenec->ime); ?>
                    <?php if ($udelezenec->vodja) : ?>
                        <span class="badge bg-secondary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA')); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($udelezenec->gost)) : ?>
                        <span class="badge bg-info text-dark"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_ZNACKA')); ?></span>
                        <?php if ($this->vodja) : ?>
                            <?php if ($udelezenec->telefon !== '') : ?>
                                <span><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_TELEFON') . ': ' . $udelezenec->telefon); ?></span>
                            <?php endif; ?>
                            <?php if ($udelezenec->email !== '') : ?>
                                <span><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_GOST_EMAIL') . ': ' . $udelezenec->email); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
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
    <?php if ($this->gostObrazec !== null) : ?>
        <?php require __DIR__ . '/gost.php'; ?>
    <?php endif; ?>
    <?php if ($this->deljiva) : ?>
        <?php require __DIR__ . '/deli.php'; ?>
    <?php endif; ?>
    <?php if (!$this->clan) : ?>
        <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POZIV_CLANSTVO')); ?></p>
    <?php endif; ?>
    <p><a href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=runde', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_NAZAJ_NA_RUNDE')); ?></a></p>
</article>
