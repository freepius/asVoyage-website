<?php

define('APP',  __DIR__);
define('SRC',  dirname(APP));
define('ROOT', dirname(SRC));

$loader = require ROOT.'/vendor/autoload.php';

// Hack to load SmartyPantsTypographer class :-/
$loader->add('michelf', ROOT.'/vendor/michelf/php-smartypants');
\michelf\SmartyPants::SMARTYPANTS_VERSION;

$app = new \Silex\Application();

/* environment */
$app['env'] = 'dev';

/* debug */
$app['debug'] = ($app['env'] === 'dev') ? true : false;

/* MongoDB config */
$app['mongo.connection'] = new \MongoClient(); // default connection
$app['mongo.database'] = $app['mongo.connection']->asVoyage;


/*************************************************
 * Register services
 ************************************************/

/* controller as service */
//$app->register(new Silex\Provider\ServiceControllerServiceProvider());

/* session */
$app->register(new \Silex\Provider\SessionServiceProvider());

/* cache */
$app->register(new \Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => ROOT.'/cache'
));

/* twig */
$app->register(new \Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(APP.'/Resources/views'),
));

/* validator */
$app->register(new Silex\Provider\ValidatorServiceProvider());

/* translator */
$app->register(new \Silex\Provider\TranslationServiceProvider(), array(
    'locale' => $app['session']->get('locale') ?: 'fr',
));

/* monolog */
//$app->register(new \Silex\Provider\MonologServiceProvider(), array(
    //'monolog.name' => 'asVoyage',
    //'monolog.handler' => $app->share(function ($app) {
        //return new \Monolog\Handler\MongoDBHandler($app['mongo.connection'], $app['mongo.database'], 'log');
    //}),
//));

/* markdown and typo */
$app['markdownTypo'] = $app->share(function () {
    return new \App\Util\MarkdownTypo();
});


/*************************************************
 * Twig extensions, global variables and other
 ************************************************/

$loader->add('Twig', ROOT.'/vendor/twig/extensions/lib');

$app['twig'] = $app->share($app->extend('twig', function($twig, $app)
{
    $twig->addExtension(new Twig_Extensions_Extension_Intl());  // for 'localizeddate' filter

    $twig->addFilter('sum', new \Twig_Filter_Function('array_sum'));

    return $twig;
}));


/*************************************************
 * Add translation resources
 ************************************************/

$translator = $app['translator'];
$transDir   = APP.'/Resources/translations';
$locales    = ['fr', 'en'];
$resources  = ['messages', 'blog'];

foreach ($locales as $locale) {
    foreach ($resources as $resource) {
        $translator->addResource('array', require "$transDir/$resource.$locale.php", $locale);
    }
}


/*************************************************
 * Register repositories
 ************************************************/

$app['model.repository.blog.article'] = $app->share(function ($app)
{
    return new \App\Model\Repository\BlogArticle($app['mongo.database']->blogArticle);
});


/*************************************************
 * Register entity factories
 ************************************************/

$app['model.factory.blog.article'] = $app->share(function ($app)
{
    return new \App\Model\Factory\BlogArticle($app['validator'], $app['model.repository.blog.article']);
});


/*************************************************
 * Define the routes
 ************************************************/

$app->mount('/'    , new \App\Controller\BaseController);
$app->mount('/blog', new \App\Controller\BlogController($app));


return $app;

?>
