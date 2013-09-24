<?php

namespace App\HttpCache;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    App\Model\Repository\MongoRepository;


class MongoCache extends MongoRepository
{
    /**
     * Create some indexes
     */
    protected function init()
    {
        $this->collection->ensureIndex('dependencies');
    }

    /**
     * Create a http response ready to be :
     *  -> retrieved from the http cache
     *  -> OR cached if none exists.
     *
     *  Example of use:
     *      // $cacheEngine is an instance of App\HttpCache\MongoCache
     *
     *      $response = $cacheEngine->response('my.cache.key', ['dep_1', 'dep_2']);
     *
     *      if ($response->isNotModified($request))
     *      {
     *          return $response;
     *      }
     *      else
     *      {
     *          ...
     *      }
     */
    public function response(Request $request, $key, array $dependencies = [])
    {
        $key   = $request->getLocale().'.'.$key;
        $query = ['_id' => $key];

        // Exists there a valid "MongoDB cache" for this $key ?
        $cache = $this->collection->findOne($query, ['lastModified' => 1]);

        // No => so create it !
        if (! @ $cache['lastModified'])
        {
            $this->collection->update(
                $query,
                ['$set' => [
                    'dependencies' => $dependencies,
                    'lastModified' => date('r'),
                ]],
                ['upsert' => 1]
            );
        }

        return (new Response())
            ->setPublic()
            ->setLastModified(new \DateTime(@ $cache['lastModified']));
    }

    /**
     * Drop caches that match with at least one of $dependencies.
     * @param array|string $dependencies
     */
    public function drop($dependencies)
    {
        $this->collection->remove(['dependencies' => ['$in' => (array) $dependencies]]);
    }
}
