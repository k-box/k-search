<?php

namespace Pnz\GeoJSON;

class GeoJSONBuilder
{
    /**
     * @return GeoJSONGeometry
     */
    public static function buildGeoJSONGeometryPoint()
    {
        return new GeoJSONGeometry(GeoJSON::TYPE_POINT);
    }

    /**
     * @return GeoJSONGeometry
     */
    public static function buildGeoJSONGeometryPolygon()
    {
        return new GeoJSONGeometry(GeoJSON::TYPE_POLYGON);
    }

    /**
     * @return GeoJSONGeometry
     */
    public static function buildGeoJSONGeometryLineString()
    {
        return new GeoJSONGeometry(GeoJSON::TYPE_LINE_STRING);
    }

    /**
     * @param GeoJSONGeometry $geometry
     * @param null            $id
     *
     * @return GeoJSONFeature
     */
    public static function buildGeoJSONFeature(GeoJSONGeometry $geometry, $id = null)
    {
        return new GeoJSONFeature($geometry, $id);
    }
}
