<?php

namespace App\Helper;

class SearchHelper
{

    public static function transformFieldNames(string $entityData, string $filter): string
    {
        $filterableFields = call_user_func([$entityData, 'getIndexableFields']);
        foreach ($filterableFields as $alias => $fieldName) {
            $filter = preg_replace(sprintf('/\b%s\b/', $alias), $fieldName, $filter);
        }
        return $filter;
    }

}
