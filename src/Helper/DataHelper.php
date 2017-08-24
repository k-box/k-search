<?php

namespace App\Helper;

use App\Model\Data\Data;

class DataHelper
{
    const INDEXABLE_TYPES = ['document'];

    public function isIndexable(Data $data): bool
    {
        return in_array($data->type, self::INDEXABLE_TYPES, true);
    }
}
