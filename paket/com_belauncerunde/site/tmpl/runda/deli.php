<?php

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;
$besediloId = 'br-deli-besedilo-' . (int) $this->runda->id;
?>
<section class="br-deli mb-4">
    <h2 class="br-sec-title"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_NASLOV')); ?></h2>
    <div class="d-flex gap-2 flex-wrap mb-3">
        <a class="btn btn-secondary" href="<?php echo $this->escape($this->deliViber); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_VIBER')); ?></a>
        <button type="button" class="btn btn-outline-secondary" hidden data-br-deli="sistem" data-br-deli-cilj="<?php echo $this->escape($besediloId); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_SISTEM')); ?></button>
    </div>
    <label class="form-label" for="<?php echo $this->escape($besediloId); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_BESEDILO')); ?></label>
    <?php // Prvi LF za začetno oznako HTML zavrže; dodani LF ohrani tudi začetni prelom predloge. ?>
    <textarea readonly rows="7" class="form-control mb-3" id="<?php echo $this->escape($besediloId); ?>">
<?php echo $this->escape($this->deliBesedilo); ?></textarea>
    <button type="button" class="btn btn-outline-secondary" hidden data-br-deli="kopiraj" data-br-deli-cilj="<?php echo $this->escape($besediloId); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_DELI_KOPIRAJ')); ?></button>
    <p class="small" role="status" aria-live="polite"></p>
</section>
