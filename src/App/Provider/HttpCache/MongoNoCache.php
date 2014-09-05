<?php

namespace App\Provider\HttpCache;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;


class MongoNoCache extends MongoCache
{
    public function response(Request $request, $key, array $dependencies = [])
    {
        return new Response();
    }
}
