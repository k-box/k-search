<?php

namespace App\GeoJson\Model;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\ModelFactory;

class Point implements ModelInterface
{
    /**
     * @var Position
     */
    private $position;

    public static function validate(array $data): void
    {
        if (self::getType() !== $data['type'] ?? null) {
            throw new InvalidDataException('Invalid "type" property for a Polygon');
        }

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
        $i->position = Position::build($data['coordinates'][0], $data['coordinates'][1]);

        return $i;
    }

    public function jsonSerialize()
    {
        return [
            'type' => self::getType(),
            'coordinates' => $this->position->jsonSerialize(),
        ];
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public static function getType(): string
    {
        return 'Point';
    }
}
