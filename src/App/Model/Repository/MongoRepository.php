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
        $id = new \MongoId($id);

        $result = $this->collection->remove(['_id' => $id]);

        return $result['err'] === null;
    }

    /**
     * Store an entity : if alreay exists, update it ; else, create it.
     * $entity may be changed after create/update operation.
     * Finally, return true if the operation succeed ; false, else.
     */
    public function store(array & $entity)
    {
        // update
        if ($id = @ $entity['_id'])
        {
            unset($entity['_id']);

            $result = $this->collection->update(['_id' => $id], ['$set' => $entity]);
        }
        // create
        else {
            $entity['_id'] = new \MongoId();

            $result = $this->collection->insert($entity);
        }

        return $result['err'] === null;
    }
}
