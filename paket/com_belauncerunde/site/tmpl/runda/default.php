<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Belaunce\Component\Belauncerunde\Site\Helper\VidezHelper;

\defined('_JEXEC') or die;
$runda = $this->runda;
?>
<?php
// Podstran uporablja isti preverjeni barvi kot kartice tega tipa.
$barvaTipa = VidezHelper::barvaTipa($runda->tip_barva ?? null);
$kontrastTipa = VidezHelper::kontrastBesedila($barvaTipa);
$mobilnaUdelezba = $this->odprta && !$this->vodja && ($this->clan || $this->neprijavljen);
?>
<article class="br-runda<?php echo $mobilnaUdelezba ? ' br-runda--mobile-action' : ''; ?>" style="--br-type: <?php echo $this->escape($barvaTipa); ?>; --br-type-text: <?php echo $this->escape($kontrastTipa); ?>">
    <div class="br-runda__badges">
        <span class="br-tag"><?php echo $this->escape((string) ($runda->tip_naziv ?: Text::_('COM_BELAUNCERUNDE_NI_PODATKA'))); ?></span>
        <?php require __DIR__ . '/tezavnost.php'; ?>
    </div>
    <h1><?php echo $this->escape($runda->naslov); ?></h1>
    <?php if ($mobilnaUdelezba) : ?>
        <div class="br-sticky d-md-none">
            <div class="br-sticky__info">
                <span><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVLJENI')); ?></span>
                <strong><?php echo $this->escape((string) $this->steviloUdelezencev); ?></strong>
            </div>
            <?php if ($this->clan) : ?>
                <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>" method="post">
                    <input type="hidden" name="task" value="<?php echo $this->escape($this->udelezen ? 'runda.odjava' : 'runda.pridem'); ?>">
                    <input type="hidden" name="id" value="<?php echo $this->escape((string) $runda->id); ?>">
                    <?php echo HTMLHelper::_('form.token'); ?>
                    <button type="submit" class="btn <?php echo $this->escape($this->udelezen ? 'btn-outline-secondary' : 'btn-secondary'); ?>"><?php echo $this->escape(Text::_($this->udelezen ? 'COM_BELAUNCERUNDE_ODJAVA' : 'COM_BELAUNCERUNDE_PRIDEM_NA_RUNDO')); ?></button>
                </form>
            <?php else : ?>
                <a class="btn btn-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=prijava&runda_id=' . (int) $runda->id, false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIDEM_NA_RUNDO')); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="d-flex gap-2 flex-wrap mb-3">
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
    <div class="br-banner">
        <svg class="br-banner__ico" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="4.5" width="17" height="16" rx="3"></rect><path d="M3.5 9h17M8 3v3M16 3v3"></path></svg>
        <div class="br-banner__txt">
            <strong><?php echo $this->escape($this->zacetek); ?></strong>
            <span><?php echo $this->escape($runda->lokacija); ?></span>
        </div>
    </div>
    <?php if ($this->trasa !== '') : ?>
        <div class="br-mapa">
            <?php if ($this->vdelava !== null) : ?>
                <div class="ratio br-mapa__ratio">
                    <iframe src="<?php echo $this->escape(Route::_($this->vdelava['src'], false)); ?>" loading="lazy" referrerpolicy="no-referrer" title="<?php echo $this->escape(Text::_($this->vdelava['naslov'])); ?>" allowfullscreen></iframe>
                </div>
            <?php else : ?>
                <div class="br-mapa__slika" aria-hidden="true">
                    <svg viewBox="0 0 354 150" preserveAspectRatio="none" fill="none"><path d="M18 120 C 70 60, 110 140, 160 90 S 260 30, 336 70" stroke="#6ab7e3" stroke-width="3.5" stroke-linecap="round"></path><circle cx="18" cy="120" r="6" fill="#212125"></circle><circle cx="336" cy="70" r="6" fill="#f03e0d"></circle></svg>
                </div>
            <?php endif; ?>
            <a class="br-mapa__link" tabindex="-1" aria-hidden="true" href="<?php echo $this->escape(Route::_($this->trasa, false)); ?>" rel="noopener noreferrer" target="_blank"></a>
            <a class="br-mapa__gumb" href="<?php echo $this->escape(Route::_($this->trasa, false)); ?>" rel="noopener noreferrer" target="_blank"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPRI_POT')); ?></a>
        </div>
    <?php endif; ?>
    <div class="br-stat-grid">
        <div class="br-stat"><span class="v"><?php echo $this->escape(VidezHelper::dolzina($runda->dolzina_km)); ?></span><span class="l">km</span></div>
        <div class="br-stat"><span class="v"><?php echo $runda->trajanje_min === null ? '–' : $this->escape(Text::sprintf('COM_BELAUNCERUNDE_TRAJANJE_MIN', $runda->trajanje_min)); ?></span><span class="l"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_TRAJANJE')); ?></span></div>
        <div class="br-stat"><span class="v"><?php echo $this->escape((string) $this->steviloUdelezencev); ?></span><span class="l"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIJAVLJENI')); ?></span></div>
    </div>
    <div class="br-vodja">
        <span class="br-leader__avatar" aria-hidden="true"><?php echo $this->escape(VidezHelper::zacetnicaVodje($runda->vodja_ime ?? null)); ?></span>
        <div><small><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_VODJA_RUNDE')); ?></small><strong><?php echo $this->escape((string) ($runda->vodja_ime ?? '')); ?></strong></div>
    </div>
    <?php if ((string) ($runda->opombe ?? '') !== '') : ?>
        <div class="br-opombe-kartica">
            <h2 class="br-sec-title"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_OPOMBE')); ?></h2>
            <p class="br-opombe br-prose"><?php echo $this->escape($runda->opombe); ?></p>
        </div>
    <?php endif; ?>
    <?php if ($this->clan && $this->udelezenci !== []) : ?>
        <h2 class="br-sec-title"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_UDELEZENCI')); ?></h2>
    <?php endif; ?>
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
        <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>" method="post" class="br-runda__udelezba d-none d-md-block mb-3">
            <input type="hidden" name="task" value="<?php echo $this->escape($this->udelezen ? 'runda.odjava' : 'runda.pridem'); ?>">
            <input type="hidden" name="id" value="<?php echo $this->escape((string) $runda->id); ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn <?php echo $this->escape($this->udelezen ? 'btn-outline-secondary' : 'btn-secondary'); ?>"><?php echo $this->escape(Text::_($this->udelezen ? 'COM_BELAUNCERUNDE_ODJAVA' : 'COM_BELAUNCERUNDE_PRIDEM')); ?></button>
        </form>
    <?php elseif ($this->odprta && $this->neprijavljen) : ?>
        <p class="br-runda__udelezba d-none d-md-block"><a class="btn btn-secondary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=prijava&runda_id=' . (int) $runda->id, false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_PRIDEM')); ?></a></p>
    <?php endif; ?>
    <?php if ($this->gostObrazec !== null) : ?>
        <?php require __DIR__ . '/gost.php'; ?>
    <?php endif; ?>
    <?php if ($this->deljiva) : ?>
        <?php require __DIR__ . '/deli.php'; ?>
    <?php endif; ?>
    <?php if (!$this->clan) : ?>
        <div class="br-clanstvo">
            <p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POZIV_CLANSTVO')); ?></p>
            <?php if ($this->povezavaClanstva !== null) : ?>
                <a class="btn btn-secondary btn-sm" href="<?php echo $this->escape($this->povezavaClanstva['href']); ?>"<?php if ($this->povezavaClanstva['zunanja']) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_POSTANI_CLAN')); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</article>
