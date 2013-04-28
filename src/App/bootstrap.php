<?php

define('APP',  __DIR__);
define('SRC',  dirname(APP));
define('ROOT', dirname(SRC));

$loader = require ROOT.'/vendor/autoload.php';

$app = new \Silex\Application();

/* environment */
$app['env'] = 'dev';

/* debug */
$app['debug'] = ($app['env'] === 'dev') ? true : false;

/* MongoDB config */
$app['mongo.connection'] = new \MongoClient(); // default connection
$app['mongo.database'] = $app['mongo.connection']->asVoyage;


/*************************************************
 * Register of providers
 ************************************************/

/* controller as service */
//$app->register(new Silex\Provider\ServiceControllerServiceProvider());

/* session */
$app->register(new \Silex\Provider\SessionServiceProvider());

/* url generator */
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());

/* cache */
$app->register(new \Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => ROOT.'/cache'
));

/* twig */
$app->register(new \Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(APP.'/Resources/views'),
));

/* translator */
$app->register(new \Silex\Provider\TranslationServiceProvider(), array(
    'locale' => $app['session']->get('locale') ?: 'fr',
    'locale_fallback' => 'en',
));
$transDir = APP.'/Resources/translations';
$app['translator']->addResource('array', require "$transDir/messages.fr.php", 'fr');

/* monolog */
//$app->register(new \Silex\Provider\MonologServiceProvider(), array(
    //'monolog.name' => 'asVoyage',
    //'monolog.handler' => $app->share(function ($app) {
        //return new \Monolog\Handler\MongoDBHandler($app['mongo.connection'], $app['mongo.database'], 'log');
    //}),
//));


/*************************************************
 * Define the routes
 ************************************************/

$app->mount('/', new \App\Controller\BaseController);


return $app;

?>
