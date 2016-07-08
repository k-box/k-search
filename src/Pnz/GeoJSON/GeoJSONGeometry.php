<?php

namespace Pnz\GeoJSON;

use JMS\Serializer\Annotation\Type;


/**
 * Class GeoJSONGeometry
 * @package Pnz\GeoJSON
 */
class GeoJSONGeometry extends GeoJSON implements \Serializable{

    /**
     * @var array
     * @Type("array")
     */
    protected $coordinates = array();

    function __construct($type)
    {
        if (!self::checkType($type)) {
            throw new \InvalidArgumentException('Invalid type=' . $type);
        }
        parent::__construct($type);

    }

    /**
     * @param $type
     * @return bool
     */
    public static function checkType($type) {
        return in_array($type, self::getAllowedTypes());
    }

    /**
     * @return array
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @param $coordinates
     * @return $this
     */
    public function setCoordinates($coordinates)
    {
        $this->coordinates = $coordinates;
        return $this;
    }

    /**
     * @param $longitude
     * @param $latitude
     * @return $this
     */
    public function addCoordinatePoint($longitude, $latitude)
    {
        $coordinate = array($longitude, $latitude);
        if ($this->getType() == GeoJSON::TYPE_POINT) {
            $this->setCoordinates($coordinate);
        }
        else {
            $this->coordinates[] = $coordinate;
        }
        return $this;
    }

    /**
     * @return array
     */
    protected static function getAllowedTypes()
    {
        return array(
            GeoJSON::TYPE_POINT,
            GeoJSON::TYPE_POLYGON,
            GeoJSON::TYPE_LINE_STRING,
        );
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize(
            array(
                'type' => $this->getType(),
                'coordinates' => $this->getCoordinates(),
            )
        );
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized: The string representation of the object.
     * @return void
     */
    public function unserialize($serialized)
    {
        $arr = unserialize($serialized);
        $this->type = $arr['type'];
        $this->coordinates = $arr['coordinates'];
    }

}