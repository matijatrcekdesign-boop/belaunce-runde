<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Belaunce\Component\Belauncerunde\Administrator\Service\HtmlMailTemplate;
use Belaunce\Component\Belauncerunde\Administrator\Service\MailPodatki;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Exception\MailDisabledException;
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

    /**
     * Validira na strežniku, ne da bi napake obrazca spreminjale enoten odgovor.
     *
     * @param  array  $podatki
     * @return  bool
     */
    public function preveriVnos(array $podatki): bool
    {
        return $this->validate($this->getForm([], false), $podatki) !== false;
    }

    /**
     * Pošle Joomlino predlogo za pošto, ne da bi napaka razkrila obstoj člana.
     *
     * @param  object  $clan
     * @param  string  $surovi
     * @return  void
     */
    public function posljiPovezavo(object $clan, string $surovi): void
    {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_belauncerunde');

        try {
            $predloga = new HtmlMailTemplate('com_belauncerunde.prijava', $app->getLanguage()->getTag());
            $veljavnost = (int) $params->get('veljavnost_min', 15);
            $predloga->addTemplateData([
                'IME' => (string) $clan->ime,
                'POVEZAVA' => Uri::root() . 'index.php?option=com_belauncerunde&view=povezava&zeton=' . $surovi,
                'VELJAVNOST' => $veljavnost,
                'SITENAME' => (string) $app->get('sitename'),
                // HTML predloga (naloga 30): absolutni naslov za logotip in predglavno besedilo.
                'SITEURL' => MailPodatki::siteUrl(),
                'PREDGLAVA' => Text::sprintf('COM_BELAUNCERUNDE_MAIL_PREDGLAVA_PRIJAVA', $veljavnost),
            ]);
            // Admin lahko predlogo spremeni v HTML; osebne vrednosti naj tudi takrat ostanejo ubežane.
            $predloga->addUnsafeTags(['IME', 'POVEZAVA', 'SITENAME', 'SITEURL', 'PREDGLAVA']);
            $predloga->addRecipient((string) $clan->email, (string) $clan->ime);

            if (!$predloga->send()) {
                Log::add('MAIL_PRIJAVA_NI_POSLAN', Log::WARNING, 'com_belauncerunde');
            }
        } catch (MailDisabledException | phpmailerException $napaka) {
            Log::add('MAIL_PRIJAVA_NAPAKA', Log::ERROR, 'com_belauncerunde');
        }
    }
}
