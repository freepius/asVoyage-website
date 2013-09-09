<?php

namespace App\Controller;

use Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request;


/**
 * Summary :
 *  -> __construct
 *  -> connect
 *
 *  -> GLOBAL ACTIONS :
 *      => home
 *      => about
 *      => contact
 *      => ourTrips
 *      => diagonal3000Km [our-trips]
 *      => afrikapie      [our-trips]
 *
 *  -> TECHNICAL ACTIONS :
 *      => login
 *      => switchLocale
 *      => changeCaptcha    [ajax]
 *      => renderRichText   [ajax]
 *      => manageErrors
 */
class BaseController implements ControllerProviderInterface
{
    // TODO : real date !!
    const CURRENT_TRAVEL_BEGIN_DATE = '2013-09-03';

    public function __construct(\App\Application $app)
    {
        $this->app = $app;
    }

    public function connect(\Silex\Application $app)
    {
        $base = $app['controllers_factory'];

        // Home
        $base->get('/',     [$this, 'home']);
        $base->get('/home', [$this, 'home']);

        // Various pages
        $base->get('/about'    , [$this, 'about']);
        $base->match('/contact', [$this, 'contact']);

        // Our trips
        $base->get('our-trips'                 , [$this, 'ourTrips']);
        $base->get('our-trips/3000-km-diagonal', [$this, 'diagonal3000Km']);
        $base->get('our-trips/afrikapie'       , [$this, 'afrikapie']);

        // Technical routes
        $base->get('/login', [$this, 'login']);

        $base->get('/switch-locale/{locale}', [$this, 'switchLocale'])
            ->assert('locale', 'en|fr');

        $base->get('/captcha-change', [$this, 'changeCaptcha'])
            ->mustBeAjax();

        $base->post('/render-richtext', [$this, 'renderRichText'])
            ->mustBeAjax();

        $app->error([$this, 'manageErrors']);

        return $base;
    }


    /***************************************************************************
     * GLOBAL ACTIONS
     **************************************************************************/

    public function home()
    {
        // The 6 last blog articles
        $lastArticles = iterator_to_array(
            $this->app['model.repository.blog']->find(6)
        );

        // The 20 last favorite images
        $lastImages = $this->app['model.repository.media']
            ->find(20, 0, ['tags' => ['Favori'], 'type' => 'image']);

        // The "Travel Register" entries for mini-map
        $geoEntries = $this->app['model.repository.register']
            ->find(0, ['from' => self::CURRENT_TRAVEL_BEGIN_DATE, 'geo' => true]);
        $geoEntries->next();

        return $this->app->render('base/home.html.twig',
        [
            'articles'       => $lastArticles,
            'favoriteImages' => $lastImages,
            'geoEntries'     => $geoEntries,
            'lastGeoEntry'   => $geoEntries->current(),
        ]);
    }

    public function about()
    {
        $now = time();

        return $this->app->render("base/about.{$this->app['locale']}.html.twig",
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
        ]);
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

                return $this->app->redirect('/home');
            }
        }

        $factoryContact->addCaptcha($contact);

        return $this->app->render('base/contact.html.twig',
        [
            'contact' => $contact,
            'errors'  => $errors,
        ]);
    }

    public function ourTrips()
    {
        return $this->app->render('base/our-trips.html.twig');
    }

    public function diagonal3000Km()
    {
        $repository = $this->app['model.repository.media'];

        // Retrieve 4 favorite images per trip
        $downLoireImgs = $repository->randomImagesByTags(['Favori', 'Descente de Loire'], 4);
        $longWalkImgs  = $repository->randomImagesByTags(['Favori', 'Grande marche']    , 4);

        return $this->app->render('base/our-trips/3000-km-diagonal.html.twig',
        [
            'downLoireImgs' => $downLoireImgs,
            'longWalkImgs'  => $longWalkImgs,
        ]);
    }

    public function afrikapie()
    {
        return $this->app->render('base/our-trips/afrikapie.html.twig');
    }


    /***************************************************************************
     * TECHNICAL ACTIONS
     **************************************************************************/

    public function login(Request $request)
    {
        return $this->app->render('base/login.html.twig',
        [
            'error' => $this->app['security.last_error']($request),
        ]);
    }

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

    public function renderRichText(Request $request)
    {
        return $this->app['richText']->transform(
            $request->request->get('text')
        );
    }

    public function manageErrors(\Exception $e, $code)
    {
        if ($this->app['debug']) { return; }

        // Hack to don't make shout the "isGranted" function, in "layout.html.twig".
        $this->app['security']->setToken(
            new \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken('', '')
        );

        return $this->app->render('base/error.html.twig',
        [
            'message' => $e->getMessage(),
            'code'    => $code,
        ]);
    }
}
