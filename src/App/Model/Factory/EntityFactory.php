<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\ValidatorInterface,
    App\Model\Repository\MongoRepository;


abstract class EntityFactory
{
    protected $validator;
    protected $repository;

    public function __construct(ValidatorInterface $validator, MongoRepository $repository)
    {
        $this->validator = $validator;
        $this->repository = $repository;
    }

    /**
     * Instantiate an entity and return it as array.
     */
    abstract public function instantiate();

    /**
     * Bind $inputData to $entity and return a
     * \Symfony\Component\Validator\ConstraintViolationListInterface list.
     */
    public function bind(array & $entity, array $inputData)
    {
        $inputData = $this->processInputData($inputData);

        $violations = $this->validator->validateValue($inputData, $this->getConstraints($entity));

        $entity = array_merge($entity, $inputData);

        return $violations;
    }

    /**
     * Process the input data before to validate and store them.
     *
     * @return  array  The processed data
     */
    abstract protected function processInputData(array $data);

    /**
     * @return  \Symfony\Component\Validator\Constraints\Collection  Constraints on the input data
     */
    abstract protected function getConstraints(array $entity);
}
