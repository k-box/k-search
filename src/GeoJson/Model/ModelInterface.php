<?php

namespace App\GeoJson\Model;

interface ModelInterface extends \JsonSerializable
{
    public static function getType(): string;

    public static function validate(array $data): void;

    public static function create(array $data);
}
