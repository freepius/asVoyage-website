<?php

namespace App\HttpCache;

use Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpKernel\KernelEvents,
    Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * If user is not ADMIN => run the http cache !
 */
class HttpCacheListener implements EventSubscriberInterface
{
    protected $app;

    public function __construct(\App\Application $app)
    {
        $this->app = $app;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // If ADMIN => no http cache
        if ($this->app->isGranted('ROLE_ADMIN'))
        {
            $this->app['http_cache.mongo'] = $this->app->share(function ($app) {
                return new MongoNoCache($app['http_cache.mongo.collection']);
            });
        }
        else
        {
            $this->app['http_cache.mongo'] = $this->app->share(function ($app) {
                return new MongoCache($app['http_cache.mongo.collection']);
            });

            $dispatcher = $this->app['dispatcher'];
            $listeners  = $dispatcher->getListeners(KernelEvents::REQUEST);

            // When "http cache" will run, do not re-execute the "early" listeners and itself
            foreach ($listeners as $listener)
            {
                $dispatcher->removeListener(KernelEvents::REQUEST, $listener);

                if ($listener === [$this, 'onKernelRequest']) { break; }
            }

            // Run http_cache !
            $event->setResponse(
                $this->app['http_cache']->handle($event->getRequest())
            );
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            /* Must be registered after :
             *  -> LocaleListener to have access to the correct locale through Request [priority is 16]
             *  -> Firewall to execute correctly the isGranted() function              [priority is 8]
             */
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        );
    }
}
