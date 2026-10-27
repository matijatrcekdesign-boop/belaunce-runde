<?php

namespace Belaunce\Component\Belauncerunde\Site\View\Obrazec;

use Belaunce\Component\Belauncerunde\Site\Helper\PwaHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/** En pogled za novo in lastno rundo, brez predpomnjenja ali tujih podatkov. */
class HtmlView extends BaseHtmlView
{
    /** @var Form Obrazec z Joomlinimi kontrolnimi polji in CSRF žetonom. */
    public Form $form;
    /** @var int ID iz zahteve za prikaz, pri zapisu se vedno prebere POST. */
    public int $rundaId = 0;
    /** @var array Varna obvestila o validaciji za izpis v komponenti. */
    public array $napake = [];
    /** @var string|null Zaprt jezikovni ključ povratnega obvestila. */
    public ?string $obvestilo = null;

    /**
     * Pred branjem vnosa preveri pravico in ob zavrnitvi preusmeri s kodo stanja.
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->rundaId = $app->getInput()->getUint('id', 0);
        $model = $this->getModel();

        try {
            $stanje = $model->dovoljenje($this->rundaId, $app->getIdentity());

            if ($stanje === '') {
                $this->form = $model->getForm();
            }
        } catch (\Throwable $napaka) {
            Log::add('OBRAZEC_NAPAKA runda_id=' . $this->rundaId, Log::ERROR, 'com_belauncerunde');
            $stanje = 'napaka_zapisa';
        }

        if ($stanje !== '') {
            $app->redirect(Route::_('index.php?option=com_belauncerunde&view=runde&stanje=' . $stanje, false));

            return;
        }

        $this->form->addControlField('task', 'runda.shrani');
        $this->form->addControlField('id', (string) $this->rundaId);
        // Po čiščenju je lahko vrnjen NULL; predloga vedno potrebuje seznam napak.
        $this->napake = (array) ($app->getUserState($model->kljucSeje($this->rundaId) . '.napake') ?: []);
        $app->setUserState($model->kljucSeje($this->rundaId) . '.napake', null);
        $koda = $app->getInput()->getCmd('stanje', '');

        if (\in_array($koda, ['neveljavno', 'napaka_zapisa'], true)) {
            $this->obvestilo = 'COM_BELAUNCERUNDE_OBRAZEC_' . strtoupper($koda);
        }

        $this->getDocument()->setTitle(Text::_($this->rundaId === 0 ? 'COM_BELAUNCERUNDE_USTVARI_RUNDO' : 'COM_BELAUNCERUNDE_UREDI_RUNDO'));
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useScript('form.validate')->useScript('keepalive')
            ->useStyle('com_belauncerunde.pisave')
            ->useStyle('com_belauncerunde.site');
        PwaHelper::pripni($this->getDocument());
        parent::display($tpl);
    }
}
