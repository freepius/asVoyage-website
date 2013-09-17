<?php

namespace App\HttpCache;

use Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Silex\Application,
    Silex\ServiceProviderInterface,
    Silex\HttpCache;


class ServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['http_cache'] = $app->share(function ($app) {
            return new HttpCache($app, $app['http_cache.store'], null, $app['http_cache.options']);
        });

        $app['http_cache.store'] = $app->share(function ($app) {
            return new Store($app['http_cache.cache_dir']);
        });

        $app['http_cache.options'] = array();
    }

    public function boot(Application $app)
    {
        $this->app = $app;

        // onKernelRequest() is executed once, just after security authentication (whose priority is 8)
        $app['dispatcher']->addListener('kernel.request', [$this, 'onKernelRequest'], 7);
    }

    /**
     * If user is not ADMIN => try to use http cache
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (! $this->app->isGranted('ROLE_ADMIN'))
        {
            // Avoid an infinite recursive loop
            $this->app['dispatcher']->removeListener('kernel.request', [$this, 'onKernelRequest']);

            $event->setResponse(
                $this->app['http_cache']->handle($event->getRequest())
            );
        }
    }
}
