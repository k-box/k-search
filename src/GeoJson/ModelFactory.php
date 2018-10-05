<?php

namespace App\GeoJson;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\Exception\MalformedJsonDataException;
use App\GeoJson\Exception\UnsupportedTypeException;
use App\GeoJson\Model\ModelInterface;
use App\GeoJson\Model\Point;
use App\GeoJson\Model\Polygon;

abstract class ModelFactory
{
    /**
     * @throws InvalidDataException
     * @throws MalformedJsonDataException
     * @throws UnsupportedTypeException
     */
    public static function buildFromJson(string $jsonData): ModelInterface
    {
        $data = json_decode($jsonData, true);
        if (null === $data) {
            throw new MalformedJsonDataException(json_last_error_msg());
        }

        $type = $data['type'] ?? null;
        if (!$type) {
            throw new InvalidDataException('Invalid "type" property');
        }

        switch ($type) {
            case Point::getType():
                return Point::create($data);
            case Polygon::getType():
                return Polygon::create($data);
        }

        throw new UnsupportedTypeException($type);
    }

    public static function ensureSingleCoordinatePosition(array $coordinates, int $pos = 0): void
    {
        if (2 !== \count($coordinates)) {
            throw new InvalidDataException(sprintf('Coordinates of point #%d are incorrect', $pos));
        }

        if (!\is_numeric($coordinates[0]) || !\is_numeric($coordinates[1])) {
            throw new InvalidDataException(sprintf('Coordinates point #%d are not numeric', $pos));
        }
    }
}
