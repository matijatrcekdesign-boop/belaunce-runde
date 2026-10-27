<?php

namespace Belaunce\Component\Belauncerunde\Site\Helper;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/** Pripne skupne oznake in skripto za namestitev PWA. */
class PwaHelper
{
    /**
     * Poveže manifest, ikono in service worker samo na straneh komponente.
     *
     * @param   Document  $document  Dokument trenutnega HTML pogleda.
     * @return  void
     */
    public static function pripni(Document $document): void
    {
        $document->addHeadLink(
            Uri::root(true) . '/media/com_belauncerunde/manifest.webmanifest',
            'manifest'
        );
        $document->setMetaData('theme-color', '#2a76e0');
        $document->setMetaData('apple-mobile-web-app-capable', 'yes');
        $document->setMetaData('apple-mobile-web-app-title', 'Runde');
        $document->addHeadLink(
            Uri::root(true) . '/media/com_belauncerunde/images/apple-touch-icon.png',
            'apple-touch-icon'
        );
        // Pot do kontrolerja preda Joomlin JSON možnosti; JS je ne sestavlja sam.
        $document->addScriptOptions('com_belauncerunde.pwa', [
            'swUrl' => Uri::root() . 'index.php?option=com_belauncerunde&task=pwa.sw',
        ]);
        $document->getWebAssetManager()->useScript('com_belauncerunde.namesti');
    }
}
