<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\Constraints as Assert;


class Comment extends EntityFactory
{
    protected static function cleanText($text)
    {
        $text = trim($text);
        $text = preg_replace('/\r\n?/' , "\n"    , $text);  // unix nl
        $text = preg_replace('/\n{4,}/', "\n\n\n", $text);  // max. 3 nl

        return $text;
    }

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
            'name'     => trim($data['name']),
            'text'     => self::cleanText($data['text']),
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
