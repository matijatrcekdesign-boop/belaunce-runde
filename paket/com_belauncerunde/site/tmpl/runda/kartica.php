<?php

use Belaunce\Component\Belauncerunde\Site\Helper\VidezHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

// V CSS spremenljivki gresta samo preverjena barva in izračunano kontrastno besedilo.
$barvaTipa = VidezHelper::barvaTipa($runda->tip_barva ?? null);
$kontrastTipa = VidezHelper::kontrastBesedila($barvaTipa);
$povezava = $runda->povezava ?? Route::_('index.php?option=com_belauncerunde&view=runda&id=' . (int) $runda->id, false);
$tipOznaka = (string) ($runda->tip_naziv ?? '');
$tipOznaka = $tipOznaka !== '' ? $tipOznaka : Text::_('COM_BELAUNCERUNDE_NI_PODATKA');
$jeDanes     = !empty($runda->je_danes);
$uraPrikaz   = (string) ($runda->ura_prikaz ?? '');
$datumKratko = (string) ($runda->datum_kratko ?? '');
?>
<article class="br-card h-100" style="--br-type: <?php echo $this->escape($barvaTipa); ?>; --br-type-text: <?php echo $this->escape($kontrastTipa); ?>">
    <div class="br-card__body">
        <div class="br-card__top">
            <div class="br-card__chips">
                <span class="br-tag"><?php echo $this->escape($tipOznaka); ?></span>
                <?php require __DIR__ . '/tezavnost.php'; ?>
            </div>
            <span class="br-datum<?php echo $jeDanes ? ' br-datum--danes' : ''; ?>">
                <?php echo $jeDanes
                    ? $this->escape(Text::_('COM_BELAUNCERUNDE_DANES')) . ' · ' . $this->escape($uraPrikaz)
                    : $this->escape($datumKratko) . ' · ' . $this->escape($uraPrikaz); ?>
            </span>
        </div>

        <<?php echo $naslovNivo; ?> class="br-card__title"><a href="<?php echo $this->escape($povezava); ?>"><?php echo $this->escape($runda->naslov); ?></a></<?php echo $naslovNivo; ?>>

        <div class="br-card__facts">
            <span class="br-fact">
                <svg class="br-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="2.6"></circle></svg>
                <?php echo $this->escape($runda->lokacija); ?>
            </span>
            <span class="br-fact">
                <svg class="br-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17 9 7l3 5 3-3 5 8"></path></svg>
                <?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', VidezHelper::dolzina($runda->dolzina_km))); ?>
            </span>
            <span class="br-fact">
                <svg class="br-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path d="M12 7.5V12l3 2"></path></svg>
                <?php echo $runda->trajanje_min === null ? $this->escape(Text::_('COM_BELAUNCERUNDE_NI_PODATKA')) : $this->escape(Text::sprintf('COM_BELAUNCERUNDE_TRAJANJE_MIN', $runda->trajanje_min)); ?>
            </span>
        </div>

        <?php if ((int) $runda->stanje === 2) : ?>
            <p class="br-card__cancelled"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEDANO')); ?></p>
        <?php endif; ?>

        <?php if (isset($runda->je_vodja) && $this->pogled === 'pretekle') : ?>
            <p class="br-card__meta"><?php echo $this->escape(Text::_($runda->je_vodja ? 'COM_BELAUNCERUNDE_MOJE_VODIL' : 'COM_BELAUNCERUNDE_MOJE_UDELEZEN')); ?></p>
        <?php endif; ?>

        <div class="br-card__footer">
            <div class="br-card__footer-info">
                <span class="br-leader"><span class="br-leader__avatar" aria-hidden="true"><?php echo $this->escape(VidezHelper::zacetnicaVodje($runda->vodja_ime ?? null)); ?></span><span><?php echo $this->escape((string) ($runda->vodja_ime ?? '')); ?></span></span>
                <?php if (isset($runda->stevilo_udelezencev)) : ?>
                    <span class="br-card__count"><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_STEVILO_UDELEZENCEV', $runda->stevilo_udelezencev)); ?></span>
                <?php endif; ?>
            </div>
            <a class="btn btn-secondary" href="<?php echo $this->escape($povezava); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPRI_RUNDO')); ?></a>
        </div>

        <?php if ($runda->urejanje || !empty($runda->odjava) || !empty($runda->deljiva)) : ?>
        <div class="br-card__actions d-flex gap-2 flex-wrap">
            <?php if ($runda->urejanje) : ?>
                <?php require __DIR__ . '/upravljanje.php'; ?>
            <?php elseif (!empty($runda->odjava)) : ?>
                <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>" method="post">
                    <input type="hidden" name="task" value="runda.odjava">
                    <input type="hidden" name="id" value="<?php echo $this->escape((string) $runda->id); ?>">
                    <input type="hidden" name="vrni" value="moje">
                    <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
                    <button type="submit" class="btn btn-outline-secondary"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODJAVA')); ?></button>
                </form>
            <?php endif; ?>
            <?php if (!empty($runda->deljiva)) : ?>
                <a class="btn btn-outline-primary" href="<?php echo $this->escape($runda->deli_viber); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_VIBER')); ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</article>
