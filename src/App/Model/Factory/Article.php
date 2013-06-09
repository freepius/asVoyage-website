<?php

namespace App\Model\Factory;

use Symfony\Component\Validator\Constraints as Assert,
    Symfony\Component\Validator\ValidatorInterface,
    App\Model\Repository\MongoRepository,
    App\Validator\Constraints\Unused,
    App\Util\StringUtil;


class Article extends EntityFactory
{
    protected $repository;

    public function __construct(ValidatorInterface $validator, MongoRepository $repository)
    {
        parent::__construct($validator);
        $this->repository = $repository;
    }

    /**
     * @param  string  $tags  Tags separated by ','
     *
     * Return an array (without duplicates) of tags as strings (non-empty, natural sorted).
     */
    protected static function normalizeTags($tags)
    {
        // A tag is lowercase ; its first letter us uppercase
        $processTag = function ($tag) {
            return ucfirst(strtolower(trim($tag)));
        };

        $tags = explode(',', $tags);

        // No blank value
        $tags = array_filter(array_map($processTag, $tags));

        natsort($tags);

        // Remove duplicates + rearrange
        return array_values(array_unique($tags));
    }

    /**
     * @{inheritdoc}
     */
    public function instantiate()
    {
        return array
        (
            'title'       => '',
            'slug'        => '',
            'pubdatetime' => date('Y-m-d H:i:s'), // now
            'ispublished' => true,
            'becommented' => true,
            'text'        => '',
            'summary'     => '',
            'tags'        => array(),
            'comments'    => array(),
        );
    }

    /**
     * @{inheritdoc}
     */
    protected function processInputData(array $data)
    {
        return array
        (
            'title'       => $data['title'],
            'slug'        => StringUtil::slugify($data['slug']),
            'pubdatetime' => $data['pubdatetime'] ?: date('Y-m-d H:i:s'), // empty 'pubdatetime' === now
            'ispublished' => array_key_exists('ispublished', $data),
            'becommented' => array_key_exists('becommented', $data),
            'text'        => $data['text'],
            'summary'     => $data['summary'],
            'tags'        => self::normalizeTags($data['tags']),
        );
    }

    /**
     * @{inheritdoc}
     */
    protected function getConstraints(array $entity)
    {
        /**
         * The slug must not already be used,
         * beacause it is the human-readable ID of an article.
         */
        $slugUnused = new Unused(array
        (
            'mongoCollection' => $this->repository->getCollection(),
            'field'           => 'slug',
            'id'              => (string) @ $entity['_id'],
        ));

        return new Assert\Collection(array
        (
            'title'       => new Assert\NotBlank(),
            'slug'        => array(new Assert\NotBlank(), $slugUnused),
            'pubdatetime' => new Assert\DateTime(),
            'ispublished' => null,
            'becommented' => null,
            'text'        => new Assert\NotBlank(),
            'summary'     => null,
            'tags'        => null,
        ));
    }
}
