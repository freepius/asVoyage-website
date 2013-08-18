<?php

namespace App;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\Exception\HttpException,
    Silex\Route as BaseRoute;


class Route extends BaseRoute
{
    public function mustBeAjax()
    {
        return $this->before(function(Request $request)
        {
            if (! $request->isXmlHttpRequest()) { throw new HttpException(404); }
        });
    }
}
