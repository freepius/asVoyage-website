<?php

namespace App\Controller;

use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Summary :
 *  -> __construct
 *  -> connect
 *
 *  -> GLOBAL ACTIONS :
 *      => home                         [cached]
 *      => about                        [cached]
 *      => contact
 *      => map
 *      => ourTravels                   [cached]
 *      => diagonal3000Km [our-travels] [cached]
 *      => afrikapie      [our-travels] [cached]
 *
 *  -> ADMIN ACTIONS :
 *      => admin
 *      => cacheClear
 *
 *  -> TECHNICAL ACTIONS :
 *      => switchLocale
 *      => changeCaptcha    [ajax]
 *      => manageErrors
 */
class BaseController implements ControllerProviderInterface
{
    use \Freepius\Controller\BaseTrait;

    public function __construct(\Freepius\Silex\Application $app)
    {
        $this->app = $app;
        $this->currentTravelStartingDate = $app['currentTravel.startingDate'];
    }

    public function connect(\Silex\Application $app)
    {
        $ctrl = $app['controllers_factory'];

        // Home
        $ctrl->get('/',     [$this, 'home']);
        $ctrl->get('/home', [$this, 'home']);

        // Various pages
        $ctrl->get('/about'    , [$this, 'about']);
        $ctrl->match('/contact', [$this, 'contact']);
        $ctrl->get('/map'      , [$this, 'map']);

        // Our travels
        $ctrl->get('our-travels'                   , [$this, 'ourTravels']);
        $ctrl->get('our-travels/3000-km-diagonal'  , [$this, 'diagonal3000Km']);
        $ctrl->get('our-travels/afrikapie'         , [$this, 'afrikapie']);
        $ctrl->get('our-travels/afrikapie/original', [$this, 'afrikapieOriginal']);

        // Admin routes
        $ctrl->get('/admin'            , [$this, 'admin']);
        $ctrl->get('/admin/cache-clear', [$this, 'cacheClear']);

        // Technical routes
        $ctrl->get('/switch-locale/{locale}', [$this, 'switchLocale'])
            ->assert('locale', 'en|fr');

        $ctrl->get('/captcha-change', [$this, 'changeCaptcha'])
            ->mustBeAjax();

        $app->error([$this, 'manageErrors']);

        // Add the trait routes
        $this->addRoutes($ctrl);

        return $ctrl;
    }


    /***************************************************************************
     * GLOBAL ACTIONS
     **************************************************************************/

    /**
     * CACHE: public ; validation
     */
    public function home(Request $request)
    {
        $response = $this->app['http_cache.mongo']->response(
            $request, 'base.home', ['blog', 'media', 'register']
        );
        if ($response->isNotModified($request)) { return $response; }


        $blogRepo     = $this->app['model.repository.blog'];
        $mediaRepo    = $this->app['model.repository.media'];
        //$registerRepo = $this->app['model.repository.register'];

        // The 6 last blog articles
        $lastArticles = iterator_to_array($blogRepo->find(6));

        // The 20 last favorite images
        $lastImages = $mediaRepo->find(20, 0, ['tags' => ['Favori'], 'type' => 'image']);

        // Get the last "geo. entry" from "Travel Register" module
        //$lastGeoEntry = $registerRepo->getLastGeoEntry();

        // Get the last "message entry" from "Travel Register"
        //$lastMsgEntry = $registerRepo->getLastMsgEntry();

        // Get/generate the javascript file containing the travel register entries
        //$geoEntries_js = $registerRepo->getGeoJsFile($this->currentTravelStartingDate);

        return $this->app->render('base/home.html.twig',
        [
            'articles'       => $lastArticles,
            'favoriteImages' => $lastImages,
            //'lastGeoEntry'   => $lastGeoEntry,
            //'lastMsgEntry'   => $lastMsgEntry,
            //'geoEntries_js'  => $geoEntries_js,
        ], $response);
    }

    /**
     * CACHE: public ; 30 days
     */
    public function about(Request $request)
    {
        $now = time();

        return $this->app->render("base/about/{$this->app['locale']}.html.twig",
        [
            // Years together of Marie and Mathieu
            // ("Now" - "2004/10/15 16:00:00") / (60 * 60 * 24 * 365.25)
            'yearsMMM' => (int) round(($now - 1097769600) / 31557600),

            // Years of Mathieu
            // ("Now" - "1987/08/05 12:00:00") / (60 * 60 * 24 * 365.25)
            'yearsMathieu' => (int) round(($now - 555156000) / 31557600),

            // Years of Marie
            // ("Now" - "1988/09/26 00:05:00") / (60 * 60 * 24 * 365.25)
            'yearsMarie' => (int) round(($now - 591231900) / 31557600),
        ])
        ->setSharedMaxAge(3600 * 24 * 30);
    }

    /**
     * CACHE: public ; validation
     */
    public function map(Request $request)
    {
        $response = $this->app['http_cache.mongo']->response(
            $request, 'base.map', ['media', 'register']
        );
        if ($response->isNotModified($request)) { return $response; }


        $mediaRepo    = $this->app['model.repository.media'];
        $registerRepo = $this->app['model.repository.register'];

        return $this->app->render('base/map.html.twig', [
            'media_elements_js'   => $mediaRepo->getGeoJsFile($this->currentTravelStartingDate),
            'register_entries_js' => $registerRepo->getGeoJsFile($this->currentTravelStartingDate),
        ], $response);
    }

