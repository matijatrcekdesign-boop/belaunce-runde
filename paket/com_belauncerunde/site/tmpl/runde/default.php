<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<div class="br-runde">
    <h1><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDE_NASLOV')); ?></h1>
    <p class="br-podnaslov"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDE_PODNASLOV')); ?></p>

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
        <div class="br-cipi-wrap">
        <button type="button" class="br-cipi__nav br-cipi__nav--l" hidden aria-label="Levo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"></path></svg></button>
        <button type="button" class="br-cipi__nav br-cipi__nav--d" hidden aria-label="Desno"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"></path></svg></button>
        <nav class="br-cipi" aria-label="<?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_FILTRIRAJ')); ?>">
            <a class="br-cip<?php echo $brezFiltra ? ' br-cip--on' : ''; ?>" href="<?php echo $this->escape($urlFilter(0, 0)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VSE')); ?></a>
            <?php foreach ($this->tipi as $tip) : ?>
                <a class="br-cip<?php echo (int) $tip->id === $this->tip ? ' br-cip--on' : ''; ?>" href="<?php echo $this->escape($urlFilter($tip->id, $this->tezavnost)); ?>"><?php echo $this->escape($tip->naziv); ?></a>
            <?php endforeach; ?>
            <?php foreach ($this->tezavnosti as $tezavnost) : ?>
                <a class="br-cip<?php echo (int) $tezavnost->id === $this->tezavnost ? ' br-cip--on' : ''; ?>" href="<?php echo $this->escape($urlFilter($this->tip, $tezavnost->id)); ?>"><?php echo $this->escape($tezavnost->naziv); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php
        \Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript("document.addEventListener('DOMContentLoaded',function(){var w=document.querySelector('.br-cipi-wrap');if(!w)return;var n=w.querySelector('.br-cipi'),l=w.querySelector('.br-cipi__nav--l'),r=w.querySelector('.br-cipi__nav--d');function u(){var m=n.scrollWidth-n.clientWidth;l.hidden=n.scrollLeft<=2;r.hidden=m<=2||n.scrollLeft>=m-2;}l.addEventListener('click',function(){n.scrollBy({left:-160,behavior:'smooth'});});r.addEventListener('click',function(){n.scrollBy({left:160,behavior:'smooth'});});n.addEventListener('scroll',u,{passive:true});window.addEventListener('resize',u);u();});");
        ?>
        </div>
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
