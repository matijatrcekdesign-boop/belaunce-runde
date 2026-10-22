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
        <form method="get" action="<?php echo $this->escape($this->povezavaSeznama); ?>" class="row g-3 align-items-end mb-4">
            <div class="col-12 col-sm-6 col-lg-4">
                <label for="br-filter-tip" class="form-label"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TIP')); ?></label>
                <select id="br-filter-tip" name="filter_tip" class="form-select">
                    <option value="0"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VSI_TIPI')); ?></option>
                    <?php foreach ($this->tipi as $tip) : ?>
                        <option value="<?php echo $this->escape((string) $tip->id); ?>"<?php echo (int) $tip->id === $this->tip ? ' selected' : ''; ?>><?php echo $this->escape($tip->naziv); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <label for="br-filter-tezavnost" class="form-label"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TEZAVNOST')); ?></label>
                <select id="br-filter-tezavnost" name="filter_tezavnost" class="form-select">
                    <option value="0"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VSE_TEZAVNOSTI')); ?></option>
                    <?php foreach ($this->tezavnosti as $tezavnost) : ?>
                        <option value="<?php echo $this->escape((string) $tezavnost->id); ?>"<?php echo (int) $tezavnost->id === $this->tezavnost ? ' selected' : ''; ?>><?php echo $this->escape($tezavnost->naziv); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_FILTRIRAJ')); ?></button>
                <a href="<?php echo $this->escape($this->povezavaSeznama); ?>" class="btn btn-outline-secondary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POCISTI')); ?></a>
            </div>
            <?php // GET obrazec ohrani usmerjanje tudi, ko brskalnik nadomesti query iz action. ?>
            <input type="hidden" name="option" value="com_belauncerunde">
            <input type="hidden" name="view" value="runde">
            <?php if ($this->menijskaPostavka > 0) : ?>
                <input type="hidden" name="Itemid" value="<?php echo $this->escape((string) $this->menijskaPostavka); ?>">
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <?php if (!$this->runde) : ?>
        <p class="alert alert-info"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_RUNDE_PRAZNO')); ?></p>
    <?php else : ?>
        <div class="row g-3">
            <?php foreach ($this->runde as $runda) : ?>
                <div class="col-12 col-md-6 col-lg-4">
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
