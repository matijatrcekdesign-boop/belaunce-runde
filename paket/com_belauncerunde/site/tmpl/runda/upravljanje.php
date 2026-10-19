<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

// Delni prikaz se vključi samo za vodjo odprte runde, na strani runde in seznamu.
// ID modala je za vsako rundo drugačen, da je potrjevanje pravilno tudi na seznamu.
$modalId = 'br-odpoved-' . (int) $runda->id;
?>
<a class="btn btn-outline-primary" href="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde&view=obrazec&id=' . (int) $runda->id, false)); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_UREDI_RUNDO')); ?></a>
<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#<?php echo $this->escape($modalId); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEJ_RUNDO')); ?></button>
<div class="modal fade" id="<?php echo $this->escape($modalId); ?>" tabindex="-1" aria-labelledby="<?php echo $this->escape($modalId . '-naslov'); ?>" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5" id="<?php echo $this->escape($modalId . '-naslov'); ?>"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEJ_RUNDO')); ?></h2></div>
        <div class="modal-body"><p><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVED_POTRDITEV')); ?></p></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_OBRAZEC_PREKLICI')); ?></button>
            <form action="<?php echo $this->escape(Route::_('index.php?option=com_belauncerunde', false)); ?>" method="post">
                <input type="hidden" name="task" value="runda.odpovej">
                <input type="hidden" name="id" value="<?php echo $this->escape((string) $runda->id); ?>">
                <?php echo HTMLHelper::_('form.token'); ?>
                <button type="submit" class="btn btn-danger"><?php echo $this->escape(Text::_('COM_BELAUNCERUNDE_ODPOVEJ_RUNDO')); ?></button>
            </form>
        </div>
    </div></div>
</div>
