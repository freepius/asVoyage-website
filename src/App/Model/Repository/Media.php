<?php

namespace App\Model\Repository;

use App\Exception\MediaElementNotFound;
use Freepius\Model\MongoRepository;
use Freepius\Util\Group;
use Freepius\Util\StringUtil;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Summary :
 *  -> __construct
 *  -> init
 *  -> getById
 *  -> deleteById
 *  -> store
 *  -> collectGarbage
 *  -> deleteLocal      [protected]
 *  -> filter           [protected]
 *  -> count
 *  -> find
 *  -> listTags
 *  -> countByYearMonth
 *  -> randomImagesByTags
 *  -> getGeoJsFile
 *  -> clearCacheDir
 */
class Media extends MongoRepository
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
     * Create some indexes.
     */
    protected function init()
    {
        $this->collection->ensureIndex('creationDate');
        $this->collection->ensureIndex('tags');
    }

    /**
     * From its id., find a media element (temporary or not).
     * If [$id, $isTmp] doesn't match any media, throw a MediaElementNotFound exception.
     */
    public function getById($id, $isTmp = false)
    {
        $id    = new \MongoId($id);
        $isTmp = (bool) $isTmp;

        $media = $this->collection->findOne(['_id' => $id, 'isTmp' => $isTmp]);

        if (! is_array($media)) {
            throw new MediaElementNotFound("id = $id and isTmp = $isTmp");
        }

        return $media;
    }

    /**
     * From its id., delete a media element (temporary or not) in MongoCollection and on filesystem.
     * Return true if the operation succeed ; false, else.
     */
    public function deleteById($id, $isTmp = false)
    {
        try {
            $media = $this->getById($id, $isTmp);

            $this->deleteLocal($media['content']);

            return parent::deleteById($media['_id']);
        }
        catch (MediaElementNotFound $e) { return false; }
    }

    /**
     * Store a media element (temporary or not).
     */
    public function store(array & $entity, $isTmp = false)
    {
        // Useful for garbage collection
        $entity['insertionTime'] = time();

        $entity['isTmp'] = (bool) $isTmp;

        return parent::store($entity);
    }

    /**
     * Remove all old uploaded, unvalidated elements (older than 24 hours).
     */
    public function collectGarbage()
    {
        $oneDayBefore = time() - 86400; // now - 24 hours

        $query = ['isTmp' => true, 'insertionTime' => ['$lt' => $oneDayBefore]];

        $elements = $this->collection->find($query, ['content']);

        foreach ($elements as $e) { $this->deleteLocal($e['content']); }

        $this->collection->remove($query);
    }

   /**
     * Delete $file in media folder and subfolders.
     */
    protected function deleteLocal($file)
    {
        $mediaPath = $this->webPath.'/media';

        foreach (['/', '/web/', '/thumb/'] as $folder)
        {
            @ unlink($mediaPath.$folder.$file);
        }
    }

    /**
     * Return some filters for querying media elements :
     *    -> not the temporary elements
     *    -> between two creation datetime ("from" and "to", included)
     *    -> having all "tags"
     *    -> having geo. coords (if "geo" is true)
     *    -> being of a certain "type"
     *
     * Null (or equivalent) filter are ignored.
     *
     * $filters =
     * {
     *      from : The old bound date   ; format = Y-m-d (H:i:s)
     *      to   : The young bound date ; format = Y-m-d (H:i:s)
     *      tags : An array of strings
     *      geo  : If true, entry must have geo. coords
     *      type : A string
     * }
     *
     * @return array
     */
    protected function filter(array $filters = [])
    {
        $query = ['isTmp' => false];

        $from      = @ $filters['from'];
        $to        = @ $filters['to'];
        $tags      = @ $filters['tags'];
        $havingGeo = (bool) @ $filters['geo'];
        $type      = @ $filters['type'];

        if ($from)      { $query['creationDate']['$gte'] = $from; }
        if ($to)        { $query['creationDate']['$lte'] = $to; }
        if ($tags)      { $query['tags']['$all'] = $tags; }
        if ($havingGeo) { $query['geoCoords']['$ne'] = ''; }
        if ($type)      { $query['mainType'] = $type; }

        return $query;
    }

    /**
     * Count media elements depending on some filters { @see filter }.
     *
     * @return integer
     */
    public function count(array $filters = [])
    {
        return $this->collection->count($this->filter($filters));
    }

    /**
     * Search media elements depending on some filters { @see filter } and :
     *
     *    -> sorted DESC by creation datetime (ie, younger first)
     *    -> with $limit occurences max.
     *    -> skipping the $skip-th first found
     *
     * @param integer $limit    Must be >= 0
     * @param integer $skip     Must be >= 0
     *
     * @return \MongoCursor
     */
    public function find($limit = 0, $skip = 0, array $filters = [])
    {
        $media = $this->collection->find($this->filter($filters))
            ->sort(['creationDate' => -1]); // desc = younger first

        if ($skip > 0)  { $media->skip($skip); }
        if ($limit > 0) { $media->limit($limit); }

        return $media;
    }

    /**
     * @see \Freepius\Util\Group::byTags
     */
    public function listTags()
    {
        return Group::byTags(
            $this->collection->find(['isTmp' => false], ['tags' => 1])
        );
    }

    /**
     * @see \Freepius\Util\Group::byYearMonth
     */
    public function countByYearMonth()
    {
        return Group::byYearMonth(
            $this->collection->find(['isTmp' => false], ['creationDate' => 1]),
            'creationDate'
        );
    }

    /**
     * Return $limit random images containing all $tags.
     *
     * Warning : use this function for a small set of images !
     * Indeed, the random selection of $limit images is realized from a PHP array
     * (and not directly by MongoDB engine).
     */
    public function randomImagesByTags(array $tags, $limit = null)
    {
        $media = iterator_to_array($this->collection->find(
            $this->filter(['tags' => $tags, 'type' => 'image'])
        ));

        shuffle($media);

        return array_slice(array_values($media), 0, $limit);
    }

    /**
     * Select all **images** having geo. coords
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
            $content = $this->twig->render('media/geo-elements.js.twig', [
                'elements' => $this->find(0, 0, [
                    'type' => 'image', 'geo' => true,
                    'from' => $from, 'to' => $to
                ])
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
