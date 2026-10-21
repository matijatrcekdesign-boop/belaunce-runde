<?php

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Belaunce\Component\Belauncerunde\Administrator\Helper\OmejitevHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/** Izvaja tri neodvisne dele dnevnega vzdrževanja rund. */
class VzdrzevanjeService
{
    /** @var DatabaseInterface Povezava do baze. */
    private DatabaseInterface $db;

    /** @var Registry Možnosti komponente. */
    private Registry $params;

    /** Pripravi odvisnosti tudi za zagon brez prijavljenega uporabnika. */
    public function __construct(?DatabaseInterface $db = null, ?Registry $params = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
        $this->params = $params ?: ComponentHelper::getParams('com_belauncerunde');
    }

    /** Uskladi povezane uporabnike s trenutno AcyMailing listo članov. */
    public function sinhronizacijaClanov(): void
    {
        $listaId = (int) $this->params->get('lista_clanov', 0);
        $skupinaId = (int) $this->params->get('skupina_clani', 0);
        $adapter = new AcymailingAdapter($this->db);

        // Prazna ali neobstoječa lista oziroma manjkajoč AcyMailing ne smeta pomeniti izstopa vseh članov.
        if ($listaId <= 0 || $skupinaId <= 0 || !$adapter->jeNamescen()) {
            Log::add('SINHRONIZACIJA_PRESKOCENA', Log::WARNING, 'com_belauncerunde');

            return;
        }

        $liste = $adapter->seznamList();

        if (!isset($liste[$listaId])) {
            Log::add('SINHRONIZACIJA_LISTA_NI_NAJDENA', Log::WARNING, 'com_belauncerunde');

            return;
        }

        // Strogo branje ob napaki vrže izjemo; prazen seznam je tedaj res prazen.
        $trenutni = array_fill_keys($adapter->idjiClanov($listaId, true), true);
        $db = $this->db;
        $poizvedba = $db->createQuery()->select($db->quoteName(['uporabnik_id', 'acym_uporabnik_id']))
            ->from($db->quoteName('#__belaunce_clani'));
        $povezave = $db->setQuery($poizvedba)->loadObjectList();
        $clani = new ClanService($db, $this->params);

        foreach ($povezave as $povezava) {
            $uporabnikId = (int) $povezava->uporabnik_id;
            $najBoClan = isset($trenutni[(int) $povezava->acym_uporabnik_id]);

            try {
                $rezultat = $clani->uskladiSkupino($uporabnikId, $najBoClan);

                if ($rezultat === 'VRNJENA' || $rezultat === 'ODVZETA') {
                    Log::add('SKUPINA_' . $rezultat . ' uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');
                } elseif ($rezultat === 'PRESKOCENO_BLOKIRAN') {
                    Log::add('PRESKOCENO_BLOKIRAN uporabnik_id=' . $uporabnikId, Log::INFO, 'com_belauncerunde');
                }
            } catch (\Throwable $napaka) {
                // Ena pokvarjena povezava ne ustavi uskladitve drugih računov.
                Log::add('SINHRONIZACIJA_UPORABNIK_NAPAKA uporabnik_id=' . $uporabnikId, Log::ERROR, 'com_belauncerunde');
            }
        }
    }

    /** Izbriše goste 30 dni po začetku runde, ne glede na njeno stanje. */
    public function izbrisGostov(): int
    {
        $db = $this->db;
        $stareRunde = $db->createQuery()->select($db->quoteName('id'))
            ->from($db->quoteName('#__belaunce_runde'))
            ->where('DATE_ADD(' . $db->quoteName('zacetek') . ', INTERVAL 30 DAY) < UTC_TIMESTAMP()');
        $poizvedba = $db->createQuery()->delete($db->quoteName('#__belaunce_gostje'))
            ->where($db->quoteName('runda_id') . ' IN (' . $stareRunde . ')');
        $db->setQuery($poizvedba)->execute();
        $izbrisanih = $db->getAffectedRows();
        Log::add('GOSTJE_IZBRIS izbrisanih=' . $izbrisanih, Log::INFO, 'com_belauncerunde');

        return $izbrisanih;
    }

    /** Uporabi obstoječa roka za žetone in zapise poskusov. */
    public function ciscenje(): void
    {
        $zetoni = (new ZetonService($this->db, $this->params))->pociscenje();
        Log::add('ZETONI_CISCENJE izbrisanih=' . $zetoni, Log::INFO, 'com_belauncerunde');

        $poskusi = (new OmejitevHelper($this->db))->pociscenje();
        Log::add('POSKUSI_CISCENJE izbrisanih=' . $poskusi, Log::INFO, 'com_belauncerunde');
    }
}
