<?php

namespace Pnz\GeoJSON;

use JMS\Serializer\Annotation\Type;

abstract class GeoJSON
{
    const TYPE_POLYGON = 'Polygon';
    const TYPE_POINT = 'Point';
    const TYPE_FEATURE = 'Feature';
    const TYPE_LINE_STRING = 'LineString';

    /**
     * @var string
     * @Type("string")
     */
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
