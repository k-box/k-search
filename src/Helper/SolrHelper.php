<?php

namespace App\Helper;

class SolrHelper
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    public static function transformFieldNames(string $entityData, string $filter): string
    {
        $indexableFields = call_user_func([$entityData, 'getIndexableFields']);
        foreach ($indexableFields as $alias => $fieldName) {
            $filter = preg_replace(sprintf('/\b%s\b/', $alias), $fieldName, $filter);
        }

        return $filter;
    }

    /**
     * Replaces the properties with Solr field names in the given $filterString.
     *
     * @param string $filterString           The filter string
     * @param array  $propertyToFieldMapping the ['property-name' => 'solr_field_name'] mapping
     *
     * @return string
     */
    public static function replacePropertyToFieldNames(string $filterString, array $propertyToFieldMapping): string
    {
        foreach ($propertyToFieldMapping as $propertyName => $fieldName) {
            $filterString = preg_replace(sprintf('/\b%s\b/', $propertyName), $fieldName, $filterString);
        }

        return $filterString;
    }

    /**
     * Returns the properties in the given string.
     *
     * @param string $filter
     *
     * @return string[]
     */
    public static function getModelPropertiesInFilterQuery(string $filter): array
    {
        $matches = [];
        preg_match_all('/\b([a-z_.]+):/', $filter, $matches);

        return $matches[1];
    }

    /**
     * Transforms the given string into a Solr 'key' format.
     *
     * @param string $name
     *
     * @return string
     */
    public static function buildSolrKey(string $name): string
    {
        return str_replace('_', '-', strtolower($name));
    }

    public static function createUtcDate(string $dateString)
    {
        return new \DateTime($dateString, new \DateTimeZone('UTC'));
    }
}
