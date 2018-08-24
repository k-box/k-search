<?php

namespace App\GeoJson\Model;

class Coordinates
{
    public $lat = '';
    public $lon = '';

    public static function build(string $lat, string $lon): Coordinates
    {
        $i = new self();
        $i->lat = $lat;
        $i->lon = $lon;

        return $i;
    }
}