<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Belaunce\Component\Belauncerunde\Administrator\View\Tip\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('form.validate');
$wa->useScript('keepalive');
$this->useCoreUI = true;
?>
<form action="<?php echo Route::_('index.php?option=com_belauncerunde&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="tip-form" aria-label="<?php echo Text::_('COM_BELAUNCERUNDE_TIP_FORM_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="main-card form-validate">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_BELAUNCERUNDE_FIELDSET_DETAILS')); ?>
    <div class="form-grid">
        <?php echo $this->form->renderField('naziv'); ?>
        <?php echo $this->form->renderField('alias'); ?>
        <?php echo $this->form->renderField('opis'); ?>
        <?php echo $this->form->renderField('stanje'); ?>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <?php echo $this->form->renderControlFields(); ?>
</form>
