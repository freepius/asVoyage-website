<?php

namespace App\HttpCache;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpCache\Store as BaseStore;


/**
 * With HttpKernel 2.3, it is not possible to extend the BaseStore class
 * (because of private methods and properties).
 * With HttpKernel 2.3-dev, yes !
 * TODO: Follow this.
 */
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
