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
 *
 *  -> TECHNICAL ACTIONS :
 *      => login
 *      => switchLocale
 *      => changeCaptcha
 */
class BaseController implements ControllerProviderInterface
{
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

        // Technical routes
        $base->get('/login', [$this, 'login']);

        $base->get('/switch-locale/{locale}', [$this, 'switchLocale'])
            ->assert('locale', 'en|fr');

        $base->get('/captcha-change', [$this, 'changeCaptcha']);

        $base->post('/render-markdown', function (Application $app)
        {
            return $app['markdownTypo']->transform(
                $app['request']->request->get('text')
            );
        });

        return $base;
    }


    /***************************************************************************
     * GLOBAL ACTIONS
     **************************************************************************/

    public function home()
    {
        return $this->app->render('base/home.html.twig');
    }

    public function about()
    {
        return $this->app->render("base/about.{$this->app['locale']}.html.twig",
        [
            // Years together of Marie and Mathieu
            // ("Today" - "2004/10/15 16:00:00") / (60 * 60 * 24 * 365.25)
            'yearsMMM' => (int) round((time() - 1097769600) / 31557600),
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
            if ([] === $errors)
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
}
