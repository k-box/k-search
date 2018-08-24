<?php

namespace App\GeoJson\Model;

interface ModelInterface
{
    public static function validate(array $data): void;

    public static function create(array $data);
}