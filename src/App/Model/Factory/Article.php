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
     * @{inheritdoc}
     */
    public function instantiate()
    {
        return [
            'title'         => '',
            'slug'          => '',
            'pubDatetime'   => date('Y-m-d H:i:s'), // now
            'isPublished'   => true,
            'beCommented'   => true,
            'text'          => '',
            'summary'       => '',
            'tags'          => [],
            'comments'      => [],
            'countComments' => 0,
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function processInputData(array $data)
    {
        return [
            'title'       => trim($data['title']),
            'slug'        => StringUtil::slugify($data['slug']),
            'pubDatetime' => $data['pubDatetime'] ?: date('Y-m-d H:i:s'), // empty === now
            'isPublished' => array_key_exists('isPublished', $data),
            'beCommented' => array_key_exists('beCommented', $data),
            'text'        => $data['text'],
            'summary'     => $data['summary'],
            'tags'        => StringUtil::normalizeTags($data['tags']),
        ];
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
        $slugUnused = new Unused([
            'mongoCollection' => $this->repository->getCollection(),
            'field'           => 'slug',
            'id'              => (string) @ $entity['_id'],
        ]);

        return new Assert\Collection([
            'title'       => new Assert\NotBlank(),
            'slug'        => [new Assert\NotBlank(), $slugUnused],
            'pubDatetime' => new Assert\DateTime(),
            'isPublished' => null,
            'beCommented' => null,
            'text'        => new Assert\NotBlank(),
            'summary'     => null,
            'tags'        => null,
        ]);
    }
}
