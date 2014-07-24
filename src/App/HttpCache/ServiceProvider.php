<?php

namespace App\HttpCache;

use Pimple\Container,
    Pimple\ServiceProviderInterface,
    Silex\Provider\HttpCache\HttpCache;


/**
 * Active http cache mechanisms when :
 *  -> debug is off
 *  -> user is not admin
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['http_cache.mongo.collection'] = $app['mongo.database']->httpCache;

        // When DEBUG is OFF
        if (! $app['debug'])
        {
            $app['http_cache.options'] = array();

            $app['http_cache'] = function ($app) {
                return new HttpCache($app, $app['http_cache.store'], null, $app['http_cache.options']);
            };

            $app['http_cache.store'] = function ($app) {
                return new Store($app['http_cache.cache_dir']);
            };

            $app['dispatcher']->addSubscriber(new HttpCacheListener($app));
        }
        // When DEBUG is ON
        else
        {
            $app['http_cache.mongo'] = function ($app) {
                return new MongoNoCache($app['http_cache.mongo.collection']);
            };
        }
    }
}
