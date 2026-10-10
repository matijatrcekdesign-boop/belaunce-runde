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

/** @var \Belaunce\Component\Belauncerunde\Administrator\View\Preverjanje\HtmlView $this */

$optionsUrl = Route::_('index.php?option=com_config&view=component&component=com_belauncerunde');
?>
<div class="row">
    <div class="col-lg-8">
        <div id="j-main-container" class="j-main-container">
            <?php if (!$this->acymNamescen) : ?>
                <div class="alert alert-warning">
                    <?php echo Text::_('COM_BELAUNCERUNDE_PREVERJANJE_ACYM_NI_NAMESCEN'); ?>
                </div>
            <?php endif; ?>

            <?php if ($this->listaClanov <= 0) : ?>
                <div class="alert alert-warning">
                    <?php echo Text::sprintf('COM_BELAUNCERUNDE_PREVERJANJE_LISTA_NI_NASTAVLJENA', $this->escape($optionsUrl)); ?>
                </div>
            <?php endif; ?>

            <p>
                <?php echo Text::sprintf('COM_BELAUNCERUNDE_PREVERJANJE_STEVILO_CLANOV', (int) $this->steviloClanov); ?>
            </p>

            <?php if ($this->rezultat !== null) : ?>
                <div class="alert <?php echo ($this->rezultat['koda'] ?? '') === 'CLAN' ? 'alert-success' : 'alert-info'; ?>">
                    <h2 class="h4"><?php echo Text::_('COM_BELAUNCERUNDE_PREVERJANJE_REZULTAT'); ?></h2>
                    <p>
                        <strong><?php echo $this->escape((string) ($this->rezultat['email'] ?? '')); ?></strong>:
                        <?php echo Text::_('COM_BELAUNCERUNDE_PREVERJANJE_KODA_' . (string) ($this->rezultat['koda'] ?? 'NI_NAROCNIK')); ?>
                    </p>
                    <?php if (($this->rezultat['koda'] ?? '') === 'CLAN' && !empty($this->rezultat['clan'])) :
                        $clan = $this->rezultat['clan'];
                        ?>
                        <dl class="row mb-0">
                            <dt class="col-sm-4"><?php echo Text::_('COM_BELAUNCERUNDE_PREVERJANJE_ACYM_ID'); ?></dt>
                            <dd class="col-sm-8"><?php echo (int) $clan->acym_id; ?></dd>
                            <dt class="col-sm-4"><?php echo Text::_('COM_BELAUNCERUNDE_PREVERJANJE_IME'); ?></dt>
                            <dd class="col-sm-8"><?php echo $this->escape((string) $clan->ime); ?></dd>
                            <dt class="col-sm-4"><?php echo Text::_('COM_BELAUNCERUNDE_PREVERJANJE_CMS_ID'); ?></dt>
                            <dd class="col-sm-8">
                                <?php echo ((int) $clan->cms_id > 0) ? Text::_('JYES') : Text::_('JNO'); ?>
                            </dd>
                        </dl>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo Route::_('index.php?option=com_belauncerunde&task=preverjanje.preveri'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
                <?php echo $this->form->renderField('email'); ?>
                <button type="submit" class="btn btn-primary">
                    <?php echo Text::_('COM_BELAUNCERUNDE_PREVERJANJE_GUMB'); ?>
                </button>
                <input type="hidden" name="task" value="preverjanje.preveri">
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>
