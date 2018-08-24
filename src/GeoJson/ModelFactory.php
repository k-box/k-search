<?php

namespace App\GeoJson;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\Exception\MalformedJsonDataException;
use App\GeoJson\Exception\UnsupportedTypeException;
use App\GeoJson\Model\Point;
use App\GeoJson\Model\Polygon;

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

        switch ($type) {
            case self::TYPE_POINT:
                Point::create($data);

                break;
            case self::TYPE_POLYGON:
                Polygon::create($data);
                break;
            default:
                throw new UnsupportedTypeException($type);
        }

        return $type;
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
