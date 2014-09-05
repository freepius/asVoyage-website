<?php

namespace App\Provider;

use App\Provider\Locale\LocaleListener,
    Pimple\Container,
    Pimple\ServiceProviderInterface,
    Silex\Api\EventListenerProviderInterface,
    Symfony\Component\EventDispatcher\EventDispatcherInterface;


class LocaleServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
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
