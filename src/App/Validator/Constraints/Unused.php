<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;


/**
 * Constraint for the Unused validator
 *
 * @Annotation
 */
class Unused extends Constraint
{
    public $message = 'This value is already used.';
    public $mongoCollection = null;
    public $field = '';
    public $id = '';

    public function getRequiredOptions()
    {
        return array('mongoCollection', 'field');
    }
}
