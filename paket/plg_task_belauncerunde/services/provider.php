<?php

use Belaunce\Plugin\Task\Belauncerunde\Extension\Belauncerunde;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

\defined('_JEXEC') or die;

/** Ponudnik vtičnika po vzoru jedrnega task/requests. */
return new class () implements ServiceProviderInterface {
    /** Registrira vtičnik Schedulerja in trenutno aplikacijo. */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Belauncerunde::class, function (Container $vsebnik) {
                $vticnik = new Belauncerunde((array) PluginHelper::getPlugin('task', 'belauncerunde'));
                $vticnik->setApplication(Factory::getApplication());

                return $vticnik;
            })
        );
    }
};
