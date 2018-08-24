<?php

namespace App\GeoJson\Model;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\ModelFactory;

class Polygon implements ModelInterface
{
    /**
     * @var Coordinates[]
     */
    public $coordinates = [];

    public static function validate(array $data): void
    {
        if (!\is_array($data['coordinates'] ?? null)) {
            throw new InvalidDataException('Invalid "coordinates" property');
        }

        // Coordinates of a Polygon are an array of linear ring, we do not support holes
        if (1 !== \count($data['coordinates'])) {
            throw new InvalidDataException('Polygon with holes is not supported');
        }

        if (4 > \count($data['coordinates'][0])) {
            throw new InvalidDataException('Polygon does not define a closed linear ring');
        }

        foreach ($data['coordinates'][0] as $index => $coordinate) {
            ModelFactory::ensureSingleCoordinatePosition($coordinate, $index);
        }

        if (reset($data['coordinates'][0]) !== end($data['coordinates'][0])) {
            throw new InvalidDataException('Polygon does not define a closed linear ring, first and last point MUT be identical');
        }
    }

    public static function create(array $data)
    {
        self::validate($data);

        $i = new self();
        foreach ($data['coordinates'] as $coordinate) {
            $i->coordinates[] = Coordinates::build($coordinate[0], $coordinate[1]);
        }

        return $i;
    }
}