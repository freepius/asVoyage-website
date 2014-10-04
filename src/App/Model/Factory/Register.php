<?php

namespace App\Model\Factory;

use Freepius\Model\EntityFactory;
use Freepius\Util\Geo;
use Symfony\Component\Validator\Constraints as Assert;

class Register extends EntityFactory
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
        $data = array_map('trim', $data);

        return [
            '_id'         => @ $data[0], // the unique datetime !
            'geoCoords'   => @ $data[1],
            'temperature' => is_numeric(@ $data[2]) ? (int) $data[2] : null,
            'meteo'       => is_numeric(@ $data[3]) ? (int) $data[3] : null,
            'message'     => @ $data[4],
        ];
    }

    /**
     * @{inheritdoc}
     */
    protected function getConstraints(array $entity)
    {
        return new Assert\Collection([
            '_id'         => [new Assert\NotBlank(), new Assert\DateTime()],
            'geoCoords'   => new Assert\Regex(['pattern' => Geo::LAT_LON_DD_PATTERN]),
            'temperature' => [new Assert\Type('integer'), new Assert\Range(['min' => -100, 'max' => 100])],
            'meteo'       => [new Assert\Type('integer'), new Assert\Range(['min' => 1, 'max' => 7])],
            'message'     => new Assert\Length(['max' => 500]),
        ]);
    }
}
