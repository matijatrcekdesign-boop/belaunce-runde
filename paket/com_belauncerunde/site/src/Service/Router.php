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

        // Obstoječa prijava in potrditev povezave morata delovati tudi s SEF.
        $this->registerView(new RouterViewConfiguration('prijava'));
        $this->registerView(new RouterViewConfiguration('povezava'));

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

        return (int) $segment;
    }
}
