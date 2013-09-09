<?php

namespace App\Model\Repository;


/**
 * Summary :
 *  -> store
 *  -> filter   [protected]
 *  -> find
 */
class Register extends MongoRepository
{
    /**
     * Store an entity : if alreay exists, update it ; else, create it.
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
     *      from :  The old bound date   ; format = Y-m-d (H:i:s)
     *      to   :  The young bound date ; format = Y-m-d (H:i:s)
     *      geo  :  If true, entry must have geo. coords
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
}
