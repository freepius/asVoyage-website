<?php

namespace App\Model\Factory;

use Freepius\Model\EntityFactory;
use Freepius\Util\Geo;
use Freepius\Util\StringUtil;
use Symfony\Component\Validator\Constraints as Assert;

class Media extends EntityFactory
{
    /**
     * @{inheritdoc}
     */
    public function instantiate() { return []; } // not used

    /**
     * @{inheritdoc}
     */
    protected function processInputData(array $data)
    {
        return [
            'caption'      => StringUtil::cleanText($data['caption'], true),
            'creationDate' => trim($data['creationDate']),
            'geoCoords'    => trim($data['geoCoords']),
            'tags'         => StringUtil::normalizeTags($data['tags']),
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function getConstraints(array $entity)
    {
        return new Assert\Collection([
            'caption'      => new Assert\Length(['max' => 200]),
            'creationDate' => new Assert\DateTime(),
            'geoCoords'    => new Assert\Regex(['pattern' => Geo::LAT_LON_DD_PATTERN]),
            'tags'         => null,
        ]);
    }
}
