<?php

namespace App\Helper;

use App\Model\Data\Data;

class DataHelper
{
    const INDEXABLE_TYPES = [
        Data::DATA_TYPE_DOCUMENT,
    ];

    public static function isIndexable(Data $data): bool
    {
        return in_array($data->type, self::INDEXABLE_TYPES, true);
    }

    public static function createUtcDate(string $dateString)
    {
        return new \DateTime($dateString, new \DateTimeZone('UTC'));
    }
}
