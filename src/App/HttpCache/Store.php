<?php

namespace App\HttpCache;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpCache\Store as BaseStore;


class Store extends BaseStore
{
    /**
     * The cache key is generated from URI and Locale !
     */
    protected function getCacheKey(Request $request)
    {
        if (isset($this->keyCache[$request])) {
            return $this->keyCache[$request];
        }

        return $this->keyCache[$request] = 'md'.$request->getLocale().sha1($request->getUri());
    }
}
