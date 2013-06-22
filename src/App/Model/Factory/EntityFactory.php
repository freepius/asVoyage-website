<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\ValidatorInterface;


abstract class EntityFactory
{
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Instantiate an entity and return it as array.
     */
    abstract public function instantiate();

    /**
     * Bind $inputData to $entity and return an array whose :
     * keys = fields and values = error messages.
     */
    public function bind(array & $entity, array $inputData)
    {
        $inputData = $this->processInputData($inputData);

        $violations = $this->validator->validateValue($inputData, $this->getConstraints($entity));

        $entity = $this->merge($entity, $inputData);

        $errors = [];

        // Turn violations in [field => error]+
        foreach ($violations as $violation)
        {
            $field = $violation->getPropertyPath();         // eg: "[My field]"
            $field = substr($field, 1, strlen($field)-2);   //  => "My field"

            $errors[$field] = $violation->getMessage();
        }

        return $errors;
    }

    protected function merge(array $entity, array $inputData)
    {
        return array_merge($entity, $inputData);
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
