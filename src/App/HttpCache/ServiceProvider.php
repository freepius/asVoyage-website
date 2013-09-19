<?php

namespace App\HttpCache;

use Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Silex\Application,
    Silex\ServiceProviderInterface,
    Silex\HttpCache;


/**
 * Active http cache mechanisms when :
 *  -> debug is off
 *  -> user is not admin
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['http_cache.mongo.collection'] = $app['mongo.database']->httpCache;

        // When DEBUG is OFF
        if (! $app['debug'])
        {
            $app['http_cache.options'] = array();

            $app['http_cache'] = $app->share(function ($app) {
                return new HttpCache($app, $app['http_cache.store'], null, $app['http_cache.options']);
            });

            $app['http_cache.store'] = $app->share(function ($app) {
                return new Store($app['http_cache.cache_dir']);
            });

            $app['dispatcher']->addSubscriber(new HttpCacheListener($app));
        }
        // When DEBUG is ON
        else
        {
            $app['http_cache.mongo'] = $app->share(function ($app) {
                return new MongoNoCache($app['http_cache.mongo.collection']);
            });
        }
    }

    public function boot(Application $app)
    {
    }
}
