<?php

use Belaunce\Plugin\Authentication\Belauncerunde\Extension\Belauncerunde;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

\defined('_JEXEC') or die;

/** Ponudnik vtičnika po vzoru jedra authentication/cookie. */
return new class () implements ServiceProviderInterface {
    /**
     * Registrira vtičnik in mu nastavi trenutno aplikacijo.
     *
     * @param  Container  $container
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Belauncerunde::class, function (Container $vsebnik) {
                $vticnik = new Belauncerunde((array) PluginHelper::getPlugin('authentication', 'belauncerunde'));
                $vticnik->setApplication(Factory::getApplication());

                return $vticnik;
            })
        );
    }
};
