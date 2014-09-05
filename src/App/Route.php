<?php

namespace App;

use Silex\Route as BaseRoute,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\Exception\HttpException;


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
