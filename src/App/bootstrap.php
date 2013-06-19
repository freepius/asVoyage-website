<?php

define('APP',  __DIR__);
define('SRC',  dirname(APP));
define('ROOT', dirname(SRC));
define('WEB',  ROOT.'/web');

$loader = require ROOT.'/vendor/autoload.php';

// Locale of the application
setlocale(LC_ALL, 'fr_FR.UTF-8');

// Hack to load SmartyPantsTypographer class :-/
// TODO : delete this when corrected by author
$loader->add('michelf', ROOT.'/vendor/michelf/php-smartypants');
\michelf\SmartyPants::SMARTYPANTS_VERSION;

// Enable _method request parameter support
\Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

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

/* security */
$app->register(new \Silex\Provider\SecurityServiceProvider());

/* autolink Twig extension */
$app->register(new \Nicl\Silex\AutolinkServiceProvider());

/* markdown and typo */
$app['markdownTypo'] = $app->share(function () {
    return new \App\Util\MarkdownTypo();
});

/* captcha manager */
$app['captcha.manager'] = $app->share(function ($app) {
    return new \App\Util\CaptchaManager($app['session']);
});

/* monolog */
/*
$app->register(new \Silex\Provider\MonologServiceProvider(), array(
    'monolog.name' => 'asVoyage',
    'monolog.handler' => $app->share(function ($app) {
        return new \Monolog\Handler\MongoDBHandler($app['mongo.connection'], $app['mongo.database'], 'log');
    }),
));
*/


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
 * Security configuration
 ************************************************/

$app['security.firewalls'] = array(
    'all' => array(
        'anonymous' => true,
        'pattern'   => '^/',
        'form'      => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
        'logout'    => array('logout_path' => '/admin/logout'),
        'users'     => array(
            'vagabond' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
        ),
    ),
);

$app['security.access_rules'] = array(array
(
    '^/admin'                   .'|'.
    '^/render-markdown'         .'|'.
    '^/blog/(dashboard|create)' .'|'.
    '^/blog/.+/(update|delete)' .'|'.
    '^/blog/.+/read/.+'         .'|'.   // <=> CRUD for comment
    '^/blog/.+/comments.*'              // <=> idem
,
'ROLE_ADMIN'));


/*************************************************
 * Add translation resources
 ************************************************/

$translator = $app['translator'];
$transDir   = APP.'/Resources/translations';
$locales    = ['fr', 'en'];
$resources  = ['messages', 'blog', 'comment'];

foreach ($locales as $locale) {
    foreach ($resources as $resource) {
        $translator->addResource('array', require "$transDir/$resource.$locale.php", $locale);
    }
}


/*************************************************
 * Register repositories
 ************************************************/

$app['model.repository.blog'] = $app->share(function ($app)
{
    return new \App\Model\Repository\Blog($app['mongo.database']->article);
});


/*************************************************
 * Register entity factories
 ************************************************/

$app['model.factory.article'] = $app->share(function ($app)
{
    return new \App\Model\Factory\Article($app['validator'], $app['model.repository.blog']);
});

$app['model.factory.comment'] = $app->share(function ($app)
{
    return new \App\Model\Factory\Comment($app['validator'], $app['security'], $app['captcha.manager']);
});


/*************************************************
 * Define the routes
 ************************************************/

$app->mount('/'    , new \App\Controller\BaseController);
$app->mount('/blog', new \App\Controller\BlogController($app));


return $app;
