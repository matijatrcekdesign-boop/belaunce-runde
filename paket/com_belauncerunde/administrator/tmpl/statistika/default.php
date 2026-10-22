<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Belaunce\Component\Belauncerunde\Administrator\View\Statistika\HtmlView $this */

// Števila so iz SQL agregatov; prazno povprečje ostane brez vrednosti.
$stevilo = static fn ($vrednost): string => number_format((float) ($vrednost ?? 0), 0, ',', '.');
$decimalno = static fn ($vrednost): string => $vrednost === null
    ? Text::_('COM_BELAUNCERUNDE_STATISTIKA_BREZ_VREDNOSTI') : number_format((float) $vrednost, 1, ',', '.');
$dolzina = static fn ($vrednost): string => $vrednost === null
    ? Text::_('COM_BELAUNCERUNDE_STATISTIKA_BREZ_VREDNOSTI')
    : Text::sprintf('COM_BELAUNCERUNDE_DOLZINA_KM', $decimalno($vrednost));
?>
<form action="<?php echo Route::_('index.php?option=com_belauncerunde&view=statistika'); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <h2><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_POVZETEK'); ?></h2>
        <table class="table table-striped">
            <caption class="visually-hidden"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_POVZETEK'); ?></caption>
            <tbody>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_STEVILO_RUND'); ?></th><td><?php echo $stevilo($this->povzetek->stevilo_rund); ?></td></tr>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_OBJAVLJENE'); ?></th><td><?php echo $stevilo($this->povzetek->objavljene); ?></td></tr>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_ODPOVEDANE'); ?></th><td><?php echo $stevilo($this->povzetek->odpovedane); ?></td></tr>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_SKUPNA_DOLZINA'); ?></th><td><?php echo $dolzina($this->povzetek->skupna_dolzina ?? 0); ?></td></tr>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_POVPRECNA_DOLZINA'); ?></th><td><?php echo $dolzina($this->povzetek->povprecna_dolzina); ?></td></tr>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_POVPRECNO_TRAJANJE'); ?></th><td><?php echo $decimalno($this->povzetek->povprecno_trajanje); ?></td></tr>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_SKUPNO_UDELEZENCEV'); ?></th><td><?php echo $stevilo($this->povzetek->skupno_udelezencev); ?></td></tr>
                <tr><th scope="row"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_POVPRECNO_UDELEZENCEV'); ?></th><td><?php echo $decimalno($this->povzetek->povprecno_udelezencev); ?></td></tr>
            </tbody>
        </table>

        <?php foreach ([['COM_BELAUNCERUNDE_STATISTIKA_PO_TIPU', $this->tipi], ['COM_BELAUNCERUNDE_STATISTIKA_PO_TEZAVNOSTI', $this->tezavnosti]] as [$naslov, $vrstice]) : ?>
            <h2><?php echo Text::_($naslov); ?></h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <caption class="visually-hidden"><?php echo Text::_($naslov); ?></caption>
                    <thead><tr>
                        <th scope="col"><?php echo Text::_('COM_BELAUNCERUNDE_HEADING_NAZIV'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_STEVILO_RUND'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_POVPRECNA_DOLZINA'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_BELAUNCERUNDE_STATISTIKA_POVPRECNO_UDELEZENCEV'); ?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($vrstice as $vrstica) : ?>
                            <tr>
                                <th scope="row"><?php echo $this->escape($vrstica->naziv); ?></th>
                                <td><?php echo $stevilo($vrstica->stevilo_rund); ?></td>
                                <td><?php echo $dolzina($vrstica->povprecna_dolzina); ?></td>
                                <td><?php echo $decimalno($vrstica->povprecno_udelezencev); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        <?php echo $this->filterForm->renderControlFields(); ?>
    </div>
</form>
