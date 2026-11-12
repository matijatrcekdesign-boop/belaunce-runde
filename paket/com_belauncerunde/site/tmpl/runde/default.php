<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<div class="br-runde">
    <h1><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDE_NASLOV')); ?></h1>
    <?php if ($this->clan) : ?>
        <p><a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=obrazec&id=0', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_USTVARI_RUNDO')); ?></a></p>
    <?php endif; ?>

    <?php if ($this->obvestilo !== null) : ?>
        <p class="alert <?php echo $this->escape($this->obvestilo['razred']); ?>" role="alert"><?php echo $this->escape($this->obvestilo['besedilo']); ?></p>
    <?php endif; ?>

    <?php if ((int) $this->parametri->get('prikazi_filtre', 1) === 1) : ?>
        <?php
        $itemid = (int) $this->menijskaPostavka;
        $urlFilter = static function ($tip, $tezavnost) use ($itemid) {
            $q = 'index.php?option=com_belauncerunde&view=runde';
            if ((int) $tip > 0) {
                $q .= '&filter_tip=' . (int) $tip;
            }
            if ((int) $tezavnost > 0) {
                $q .= '&filter_tezavnost=' . (int) $tezavnost;
            }
            if ($itemid > 0) {
                $q .= '&Itemid=' . $itemid;
            }
            return Route::_($q, false);
        };
        $brezFiltra = !$this->tip && !$this->tezavnost;
        ?>
        <nav class="br-cipi" aria-label="<?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_FILTRIRAJ')); ?>">
            <a class="br-cip<?php echo $brezFiltra ? ' br-cip--on' : ''; ?>" href="<?php echo $this->escape($urlFilter(0, 0)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VSE')); ?></a>
            <?php foreach ($this->tipi as $tip) : ?>
                <a class="br-cip<?php echo (int) $tip->id === $this->tip ? ' br-cip--on' : ''; ?>" href="<?php echo $this->escape($urlFilter($tip->id, $this->tezavnost)); ?>"><?php echo $this->escape($tip->naziv); ?></a>
            <?php endforeach; ?>
            <?php foreach ($this->tezavnosti as $tezavnost) : ?>
                <a class="br-cip<?php echo (int) $tezavnost->id === $this->tezavnost ? ' br-cip--on' : ''; ?>" href="<?php echo $this->escape($urlFilter($this->tip, $tezavnost->id)); ?>"><?php echo $this->escape($tezavnost->naziv); ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (!$this->runde) : ?>
        <p class="alert alert-info"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDE_PRAZNO')); ?></p>
    <?php else : ?>
        <div class="row g-3">
            <?php foreach ($this->runde as $runda) : ?>
                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <?php $naslovNivo = 'h2'; require __DIR__ . '/../runda/kartica.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($this->paginacija->pagesTotal > 1) : ?>
            <div class="mt-4">
                <?php // Zaupanja vreden HTML ustvari Joomlina paginacija iz filtriranih parametrov. ?>
                <?php echo $this->paginacija->getPagesLinks(); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
