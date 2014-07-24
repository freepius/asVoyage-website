<?php

namespace App\Locale;

use Pimple\Container,
    Pimple\ServiceProviderInterface,
    Silex\Api\EventListenerProviderInterface,
    Symfony\Component\EventDispatcher\EventDispatcherInterface;


class ServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['locale.listener'] = function ($app)
        {
            return new LocaleListener($app);
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['locale.listener']);
    }
}
