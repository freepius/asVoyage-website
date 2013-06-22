<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint,
    Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Exception\UnexpectedTypeException;


/**
 * Unused Validator checks if a given field contains a unused value.
 */
class UnusedValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint->mongoCollection instanceof \MongoCollection) {
            throw new UnexpectedTypeException($constraint->mongoCollection, '\MongoCollection');
        }

        if (! is_string($constraint->field)) {
            throw new UnexpectedTypeException($constraint->field, 'string');
        }

        if (! is_string($constraint->id)) {
            throw new UnexpectedTypeException($constraint->id, 'string');
        }

        $query = [$constraint->field => $value];

        // Useful to not count a specific entity
        if ($constraint->id)
        {
            $query['_id'] = ['$ne' => new \MongoId($constraint->id)];
        }

        if (0 !== $constraint->mongoCollection->count($query, 1))
        {
            $this->context->addViolation($constraint->message);
        }
    }
}
