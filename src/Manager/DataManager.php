<?php

namespace App\Manager;

use App\Model\Data\Data;

class DataManager
{
    public function handleIndexableDataWithoutTextualContent(Data $data): bool
    {
        // @todo: Handle indexable data (queue/download from source, verify hash)
    }
}
