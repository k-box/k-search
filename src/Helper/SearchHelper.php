<?php

namespace App\Helper;

class SearchHelper
{
    public static function transformFieldNames(string $entityData, string $filter): string
    {
        $indexableFields = call_user_func([$entityData, 'getIndexableFields']);
        foreach ($indexableFields as $alias => $fieldName) {
            $filter = preg_replace(sprintf('/\b%s\b/', $alias), $fieldName, $filter);
        }

        return $filter;
    }

    public static function getFieldsInFilterQuery(string $entityData, string $filter): array
    {
        $matches = [];
        preg_match_all('/\b([a-z_]+):/', $filter, $matches);

        return $matches[1];
    }
}
