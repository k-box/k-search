<?php

declare(strict_types=1);

namespace App\GeoJson\Model;

class Position implements \JsonSerializable
{
    public $lon = '';
    public $lat = '';

    public static function build(float $lon, float $lat): self
    {
        $i = new self();
        $i->lon = $lon;
        $i->lat = $lat;

        return $i;
    }

    public function jsonSerialize()
    {
        return [$this->lon, $this->lat];
    }
}
