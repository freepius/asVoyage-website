<?php

namespace App\Model\Repository;

use Symfony\Component\Filesystem\Filesystem,
    App\Util\StringUtil;


/**
 * Summary :
 *  -> __construct
 *  -> store
 *  -> filter   [protected]
 *  -> find
 *  -> getGeoJsFile
 *  -> clearCacheDir
 */
class Register extends MongoRepository
{
    protected $twig;
    protected $webPath;
    protected $cacheDir;

    public function __construct(\MongoCollection $collection, $twig, $webPath, $cacheDir)
    {
        parent::__construct($collection);
        $this->twig     = $twig;
        $this->webPath  = $webPath;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Store an entity : if already exists, update it ; else, create it.
     * Return :
     *  -> -1 in case of failure
     *  ->  0 in case of creation
     *  ->  1 in case of updating
     */
    public function store(array & $entity)
    {
        $id = $entity['_id'];

        unset($entity['_id']);

        $result = $this->collection->update
        (
            ['_id'    => $id],
            ['$set'   => $entity],
            ['upsert' => true]
        );

        return $result['err'] !== null ? -1 : (int) @ $result['updatedExisting'];
    }

    /**
     * Return some filters for querying register entries :
     *    -> between two datetime ("from" and "to" included)
     *    -> having geo. coords (if "geo" is true)
     *
     * Null (or equivalent) filter are ignored.
     *
     * $filters =
     * {
     *      from : The old bound date   ; format = Y-m-d (H:i:s)
     *      to   : The young bound date ; format = Y-m-d (H:i:s)
     *      geo  : If true, entry must have geo. coords
     * }
     *
     * @return array
     */
    protected function filter(array $filters = [])
    {
        $query = [];

        $from      = @ $filters['from'];
        $to        = @ $filters['to'];
        $havingGeo = (bool) @ $filters['geo'];

        if ($from)      { $query['_id']['$gte'] = $from; }
        if ($to)        { $query['_id']['$lte'] = $to; }
        if ($havingGeo) { $query['geoCoords']['$ne'] = ''; }

        return $query;
    }

    /**
     * Search register entries depending on some filters { @see filter } and :
     *
     *    -> sorted DESC by datetime (ie by _id)
     *    -> with $limit occurences max.
     *
     * @param integer $limit    Must be >= 0
     *
     * @return \MongoCursor
     */
    public function find($limit = 0, array $filters = [])
    {
        $query = $this->filter($filters);

        $entries = $this->collection->find($query)
            ->sort(['_id' => -1]); // desc = younger first

        if ($limit > 0) { $entries->limit($limit); }

        return $entries;
    }

    /**
     * Select all entries having geo. coords
     * (eventually between two datetime, $from and $to included).
     *
     * Then, generate a *public javascript file* including these entries.
     * Finally, return its path (relative to web dir).
     *
     * If the js file already exists, return directly its path.
     */
    public function getGeoJsFile($from = null, $to = null)
    {
        $fs = new Filesystem();

        $filepath = sprintf('/%s/from-%s-to-%s.js',
            $this->cacheDir,
            StringUtil::slugify($from),
            StringUtil::slugify($to)
        );

        if (! $fs->exists($this->webPath.$filepath))
        {
            $content = $this->twig->render('register/geo-entries.js.twig', [
                'entries' => $this->find(0, ['geo' => true, 'from' => $from, 'to' => $to])
            ]);

            $fs->dumpFile($this->webPath.$filepath, $content);
        }

        return $filepath;
    }

    public function clearCacheDir()
    {
        $fs = new Filesystem();
        $fs->remove($this->webPath.'/'.$this->cacheDir);
        $fs->mkdir ($this->webPath.'/'.$this->cacheDir);
    }
}
