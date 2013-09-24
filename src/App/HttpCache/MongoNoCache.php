<?php

namespace App\HttpCache;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request;


class MongoNoCache extends MongoCache
{
    public function response(Request $request, $key, array $dependencies = [])
    {
        return new Response();
    }
}
