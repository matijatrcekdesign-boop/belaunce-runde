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

/** @var \Belaunce\Component\Belauncerunde\Administrator\View\Runda\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('form.validate');
$wa->useScript('keepalive');
?>
<form action="<?php echo Route::_('index.php?option=com_belauncerunde&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="runda-form" class="form-validate">
    <div class="main-card">
        <?php echo $this->form->renderField('naslov'); ?>
        <?php echo $this->form->renderField('alias'); ?>
        <?php echo $this->form->renderField('tip_id'); ?>
        <?php echo $this->form->renderField('tezavnost_id'); ?>
        <?php echo $this->form->renderField('lokacija'); ?>
        <?php echo $this->form->renderField('zacetek'); ?>
        <?php echo $this->form->renderField('dolzina_km'); ?>
        <?php echo $this->form->renderField('trajanje_min'); ?>
        <?php echo $this->form->renderField('opombe'); ?>
        <?php echo $this->form->renderField('trasa_url'); ?>
        <?php echo $this->form->renderField('odprto_za_goste'); ?>
        <?php echo $this->form->renderField('stanje'); ?>
        <?php echo $this->form->renderField('vodja_id'); ?>
    </div>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
