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
use Joomla\CMS\Router\Route;

/** @var \Belaunce\Component\Belauncerunde\Administrator\View\Tip\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('form.validate');
$wa->useScript('keepalive');
?>
<form action="<?php echo Route::_('index.php?option=com_belauncerunde&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="tip-form" class="form-validate">
    <div class="main-card">
        <?php echo $this->form->renderField('naziv'); ?>
        <?php echo $this->form->renderField('alias'); ?>
        <?php echo $this->form->renderField('opis'); ?>
        <?php echo $this->form->renderField('stanje'); ?>
    </div>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
