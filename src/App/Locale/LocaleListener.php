<?php

namespace App\Locale;

use App\Application,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpKernel\KernelEvents,
    Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Initializes the Application locale based on session,
 * and the Request locale based on Application locale.
 */
class LocaleListener implements EventSubscriberInterface
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->app->hasSession('locale'))
        {
            $this->app['locale'] = $this->app->getSession('locale');
        }

        $event->getRequest()->setLocale($this->app['locale']);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 16],
        ];
    }
}