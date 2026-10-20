<?php

namespace Belaunce\Component\Belauncerunde\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** SEF poti javnega seznama in rund po vzorcu com_contact iz jedra 6.1. */
class Router extends RouterView
{
    /** @var DatabaseInterface Baza za pridobivanje trenutnega aliasa. */
    private DatabaseInterface $baza;

    /**
     * Registrira hierarhijo pogledov in standardna Joomlina pravila.
     *
     * @param SiteApplication            $app             Aplikacija strani.
     * @param AbstractMenu               $menu            Meni strani.
     * @param ?CategoryFactoryInterface  $categoryFactory Neuporabljeno; argument jedrne tovarne.
     * @param DatabaseInterface          $db              Baza iz tovarne routerja.
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
    {
        $this->baza = $db;

        $runde = new RouterViewConfiguration('runde');
        $this->registerView($runde);
        $runda = new RouterViewConfiguration('runda');
        $runda->setKey('id')->setParent($runde);
        $this->registerView($runda);
        $obrazec = new RouterViewConfiguration('obrazec');
        $obrazec->setKey('id')->setParent($runde);
        $this->registerView($obrazec);

        // Obstoječa prijava in potrditev povezave morata delovati tudi s SEF.
        $this->registerView(new RouterViewConfiguration('prijava'));
        $this->registerView(new RouterViewConfiguration('povezava'));
        $odjava = new RouterViewConfiguration('odjavagosta');
        $odjava->setParent($runde);
        $this->registerView($odjava);

        parent::__construct($app, $menu);
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Zgradi segment iz ID-ja in trenutnega aliasa, tudi pri stari vhodni povezavi.
     *
     * @param mixed $id    ID runde, lahko z dodanim aliasom.
     * @param array $query Spremenljivke povezave.
     * @return array
     */
    public function getRundaSegment($id, $query): array
    {
        $rundaId = (int) $id;
        $poizvedba = $this->baza->createQuery()
            ->select($this->baza->quoteName('alias'))
            ->from($this->baza->quoteName('#__belaunce_runde'))
            ->where($this->baza->quoteName('id') . ' = :id')
            ->bind(':id', $rundaId, ParameterType::INTEGER);
        $this->baza->setQuery($poizvedba);
        $alias = $this->baza->loadResult();

        // Manjkajoče runde ne zavrnemo tukaj; pogled zanjo vrne 404.
        return [$rundaId => $alias === null ? (string) $rundaId : $rundaId . '-' . $alias];
    }

    /**
     * Vrne samo ID; alias ne odloča o obstoju ali dostopu do runde.
     *
     * @param string $segment Segment poti.
     * @param array  $query   Spremenljivke povezave.
     * @return int
     */
    public function getRundaId($segment, $query): int
    {
        if (preg_match('/^([0-9]+)-.*$/', $segment, $ujemanje)) {
            return (int) $ujemanje[1];
        }

        return ctype_digit($segment) ? (int) $segment : 0;
    }

    /** Sestavi novo ali urejevalno pot brez spremembe obstoječih URL-jev rund. */
    public function getObrazecSegment($id, $query): array
    {
        $id = (int) $id;

        if ($id === 0) {
            return [0 => 'nova'];
        }

        $segment = $this->getRundaSegment($id, $query);

        return [$id => 'uredi-' . $segment[$id]];
    }

    /** Razbere prvo številko za uredi-, nova ima ID 0; druge poti ne pripadajo obrazcu. */
    public function getObrazecId($segment, $query): int
    {
        return preg_match('/^uredi-([1-9][0-9]*)(?:-.*)?$/D', $segment, $ujemanje) ? (int) $ujemanje[1] : 0;
    }

    /** Vidno ime poti odjave loči od ASCII imena MVC pogleda. */
    public function build(&$query)
    {
        $odjava = ($query['view'] ?? '') === 'odjavagosta';
        $segmenti = parent::build($query);

        if ($odjava) {
            foreach ($segmenti as &$segment) {
                if ($segment === 'odjavagosta') {
                    $segment = 'odjava-gosta';
                }
            }
            unset($segment);
        }

        return $segmenti;
    }

    /** Razbere posebno ničelno novo rundo in literal odjave gosta pod menijem seznama. */
    public function parse(&$segments)
    {
        $aktivna = $this->menu->getActive();

        // Brez menija NomenuRules pričakuje ime pogleda kot prvi segment.
        if (($segments[0] ?? '') === 'odjava-gosta'
            && ($aktivna === null || $aktivna->component !== 'com_belauncerunde' || ($aktivna->query['view'] ?? '') === 'runde')) {
            $segments[0] = 'odjavagosta';
        }

        // Poseben je samo ključ 0 pod menijem seznama. Številčne poti in uredi-
        // ostanejo pri StandardRules; brez menija nova deluje prek NomenuRules.
        if ($segments === ['nova'] && $aktivna !== null
            && $aktivna->component === 'com_belauncerunde' && ($aktivna->query['view'] ?? '') === 'runde') {
            array_shift($segments);

            return ['view' => 'obrazec', 'id' => $this->getObrazecId('nova', [])];
        }

        return parent::parse($segments);
    }
}
