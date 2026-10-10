<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\View\Preverjanje;

use Belaunce\Component\Belauncerunde\Administrator\Service\AcymailingAdapter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

/**
 * Pogled za ročno preverjanje AcyMailing članstva.
 *
 * @since  0.3.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var Form Obrazec za email. */
    public $form;

    /** @var array|null Rezultat zadnjega preverjanja. */
    public ?array $rezultat = null;

    /** @var bool Ali so AcyMailing tabele na voljo. */
    public bool $acymNamescen = false;

    /** @var int Nastavljena lista članov. */
    public int $listaClanov = 0;

    /** @var int Število trenutnih članov nastavljene liste. */
    public int $steviloClanov = 0;

    /**
     * Prikaže diagnostiko.
     *
     * @param   string|null  $tpl  Predloga.
     *
     * @return  void
     *
     * @since   0.3.0
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();

        if (!$app->getIdentity()->authorise('core.admin', 'com_belauncerunde')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $model = $this->getModel();
        $this->form = $model->getForm();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $params = ComponentHelper::getParams('com_belauncerunde');
        $adapter = new AcymailingAdapter();

        $this->listaClanov  = (int) $params->get('lista_clanov', 0);
        $this->acymNamescen = $adapter->jeNamescen();
        $this->steviloClanov = $model->prestejClane();
        $this->rezultat = $app->getSession()->get('com_belauncerunde.preverjanje.rezultat');
        $app->getSession()->set('com_belauncerunde.preverjanje.rezultat', null);

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Doda orodno vrstico.
     *
     * @return  void
     *
     * @since   0.3.0
     */
    protected function addToolbar(): void
    {
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_BELAUNCERUNDE_PREVERJANJE'), 'search');

        if ($user->authorise('core.admin', 'com_belauncerunde') || $user->authorise('core.options', 'com_belauncerunde')) {
            ToolbarHelper::preferences('com_belauncerunde');
        }
    }
}
