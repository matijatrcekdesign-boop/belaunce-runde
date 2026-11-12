<?php

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;
$besediloId = 'br-deli-besedilo-' . (int) $this->runda->id;
?>
<section class="br-deli">
    <h2 class="visually-hidden"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_NASLOV')); ?></h2>
    <div class="br-deli__vrsta">
        <a class="br-deli__gumb" href="<?php echo $this->escape($this->deliViber); ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="#7360f2" aria-hidden="true"><path d="M12 2C6.9 2 3 5.5 3 10c0 2.5 1.3 4.7 3.3 6.1-.1 1-.6 2.4-1.3 3.4-.2.3.1.7.5.6 1.9-.5 3.3-1.4 4-2 .8.1 1.6.2 2.5.2 5.1 0 9-3.5 9-8s-3.9-8-9-8z"></path></svg><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_VIBER_KRATKO')); ?></a>
        <button type="button" class="br-deli__gumb" hidden data-br-deli="sistem" data-br-deli-cilj="<?php echo $this->escape($besediloId); ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="2.6"></circle><circle cx="6" cy="12" r="2.6"></circle><circle cx="18" cy="19" r="2.6"></circle><path d="M8.3 10.7l7.4-4.4M8.3 13.3l7.4 4.4"></path></svg><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_SISTEM_KRATKO')); ?></button>
        <button type="button" class="br-deli__gumb" hidden data-br-deli="kopiraj" data-br-deli-cilj="<?php echo $this->escape($besediloId); ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5"></rect><path d="M6 15H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1"></path></svg><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_KOPIRAJ_KRATKO')); ?></button>
    </div>
    <label class="visually-hidden" for="<?php echo $this->escape($besediloId); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_BESEDILO')); ?></label>
    <?php // Prvi LF za začetno oznako HTML zavrže; dodani LF ohrani tudi začetni prelom predloge. ?>
    <textarea readonly rows="7" class="visually-hidden" id="<?php echo $this->escape($besediloId); ?>">
<?php echo $this->escape($this->deliBesedilo); ?></textarea>
    <p class="small" role="status" aria-live="polite"></p>
</section>
