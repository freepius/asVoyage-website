<?php

namespace App\Controller;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request;


class BaseController implements ControllerProviderInterface
{
    public function home(Application $app)
    {
        return $app['twig']->render('home.html.twig', array(
            'currentNav' => 'home'
        ));
    }

    public function switchLocale(Application $app, Request $request, $locale)
    {
        $app['session']->set('locale', $locale);

        $referer = $request->headers->get('referer');

        return $app->redirect($referer ?: $app['url_generator']->generate('base.home'));
    }

    public function connect(Application $app)
    {
        $base = $app['controllers_factory'];

        $base->get('/', array($this, 'home'))->bind('base.home');

        $base->get('/switch-locale/{locale}', array($this, 'switchLocale'))
            ->bind('base.switchLocale')
            ->assert('locale', 'en|fr');

        return $base;
    }
}
