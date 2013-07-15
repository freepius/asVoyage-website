<?php

define('APP' , __DIR__);
define('SRC' , dirname(APP));
define('ROOT', dirname(SRC));

$loader = require ROOT.'/vendor/autoload.php';

// Locale of the application
setlocale(LC_ALL, 'fr_FR.UTF-8');

// Hack to load SmartyPantsTypographer class :-/
// TODO : delete this when corrected by author
$loader->add('michelf', ROOT.'/vendor/michelf/php-smartypants');
\michelf\SmartyPants::SMARTYPANTS_VERSION;

// Enable _method request parameter support
\Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

$app = new \App\Application();

/* debug */
$app['debug'] = true;

/* MongoDB config */
$app['mongo.connection'] = new \MongoClient(); // default connection
$app['mongo.database'] = $app['mongo.connection']->selectDB('asVoyage');

/* Paths and directories */
$app['path.web'] = ROOT.'/web';
$app['dir.captcha'] = 'tmp/captcha';


/*************************************************
 * Register services
 ************************************************/

/* session */
$app->register(new \Silex\Provider\SessionServiceProvider());

/* cache */
$app->register(new \Silex\Provider\HttpCacheServiceProvider(), [
    'http_cache.cache_dir' => ROOT.'/cache'
]);

/* twig */
$app->register(new \Silex\Provider\TwigServiceProvider(), [
    'twig.path' => [APP.'/Resources/views'],
]);

/* swiftmailer */
$app->register(new Silex\Provider\SwiftmailerServiceProvider());

/* validator */
$app->register(new Silex\Provider\ValidatorServiceProvider());

/* translator */
$app->register(new \Silex\Provider\TranslationServiceProvider(), [
    'locale' => $app['session']->get('locale') ?: 'fr',
]);

/* security */
$app->register(new \Silex\Provider\SecurityServiceProvider());

/* autolink Twig extension */
$app->register(new \Nicl\Silex\AutolinkServiceProvider());

/* richText (markdown and typo) */
$app['richText'] = $app->share(function () {
    return new \App\Util\RichText();
});

/* captcha manager */
$app['captcha.manager'] = $app->share(function ($app) {
    return new \App\Util\CaptchaManager(
        $app['session'],
        [
            'webPath'     => $app['path.web'],
            'imageFolder' => $app['dir.captcha'],
        ]
    );
});

/* monolog */
/*
$app->register(new \Silex\Provider\MonologServiceProvider(), [
    'monolog.name' => 'asVoyage',
    'monolog.handler' => $app->share(function ($app) {
        return new \Monolog\Handler\MongoDBHandler($app['mongo.connection'], $app['mongo.database'], 'log');
    }),
]);
*/


/*************************************************
 * Twig extensions, global variables, filters and functions.
 ************************************************/

$loader->add('Twig', ROOT.'/vendor/twig/extensions/lib');

$app['twig'] = $app->share($app->extend('twig', function($twig, $app)
{
    $twig->addExtension(new Twig_Extensions_Extension_Intl());  // for 'localizeddate' filter

    $twig->addFilter('sum', new \Twig_Filter_Function('array_sum'));

    $twig->addFilter('shuffle', new \Twig_Filter_Function(function ($array)
    {
        shuffle($array);
        return $array;
    }));

    $twig->addFilter(
        'richText',
        new \Twig_Filter_Function([$app['richText'], 'transform']),
        ['is_safe' => ['all']]
    );

    return $twig;
}));


/*************************************************
 * SwiftMailer configuration
 ************************************************/

$app['swiftmailer.options'] =
[
    'host'       => 'smtp.alwaysdata.com',
    'port'       => 587,
    'username'   => 'contact@anarchos-semitas.net',
    'password'   => '',
    'encryption' => null,
    'auth_mode'  => null,
];


/*************************************************
 * Security configuration
 ************************************************/

$app['security.firewalls'] = [
    'all' => [
        'anonymous' => true,
        'pattern'   => '^/',
        'form'      => ['login_path' => '/login', 'check_path' => '/admin/login_check'],
        'logout'    => ['logout_path' => '/admin/logout'],
        'users'     => [
            'vagabond' => ['ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='],
        ],
    ],
];

$app['security.access_rules'] =
[[
    '^/admin'                   .'|'.
    '^/render-markdown'         .'|'.
    '^/blog/(dashboard|create)' .'|'.
    '^/blog/.+/(update|delete)' .'|'.
    '^/blog/.+/read/.+'         .'|'.   // <=> CRUD for comment
    '^/blog/.+/comments.*'              // <=> idem
,
'ROLE_ADMIN']];

/**
 * Hack: call 'security.firewall' allows to call
 * 'security' and 'twig' services at this point !
 */
$app['security.firewall'];


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

$app['model.factory.contact'] = $app->share(function ($app)
{
    return new \App\Model\Factory\Contact($app['validator'], $app['captcha.manager']);
});


/*************************************************
 * Define the routes
 ************************************************/

$app->mount('/'    , new \App\Controller\BaseController($app));
//$app->mount('/blog', new \App\Controller\BlogController($app));


return $app;
