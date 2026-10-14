<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Uri\Uri;
use PHPMailer\PHPMailer\Exception as phpmailerException;

\defined('_JEXEC') or die;

/** Obrazec in validacija zahteve za prijavno povezavo. */
class PrijavaModel extends FormModel
{
    /** Naloži obrazec z email filtrom in skritim ID runde. */
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_belauncerunde.prijava', 'prijava', ['control' => 'jform', 'load_data' => $loadData]);
    }

    /** Za začetni GET prenese samo nepredznačen ID runde, emaila ne hrani v seji. */
    protected function loadFormData()
    {
        return ['runda_id' => Factory::getApplication()->getInput()->getUint('runda_id', 0)];
    }

    /** Validira na strežniku, ne da bi napake obrazca spreminjale enoten odgovor. */
    public function preveriVnos(array $podatki): bool
    {
        return $this->validate($this->getForm([], false), $podatki) !== false;
    }

    /** Pošle Joomlino predlogo za pošto, ne da bi napaka razkrila obstoj člana. */
    public function posljiPovezavo(object $clan, string $surovi): void
    {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_belauncerunde');

        try {
            $predloga = new MailTemplate('com_belauncerunde.prijava', $app->getLanguage()->getTag());
            $predloga->addTemplateData([
                'IME' => (string) $clan->ime,
                'POVEZAVA' => Uri::root() . 'index.php?option=com_belauncerunde&view=povezava&zeton=' . $surovi,
                'VELJAVNOST' => (int) $params->get('veljavnost_min', 15),
                'SITENAME' => (string) $app->get('sitename'),
            ]);
            // Admin lahko predlogo spremeni v HTML; osebne vrednosti naj tudi takrat ostanejo ubežane.
            $predloga->addUnsafeTags(['IME', 'POVEZAVA', 'SITENAME']);
            $predloga->addRecipient((string) $clan->email, (string) $clan->ime);

            if (!$predloga->send()) {
                Log::add('MAIL_PRIJAVA_NI_POSLAN', Log::WARNING, 'com_belauncerunde');
            }
        } catch (MailDisabledException | phpmailerException $napaka) {
            Log::add('MAIL_PRIJAVA_NAPAKA', Log::ERROR, 'com_belauncerunde');
        }
    }
}
