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

/* Enable _method request parameter support */
\Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

$app = new \App\Application();

$app['route_class'] = 'App\\Route';

/* Locale of the application */
setlocale(LC_ALL, 'fr_FR.UTF-8');

/* Locale of the current request */
$app['locale'] = $app->share(function ($app)
{
    return $app->getSession('locale') ?: 'fr';
});

/* debug */
$app['debug'] = DEBUG;

/* MongoDB config */
$app['mongo.connection'] = new \MongoClient(MONGO_SERVER); // default connection
$app['mongo.database'] = $app['mongo.connection']->selectDB(MONGO_DB);

/* Paths and directories */
$app['path.cache']  = ROOT.'/cache';
$app['path.web']    = ROOT.'/web';
$app['dir.captcha'] = 'tmp/captcha';

/* Config. parameters in bulk */
$app['currentTravel.startingDate'] = '2013-10-02';


/*************************************************
 * Register services
 ************************************************/

/* session */
$app->register(new \Silex\Provider\SessionServiceProvider());

/* http cache */
$app->register(new \App\HttpCache\ServiceProvider(), [
    'http_cache.cache_dir' => $app['path.cache'].'/http',
]);

/* twig */
$app->register(new \Silex\Provider\TwigServiceProvider(), [
    'twig.path' => [APP.'/Resources/views'],
    'twig.options' => ['cache' => DEBUG ? null : ($app['path.cache'].'/twig')],
]);

/* swiftmailer */
$app->register(new Silex\Provider\SwiftmailerServiceProvider());

/* validator */
$app->register(new Silex\Provider\ValidatorServiceProvider());

/* translator */
$app->register(new \Silex\Provider\TranslationServiceProvider());

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
    '^/register/post'           .'|'.

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
$resources  = ['messages', 'blog', 'comment', 'media', 'register'];

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

$app['model.repository.register'] = $app->share(function ($app)
{
    return new \App\Model\Repository\Register(
        $app['mongo.database']->register, $app['twig'],
        $app['path.web'], $app['register.config']['cache_dir']
    );
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

$app['model.factory.register'] = $app->share(function ($app)
{
    return new \App\Model\Factory\Register($app['validator']);
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
 * Configuration for the "travel register"
 ************************************************/

$app['register.config'] =
[
    'twilio.account' => TWILIO_ACCOUNT_SID,
    'twilio.number'  => TWILIO_NUMBER,
    'cache_dir'      => 'tmp/register',  // relative to web path
];


/*************************************************
 * Define the routes
 ************************************************/

$app->mount('/'        , new \App\Controller\BaseController($app));
$app->mount('/blog'    , new \App\Controller\BlogController($app));
$app->mount('/media'   , new \App\Controller\MediaController($app));
$app->mount('/register', new \App\Controller\RegisterController($app));


return $app;