    public function contact(Request $request)
    {
        $ourMail = $this->app['swiftmailer.options']['username'];

        $factoryContact = $this->app['model.factory.contact'];

        $contact = $factoryContact->instantiate();

        $errors = [];

        if ($request->isMethod('POST'))
        {
            $httpData = $request->request->all(); // http POST data

            $errors = $factoryContact->bind($contact, $httpData);

            // No error => send a mail + redirect to home
            if (! $errors)
            {
                $this->app->mail(\Swift_Message::newInstance()
                    ->setSubject($contact['subject'])
                    ->setFrom([$contact['email'] => $contact['name']])
                    ->setTo($ourMail)
                    ->setBody($contact['message'])
                );

                $this->app->addFlash('success', $this->app->trans('contact.sent'));

                return $this->app->redirect('/contact');
            }
        }

        $factoryContact->addCaptcha($contact);

        return $this->app->render('base/contact.html.twig',
        [
            'contact' => $contact,
            'errors'  => $errors,
        ]);
    }

    /**
     * CACHE: public ; 30 days
     */
    public function ourTravels()
    {
        return $this->app->render("base/our-travels/{$this->app['locale']}.html.twig")
            ->setSharedMaxAge(3600 * 24 * 30);
    }

    /**
     * CACHE: public ; 30 days
     */
    public function diagonal3000Km()
    {
        $repository = $this->app['model.repository.media'];

        // Retrieve 4 favorite images per travel
        $downLoireImgs = $repository->randomImagesByTags(['Favori', 'Descente de Loire'], 4);
        $longWalkImgs  = $repository->randomImagesByTags(['Favori', 'Grande marche']    , 4);

        return $this->app->render("base/our-travels/3000-km-diagonal/{$this->app['locale']}.html.twig",
        [
            'downLoireImgs' => $downLoireImgs,
            'longWalkImgs'  => $longWalkImgs,
        ])
        ->setSharedMaxAge(3600 * 24 * 30);
    }

    /**
     * CACHE: public ; 30 days
     */
    public function afrikapie()
    {
        $repository = $this->app['model.repository.media'];

        // Retrieve 4 favorite images per country
        $images = [
            'morocco'    => $repository->randomImagesByTags(['Afrikapié', 'Favori', 'Maroc']     , 4),
            'mauritania' => $repository->randomImagesByTags(['Afrikapié', 'Favori', 'Mauritanie'], 4),
            'senegal'    => $repository->randomImagesByTags(['Afrikapié', 'Favori', 'Sénégal']   , 4),
            'mali'       => $repository->randomImagesByTags(['Afrikapié', 'Favori', 'Mali']      , 4),
        ];

        return $this->app->render("base/our-travels/afrikapie/{$this->app['locale']}.html.twig",
        [
            'images' => $images,
        ])
        ->setSharedMaxAge(3600 * 24 * 30);
    }

    /**
     * CACHE: public ; 30 days
     */
    public function afrikapieOriginal()
    {
        return $this->app->render("base/our-travels/afrikapie/original/{$this->app['locale']}.html.twig")
            ->setSharedMaxAge(3600 * 24 * 30);
    }


    /***************************************************************************
     * ADMIN ACTIONS
     **************************************************************************/

    /**
     * Dashboard for admin actions (for blog, media, register, etc.)
     */
    public function admin()
    {
        return $this->app->render('base/admin.html.twig');
    }

    public function cacheClear()
    {
        $app = $this->app;

        $fs = new Filesystem();

        // Empty the cache dir
        $fs->remove($app['path.cache']);
        $fs->mkdir ($app['path.cache']);

        // Empty the public captcha dir
        $fs->remove($app['path.web'].'/'.$app['dir.captcha']);
        $fs->mkdir ($app['path.web'].'/'.$app['dir.captcha']);

        // Empty the public register dir
        $app['model.repository.register']->clearCacheDir();

        // Empty the public media dir
        $app['model.repository.media']->clearCacheDir();

        // Drop the "http cache" mongo collection
        $this->app['http_cache.mongo.collection']->drop();

        $this->app->addFlash('success', $this->app->trans('admin.cacheCleared'));

        return $this->app->redirect('/admin');
    }


    /***************************************************************************
     * TECHNICAL ACTIONS
     **************************************************************************/

    public function switchLocale($locale)
    {
        $this->app->setSession('locale', $locale);

        return $this->app->redirect('/home');
    }

    /**
     * Generate a new captcha for current user, and return the associated filename.
     */
    public function changeCaptcha()
    {
        $captchaManager = $this->app['captcha.manager'];

        $captchaManager->revoke();

        return $captchaManager->getFilename();
    }

    public function manageErrors(\Exception $e, Request $request, $code)
    {
        if ($this->app['debug']) { return; }

        // Hack to don't make shout the "isGranted" function, in "layout.html.twig".
        $this->app['security']->setToken(
            new \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken('', '')
        );

        // Case of an ajax request => return a "simple text" response
        if ($request->isXmlHttpRequest()) {
            return new Response($e->getMessage(), $code);
        }

        // Other cases => return a html response
        return $this->app->render('base/error.html.twig',
        [
            'message' => $e->getMessage(),
            'code'    => $code,
        ]);
    }
}
