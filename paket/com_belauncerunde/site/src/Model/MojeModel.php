<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/** Zasebni seznami člana; bere samo izbrani zavihek in nikoli imen udeležencev. */
class MojeModel extends BaseDatabaseModel
{
    /** Preveri članstvo tudi v modelu in vrne sezname z največ dvema poizvedbama. */
    public function podatki(string $pogled): array
    {
        $app = Factory::getApplication();
        $uporabnik = $app->getIdentity();
        $prijave = $app->bootComponent('com_belauncerunde')->getMVCFactory()->createModel('Prijave', 'Site', []);
        $podatki = ['vodim' => [], 'prijavljen' => [], 'pretekle' => [], 'paginacija' => null];

        if (!$prijave->jeClan($uporabnik)) {
            return $podatki;
        }

        $parametri = $app->getParams('com_belauncerunde');
        $pretekle = $pogled === 'pretekle' && (int) $parametri->get('prikazi_pretekle', 1) === 1;
        $baza = $this->getDatabase();

        if (!$pretekle) {
            // Ločena sklopa ne podvajata vodje niti ob stari, napačno ohranjeni prijavi.
            $podatki['vodim'] = $baza->setQuery($this->poizvedba('vodim', $prijave))->loadObjectList();
            $podatki['prijavljen'] = $baza->setQuery($this->poizvedba('prijavljen', $prijave))->loadObjectList();

            return $podatki;
        }

        // COUNT in stran sta edini poizvedbi tega zavihka; ne beremo prihajajočih seznamov.
        $poizvedba = $this->poizvedba('pretekle', $prijave);
        $stetje = clone $poizvedba;
        $stetje->clear('select')->clear('order')->select('COUNT(*)');
        $skupaj = (int) $baza->setQuery($stetje)->loadResult();
        $omejitev = max(1, (int) $parametri->get('pretekle_omejitev', 20));
        $odmik = (int) (floor($app->getInput()->getUint('limitstart', 0) / $omejitev) * $omejitev);
        // Zastarel odmik po izbrisu podatkov vrnemo na zadnjo veljavno stran brez dodatnega branja.
        $odmik = min($odmik, $skupaj > 0 ? (int) (floor(($skupaj - 1) / $omejitev) * $omejitev) : 0);
        $podatki['paginacija'] = new Pagination($skupaj, $odmik, $omejitev);

        if ($skupaj > 0) {
            $podatki['pretekle'] = $baza->setQuery($poizvedba, $odmik, $omejitev)->loadObjectList();
        }

        return $podatki;
    }

    /** Sestavi seznam z vezano identiteto, UTC koncem in skupnim izrazom števila. */
    private function poizvedba(string $sklop, PrijaveModel $prijave): QueryInterface
    {
        $app = Factory::getApplication();
        $baza = $this->getDatabase();
        $vodjaId = (int) $app->getIdentity()->id;
        $prijavljenId = $vodjaId;
        $privzetoTrajanje = (int) $app->getParams('com_belauncerunde')->get('privzeto_trajanje_min', 180);
        $pretekle = $sklop === 'pretekle';
        $poizvedba = $baza->createQuery()
            ->select($baza->quoteName(['a.id', 'a.naslov', 'a.alias', 'a.zacetek', 'a.lokacija', 'a.dolzina_km', 'a.trajanje_min', 'a.stanje', 'a.vodja_id']))
            ->select($baza->quoteName('t.naziv', 'tip_naziv'))
            ->select($baza->quoteName('tz.naziv', 'tezavnost_naziv'))
            ->select($baza->quoteName('u.name', 'vodja_ime'))
            ->select('a.zacetek <= UTC_TIMESTAMP() AS startala')
            ->select('(' . $prijave->izrazStevilaUdelezencev('a') . ') AS stevilo_udelezencev')
            ->from($baza->quoteName('#__belaunce_runde', 'a'))
            ->join('LEFT', $baza->quoteName('#__belaunce_tipi', 't') . ' ON t.id = a.tip_id')
            ->join('LEFT', $baza->quoteName('#__belaunce_tezavnosti', 'tz') . ' ON tz.id = a.tezavnost_id')
            ->join('LEFT', $baza->quoteName('#__users', 'u') . ' ON u.id = a.vodja_id')
            ->where($pretekle ? 'a.stanje = 1' : 'a.stanje IN (1, 2)')
            ->where('DATE_ADD(a.zacetek, INTERVAL COALESCE(a.trajanje_min, :privzeto_trajanje) MINUTE)'
                . ($pretekle ? ' < ' : ' >= ') . 'UTC_TIMESTAMP()')
            ->bind(':privzeto_trajanje', $privzetoTrajanje, ParameterType::INTEGER)
            ->bind(':vodja', $vodjaId, ParameterType::INTEGER)
            ->order($pretekle ? 'a.zacetek DESC, a.id DESC' : 'a.zacetek ASC, a.id ASC');

        $udelezba = 'EXISTS (SELECT 1 FROM #__belaunce_prijave AS p WHERE p.runda_id = a.id AND p.uporabnik_id = :prijavljen)';

        if ($sklop === 'vodim') {
            $poizvedba->where('a.vodja_id = :vodja');
        } else {
            // EXISTS ne množi vrstic; pretekle združimo z OR brez UNION ali podvojitev.
            $poizvedba->where($pretekle ? '(a.vodja_id = :vodja OR ' . $udelezba . ')'
                : '(a.vodja_id <> :vodja AND ' . $udelezba . ')')
                ->bind(':prijavljen', $prijavljenId, ParameterType::INTEGER);
        }

        return $poizvedba;
    }
}
