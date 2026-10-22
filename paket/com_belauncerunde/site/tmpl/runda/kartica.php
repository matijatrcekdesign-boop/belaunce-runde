<?php

use Belaunce\Component\Belauncerunde\Site\Helper\VidezHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

// Razred je iz zaprtega seznama aliasov; predloga nikoli ne izpiše CSS iz baze.
$razredTipa = VidezHelper::razredTipa($runda->tip_alias ?? null);
$povezava = $runda->povezava ?? Route::_('index.php?option=com_belauncerunde&view=runda&id=' . (int) $runda->id, false);
$tipOznaka = (string) ($runda->tip_naziv ?? '');
$tipOznaka = $tipOznaka !== '' ? $tipOznaka : Text::_('COM_BELAUNCERUNDE_NI_PODATKA');
?>
<article class="br-card h-100 <?php echo $this->escape($razredTipa); ?>">
    <div class="br-card__band">
        <span class="br-tag"><?php echo $this->escape($tipOznaka); ?></span>
        <?php if ((int) $runda->stanje === 2) : ?>
            <span class="br-card__cancelled"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEDANO')); ?></span>
        <?php endif; ?>
    </div>
    <div class="br-card__body">
        <<?php echo $naslovNivo; ?> class="br-card__title"><a href="<?php echo $this->escape($povezava); ?>"><?php echo $this->escape($runda->naslov); ?></a></<?php echo $naslovNivo; ?>>
        <p class="br-card__date"><?php echo $this->escape($runda->zacetek_prikaz); ?></p>
        <div class="br-stats">
            <span><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DOLZINA') . ': ' . Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', VidezHelper::dolzina($runda->dolzina_km))); ?></span>
            <span><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TRAJANJE') . ': ' . ($runda->trajanje_min === null ? Text::_('COM_BELAUNCERUNDE_NI_PODATKA') : Text::sprintf('COM_BELAUNCERUNDE_TRAJANJE_MIN', $runda->trajanje_min))); ?></span>
        </div>
        <?php require __DIR__ . '/tezavnost.php'; ?>
        <p class="br-card__meta"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_LOKACIJA') . ': ' . $runda->lokacija); ?><br><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA') . ': ' . (string) ($runda->vodja_ime ?? '')); ?></p>
        <?php if (isset($runda->stevilo_udelezencev)) : ?>
            <p class="br-card__meta"><?php echo $this->escape(Text::sprintf('COM_BELAUNCERUNDE_STEVILO_UDELEZENCEV', $runda->stevilo_udelezencev)); ?></p>
        <?php endif; ?>
        <?php if (isset($runda->je_vodja) && $this->pogled === 'pretekle') : ?>
            <p class="br-card__meta"><?php echo $this->escape(Text::_($runda->je_vodja ? 'COM_BELAUNCERUNDE_MOJE_VODIL' : 'COM_BELAUNCERUNDE_MOJE_UDELEZEN')); ?></p>
        <?php endif; ?>
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
