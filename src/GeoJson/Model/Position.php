<?php

declare(strict_types=1);

namespace App\GeoJson\Model;

class Position implements \JsonSerializable
{
    public $lat = '';
    public $lon = '';

    public static function build(float $lat, float $lon): self
    {
        $i = new self();
        $i->lat = $lat;
        $i->lon = $lon;

        return $i;
    }

    public function jsonSerialize()
    {
        return [$this->lat, $this->lon];
    }
}
