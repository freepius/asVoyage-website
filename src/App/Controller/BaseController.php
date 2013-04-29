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
        return $app['twig']->render("about.{$app['locale']}.html.twig");
    }

    public function switchLocale(Application $app, Request $request, $locale)
    {
        $app['session']->set('locale', $locale);

        return $app->redirect('/home');
    }

    public function connect(Application $app)
    {
        $base = $app['controllers_factory'];

        $base->get('/home', array($this, 'home'));

        $base->get('/about', array($this, 'about'));

        $base->get('/switch-locale/{locale}', array($this, 'switchLocale'))
            ->assert('locale', 'en|fr');

        return $base;
    }
}
