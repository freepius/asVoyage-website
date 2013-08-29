<?php

define('APP' , __DIR__);
define('SRC' , dirname(APP));
define('ROOT', dirname(SRC));

/*
 * Include host-dependent configuration parameters
 * (with servernames, keywords...).
 */
include APP.'/config.php';

$loader = require ROOT.'/vendor/autoload.php';

// Locale of the application
setlocale(LC_ALL, 'fr_FR.UTF-8');

// Enable _method request parameter support
\Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

$app = new \App\Application();

$app['route_class'] = 'App\\Route';

/* debug */
$app['debug'] = DEBUG;

/* MongoDB config */
$app['mongo.connection'] = new \MongoClient(MONGO_SERVER); // default connection
$app['mongo.database'] = $app['mongo.connection']->selectDB(MONGO_DB);

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
$app['richText'] = $app->share(function ($app) {
    return new \App\Util\RichText($app['locale']);
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

    $twig->addGlobal('host', $app['request']->getUriForPath('/'));

    // TODO: change this just before Afrikapié starting !
    $twig->addGlobal('afrikapie_days2wait', round((strtotime('2013-10-01') - time()) / (60 * 60 * 24)));

    $twig->addFilter(new \Twig_SimpleFilter('sum', 'array_sum'));

    $twig->addFilter(new \Twig_SimpleFilter('shuffle', function ($array)
    {
        shuffle($array);
        return $array;
    }));

    $twig->addFilter(new \Twig_SimpleFilter('richText',
        [$app['richText'], 'transform'],
        ['is_safe' => ['all']]
    ));

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
    'password'   => SMTP_PASSWORD,
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
            'vagabond' => ['ROLE_ADMIN', ADMIN_PASSWORD],
        ],
    ],
];

$app['security.access_rules'] =
[[
    '^/admin'                   .'|'.
    '^/render-richtext'         .'|'.
    '^/blog/(dashboard|create)' .'|'.
    '^/blog/.+/(update|delete)' .'|'.
    '^/blog/.+/read/.+'         .'|'.   // <=> CRUD for comment
    '^/blog/.+/comments.*'      .'|'.   // <=> idem

    // All Media pages excepted home
    '^/media/(create|delete|delete-uploaded|init-update|update|upload)'
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
$resources  = ['messages', 'blog', 'comment', 'media'];

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
    return new \App\Model\Repository\Blog($app['mongo.database']->blog);
});

$app['model.repository.media'] = $app->share(function ($app)
{
    return new \App\Model\Repository\Media($app['mongo.database']->media, $app['path.web']);
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

$app['model.factory.media'] = $app->share(function ($app)
{
    return new \App\Model\Factory\Media($app['validator']);
});

$app['model.factory.media.uploaded'] = $app->share(function ($app)
{
    return new \App\Model\Factory\MediaUploaded($app['validator'], $app['path.web'], $app['media.config']);
});


/*************************************************
 * Configuration for media platform
 ************************************************/

$app['media.config'] =
[
    'image.web.size'       => 1200,     // size of small side in px
    'image.thumb.size'     => 120,      // size of small side in px
    'maxFileSize'          => 10000000, // 10M
    'acceptTypes.mime'     => ['application/ogg', 'audio/ogg', 'image/jpeg', 'image/png'],
    'acceptTypes.jsRegexp' => '/(\.|\/)(oga|ogg|ogx|jpe?g|png)$/i',
];


/*************************************************
 * Define the routes
 ************************************************/

$app->mount('/'    , new \App\Controller\BaseController($app));
$app->mount('/blog', new \App\Controller\BlogController($app));
$app->mount('/media', new \App\Controller\MediaController($app));


return $app;
