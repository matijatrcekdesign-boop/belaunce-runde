<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Belaunce\Component\Belauncerunde\Administrator\Service\ZetonService;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** Pripravi veljavnost povezave in informativni naslov runde brez porabe žetona. */
class PovezavaModel extends BaseDatabaseModel
{
    /**
     * Vrne podatke za prikaz; ne ustvari uporabnika, seje ali prijave na rundo.
     *
     * @param  string  $surovi
     * @return  ?object
     */
    public function podatkiPovezave(string $surovi): ?object
    {
        try {
            $db = $this->getDatabase();
            $zapis = (new ZetonService($db))->preveri($surovi);

            if (!$zapis) {
                return null;
            }

            $naslov = '';

            // Runda je informativna tudi, če je že odpovedana ali je štartala (naloga 06).
            if ($zapis->runda_id !== null) {
                $rundaId = (int) $zapis->runda_id;
                $poizvedba = $db->createQuery()->select($db->quoteName('naslov'))
                    ->from($db->quoteName('#__belaunce_runde'))
                    ->where($db->quoteName('id') . ' = :runda')
                    ->bind(':runda', $rundaId, ParameterType::INTEGER);
                $naslov = (string) $db->setQuery($poizvedba)->loadResult();
            }

            return (object) ['naslov' => $naslov];
        } catch (\Throwable $napaka) {
            Log::add('POVEZAVA_PRIKAZ_NAPAKA', Log::ERROR, 'com_belauncerunde');

            return null;
        }
    }
}
