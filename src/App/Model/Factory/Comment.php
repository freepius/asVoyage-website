<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\Constraints as Assert;


class Comment extends EntityFactory
{
    /**
     * @{inheritdoc}
     */
    public function instantiate()
    {
        return array
        (
            'name'     => '',
            'text'     => '',
            'datetime' => '',
        );
    }

    /**
     * @{inheritdoc}
     */
    protected function processInputData(array $data)
    {
        return array
        (
            'name'     => $data['name'],
            'text'     => $data['text'],
            'datetime' => date('Y-m-d H:i:s'), // now
        );
    }

    /**
     * @{inheritdoc}
     */
    protected function getConstraints(array $entity)
    {
        return new Assert\Collection(array
        (
            'name'     => new Assert\NotBlank(),
            'text'     => new Assert\NotBlank(),
            'datetime' => array(new Assert\NotBlank(), new Assert\DateTime()),
        ));
    }
}
