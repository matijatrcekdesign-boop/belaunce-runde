<?php

use Joomla\CMS\HTML\HTMLHelper;
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
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <?php foreach ($runde as $runda) : ?>
                        <div class="col"><article class="card h-100"><div class="card-body">
                            <h3 class="h4 card-title"><a href="<?php echo $this->escape($runda->povezava); ?>"><?php echo $this->escape($runda->naslov); ?></a></h3>
                            <?php if ((int) $runda->stanje === 2) : ?>
                                <p><span class="badge bg-danger"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEDANO')); ?></span></p>
                            <?php endif; ?>
                            <?php if ($this->pogled === 'pretekle') : ?>
                                <p><span class="badge bg-secondary"><?php echo $this->escape(Text::_($runda->je_vodja ? 'COM_BELAUNCERUNDE_MOJE_VODIL' : 'COM_BELAUNCERUNDE_MOJE_UDELEZEN')); ?></span></p>
                            <?php endif; ?>
                            <dl>
                                <dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ZACETEK')); ?></dt>
                                <dd><?php echo $this->escape($runda->zacetek_prikaz); ?></dd>
                                <dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TIP')); ?></dt>
                                <dd><?php echo $this->escape((string) ($runda->tip_naziv ?? '')); ?></dd>
                                <dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TEZAVNOST')); ?></dt>
                                <dd><?php echo $this->escape((string) ($runda->tezavnost_naziv ?? '')); ?></dd>
                                <dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_LOKACIJA')); ?></dt>
                                <dd><?php echo $this->escape($runda->lokacija); ?></dd>
                                <dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DOLZINA')); ?></dt>
                                <dd><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', $runda->dolzina_km)); ?></dd>
                                <dt><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA')); ?></dt>
                                <dd><?php echo $this->escape((string) ($runda->vodja_ime ?? '')); ?></dd>
                            </dl>
                            <p><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_STEVILO_UDELEZENCEV', $runda->stevilo_udelezencev)); ?></p>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($runda->urejanje) : ?>
                                    <?php require __DIR__ . '/../runda/upravljanje.php'; ?>
                                <?php elseif ($runda->odjava) : ?>
                                    <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>" method="post">
                                        <input type="hidden" name="task" value="runda.odjava">
                                        <input type="hidden" name="id" value="<?php echo $this->escape((string) $runda->id); ?>">
                                        <input type="hidden" name="vrni" value="moje">
                                        <?php echo HTMLHelper::_('form.token'); ?>
                                        <button type="submit" class="btn btn-outline-secondary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODJAVA')); ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($runda->deljiva) : ?>
                                    <a class="btn btn-outline-primary" href="<?php echo $this->escape($runda->deli_viber); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_VIBER')); ?></a>
                                <?php endif; ?>
                            </div>
                        </div></article></div>
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
