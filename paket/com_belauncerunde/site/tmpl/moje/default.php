<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;
?>
<div class="br-moje">
    <h1><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_MOJE_NASLOV')); ?></h1>
    <p><a class="btn btn-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=obrazec&id=0', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_USTVARI_RUNDO')); ?></a></p>

    <?php if ($this->obvestilo !== null) : ?>
        <p class="alert <?php echo $this->escape($this->obvestilo['razred']); ?>" role="alert"><?php echo $this->escape($this->obvestilo['besedilo']); ?></p>
    <?php endif; ?>

    <nav class="nav nav-tabs mb-4" aria-label="<?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_MOJE_NASLOV')); ?>">
        <a class="nav-link<?php echo $this->pogled === 'prihajajoce' ? ' active' : ''; ?>"<?php echo $this->pogled === 'prihajajoce' ? ' aria-current="page"' : ''; ?> href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=moje', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_MOJE_PRIHAJAJOCE')); ?></a>
        <?php if ($this->prikaziPretekle) : ?>
            <a class="nav-link<?php echo $this->pogled === 'pretekle' ? ' active' : ''; ?>"<?php echo $this->pogled === 'pretekle' ? ' aria-current="page"' : ''; ?> href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=moje&pogled=pretekle', false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_MOJE_PRETEKLE')); ?></a>
        <?php endif; ?>
    </nav>

    <?php foreach ($this->sklopi as $sklop => $runde) : ?>
        <section class="mb-4" aria-labelledby="<?php echo $this->escape('br-moje-' . $sklop); ?>">
            <h2 class="h3" id="<?php echo $this->escape('br-moje-' . $sklop); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_MOJE_' . strtoupper($sklop))); ?></h2>
            <?php if (!$runde) : ?>
                <p class="alert alert-info"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_MOJE_PRAZNO_' . strtoupper($sklop))); ?></p>
            <?php else : ?>
                <div class="row g-3">
                    <?php foreach ($runde as $runda) : ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-3">
                            <?php $naslovNivo = 'h3'; require __DIR__ . '/../runda/kartica.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if ($this->paginacija !== null && $this->paginacija->pagesTotal > 1) : ?>
        <div class="mt-4">
            <?php // Zaupanja vreden HTML ustvari Joomlina paginacija z zaprtimi parametri. ?>
            <?php echo $this->paginacija->getPagesLinks(); ?>
        </div>
    <?php endif; ?>
</div>
