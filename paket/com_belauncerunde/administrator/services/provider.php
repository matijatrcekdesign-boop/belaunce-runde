<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Belaunce\Component\Belauncerunde\Administrator\Extension\BelauncerundeComponent;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * Ponudnik storitev za komponento.
 *
 * @since  0.1.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registrira tovarne, ki jih Joomla potrebuje za MVC.
     *
     * @param   Container  $container  DI vsebnik.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Belaunce\\Component\\Belauncerunde'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Belaunce\\Component\\Belauncerunde'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new BelauncerundeComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
