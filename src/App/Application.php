<?php

namespace App;


class Application extends \Silex\Application
{
    use \Silex\Application\TranslationTrait,
        \Silex\Application\TwigTrait;


    public function isGranted($attributes, $object = null)
    {
        return $this['security']->isGranted($attributes, $object);
    }

    public function getSession($name, $default = null)
    {
        return $this['session']->get($name, $default);
    }

    public function setSession($name, $value)
    {
        return $this['session']->set($name, $value);
    }

    public function addFlash($type, $message)
    {
        return $this['session']->getFlashBag()->add($type, $message);
    }
}
