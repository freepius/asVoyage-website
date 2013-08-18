<?php

namespace App;


class Application extends \Silex\Application
{
    use \Silex\Application\TranslationTrait,
        \Silex\Application\TwigTrait,
        \Silex\Application\SwiftmailerTrait;

    // Security shortcuts

    public function isGranted($attributes, $object = null)
    {
        return $this['security']->isGranted($attributes, $object);
    }

    // Session shortcuts

    public function getSession($name, $default = null)
    {
        return $this['session']->get($name, $default);
    }

    public function setSession($name, $value)
    {
        return $this['session']->set($name, $value);
    }

    public function removeSession($name)
    {
        return $this['session']->remove($name);
    }

    public function addFlash($type, $message)
    {
        return $this['session']->getFlashBag()->add($type, $message);
    }
}
