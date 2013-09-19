<?php

namespace App\HttpCache;

use Symfony\Component\HttpFoundation\Response;


class MongoNoCache extends MongoCache
{
    public function response($key, array $dependencies = [])
    {
        return new Response();
    }
}
