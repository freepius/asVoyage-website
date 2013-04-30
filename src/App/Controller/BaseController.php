<?php

namespace App\Controller;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request;


class BaseController implements ControllerProviderInterface
{
    public function home(Application $app)
    {
        return $app['twig']->render('home.html.twig');
    }

    public function about(Application $app)
    {
        return $app['twig']->render("about.{$app['locale']}.html.twig", array
        (
            // Years together of Marie and Mathieu
            // ("Today" - "2004/10/15 16:00:00") / (60 * 60 * 24 * 365.25)
            'yearsMMM' => (int) round((time() - 1097769600) / 31557600),
        ));
    }

    public function switchLocale(Application $app, Request $request, $locale)
    {
        $app['session']->set('locale', $locale);

        return $app->redirect('/home');
    }

    public function connect(Application $app)
    {
        $base = $app['controllers_factory'];

        // Home
        $base->get('/',     array($this, 'home'));
        $base->get('/home', array($this, 'home'));

        $base->get('/about', array($this, 'about'));

        $base->get('/switch-locale/{locale}', array($this, 'switchLocale'))
            ->assert('locale', 'en|fr');

        return $base;
    }
}
