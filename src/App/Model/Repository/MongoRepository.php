<?php

namespace App\Model\Repository;


abstract class MongoRepository
{
    protected $collection;

    public function __construct(\MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Delete an entity from its id.
     * Return true if the operation succeed ; false, else.
     */
    public function deleteById($id)
    {
        if (! $id instanceof \MongoId) { $id = new \MongoId($id); }

        $result = $this->collection->remove(array('_id' => $id));

        return $result['n'] > 0;
    }

    /**
     * Store an entity : if alreay exists, update it ; else, create it.
     * $entity may be changed after create/update operation.
     * Finally, return true if the operation succeed ; false, else.
     */
    public function store(array & $entity)
    {
        $result = $this->collection->save($entity);

        return $result['n'] > 0;
    }
}
