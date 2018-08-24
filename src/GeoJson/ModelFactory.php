<?php

namespace App\GeoJson;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\Exception\MalformedJsonDataException;
use App\GeoJson\Exception\UnsupportedTypeException;

class ModelFactory
{
    public const TYPE_POINT = 'Point';
    public const TYPE_POLYGON = 'Polygon';

    public const SUPPORTED_TYPES = [
        self::TYPE_POINT,
        self::TYPE_POLYGON,
    ];

    /**
     * @throws InvalidDataException
     * @throws MalformedJsonDataException
     * @throws UnsupportedTypeException
     */
    public static function buildFromJson(string $jsonData): string
    {
        $data = json_decode($jsonData, true);
        if (null === $data) {
            throw new MalformedJsonDataException(json_last_error_msg());
        }

        $type = $data['type'] ?? null;
        if (!$type) {
            throw new InvalidDataException('Invalid "type" property');
        }

        $coordinates = $data['coordinates'] ?? null;

        if (!\is_array($coordinates)) {
            throw new InvalidDataException('Invalid "coordinates" property');
        }

        switch ($type) {
            case self::TYPE_POINT:
                // For type "Point", the "coordinates" member is a single position.
                self::ensureSinglePosition($data['coordinates']);

                break;
            case self::TYPE_POLYGON:
                // Coordinates of a Polygon are an array of linear ring, we do not support holes
                if (1 !== \count($data['coordinates'])) {
                    throw new InvalidDataException('Polygon with holes is not supported');
                }

                if (4 > \count($data['coordinates'][0])) {
                    throw new InvalidDataException('Polygon does not define a closed linear ring');
                }

                foreach ($data['coordinates'][0] as $index => $coordinate) {
                    self::ensureSinglePosition($coordinate, $index);
                }

                if (reset($data['coordinates'][0]) !== end($data['coordinates'][0])) {
                    throw new InvalidDataException('Polygon does not define a closed linear ring, first and last point MUT be identical');
                }

                break;
            default:
                throw new UnsupportedTypeException($type);
        }

        return $type;
    }

    private static function ensureSinglePosition(array $data, int $pos = 0): void
    {
        if (2 !== \count($data)) {
            throw new InvalidDataException(sprintf('Coordinates of point #%d are incorrect', $pos));
        }

        if (!\is_numeric($data[0]) || !\is_numeric($data[1])) {
            throw new InvalidDataException(sprintf('Coordinates point #%d are not numeric', $pos));
        }
    }
}
