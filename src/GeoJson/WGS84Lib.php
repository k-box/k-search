<?php

namespace App\GeoJson;

use App\GeoJson\Exception\InvalidWGS84DataException;
use App\GeoJson\Exception\UnsupportedTypeException;
use App\GeoJson\Model\ModelInterface;
use App\GeoJson\Model\Point;
use App\GeoJson\Model\Polygon;
use App\GeoJson\Model\Position;

abstract class WGS84Lib
{
    public const MAX_LON = 180;
    public const MIN_LON = -180;
    public const MAX_LAT = 90;
    public const MIN_LAT = -90;

    /**
     * Validates the given model to fit the WGS84 coordinate system.
     *
     * @throws InvalidWGS84DataException
     * @throws UnsupportedTypeException
     */
    public static function validate(ModelInterface $model): void
    {
        switch (\get_class($model)) {
            case Polygon::class:
                self::validatePolygon($model);
                break;
            case Point::class:
                self::validatePoint($model);
                break;
            default:
                throw new UnsupportedTypeException($model::getType());
        }
    }

    /**
     * Validate the given polygon to contain WGS84 coordinates.
     *
     * @throws InvalidWGS84DataException
     */
    public static function validatePolygon(Polygon $polygon): void
    {
        // Ensure points are not external to the max-pane coordinates
        foreach ($polygon->getExteriorRing() as $index => $position) {
            if (!self::isValidPosition($position)) {
                throw new InvalidWGS84DataException(sprintf('Invalid WGS84 lon/lat coordinates (polygon external-ring, point #%d)', $index));
            }
        }
    }

    /**
     * Validate the given point to contain WGS84 coordinates.
     *
     * @throws InvalidWGS84DataException
     */
    public static function validatePoint(Point $point): void
    {
        if (!self::isValidPosition($point->getPosition())) {
            throw new InvalidWGS84DataException('Invalid WGS84 lon/lat coordinates');
        }
    }

    /**
     * Checks if the given position is valid, thus inside the WGS84 coordinates plane.
     */
    public static function isValidPosition(Position $position): bool
    {
        return
            $position->lon >= self::MIN_LON && $position->lon <= self::MAX_LON &&
            $position->lat >= self::MIN_LAT && $position->lat <= self::MAX_LAT;
    }
}
