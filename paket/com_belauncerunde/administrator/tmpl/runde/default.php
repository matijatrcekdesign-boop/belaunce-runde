<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Belaunce\Component\Belauncerunde\Administrator\View\Runde\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user      = $this->getCurrentUser();
$userId    = (int) $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$params    = ComponentHelper::getParams('com_belauncerunde');
$privzetoTrajanje = (int) $params->get('privzeto_trajanje_min', 180);
$zdajUtc = new DateTimeImmutable(CasHelper::zdajUtc(), new DateTimeZone('UTC'));
?>
<form action="<?php echo Route::_('index.php?option=com_belauncerunde&view=runde'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="rundeList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_BELAUNCERUNDE_TABLE_CAPTION_RUNDE'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_BELAUNCERUNDE_HEADING_ZACETEK', 'a.zacetek', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_BELAUNCERUNDE_HEADING_NASLOV', 'a.naslov', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_BELAUNCERUNDE_FIELD_TIP_LABEL'); ?>
                                </th>
                                <th scope="col" class="d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_BELAUNCERUNDE_FIELD_TEZAVNOST_LABEL'); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo Text::_('COM_BELAUNCERUNDE_HEADING_DOLZINA'); ?>
                                </th>
                                <th scope="col" class="d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_BELAUNCERUNDE_FIELD_VODJA_LABEL'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.stanje', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) :
                            $canEdit    = $user->authorise('core.edit', 'com_belauncerunde');
                            $canCheckin = $user->authorise('core.manage', 'com_checkin') || (int) $item->checked_out === $userId || empty($item->checked_out);
                            $zacetekLokalno = CasHelper::izUtc((string) $item->zacetek);
                            $konecUtc = (new DateTimeImmutable((string) $item->zacetek, new DateTimeZone('UTC')))
                                ->modify('+' . ((int) ($item->trajanje_min ?: $privzetoTrajanje)) . ' minutes');
                            $pretekla = $konecUtc < $zdajUtc;
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->naslov); ?>
                                </td>
                                <td>
                                    <?php echo $this->escape((new DateTimeImmutable($zacetekLokalno))->format('j. n. Y H:i')); ?>
                                </td>
                                <th scope="row" class="has-context">
                                    <div>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'runde.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_belauncerunde&task=runda.edit&id=' . (int) $item->id); ?>">
                                                <?php echo $this->escape($item->naslov); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->naslov); ?>
                                        <?php endif; ?>
                                        <div class="small">
                                            <?php echo $this->escape($item->lokacija); ?>
                                        </div>
                                    </div>
                                </th>
                                <td class="d-none d-lg-table-cell">
                                    <?php echo $this->escape($item->tip_naziv); ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php echo $this->escape($item->tezavnost_naziv); ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo number_format((float) $item->dolzina_km, 1, ',', '.'); ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php echo $item->vodja_ime === null ? Text::_('COM_BELAUNCERUNDE_VODJA_NI_UPORABNIKA') : $this->escape($item->vodja_ime); ?>
                                </td>
                                <td>
                                    <?php if ((int) $item->stanje === 2) : ?>
                                        <span class="badge bg-danger"><?php echo Text::_('COM_BELAUNCERUNDE_STANJE_ODPOVEDANA'); ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-success"><?php echo Text::_('COM_BELAUNCERUNDE_STANJE_OBJAVLJENA'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($pretekla) : ?>
                                        <span class="badge bg-secondary"><?php echo Text::_('COM_BELAUNCERUNDE_STANJE_PRETEKLA'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
                <?php echo $this->filterForm->renderControlFields(); ?>
            </div>
        </div>
    </div>
</form>
