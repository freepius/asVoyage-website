<?php

define('APP' , __DIR__);
define('SRC' , dirname(APP));
define('ROOT', dirname(SRC));

$loader = require ROOT.'/vendor/autoload.php';

/*
 * Include host-dependent configuration parameters
 * (with servernames, passwords...).
 */
require_once APP.'/load-config.php';

/* Enable _method request parameter support */
\Symfony\Component\HttpFoundation\Request::enableHttpMethodParameterOverride();

$app = new \Freepius\Application();

/* Locale of the application */
setlocale(LC_ALL, 'fr_FR.UTF-8');

/* Locale of the current request : default = fr */
$app['locale'] = 'fr';

/* debug */
$app['debug'] = DEBUG;

/* MongoDB config */
$app['mongo.connection'] = new \MongoClient(MONGO_SERVER);
$app['mongo.database'] = $app['mongo.connection']->selectDB(MONGO_DB);

/* Paths and directories */
$app['path.cache']  = ROOT.'/cache';
$app['path.web']    = ROOT.'/web';
$app['dir.captcha'] = 'tmp/captcha';

/* Config. parameters in bulk */
$app['currentTravel.startingDate'] = '2013-09-29';


/*************************************************
 * Register services
 ************************************************/

/* session */
$app->register(new \Silex\Provider\SessionServiceProvider());

/* http cache */
$app->register(new \Freepius\Provider\HttpCacheServiceProvider(), [
    'http_cache.cache_dir' => $app['path.cache'].'/http',
]);

/* twig */
$app->register(new \Silex\Provider\TwigServiceProvider(), [
    'twig.path' => [APP.'/Resources/views'],
    'twig.options' => ['cache' => DEBUG ? null : ($app['path.cache'].'/twig')],
]);

/* swiftmailer */
$app->register(new \Silex\Provider\SwiftmailerServiceProvider());

/* validator */
$app->register(new \Silex\Provider\ValidatorServiceProvider());

/* locale */
$app->register(new \App\Provider\LocaleServiceProvider());

/* translator */
$app->register(new \Silex\Provider\TranslationServiceProvider());

/* security */
$app->register(new \Silex\Provider\SecurityServiceProvider());

/* freepius/php-richtext extension */
$app->register(new \Freepius\Pimple\Provider\RichtextProvider());
$app['richtext.config'] += ['remove.script.tags' => false];

/* freepius/php-toolbox extension */
$app->register(new \Freepius\Provider\ToolboxProvider());

/* autolink Twig extension */
/* TODO: Keep watch the Silex-Autolink from Nicl */
$app->register(new \Nicl\Silex\AutolinkServiceProvider());

/* captcha manager */
$app['captcha.manager'] = function ($app) {
    return new \Freepius\Util\CaptchaManager(
        $app['session'],
        [
            'webPath'     => $app['path.web'],
            'imageFolder' => $app['dir.captcha'],
        ]
    );
};

/* monolog */
/*
$app->register(new \Silex\Provider\MonologServiceProvider(), [
    'monolog.name' => 'asVoyage',
    'monolog.logfile' => ROOT.'/development.log',
    //'monolog.handler' => function ($app) {
        //return new \Monolog\Handler\MongoDBHandler($app['mongo.connection'], $app['mongo.database'], 'log');
    //},
]);
*/


/*************************************************
 * Twig extensions, global variables, filters and functions.
 ************************************************/

$app['twig'] = $app->extend('twig', function($twig, $app)
{
    // for 'shuffle' filter
    $twig->addExtension(new \Twig_Extensions_Extension_Array());

    // for 'localizeddate' filter
    $twig->addExtension(new \Twig_Extensions_Extension_Intl());

    $twig->addGlobal('host', $app['request_stack']->getMasterRequest()->getUriForPath('/'));

    $twig->addFilter(new \Twig_SimpleFilter('sum', 'array_sum'));

    return $twig;
});


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
$app->boot();
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

// TODO: Bug ! Actually, calling *translator* service sets to null the 'locale' parameter.
// The Bug is declared on GitHub #982. Follow it !
$app['locale'] = 'fr';


/*************************************************
 * Register repositories
 ************************************************/

$app['model.repository.blog'] = function ($app)
{
    return new \App\Model\Repository\Blog($app['mongo.database']->blog);
};

$app['model.repository.media'] = function ($app)
{
    $defaultFilters = [];

    // Consider the media elements having "private tags" only for ADMIN
    if ($app['media.config']['tags.private'] && false === $app->isGranted('ROLE_ADMIN'))
    {
        $defaultFilters['excludedTags'] = $app['media.config']['tags.private'];
    }

    return new \App\Model\Repository\Media(
        $app['mongo.database']->media,
        $app['twig'],
        $app['path.web'],
        $app['media.config']['cache_dir'],
        $defaultFilters
    );
};

$app['model.repository.register'] = function ($app)
{
    return new \App\Model\Repository\Register(
        $app['mongo.database']->register, $app['twig'],
        $app['path.web'], $app['register.config']['cache_dir']
    );
};


/*************************************************
 * Register entity factories
 ************************************************/

$app['model.factory.article'] = function ($app)
{
    return new \App\Model\Factory\Article($app['validator'], $app['model.repository.blog']);
};

$app['model.factory.comment'] = function ($app)
{
    return new \App\Model\Factory\Comment($app['validator'], $app['security'], $app['captcha.manager']);
};

$app['model.factory.contact'] = function ($app)
{
    return new \App\Model\Factory\Contact($app['validator'], $app['captcha.manager']);
};

$app['model.factory.media'] = function ($app)
{
    return new \App\Model\Factory\Media($app['validator']);
};

$app['model.factory.media.uploaded'] = function ($app)
{
    return new \App\Model\Factory\MediaUploaded($app['validator'], $app['path.web'], $app['media.config']);
};

$app['model.factory.register'] = function ($app)
{
    return new \App\Model\Factory\Register($app['validator']);
};


/*************************************************
 * Configuration for media platform
 ************************************************/

$app['media.config'] =
[
    'cache_dir'            => 'tmp/media', // relative to web path
    'image.web.size'       => 1200,        // size of small side in px
    'image.thumb.size'     => 120,         // size of small side in px
    'maxFileSize'          => 10000000,    // 10M
    'acceptTypes.mime'     => ['application/ogg', 'audio/ogg', 'image/jpeg', 'image/png'],
    'acceptTypes.jsRegexp' => '/(\.|\/)(oga|ogg|ogx|jpe?g|png)$/i',
    'tags.private'         => ['_Technical'],
];


/*************************************************
 * Configuration for the "travel register"
 ************************************************/

$app['register.config'] =
[
    'cache_dir'         => 'tmp/register',  // relative to web path
    'bing_maps_api_key' => BING_MAPS_API_KEY,
    'twilio.account'    => TWILIO_ACCOUNT_SID,
    'twilio.number'     => TWILIO_NUMBER,
];


/*************************************************
 * Define the routes
 ************************************************/

$app->mount('/'        , new \App\Controller\BaseController($app));
$app->mount('/blog'    , new \App\Controller\BlogController($app));
$app->mount('/media'   , new \App\Controller\MediaController($app));
$app->mount('/register', new \App\Controller\RegisterController($app));


return $app;
