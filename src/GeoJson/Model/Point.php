<?php

namespace App\GeoJson\Model;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\ModelFactory;

class Point implements ModelInterface
{
    /**
     * @var Coordinates
     */
    public $coordinates;

    public static function validate(array $data): void
    {
        if (!\is_array($data['coordinates'] ?? null)) {
            throw new InvalidDataException('Invalid "coordinates" property');
        }

        // For type "Point", the "coordinates" member is a single position.
        ModelFactory::ensureSingleCoordinatePosition($data['coordinates']);
    }

    public static function create(array $data)
    {
        self::validate($data);

        $i = new self();
        $i->coordinates = Coordinates::build($data['coordinates'][0], $data['coordinates'][1]);

        return $i;
    }
}