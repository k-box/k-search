<?php

namespace Pnz\GeoJSON;


use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;

class GeoJSONFeature extends GeoJSON implements \Serializable {

    /**
     * @var null
     * @Exclude()
     */
    protected $id;
    /**
     * @var GeoJSONGeometry
     * @Type("Pnz\GeoJSON\GeoJSONGeometry")
     */
    protected $geometry;

    /**
     * @var array
     * @Type("array")
     */
    protected $properties;

    /**
     * @param GeoJSONGeometry $geometry
     * @param null $id
     */
    function __construct(GeoJSONGeometry $geometry, $id = null)
    {
        parent::__construct(GeoJSON::TYPE_FEATURE);
        $this->geometry = $geometry;
        $this->id       = $id;
        $this->properties = array();
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
        return $this;
    }

    /**
     * @param $name
     * @return null
     */
    public function getProperty($name) {
        if ($this->isProperty($name)) {
            return $this->properties[$name];
        }
        return null;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isProperty($name) {
        return array_key_exists($name, $this->properties);
    }

    /**
     * @return GeoJSONGeometry
     */
    public function getGeometry() {
        return $this->geometry;
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
                'properties' => $this->properties,
                'geometry' => serialize($this->getGeometry()),
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
        $this->properties = $arr['properties'];
        $this->geometry = unserialize($arr['geometry']);
        $this->type = GeoJSON::TYPE_FEATURE;
    }
}
